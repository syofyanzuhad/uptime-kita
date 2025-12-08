<?php

namespace App\Services;

use App\Models\Monitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MaintenanceWindowService
{
    /**
     * Check if a monitor is currently in a maintenance window.
     */
    public function isInMaintenance(Monitor $monitor): bool
    {
        // Quick check using the cached flag first
        if ($monitor->is_in_maintenance) {
            // Verify the maintenance is still valid
            if ($monitor->maintenance_ends_at && Carbon::parse($monitor->maintenance_ends_at)->isFuture()) {
                return true;
            }
        }

        // Check maintenance windows configuration
        $windows = $monitor->maintenance_windows;

        if (empty($windows) || ! is_array($windows)) {
            return false;
        }

        $now = Carbon::now();

        foreach ($windows as $window) {
            if ($this->isInWindow($window, $now)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current time falls within a maintenance window.
     */
    protected function isInWindow(array $window, Carbon $now): bool
    {
        $type = $window['type'] ?? null;

        if ($type === 'one_time') {
            return $this->isInOneTimeWindow($window, $now);
        }

        if ($type === 'recurring') {
            return $this->isInRecurringWindow($window, $now);
        }

        return false;
    }

    /**
     * Check if current time is in a one-time maintenance window.
     */
    protected function isInOneTimeWindow(array $window, Carbon $now): bool
    {
        $start = isset($window['start']) ? Carbon::parse($window['start']) : null;
        $end = isset($window['end']) ? Carbon::parse($window['end']) : null;

        if (! $start || ! $end) {
            return false;
        }

        return $now->between($start, $end);
    }

    /**
     * Check if current time is in a recurring maintenance window.
     */
    protected function isInRecurringWindow(array $window, Carbon $now): bool
    {
        $dayOfWeek = $window['day_of_week'] ?? null; // 0 = Sunday, 6 = Saturday
        $startTime = $window['start_time'] ?? null; // "HH:MM" format
        $endTime = $window['end_time'] ?? null; // "HH:MM" format
        $timezone = $window['timezone'] ?? config('app.timezone', 'UTC');

        if ($dayOfWeek === null || ! $startTime || ! $endTime) {
            return false;
        }

        // Convert now to the configured timezone
        $nowInTimezone = $now->copy()->setTimezone($timezone);

        // Check if today is the maintenance day
        if ($nowInTimezone->dayOfWeek !== (int) $dayOfWeek) {
            return false;
        }

        // Parse start and end times in the configured timezone
        $startOfWindow = $nowInTimezone->copy()->setTimeFromTimeString($startTime);
        $endOfWindow = $nowInTimezone->copy()->setTimeFromTimeString($endTime);

        // Handle overnight windows (e.g., 23:00 to 02:00)
        if ($endOfWindow->lt($startOfWindow)) {
            // If we're after the start time, the window extends to midnight
            // If we're before the end time, the window started yesterday
            return $nowInTimezone->gte($startOfWindow) || $nowInTimezone->lte($endOfWindow);
        }

        return $nowInTimezone->between($startOfWindow, $endOfWindow);
    }

    /**
     * Get the next scheduled maintenance window for a monitor.
     */
    public function getNextMaintenanceWindow(Monitor $monitor): ?array
    {
        $windows = $monitor->maintenance_windows;

        if (empty($windows) || ! is_array($windows)) {
            return null;
        }

        $now = Carbon::now();
        $nextWindow = null;
        $nextStart = null;

        foreach ($windows as $window) {
            $windowStart = $this->getNextWindowStart($window, $now);

            if ($windowStart && (! $nextStart || $windowStart->lt($nextStart))) {
                $nextStart = $windowStart;
                $nextWindow = array_merge($window, [
                    'next_start' => $windowStart->toIso8601String(),
                    'next_end' => $this->getWindowEnd($window, $windowStart)->toIso8601String(),
                ]);
            }
        }

        return $nextWindow;
    }

    /**
     * Get the next start time for a maintenance window.
     */
    protected function getNextWindowStart(array $window, Carbon $now): ?Carbon
    {
        $type = $window['type'] ?? null;

        if ($type === 'one_time') {
            $start = isset($window['start']) ? Carbon::parse($window['start']) : null;

            return ($start && $start->isFuture()) ? $start : null;
        }

        if ($type === 'recurring') {
            $dayOfWeek = $window['day_of_week'] ?? null;
            $startTime = $window['start_time'] ?? null;
            $timezone = $window['timezone'] ?? config('app.timezone', 'UTC');

            if ($dayOfWeek === null || ! $startTime) {
                return null;
            }

            $nowInTimezone = $now->copy()->setTimezone($timezone);

            // Calculate next occurrence of this day
            $nextOccurrence = $nowInTimezone->copy()->next((int) $dayOfWeek);

            // If today is the day and we haven't passed the start time, use today
            if ($nowInTimezone->dayOfWeek === (int) $dayOfWeek) {
                $todayStart = $nowInTimezone->copy()->setTimeFromTimeString($startTime);
                if ($todayStart->isFuture()) {
                    return $todayStart->setTimezone(config('app.timezone'));
                }
            }

            return $nextOccurrence->setTimeFromTimeString($startTime)->setTimezone(config('app.timezone'));
        }

        return null;
    }

    /**
     * Get the end time for a maintenance window given a start time.
     */
    protected function getWindowEnd(array $window, Carbon $start): Carbon
    {
        $type = $window['type'] ?? null;

        if ($type === 'one_time') {
            return Carbon::parse($window['end']);
        }

        if ($type === 'recurring') {
            $endTime = $window['end_time'] ?? '00:00';
            $timezone = $window['timezone'] ?? config('app.timezone', 'UTC');

            $end = $start->copy()->setTimeFromTimeString($endTime);

            // Handle overnight windows
            if ($end->lt($start)) {
                $end->addDay();
            }

            return $end;
        }

        return $start->copy()->addHour(); // Default 1 hour if unknown
    }

    /**
     * Update maintenance status for all monitors.
     * This should be called by a scheduled command.
     */
    public function updateAllMaintenanceStatuses(): int
    {
        $updated = 0;

        // Get all monitors with maintenance windows configured
        $monitors = Monitor::withoutGlobalScopes()
            ->whereNotNull('maintenance_windows')
            ->where('maintenance_windows', '!=', '[]')
            ->where('maintenance_windows', '!=', 'null')
            ->get();

        foreach ($monitors as $monitor) {
            if ($this->updateMaintenanceStatus($monitor)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Update maintenance status for a single monitor.
     */
    public function updateMaintenanceStatus(Monitor $monitor): bool
    {
        $isInMaintenance = $this->isInMaintenance($monitor);
        $wasInMaintenance = $monitor->is_in_maintenance;

        // Only update if status changed
        if ($isInMaintenance !== $wasInMaintenance) {
            $monitor->is_in_maintenance = $isInMaintenance;

            if ($isInMaintenance) {
                // Find the current active window to set start/end times
                $activeWindow = $this->findActiveWindow($monitor);
                if ($activeWindow) {
                    $monitor->maintenance_starts_at = $activeWindow['start'];
                    $monitor->maintenance_ends_at = $activeWindow['end'];
                }

                Log::info('MaintenanceWindowService: Monitor entered maintenance', [
                    'monitor_id' => $monitor->id,
                    'url' => (string) $monitor->url,
                    'ends_at' => $monitor->maintenance_ends_at,
                ]);
            } else {
                $monitor->maintenance_starts_at = null;
                $monitor->maintenance_ends_at = null;

                Log::info('MaintenanceWindowService: Monitor exited maintenance', [
                    'monitor_id' => $monitor->id,
                    'url' => (string) $monitor->url,
                ]);
            }

            $monitor->saveQuietly();

            return true;
        }

        return false;
    }

    /**
     * Find the currently active maintenance window.
     */
    protected function findActiveWindow(Monitor $monitor): ?array
    {
        $windows = $monitor->maintenance_windows;

        if (empty($windows) || ! is_array($windows)) {
            return null;
        }

        $now = Carbon::now();

        foreach ($windows as $window) {
            if ($this->isInWindow($window, $now)) {
                $type = $window['type'] ?? null;

                if ($type === 'one_time') {
                    return [
                        'start' => Carbon::parse($window['start']),
                        'end' => Carbon::parse($window['end']),
                    ];
                }

                if ($type === 'recurring') {
                    $timezone = $window['timezone'] ?? config('app.timezone', 'UTC');
                    $nowInTimezone = $now->copy()->setTimezone($timezone);

                    $start = $nowInTimezone->copy()->setTimeFromTimeString($window['start_time']);
                    $end = $nowInTimezone->copy()->setTimeFromTimeString($window['end_time']);

                    // Handle overnight windows
                    if ($end->lt($start)) {
                        if ($nowInTimezone->lt($end)) {
                            $start->subDay();
                        } else {
                            $end->addDay();
                        }
                    }

                    return [
                        'start' => $start->setTimezone(config('app.timezone')),
                        'end' => $end->setTimezone(config('app.timezone')),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Clean up expired one-time maintenance windows.
     */
    public function cleanupExpiredWindows(): int
    {
        $cleaned = 0;
        $now = Carbon::now();

        $monitors = Monitor::withoutGlobalScopes()
            ->whereNotNull('maintenance_windows')
            ->where('maintenance_windows', '!=', '[]')
            ->where('maintenance_windows', '!=', 'null')
            ->get();

        foreach ($monitors as $monitor) {
            $windows = $monitor->maintenance_windows;

            if (empty($windows) || ! is_array($windows)) {
                continue;
            }

            $filteredWindows = array_filter($windows, function ($window) {
                if (($window['type'] ?? null) !== 'one_time') {
                    return true; // Keep recurring windows
                }

                $end = isset($window['end']) ? Carbon::parse($window['end']) : null;

                return $end && $end->isFuture();
            });

            if (count($filteredWindows) !== count($windows)) {
                $monitor->maintenance_windows = array_values($filteredWindows);
                $monitor->saveQuietly();
                $cleaned++;
            }
        }

        return $cleaned;
    }
}
