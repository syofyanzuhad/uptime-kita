<?php

namespace App\Http\Controllers;

use App\Models\StatusPage;
use App\Models\StatusPageSubscriber;
use App\Notifications\VerifyStatusPageSubscriptionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class SubscribeStatusPageController extends Controller
{
    public function __invoke(Request $request, string $path)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $statusPage = StatusPage::where('path', $path)->firstOrFail();

        $subscriber = StatusPageSubscriber::where('status_page_id', $statusPage->id)
            ->where('email', $request->email)
            ->first();

        if ($subscriber && $subscriber->verified_at) {
            $message = 'You are already subscribed to this status page.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 200);
            }

            return redirect()->back()->with('flash', [
                'type' => 'info',
                'message' => $message,
            ]);
        }

        if (! $subscriber) {
            $subscriber = StatusPageSubscriber::create([
                'status_page_id' => $statusPage->id,
                'email' => $request->email,
            ]);
        }

        // Send verification notification
        Notification::route('mail', $subscriber->email)
            ->notify(new VerifyStatusPageSubscriptionNotification($subscriber));

        $successMessage = 'A verification email has been sent. Please check your inbox to confirm your subscription.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $successMessage], 200);
        }

        return redirect()->back()->with('flash', [
            'type' => 'success',
            'message' => $successMessage,
        ]);
    }
}
