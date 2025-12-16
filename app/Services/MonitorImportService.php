<?php

namespace App\Services;

use App\Models\Monitor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MonitorImportService
{
    private array $validationRules = [
        'url' => ['required', 'url'],
        'display_name' => ['nullable', 'string', 'max:255'],
        'uptime_check_enabled' => ['nullable'],
        'certificate_check_enabled' => ['nullable'],
        'uptime_check_interval' => ['nullable', 'integer', 'min:1', 'max:60'],
        'is_public' => ['nullable'],
        'sensitivity' => ['nullable', 'string', 'in:low,medium,high'],
        'expected_status_code' => ['nullable', 'integer', 'min:100', 'max:599'],
        'tags' => ['nullable'],
    ];

    /**
     * Parse uploaded file and validate rows
     */
    public function parseFile(UploadedFile $file, string $format): array
    {
        $rows = $format === 'csv'
            ? $this->parseCsv($file)
            : $this->parseJson($file);

        return $this->validateRows($rows);
    }

    /**
     * Parse CSV file
     */
    private function parseCsv(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return [];
        }

        // Normalize headers
        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $row = array_combine($headers, $data);
                $rows[] = $this->normalizeRow($row);
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Parse JSON file
     */
    private function parseJson(UploadedFile $file): array
    {
        $content = file_get_contents($file->getPathname());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        // Handle both array of monitors and {monitors: [...]} format
        $monitors = isset($data['monitors']) ? $data['monitors'] : $data;

        if (! is_array($monitors)) {
            return [];
        }

        return array_map(fn ($row) => $this->normalizeRow($row), $monitors);
    }

    /**
     * Normalize row data
     */
    private function normalizeRow(array $row): array
    {
        // Normalize URL (enforce HTTPS)
        if (isset($row['url']) && ! empty($row['url'])) {
            $url = rtrim(filter_var($row['url'], FILTER_VALIDATE_URL) ?: $row['url'], '/');
            if (str_starts_with($url, 'http://')) {
                $url = 'https://'.substr($url, 7);
            }
            $row['url'] = $url;
        }

        // Normalize booleans
        foreach (['uptime_check_enabled', 'certificate_check_enabled', 'is_public'] as $field) {
            if (isset($row[$field])) {
                $row[$field] = filter_var($row[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Normalize tags (support comma-separated string or array)
        if (isset($row['tags']) && is_string($row['tags']) && ! empty($row['tags'])) {
            $row['tags'] = array_map('trim', explode(',', $row['tags']));
        } elseif (! isset($row['tags']) || empty($row['tags'])) {
            $row['tags'] = [];
        }

        // Cast numeric fields
        if (isset($row['uptime_check_interval']) && is_numeric($row['uptime_check_interval'])) {
            $row['uptime_check_interval'] = (int) $row['uptime_check_interval'];
        }
        if (isset($row['expected_status_code']) && is_numeric($row['expected_status_code'])) {
            $row['expected_status_code'] = (int) $row['expected_status_code'];
        }

        return $row;
    }

    /**
     * Validate all rows and check for duplicates
     */
    private function validateRows(array $rows): array
    {
        $result = [
            'rows' => [],
            'valid_count' => 0,
            'error_count' => 0,
            'duplicate_count' => 0,
        ];

        if (empty($rows)) {
            return $result;
        }

        // Get existing URLs for duplicate detection
        $existingUrls = Monitor::withoutGlobalScopes()
            ->pluck('url')
            ->map(fn ($url) => (string) $url)
            ->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            $validator = Validator::make($row, $this->validationRules);

            if ($validator->fails()) {
                $result['rows'][] = array_merge($row, [
                    '_row_number' => $rowNumber,
                    '_status' => 'error',
                    '_errors' => $validator->errors()->all(),
                ]);
                $result['error_count']++;

                continue;
            }

            // Check for duplicates
            $url = $row['url'] ?? '';
            if (in_array($url, $existingUrls)) {
                $existingMonitor = Monitor::withoutGlobalScopes()
                    ->where('url', $url)
                    ->first();

                $result['rows'][] = array_merge($row, [
                    '_row_number' => $rowNumber,
                    '_status' => 'duplicate',
                    '_existing_monitor_id' => $existingMonitor?->id,
                    '_existing_monitor_name' => $existingMonitor?->raw_url,
                ]);
                $result['duplicate_count']++;

                continue;
            }

            $result['rows'][] = array_merge($row, [
                '_row_number' => $rowNumber,
                '_status' => 'valid',
            ]);
            $result['valid_count']++;
        }

        return $result;
    }

    /**
     * Import monitors with duplicate resolution
     */
    public function import(array $rows, string $defaultAction, array $resolutions = []): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $status = $row['_status'] ?? 'valid';
                $rowNumber = $row['_row_number'] ?? 0;

                // Skip error rows
                if ($status === 'error') {
                    $skipped++;

                    continue;
                }

                // Determine action for duplicates
                $action = $status === 'duplicate'
                    ? ($resolutions[$rowNumber] ?? $defaultAction)
                    : 'create';

                // Clean metadata from row
                $monitorData = $this->cleanRowData($row);

                switch ($action) {
                    case 'skip':
                        $skipped++;
                        break;

                    case 'update':
                        $this->updateMonitor($monitorData);
                        $updated++;
                        break;

                    case 'create':
                    default:
                        $this->createMonitor($monitorData);
                        $imported++;
                        break;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear cache
        if (auth()->check()) {
            cache()->forget('monitors_list_page_1_per_page_15_user_'.auth()->id());
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Create a new monitor
     */
    private function createMonitor(array $data): Monitor
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $monitor = Monitor::create([
            'url' => $data['url'],
            'display_name' => $data['display_name'] ?? null,
            'is_public' => $data['is_public'] ?? false,
            'uptime_check_enabled' => $data['uptime_check_enabled'] ?? true,
            'certificate_check_enabled' => $data['certificate_check_enabled'] ?? false,
            'uptime_check_interval_in_minutes' => $data['uptime_check_interval'] ?? 5,
            'sensitivity' => $data['sensitivity'] ?? 'medium',
            'expected_status_code' => $data['expected_status_code'] ?? 200,
        ]);

        if (! empty($tags)) {
            $monitor->attachTags($tags);
        }

        return $monitor;
    }

    /**
     * Update an existing monitor
     */
    private function updateMonitor(array $data): Monitor
    {
        $monitor = Monitor::withoutGlobalScopes()
            ->where('url', $data['url'])
            ->first();

        if (! $monitor) {
            return $this->createMonitor($data);
        }

        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $updateData = [];

        if (isset($data['display_name'])) {
            $updateData['display_name'] = $data['display_name'];
        }
        if (isset($data['is_public'])) {
            $updateData['is_public'] = $data['is_public'];
        }
        if (isset($data['uptime_check_enabled'])) {
            $updateData['uptime_check_enabled'] = $data['uptime_check_enabled'];
        }
        if (isset($data['certificate_check_enabled'])) {
            $updateData['certificate_check_enabled'] = $data['certificate_check_enabled'];
        }
        if (isset($data['uptime_check_interval'])) {
            $updateData['uptime_check_interval_in_minutes'] = $data['uptime_check_interval'];
        }
        if (isset($data['sensitivity'])) {
            $updateData['sensitivity'] = $data['sensitivity'];
        }
        if (isset($data['expected_status_code'])) {
            $updateData['expected_status_code'] = $data['expected_status_code'];
        }

        if (! empty($updateData)) {
            $monitor->update($updateData);
        }

        if ($tags !== null && ! empty($tags)) {
            $monitor->syncTags($tags);
        }

        // Attach current user if not already attached
        if (auth()->check() && ! $monitor->users->contains(auth()->id())) {
            $monitor->users()->attach(auth()->id(), ['is_active' => true, 'is_pinned' => false]);
        }

        return $monitor;
    }

    /**
     * Remove metadata fields from row
     */
    private function cleanRowData(array $row): array
    {
        return array_filter($row, fn ($key) => ! str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Generate sample CSV content
     */
    public function generateSampleCsv(): string
    {
        $headers = ['url', 'display_name', 'uptime_check_enabled', 'certificate_check_enabled', 'uptime_check_interval', 'is_public', 'sensitivity', 'expected_status_code', 'tags'];
        $sample = [
            ['https://example.com', 'Example Website', 'true', 'true', '5', 'true', 'medium', '200', 'production,web'],
            ['https://api.example.com', 'Example API', 'true', 'false', '1', 'false', 'high', '200', 'api,critical'],
        ];

        $output = implode(',', $headers)."\n";
        foreach ($sample as $row) {
            $output .= implode(',', $row)."\n";
        }

        return $output;
    }

    /**
     * Generate sample JSON content
     */
    public function generateSampleJson(): string
    {
        $sample = [
            'monitors' => [
                [
                    'url' => 'https://example.com',
                    'display_name' => 'Example Website',
                    'uptime_check_enabled' => true,
                    'certificate_check_enabled' => true,
                    'uptime_check_interval' => 5,
                    'is_public' => true,
                    'sensitivity' => 'medium',
                    'expected_status_code' => 200,
                    'tags' => ['production', 'web'],
                ],
                [
                    'url' => 'https://api.example.com',
                    'display_name' => 'Example API',
                    'uptime_check_enabled' => true,
                    'certificate_check_enabled' => false,
                    'uptime_check_interval' => 1,
                    'is_public' => false,
                    'sensitivity' => 'high',
                    'expected_status_code' => 200,
                    'tags' => ['api', 'critical'],
                ],
            ],
        ];

        return json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Detect file format from extension
     */
    public function detectFormat(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, ['json']) ? 'json' : 'csv';
    }
}
