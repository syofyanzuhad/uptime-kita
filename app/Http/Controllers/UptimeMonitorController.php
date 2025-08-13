<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Http\Resources\MonitorHistoryResource;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

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
        $cacheKey = 'monitors_list_page_' . $page . '_per_page_' . $perPage;
        if ($search) {
            $cacheKey .= '_search_' . md5($search);
        }
        if ($statusFilter !== 'all') {
            $cacheKey .= '_filter_' . $statusFilter;
        }
        if ($visibilityFilter !== 'all') {
            $cacheKey .= '_visibility_' . $visibilityFilter;
        }
        $monitors = cache()->remember($cacheKey, 60, function () use ($search, $statusFilter, $visibilityFilter, $perPage) {
            $query = Monitor::with(['uptimeDaily'])->search($search);
            if ($statusFilter === 'up' || $statusFilter === 'down') {
                $query->where('uptime_status', $statusFilter);
            }
            if ($visibilityFilter === 'public') {
                $query->public();
            } elseif ($visibilityFilter === 'private') {
                $query->private();
            }
            return new MonitorCollection(
                $query->orderBy('created_at', 'desc')->paginate($perPage)
            );
        });

        $flash = session('flash');

        return Inertia::render('uptime/Index', [
            'monitors' => $monitors,
            'flash' => $flash,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'perPage' => $perPage,
            'visibilityFilter' => $visibilityFilter,
        ]);
    }

    /**
     * Show the monitor by id.
     */
    public function show(Monitor $monitor)
    {
        // implements cache for monitor data with histories included
        $monitorData = cache()->remember("monitor_{$monitor->id}", 60, function () use ($monitor) {
            // Load uptimeDaily relationship
            $monitor->load(['uptimeDaily']);

            // Get histories from dynamic SQLite database
            $histories = $monitor->histories(100, 0);
            $latestHistory = $monitor->latestHistory();

            // Create a resource with the monitor data and histories
            $resource = new MonitorResource($monitor);

            // Convert histories to MonitorHistory models
            $historyModels = collect($histories)->map(function ($record) use ($monitor) {
                $model = new \App\Models\MonitorHistory();
                $record['monitor_id'] = $monitor->id;
                $model->fill($record);
                $model->exists = true;
                return $model;
            });

            // Set histories and latest history manually
            $resourceData = $resource->toArray(request());
            $resourceData['histories'] = MonitorHistoryResource::collection($historyModels)->toArray(request());
            $resourceData['latest_history'] = $latestHistory ? (new MonitorHistoryResource($latestHistory))->toArray(request()) : null;

            // Calculate down events count from histories
            $resourceData['down_for_events_count'] = collect($histories)->where('uptime_status', 'down')->count();

            return $resourceData;
        });

        return Inertia::render('uptime/Show', [
            'monitor' => $monitorData,
        ]);
    }

    public function getHistory(Monitor $monitor)
    {
        $histories = cache()->remember("monitor_{$monitor->id}_histories", 60, function () use ($monitor) {
            // Get history records from the dynamic SQLite database
            $historyRecords = $monitor->histories(100, 0);

            // Convert array records to MonitorHistory models for the resource
            $historyModels = collect($historyRecords)->map(function ($record) use ($monitor) {
                $model = new \App\Models\MonitorHistory();
                // Add monitor_id to the record data
                $record['monitor_id'] = $monitor->id;
                $model->fill($record);
                $model->exists = true;
                return $model;
            });

            return MonitorHistoryResource::collection($historyModels);
        });

        return response()->json([
            'histories' => $histories->toArray(request()),
        ]);
    }

    /**
     * Show the form for creating a new monitor.
     */
    public function create()
    {
        return Inertia::render('uptime/Create');
    }

    /**
     * Store a newly created monitor in storage.
     */
    public function store(Request $request)
    {
        // sanitize url
        $url = filter_var($request->url, FILTER_VALIDATE_URL);
        $monitor = Monitor::withoutGlobalScope('user')
            ->where('url', $url)
            ->first();
        if ($monitor) {
            // attach to user
            $monitor->users()->attach(auth()->id(), ['is_active' => true]);

            return redirect()->route('monitor.index')
                ->with('flash', ['message' => 'Monitor berhasil ditambahkan!', 'type' => 'success']);
        }

        $request->validate([
            'url' => ['required', 'url'],
            'uptime_check_enabled' => ['boolean'],
            'certificate_check_enabled' => ['boolean'],
            'uptime_check_interval' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $monitor = Monitor::create([
                'url' => $url,
                'is_public' => $request->boolean('is_public', false),
                'uptime_check_enabled' => $request->boolean('uptime_check_enabled'),
                'certificate_check_enabled' => $request->boolean('certificate_check_enabled'),
                'uptime_check_interval_in_minutes' => $request->uptime_check_interval,
            ]);

            // check certificate using command
            Artisan::call('monitor:check-certificate', [
                '--url' => $monitor->url,
            ]);

            return redirect()->route('monitor.index')
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
            'monitor' => new MonitorResource($monitor->load(['uptimeDaily'])),
        ]);
    }

    /**
     * Update the specified monitor in storage.
     */
    public function update(Request $request, Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        $url = filter_var($request->url, FILTER_VALIDATE_URL);
        $monitorExists = Monitor::withoutGlobalScope('user')
            ->where('url', $url)
            ->where('uptime_check_interval_in_minutes', $request->uptime_check_interval)
            ->where('is_public', 0)
            ->whereDoesntHave('users', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->first();
        if ($monitorExists) {
            // attach to user
            $monitorExists->users()->sync(auth()->id(), ['is_active' => true]);

            return redirect()->route('monitor.index')
                ->with('flash', ['message' => 'Monitor berhasil diperbarui!', 'type' => 'success']);
        }

        $request->validate([
            'url' => ['required', 'url', 'unique:monitors,url,'.$monitor->id],
            'uptime_check_enabled' => ['boolean'],
            'certificate_check_enabled' => ['boolean'],
            'uptime_check_interval' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $monitor->update([
                'url' => $request->url,
                'is_public' => $request->boolean('is_public', false),
                'uptime_check_enabled' => $request->boolean('uptime_check_enabled'),
                'certificate_check_enabled' => $request->boolean('certificate_check_enabled'),
                'uptime_check_interval_in_minutes' => $request->uptime_check_interval,
            ]);

            return redirect()->route('monitor.index')
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
        $this->authorize('delete', $monitor);

        try {
            $monitor->delete();

            return redirect()->route('monitor.index')
                ->with('flash', ['message' => 'Monitor berhasil dihapus!', 'type' => 'success']);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('flash', ['message' => 'Gagal menghapus monitor: '.$e->getMessage(), 'type' => 'error']);
        }
    }
}
