<?php

namespace App\Actions\Monitors;

use App\Models\Monitor;
use App\Models\User;

class ToggleMonitorActiveAction
{
    /**
     * Toggle the active check status of a monitor for a user.
     *
     * @return array{success: bool, monitor?: Monitor, new_status?: bool, message: string}
     */
    public function execute(User $user, int|string $monitorId): array
    {
        $monitor = Monitor::withoutGlobalScopes()->where('id', $monitorId)->first();

        if (! $monitor) {
            return [
                'success' => false,
                'message' => 'Monitor not found',
            ];
        }

        $userMonitor = $monitor->users()->where('user_id', $user->id)->first();

        if (! $userMonitor) {
            return [
                'success' => false,
                'message' => 'User is not subscribed to this monitor',
            ];
        }

        $newStatus = ! $monitor->uptime_check_enabled;
        $monitor->update(['uptime_check_enabled' => $newStatus]);

        cache()->forget('public_monitors_authenticated_'.$user->id);
        cache()->forget('private_monitors_page_'.$user->id.'_1');

        $message = $newStatus ? 'Monitor berhasil diaktifkan!' : 'Monitor berhasil dinonaktifkan!';

        return [
            'success' => true,
            'monitor' => $monitor,
            'new_status' => $newStatus,
            'message' => $message,
        ];
    }
}
