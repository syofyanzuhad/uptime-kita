<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ToggleMonitorActiveController extends Controller
{
    use AuthorizesRequests;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $monitorId): RedirectResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return redirect()->back()
                    ->with('flash', ['message' => 'User not authenticated', 'type' => 'error']);
            }

            // Get monitor without global scopes
            $monitor = Monitor::withoutGlobalScopes()
                ->where('id', $monitorId)
                ->first();

            if (!$monitor) {
                return redirect()->back()
                    ->with('flash', ['message' => 'Monitor not found', 'type' => 'error']);
            }

            // Check if user is subscribed to this monitor
            $userMonitor = $monitor->users()->where('user_id', $user->id)->first();

            if (!$userMonitor) {
                return redirect()->back()
                    ->with('flash', ['message' => 'User is not subscribed to this monitor', 'type' => 'error']);
            }

            // Use policy authorization
            $this->authorize('update', $monitor);

            // Toggle the active status
            $newStatus = !$monitor->uptime_check_enabled;
            $monitor->update(['uptime_check_enabled' => $newStatus]);

            // Clear cache
            cache()->forget('public_monitors_authenticated_' . $user->id);
            cache()->forget('private_monitors_page_' . $user->id . '_1');

            $message = $newStatus ? 'Monitor berhasil diaktifkan!' : 'Monitor berhasil dinonaktifkan!';

            return redirect()->back()
                ->with('flash', ['message' => $message, 'type' => 'success']);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('flash', ['message' => 'Gagal mengubah status monitor: ' . $e->getMessage(), 'type' => 'error']);
        }
    }
}
