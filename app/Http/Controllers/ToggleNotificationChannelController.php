<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ToggleNotificationChannelController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $channel = auth()->user()->notificationChannels()->findOrFail($id);

        $channel->update(['is_enabled' => ! $channel->is_enabled]);

        return Redirect::route('notifications.index')
            ->with('success', 'Notification channel '.($channel->is_enabled ? 'enabled' : 'disabled').' successfully.');
    }
}
