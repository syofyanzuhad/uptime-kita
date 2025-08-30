<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;

class SubscribeMonitorController extends Controller
{
    public function __invoke(Request $request, $monitorId)
    {
        try {
            $monitor = Monitor::withoutGlobalScopes()->findOrFail($monitorId);

            $errorMessage = null;
            $statusCode = 200;

            // Check if already subscribed
            $isSubscribed = $monitor->users()->where('user_id', auth()->id())->exists();

            // Check if monitor is disabled globally
            if (! $monitor->uptime_check_enabled) {
                $errorMessage = 'Cannot subscribe to disabled monitor';
                $statusCode = 403;
            } elseif (! $monitor->is_public) {
                // For private monitors, only owner can be subscribed
                if (! $isSubscribed) {
                    $errorMessage = 'Cannot subscribe to private monitor';
                    $statusCode = 403;
                }
            }

            // Check for duplicate subscription (for public monitors or if already subscribed to private)
            if (! $errorMessage && $isSubscribed) {
                // If it's a private monitor and they're the owner, allow it (idempotent)
                if (! $monitor->is_public) {
                    // Already handled, just return success
                    $successMessage = 'Subscribed to monitor successfully';
                    if ($request->wantsJson()) {
                        return response()->json(['message' => $successMessage], 200);
                    }

                    return redirect()->back()->with('flash', [
                        'type' => 'success',
                        'message' => 'Berhasil berlangganan monitor: '.$monitor?->url,
                    ]);
                }
                $errorMessage = 'Already subscribed to this monitor';
                $statusCode = 400;
            }

            if ($errorMessage) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => $errorMessage], $statusCode);
                }

                return redirect()->back()->with('flash', [
                    'type' => 'error',
                    'message' => $errorMessage,
                ]);
            }

            $monitor->users()->attach(auth()->id(), ['is_active' => true]);

            // clear monitor cache
            cache()->forget('public_monitors_authenticated_'.auth()->id());

            $successMessage = 'Subscribed to monitor successfully';

            if ($request->wantsJson()) {
                return response()->json(['message' => $successMessage], 200);
            }

            return redirect()->back()->with('flash', [
                'type' => 'success',
                'message' => 'Berhasil berlangganan monitor: '.$monitor?->url,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Monitor not found'], 404);
            }

            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'Monitor tidak ditemukan',
            ]);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Failed to subscribe: '.$e->getMessage()], 500);
            }

            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'Gagal berlangganan monitor: '.$e->getMessage(),
            ]);
        }
    }
}
