<?php

namespace App\Http\Controllers;

use App\Models\StatusPage;
use App\Http\Resources\MonitorResource;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StatusPageAvailableMonitorsController extends Controller
{
    use AuthorizesRequests;
    public function __invoke(Request $request, StatusPage $statusPage)
    {
        try {
            $this->authorize('view', $statusPage);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $availableMonitors = auth()->user()->monitors()
            ->whereNotIn('monitors.id', $statusPage->monitors->pluck('id'))
            ->get();

        return response()->json(MonitorResource::collection($availableMonitors));
    }
}
