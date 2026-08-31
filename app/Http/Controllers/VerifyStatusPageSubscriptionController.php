<?php

namespace App\Http\Controllers;

use App\Models\StatusPageSubscriber;

class VerifyStatusPageSubscriptionController extends Controller
{
    public function __invoke(string $token)
    {
        $subscriber = StatusPageSubscriber::where('verification_token', $token)->first();

        if (! $subscriber) {
            return redirect('/')->with('flash', [
                'type' => 'error',
                'message' => 'Invalid or expired verification link.',
            ]);
        }

        $subscriber->markAsVerified();
        $statusPage = $subscriber->statusPage;

        $targetUrl = $statusPage ? $statusPage->getUrl() : url('/');

        return redirect($targetUrl)->with('flash', [
            'type' => 'success',
            'message' => 'Your subscription has been verified successfully!',
        ]);
    }
}
