<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

class UptimeMonitorController extends Controller
{
    /**
     * Display a listing of the monitors.
     */
    public function index()
    {
        $monitors = Monitor::orderBy('created_at', 'desc')
                           ->get()
                           ->map(function ($monitor) {
                               return [
                                   'id' => $monitor->id,
                                   'url' => $monitor->raw_url,
                                   // Akses langsung atribut status dari model Monitor
                                   'uptime_status' => $monitor->uptime_status,
                                   'last_check_date' => $monitor->uptime_last_check_date,
                                   'certificate_check_enabled' => (bool) $monitor->certificate_check_enabled,
                                   // Akses langsung atribut status sertifikat dari model Monitor
                                   'certificate_status' => $monitor->certificate_status,
                                   'certificate_expiration_date' => $monitor->certificate_expiration_date,
                                   'down_for_events_count' => $monitor->down_for_events_count,
                                   'uptime_check_interval' => $monitor->uptime_check_interval_in_minutes,
                               ];
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
        $monitorData = [
            'id' => $monitor->id,
            'url' => $monitor->raw_url,
            'uptime_status' => $monitor->uptime_status,
            'last_check_date' => $monitor->uptime_last_check_date,
            'certificate_check_enabled' => (bool) $monitor->certificate_check_enabled,
            'certificate_status' => $monitor->certificate_status,
            'certificate_expiration_date' => $monitor->certificate_expiration_date,
            'down_for_events_count' => $monitor->down_for_events_count,
            'uptime_check_interval' => $monitor->uptime_check_interval_in_minutes,
        ];

        $histories = $monitor->histories()->orderBy('created_at', 'desc')->get()->map(function ($history) {
            return [
                'id' => $history->id,
                'uptime_status' => $history->uptime_status,
                'created_at' => $history->created_at,
            ];
        });

        return Inertia::render('uptime/Show', [
            'monitor' => $monitorData,
            'histories' => $histories,
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
                             ->with('flash', ['message' => 'Gagal menambahkan monitor: ' . $e->getMessage(), 'type' => 'error'])
                             ->withInput();
        }
    }

    /**
     * Show the form for editing the specified monitor.
     */
    public function edit(Monitor $monitor)
    {
        return Inertia::render('uptime/Edit', [
            'monitor' => [
                'id' => $monitor->id,
                'url' => $monitor->raw_url,
                'uptime_check_enabled' => (bool) $monitor->uptime_check_enabled,
                'certificate_check_enabled' => (bool) $monitor->certificate_check_enabled,
                'uptime_check_interval' => $monitor->uptime_check_interval_in_minutes,
            ]
        ]);
    }

    /**
     * Update the specified monitor in storage.
     */
    public function update(Request $request, Monitor $monitor)
    {
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
            'url' => ['required', 'url', 'unique:monitors,url,' . $monitor->id],
            'uptime_check_enabled' => ['boolean'],
            'certificate_check_enabled' => ['boolean'],
            'uptime_check_interval' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $monitor->update([
                'url' => $request->url,
                'uptime_check_enabled' => $request->boolean('uptime_check_enabled'),
                'certificate_check_enabled' => $request->boolean('certificate_check_enabled'),
                'uptime_check_interval_in_minutes' => $request->uptime_check_interval,
            ]);

            return redirect()->route('monitor.index')
                             ->with('flash', ['message' => 'Monitor berhasil diperbarui!', 'type' => 'success']);

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('flash', ['message' => 'Gagal memperbarui monitor: ' . $e->getMessage(), 'type' => 'error'])
                             ->withInput();
        }
    }

    /**
     * Remove the specified monitor from storage.
     */
    public function destroy(Monitor $monitor)
    {
        try {
            $monitor->delete();

            return redirect()->route('monitor.index')
                             ->with('flash', ['message' => 'Monitor berhasil dihapus!', 'type' => 'success']);

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('flash', ['message' => 'Gagal menghapus monitor: ' . $e->getMessage(), 'type' => 'error']);
        }
    }

    /**
     * Display a listing of public monitors.
     */
    public function public()
    {
        $publicMonitors = Monitor::withoutGlobalScope('user')
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($monitor) {
                $isSubscribed = false;

                // Check if user is authenticated and already subscribed
                if (auth()->check()) {
                    $isSubscribed = $monitor->users()->where('user_id', auth()->id())->exists();
                }

                return [
                    'id' => $monitor->id,
                    'url' => $monitor->raw_url,
                    'uptime_status' => $monitor->uptime_status,
                    'last_check_date' => $monitor->uptime_last_check_date,
                    'certificate_check_enabled' => (bool) $monitor->certificate_check_enabled,
                    'certificate_status' => $monitor->certificate_status,
                    'certificate_expiration_date' => $monitor->certificate_expiration_date,
                    'down_for_events_count' => $monitor->down_for_events_count,
                    'uptime_check_interval' => $monitor->uptime_check_interval_in_minutes,
                    'is_subscribed' => $isSubscribed,
                ];
            });

        return response()->json($publicMonitors);
    }

    /**
     * Subscribe to a public monitor.
     */
    public function subscribe(Monitor $monitor)
    {
        try {
            // Check if monitor is public
            if (!$monitor->is_public) {
                return response()->json([
                    'success' => false,
                    'message' => 'Monitor tidak tersedia untuk berlangganan'
                ], 400);
            }

            // Check if user is already subscribed
            $existingSubscription = $monitor->users()->where('user_id', auth()->id())->first();
            if ($existingSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah berlangganan monitor ini'
                ], 400);
            }

            // Attach monitor to user
            $monitor->users()->attach(auth()->id(), ['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil berlangganan monitor!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal berlangganan monitor: ' . $e->getMessage()
            ], 500);
        }
    }
}
