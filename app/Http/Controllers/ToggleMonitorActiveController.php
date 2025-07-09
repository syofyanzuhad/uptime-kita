<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ToggleMonitorActiveController extends Controller
{
    use AuthorizesRequests;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Monitor $monitor): RedirectResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return redirect()->back()
                    ->with('flash', ['message' => 'User not authenticated', 'type' => 'error']);
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
