<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToggleMonitorPinController extends Controller
{
    /**
     * Toggle the pinned status of a monitor for the authenticated user.
     */
    public function __invoke(Request $request, int $monitorId): JsonResponse
    {
        $request->validate([
            'is_pinned' => 'required|boolean',
        ]);

        try {
            $monitor = Monitor::findOrFail($monitorId);

            // Check if user is subscribed to this monitor
            if (! $monitor->is_subscribed) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be subscribed to this monitor to pin it.',
                ], 403);
            }

            // Update the pivot table
            $monitor->users()->updateExistingPivot(auth()->id(), [
                'is_pinned' => $request->boolean('is_pinned'),
            ]);

            // Clear the cache for this monitor's pinned status
            cache()->forget("is_pinned_{$monitor->id}_".auth()->id());

            return response()->json([
                'success' => true,
                'message' => $request->boolean('is_pinned') ? 'Monitor pinned successfully.' : 'Monitor unpinned successfully.',
                'is_pinned' => $request->boolean('is_pinned'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pin status: '.$e->getMessage(),
            ], 500);
        }
    }
}
