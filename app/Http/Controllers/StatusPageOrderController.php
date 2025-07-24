<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StatusPage;
use App\Models\StatusPageMonitor;

class StatusPageOrderController extends Controller
{
    public function __invoke(Request $request, StatusPage $statusPage)
    {
        // Validate the request data
        $data = $request->validate([
            'monitor_ids' => 'required|array',
            'monitor_ids.*' => 'exists:monitors,id', // Ensure each monitor ID exists in the pivot table
        ]);

        // Ensure the status page exists
        if (!$statusPage) {
            return response()->json(['error' => 'Status page not found.'], 404);
        }

        // Update the order of monitors for the status page
        foreach ($data['monitor_ids'] as $order => $monitorId) {
            // Find the status page monitor pivot table entry
            $statusPageMonitor = StatusPageMonitor::where('status_page_id', $statusPage->id)
                ->where('monitor_id', $monitorId)
                ->first();

            if ($statusPageMonitor->order === $order) {
                // If the order is the same, skip updating
                continue;
            }

            // If the entry exists and different from the current order, update it
            if ($statusPageMonitor) {
                StatusPageMonitor::where('status_page_id', $statusPage->id)
                    ->where('monitor_id', $monitorId)
                    ->update(['order' => $order]);
            } else {
                // If the entry does not exist, you may choose to handle it
                // For example, you could log an error or throw an exception
                return redirect()->back()->withErrors(['error' => "Monitor ID {$monitorId} not found in status page {$statusPage->id}."]);
            }
        }

        // Return a success response
        return redirect()->route('status-pages.show', $statusPage->id)
            ->with('success', 'Monitor order updated successfully.');
    }
}
