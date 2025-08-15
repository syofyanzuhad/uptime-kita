<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ToggleMonitorPinController extends Controller
{
    /**
     * Toggle the pinned status of a monitor for the authenticated user.
     */
    public function __invoke(Request $request, int $monitorId): RedirectResponse
    {
        $request->validate([
            'is_pinned' => 'required|boolean',
        ]);

        try {
            $monitor = Monitor::findOrFail($monitorId);

            // Check if user is subscribed to this monitor
            if (! $monitor->is_subscribed) {
                return redirect()->back()->with('flash', [
                    'type' => 'error',
                    'message' => 'You must be subscribed to this monitor to pin it.',
                ]);
            }

            // Update the pivot table
            $monitor->users()->updateExistingPivot(auth()->id(), [
                'is_pinned' => $request->boolean('is_pinned'),
            ]);

            // Clear the cache for this monitor's pinned status
            cache()->forget("is_pinned_{$monitor->id}_".auth()->id());

            return redirect()->back()->with('flash', [
                'type' => 'success',
                'message' => $request->boolean('is_pinned') ? 'Monitor pinned successfully.' : 'Monitor unpinned successfully.',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'Failed to update pin status: '.$e->getMessage(),
            ]);
        }
    }
}
