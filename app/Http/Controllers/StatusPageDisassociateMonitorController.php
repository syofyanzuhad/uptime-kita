<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\StatusPage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

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
