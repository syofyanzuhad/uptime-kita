<?php

namespace App\Http\Controllers;

use App\Models\StatusPageSubscriber;

class UnsubscribeStatusPageController extends Controller
{
    public function __invoke(string $token)
    {
        $subscriber = StatusPageSubscriber::where('unsubscribe_token', $token)->first();

        if (! $subscriber) {
            return redirect('/')->with('flash', [
                'type' => 'error',
                'message' => 'Invalid or expired unsubscribe link.',
            ]);
        }

        $statusPage = $subscriber->statusPage;
        $subscriber->delete();

        $targetUrl = $statusPage ? $statusPage->getUrl() : url('/');

        return redirect($targetUrl)->with('flash', [
            'type' => 'success',
            'message' => 'You have been unsubscribed from status page updates.',
        ]);
    }
}
