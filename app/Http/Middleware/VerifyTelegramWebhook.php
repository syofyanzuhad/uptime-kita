<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhook
{
    /**
     * Verify the request comes from Telegram using the secret token
     * configured when registering the webhook via setWebhook.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretToken = config('services.telegram-bot-api.secret_token');

        // When no secret token is configured, verification is disabled.
        // Set TELEGRAM_BOT_SECRET_TOKEN (and re-register the webhook with
        // the same value) to enforce request authenticity.
        if (empty($secretToken)) {
            return $next($request);
        }

        $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! is_string($provided) || ! hash_equals($secretToken, $provided)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
