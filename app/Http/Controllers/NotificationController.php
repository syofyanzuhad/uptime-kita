<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationChannelRequest;
use App\Http\Requests\UpdateNotificationChannelRequest;
use App\Models\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display a listing of the notification channels.
     */
    public function index(): Response
    {
        $channels = Auth::user()->notificationChannels()->latest()->get();

        return Inertia::render('settings/Notifications', [
            'channels' => $channels,
        ]);
    }

    /**
     * Show the form for creating a new notification channel.
     */
    public function create(): Response
    {
        return Inertia::render('settings/Notifications', [
            'channels' => Auth::user()->notificationChannels()->latest()->get(),
            'showForm' => true,
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created notification channel in storage.
     */
    public function store(StoreNotificationChannelRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();

        NotificationChannel::create($validated);

        return Redirect::route('notifications.index')
            ->with('success', 'Notification channel created successfully.');
    }

    /**
     * Display the specified notification channel.
     */
    public function show(int $id): Response
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);

        return Inertia::render('settings/Notifications', [
            'channels' => Auth::user()->notificationChannels()->latest()->get(),
            'editingChannel' => $channel,
            'showForm' => true,
            'isEdit' => true,
        ]);
    }

    /**
     * Show the form for editing the specified notification channel.
     */
    public function edit(int $id): Response
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);

        return Inertia::render('settings/Notifications', [
            'channels' => Auth::user()->notificationChannels()->latest()->get(),
            'editingChannel' => $channel,
            'showForm' => true,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified notification channel in storage.
     */
    public function update(UpdateNotificationChannelRequest $request, int $id): RedirectResponse
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);
        $channel->update($request->validated());

        return Redirect::route('notifications.index')
            ->with('success', 'Notification channel updated successfully.');
    }

    /**
     * Remove the specified notification channel from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $channel = Auth::user()->notificationChannels()->findOrFail($id);
        $channel->delete();

        return Redirect::route('notifications.index')
            ->with('success', 'Notification channel deleted successfully.');
    }
}
