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
        $query = Monitor::with(['tags', 'uptimeDaily']);

        // If not logged in, only show public monitors
        if (! auth()->check()) {
            $query->where('is_public', true);
        }

        $monitors = $query->orderBy('url')->get();

        $availableTags = Tag::whereIn('id', function ($query) use ($monitors) {
            $query->select('tag_id')
                ->from('taggables')
                ->whereIn('taggable_id', $monitors->pluck('id'))
                ->where('taggable_type', Monitor::class);
        })->get(['id', 'name']);

        return Inertia::render('monitors/Compact', [
            'monitors' => MonitorResource::collection($monitors),
            'availableTags' => $availableTags,
        ]);
    }
}
