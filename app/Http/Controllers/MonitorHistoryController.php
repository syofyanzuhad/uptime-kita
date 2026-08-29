<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorHistoryResource;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitorHistoryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Monitor $monitor): JsonResponse
    {
        $histories = cache()->remember("monitor_{$monitor->id}_histories", 60, function () use ($monitor) {
            $dateFormatter = MonitorHistory::getDateFormatterSql();
            // Get unique history IDs using raw SQL to ensure only one record per minute
            $sql = "
                SELECT id FROM (
                    SELECT id, created_at, ROW_NUMBER() OVER (
                        PARTITION BY monitor_id, {$dateFormatter} 
                        ORDER BY created_at DESC, id DESC
                    ) as rn
                    FROM monitor_histories
                    WHERE monitor_id = ?
                ) ranked
                WHERE rn = 1
                ORDER BY created_at DESC
                LIMIT 100
            ";

            $uniqueIds = DB::select($sql, [$monitor->id]);
            $ids = array_column($uniqueIds, 'id');

            $uniqueHistories = MonitorHistory::whereIn('id', $ids)
                ->orderBy('created_at', 'desc')
                ->get();

            return MonitorHistoryResource::collection($uniqueHistories);
        });

        return response()->json([
            'histories' => $histories->toArray($request),
        ]);
    }
}
