<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\NotificationChannel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index()
    {
        $channels = Auth::user()->notificationChannels()->latest()->get();
        return Inertia::render('settings/Notifications', [
            'channels' => $channels,
        ]);
    }

    public function create()
    {
        return Inertia::render('settings/Notifications', [
            'channels' => Auth::user()->notificationChannels()->latest()->get(),
            'showForm' => true,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'destination' => 'required|string',
            'is_enabled' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $validated['user_id'] = Auth::id();
        NotificationChannel::create($validated);

        return Redirect::route('notifications.index')
            ->with('success', 'Notification channel created successfully.');
    }

    public function show($id)
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);
        return Inertia::render('settings/Notifications', [
            'channels' => Auth::user()->notificationChannels()->latest()->get(),
            'editingChannel' => $channel,
            'showForm' => true,
            'isEdit' => true,
        ]);
    }

    public function edit($id)
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);
        return Inertia::render('settings/Notifications', [
            'channels' => Auth::user()->notificationChannels()->latest()->get(),
            'editingChannel' => $channel,
            'showForm' => true,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);
        $validated = $request->validate([
            'type' => 'required|string',
            'destination' => 'required|string',
            'is_enabled' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $channel->update($validated);

        return Redirect::route('notifications.index')
            ->with('success', 'Notification channel updated successfully.');
    }

    public function destroy($id)
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);
        $channel->delete();

        return Redirect::route('notifications.index')
            ->with('success', 'Notification channel deleted successfully.');
    }

    public function toggle($id)
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);
        $channel->update(['is_enabled' => !$channel->is_enabled]);

        return Redirect::route('notifications.index')
            ->with('success', 'Notification channel ' . ($channel->is_enabled ? 'enabled' : 'disabled') . ' successfully.');
    }
}
