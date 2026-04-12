<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Tags\Tag;

class MonitorCompactController extends Controller
{
    /**
     * Display a compact listing of all monitors.
     */
    public function index(Request $request)
    {
        // Increase memory limit for this request to handle wallboard data
        if (config('app.env') !== 'local') {
            ini_set('memory_limit', '512M');
        }

        $query = Monitor::query()
            ->select([
                'id',
                'url',
                'uptime_status',
                'uptime_check_enabled',
                'uptime_last_check_date',
                'certificate_check_enabled',
                'certificate_status',
                'certificate_expiration_date',
                'uptime_check_interval',
                'is_public',
                'page_views_count',
                'uptime_status_last_change_date',
                'uptime_check_failure_reason',
                'sensitivity',
                'confirmation_delay_seconds',
                'confirmation_retries',
                'transient_failures_count',
                'created_at',
                'updated_at',
            ])
            ->with(['tags', 'uptimeDaily', 'statistics', 'latestHistory']);

        // If not logged in, only show public monitors
        if (! auth()->check()) {
            $query->public();
        }

        $monitors = $query->orderBy('url')->get();

        $monitorIds = $monitors->pluck('id');

        $availableTags = Tag::whereIn('id', function ($query) use ($monitorIds) {
            $query->select('tag_id')
                ->from('taggables')
                ->whereIn('taggable_id', $monitorIds)
                ->where('taggable_type', Monitor::class);
        })->get(['id', 'name']);

        return Inertia::render('monitors/Compact', [
            'monitors' => MonitorResource::collection($monitors),
            'availableTags' => $availableTags,
        ]);
    }
}
