<?php
namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;

class SubscribeMonitorController extends Controller
{
    public function __invoke($monitorId)
    {
        try {
            $monitor = Monitor::withoutGlobalScopes()->findOrFail($monitorId);

            $errorMessage = null;

            if (!$monitor->is_public) {
                $errorMessage = 'Monitor tidak tersedia untuk berlangganan';
            } elseif ($monitor->users()->where('user_id', auth()->id())->exists()) {
                $errorMessage = 'Anda sudah berlangganan monitor ini';
            }

            if ($errorMessage) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 400);
            }

            $monitor->users()->attach(auth()->id(), ['is_active' => true]);

            // clear monitor cache
            cache()->forget('public_monitors_authenticated_' . auth()->id());

            return redirect()->back()->with('flash', [
                'type' => 'success',
                'message' => 'Berhasil berlangganan monitor: ' . $monitor->name,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'Gagal berlangganan monitor: ' . $e->getMessage(),
            ]);
        }
    }
}
