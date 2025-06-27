<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;

class SubscribeMonitorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Monitor $monitor)
    {
        try {
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

            // Attach monitor to user
            $monitor->users()->attach(auth()->id(), ['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil berlangganan monitor!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal berlangganan monitor: ' . $e->getMessage(),
            ], 500);
        }
    }
}
