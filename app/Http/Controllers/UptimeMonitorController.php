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
        $monitors = cache()->remember('monitors_list_page_'.$page, 60, function () {
            return new MonitorCollection(Monitor::with(['uptimeDaily', 'histories' => function ($query) {
                $query->latest()->take(100);
            }])->orderBy('created_at', 'desc')->paginate(12));
        });

        $flash = session('flash');

        return Inertia::render('uptime/Index', [
            'monitors' => $monitors,
            'flash' => $flash,
        ]);
    }

    /**
     * Show the monitor by id.
     */
    public function show(Monitor $monitor)
    {
        // implements cache for monitor data with histories included
        $monitorData = cache()->remember("monitor_{$monitor->id}", 60, function () use ($monitor) {
            return new MonitorResource($monitor->load(['uptimeDaily', 'histories' => function ($query) {
                $query->latest()->take(100);
            }]));
        });

        return Inertia::render('uptime/Show', [
            'monitor' => $monitorData,
        ]);
    }

    public function getHistory(Monitor $monitor)
    {
        $histories = cache()->remember("monitor_{$monitor->id}_histories", 60, function () use ($monitor) {
            return MonitorHistoryResource::collection($monitor->histories()->latest()->take(100)->get());
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
            'monitor' => new MonitorResource($monitor->load(['uptimeDaily', 'histories' => function ($query) {
                $query->latest()->take(100);
            }])),
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
            ->first();
        if ($monitorExists) {
            // attach to user
            $monitorExists->users()->attach(auth()->id(), ['is_active' => true]);

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
