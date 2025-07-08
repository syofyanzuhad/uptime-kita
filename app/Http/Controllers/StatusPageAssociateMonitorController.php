<?php

namespace App\Http\Controllers;

use App\Models\StatusPage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class StatusPageAssociateMonitorController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, StatusPage $statusPage)
    {
        $this->authorize('update', $statusPage);

        $validated = $request->validate([
            'monitor_ids' => 'required|array',
            'monitor_ids.*' => 'exists:monitors,id',
        ]);

        $statusPage->monitors()->syncWithoutDetaching($validated['monitor_ids']);

        return redirect()->route('status-pages.show', $statusPage)
            ->with('success', 'Monitors successfully associated.');
    }
}
