<?php

namespace App\Http\Controllers;

use App\Actions\Monitors\ToggleMonitorActiveAction;
use App\Models\Monitor;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ToggleMonitorActiveController extends Controller
{
    use AuthorizesRequests;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $monitorId, ToggleMonitorActiveAction $toggleAction): RedirectResponse
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return redirect()->back()
                    ->with('flash', ['message' => 'User not authenticated', 'type' => 'error']);
            }

            $monitor = Monitor::withoutGlobalScopes()->where('id', $monitorId)->first();

            if ($monitor) {
                $this->authorize('update', $monitor);
            }

            $result = $toggleAction->execute($user, $monitorId);

            if (! $result['success']) {
                return redirect()->back()
                    ->with('flash', ['message' => $result['message'], 'type' => 'error']);
            }

            return redirect()->back()
                ->with('flash', ['message' => $result['message'], 'type' => 'success']);
        } catch (Exception $e) {
            return redirect()->back()
                ->with('flash', ['message' => 'Gagal mengubah status monitor: '.$e->getMessage(), 'type' => 'error']);
        }
    }
}
