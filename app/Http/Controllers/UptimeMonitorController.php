<?php

namespace App\Http\Controllers;

use App\Actions\Monitors\CreateMonitorAction;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Requests\UpdateMonitorRequest;
use App\Http\Resources\MonitorCollection;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Tags\Tag;

class UptimeMonitorController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the monitors.
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $search = $request->input('search');
        $statusFilter = $request->input('status_filter', 'all');
        $perPage = $request->input('per_page', '15');
        $visibilityFilter = $request->input('visibility_filter', 'all');
        $tagFilter = $request->input('tag_filter');
        $cacheKey = 'monitors_list_page_'.$page.'_per_page_'.$perPage.'_user_'.auth()->id();
        if ($search) {
            $cacheKey .= '_search_'.md5($search);
        }
        if ($statusFilter !== 'all') {
            $cacheKey .= '_filter_'.$statusFilter;
        }
        if ($visibilityFilter !== 'all') {
            $cacheKey .= '_visibility_'.$visibilityFilter;
        }
        if ($tagFilter) {
            $cacheKey .= '_tag_'.md5($tagFilter);
        }
        $monitors = cache()->remember($cacheKey, 60, function () use ($search, $statusFilter, $visibilityFilter, $tagFilter, $perPage) {
            $query = Monitor::with(['uptimeDaily', 'tags', 'users' => function ($query) {
                $query->where('users.id', auth()->id());
            }])->search($search);
            if ($statusFilter === 'up' || $statusFilter === 'down') {
                $query->where('uptime_status', $statusFilter);
            }
            if ($visibilityFilter === 'public') {
                $query->public();
            } elseif ($visibilityFilter === 'private') {
                $query->private();
            }
            if ($tagFilter) {
                $query->withAnyTags([$tagFilter]);
            }

            return new MonitorCollection(
                $query->orderBy('created_at', 'desc')->paginate($perPage)
            );
        });

        $flash = session('flash');

        // Get all unique tags used in monitors
        $availableTags = Tag::whereIn('id', function ($query) {
            $query->select('tag_id')
                ->from('taggables')
                ->where('taggable_type', 'App\Models\Monitor');
        })->orderBy('name')->get(['id', 'name']);

        return Inertia::render('uptime/Index', [
            'monitors' => $monitors,
            'flash' => $flash,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'perPage' => $perPage,
            'visibilityFilter' => $visibilityFilter,
            'tagFilter' => $tagFilter,
            'availableTags' => $availableTags,
        ]);
    }

    /**
     * Show the monitor by id.
     */
    public function show(Monitor $monitor)
    {
        // implements cache for monitor data with histories included
        $monitorData = cache()->remember("monitor_{$monitor->id}", 60, function () use ($monitor) {
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

            $uniqueIds = \DB::select($sql, [$monitor->id]);
            $ids = array_column($uniqueIds, 'id');

            $uniqueHistories = MonitorHistory::whereIn('id', $ids)
                ->orderBy('created_at', 'desc')
                ->get();

            $monitor->load(['uptimeDaily', 'tags']);
            $monitor->setRelation('histories', $uniqueHistories);

            return new MonitorResource($monitor);
        });

        return Inertia::render('uptime/Show', [
            'monitor' => $monitorData,
        ]);
    }

    /**
     * Show the form for creating a new monitor.
     */
    public function create(Request $request)
    {
        return Inertia::render('uptime/Create', [
            'url' => $request->query('url', ''),
        ]);
    }

    /**
     * Store a newly created monitor in storage.
     */
    public function store(StoreMonitorRequest $request, CreateMonitorAction $createAction)
    {
        $url = $request->validated('url');
        $monitor = Monitor::withoutGlobalScope('user')
            ->where('url', $url)
            ->first();

        if ($monitor) {
            $monitor->users()->attach(auth()->id(), ['is_active' => true]);

            return redirect()->route('monitors.index')
                ->with('flash', ['message' => 'Monitor berhasil ditambahkan!', 'type' => 'success']);
        }

        try {
            $createAction->execute(auth()->user(), [
                'url' => $url,
                'is_public' => $request->boolean('is_public', false),
                'uptime_check_enabled' => $request->boolean('uptime_check_enabled'),
                'certificate_check_enabled' => $request->boolean('certificate_check_enabled'),
                'domain_expiration_check_enabled' => $request->boolean('domain_expiration_check_enabled'),
                'uptime_check_interval' => $request->input('uptime_check_interval'),
                'tags' => $request->input('tags'),
            ]);

            return redirect()->route('monitors.index')
                ->with('flash', ['message' => 'Monitor berhasil ditambahkan!', 'type' => 'success']);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('flash', ['message' => 'Gagal menambahkan monitor: '.$e->getMessage(), 'type' => 'error'])
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified monitor.
     */
    public function edit(Monitor $monitor)
    {
        return Inertia::render('uptime/Edit', [
            'monitor' => new MonitorResource($monitor->load(['uptimeDaily', 'tags'])),
        ]);
    }

    /**
     * Update the specified monitor in storage.
     */
    public function update(UpdateMonitorRequest $request, Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        $url = $request->validated('url');
        $monitorExists = Monitor::withoutGlobalScope('user')
            ->where('url', $url)
            ->where('uptime_check_interval_in_minutes', $request->input('uptime_check_interval'))
            ->where('is_public', 0)
            ->whereDoesntHave('users', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->first();

        if ($monitorExists) {
            $monitorExists->users()->sync(auth()->id(), ['is_active' => true]);

            return redirect()->route('monitors.index')
                ->with('flash', ['message' => 'Monitor berhasil diperbarui!', 'type' => 'success']);
        }

        try {
            $monitor->update([
                'url' => $url,
                'is_public' => $request->boolean('is_public', false),
                'uptime_check_enabled' => $request->boolean('uptime_check_enabled'),
                'certificate_check_enabled' => $request->boolean('certificate_check_enabled'),
                'domain_expiration_check_enabled' => $request->boolean('domain_expiration_check_enabled'),
                'uptime_check_interval_in_minutes' => $request->input('uptime_check_interval'),
                'sensitivity' => $request->input('sensitivity', 'medium'),
                'confirmation_delay_seconds' => $request->input('confirmation_delay_seconds'),
                'confirmation_retries' => $request->input('confirmation_retries'),
            ]);

            if ($request->has('tags')) {
                $monitor->syncTags($request->input('tags') ?? []);
            }

            return redirect()->route('monitors.index')
                ->with('flash', ['message' => 'Monitor berhasil diperbarui!', 'type' => 'success']);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('flash', ['message' => 'Gagal memperbarui monitor: '.$e->getMessage(), 'type' => 'error'])
                ->withInput();
        }
    }

    /**
     * Remove the specified monitor from storage.
     */
    public function destroy(Monitor $monitor)
    {
        try {
            // if monitor is not owned by the logged in user, detach from user
            if (! $monitor->isOwnedBy(auth()->user()) && ! auth()->user()?->is_admin) {
                $monitor->users()->detach(auth()->id());
            } else {
                if (! auth()->user()?->is_admin) {
                    $this->authorize('delete', $monitor);
                }
                $monitor->delete();
            }
            // remove cache index
            cache()->forget('monitor_list_page_1_per_page_15_user_'.auth()->id());

            return redirect()->route('monitors.index')
                ->with('flash', ['message' => 'Monitor berhasil dihapus!', 'type' => 'success']);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('flash', ['message' => 'Gagal menghapus monitor: '.$e->getMessage(), 'type' => 'error']);
        }
    }
}
