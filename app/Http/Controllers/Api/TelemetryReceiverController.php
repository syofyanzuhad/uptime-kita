<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelemetryPing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TelemetryReceiverController extends Controller
{
    /**
     * Receive telemetry ping from an Uptime-Kita instance.
     */
    public function receive(Request $request): JsonResponse
    {
        // Check if receiver is enabled
        if (! config('telemetry.receiver_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Telemetry receiver is disabled on this instance.',
            ], 403);
        }

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'instance_id' => 'required|string|size:64',
            'versions' => 'required|array',
            'versions.app' => 'nullable|string|max:50',
            'versions.php' => 'nullable|string|max:20',
            'versions.laravel' => 'nullable|string|max:20',
            'stats' => 'required|array',
            'stats.monitors_total' => 'nullable|integer|min:0',
            'stats.monitors_public' => 'nullable|integer|min:0',
            'stats.users_total' => 'nullable|integer|min:0',
            'stats.status_pages_total' => 'nullable|integer|min:0',
            'stats.install_date' => 'nullable|date',
            'system' => 'required|array',
            'system.os_family' => 'nullable|string|max:50',
            'system.os_type' => 'nullable|string|max:50',
            'system.database_driver' => 'nullable|string|max:50',
            'system.queue_driver' => 'nullable|string|max:50',
            'system.cache_driver' => 'nullable|string|max:50',
            'ping' => 'required|array',
            'ping.timestamp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid telemetry data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Find or create the telemetry record
            $ping = TelemetryPing::updateOrCreate(
                ['instance_id' => $data['instance_id']],
                [
                    'app_version' => $data['versions']['app'] ?? null,
                    'php_version' => $data['versions']['php'] ?? null,
                    'laravel_version' => $data['versions']['laravel'] ?? null,
                    'monitors_total' => $data['stats']['monitors_total'] ?? 0,
                    'monitors_public' => $data['stats']['monitors_public'] ?? 0,
                    'users_total' => $data['stats']['users_total'] ?? 0,
                    'status_pages_total' => $data['stats']['status_pages_total'] ?? 0,
                    'os_family' => $data['system']['os_family'] ?? null,
                    'os_type' => $data['system']['os_type'] ?? null,
                    'database_driver' => $data['system']['database_driver'] ?? null,
                    'queue_driver' => $data['system']['queue_driver'] ?? null,
                    'cache_driver' => $data['system']['cache_driver'] ?? null,
                    'install_date' => $data['stats']['install_date'] ?? null,
                    'last_ping_at' => now(),
                    'raw_data' => $data,
                ]
            );

            // Set first_seen_at only on creation
            if ($ping->wasRecentlyCreated) {
                $ping->first_seen_at = now();
                $ping->ping_count = 1;
                $ping->save();

                Log::info('New telemetry instance registered', [
                    'instance_id' => substr($data['instance_id'], 0, 8).'...',
                ]);
            } else {
                // Increment ping count
                $ping->increment('ping_count');
            }

            return response()->json([
                'success' => true,
                'message' => 'Telemetry received successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process telemetry ping', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process telemetry.',
            ], 500);
        }
    }
}
