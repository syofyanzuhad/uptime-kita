<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhookSignature
{
    /**
     * Verify the incoming webhook request comes from Telegram using the secret token
     * configured when registering the webhook via Telegram's setWebhook API.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretToken = config('services.telegram.webhook_secret')
            ?? config('services.telegram-bot-api.secret_token');

        // When no secret token is configured (e.g., local development), verification is disabled.
        // Set TELEGRAM_WEBHOOK_SECRET or TELEGRAM_BOT_SECRET_TOKEN to enforce authenticity.
        if (empty($secretToken)) {
            return $next($request);
        }

        $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! is_string($provided) || ! hash_equals((string) $secretToken, $provided)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
