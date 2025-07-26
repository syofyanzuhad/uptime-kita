<?php

namespace App\Http\Controllers;

use App\Models\Monitor;

class UnsubscribeMonitorController extends Controller
{
    public function __invoke($monitorId)
    {
        try {
            $monitor = Monitor::withoutGlobalScopes()->findOrFail($monitorId);

            $errorMessage = null;

            if (! $monitor->is_public) {
                $errorMessage = 'Monitor tidak tersedia untuk berlangganan';
            } elseif (!$monitor->users()->where('user_id', auth()->id())->exists()) {
                $errorMessage = 'Anda tidak berlangganan monitor ini';
            }

            if ($errorMessage) {
                return redirect()->back()->with('flash', [
                    'type' => 'error',
                    'message' => $errorMessage,
                ]);
            }

            $monitor->users()->detach(auth()->id());

            // clear monitor cache
            cache()->forget('public_monitors_authenticated_'.auth()->id());

            return redirect()->back()->with('flash', [
                'type' => 'success',
                'message' => 'Berhasil berhenti berlangganan monitor: '.$monitor?->url,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'Gagal berhenti berlangganan monitor: '.$e->getMessage(),
            ]);
        }
    }
}
