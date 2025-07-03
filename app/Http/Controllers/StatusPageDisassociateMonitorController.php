<?php

namespace App\Http\Controllers;

use App\Models\StatusPage;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StatusPageDisassociateMonitorController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, StatusPage $statusPage, Monitor $monitor)
    {
        $this->authorize('update', $statusPage);

        $statusPage->monitors()->detach($monitor->id);

        return redirect()->route('status-pages.show', $statusPage)
            ->with('success', 'Monitor disassociated successfully.');
    }
}
