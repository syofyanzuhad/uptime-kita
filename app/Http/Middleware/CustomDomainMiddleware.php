<?php

namespace App\Http\Middleware;

use App\Models\StatusPage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomDomainMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $appDomain = parse_url(config('app.url'), PHP_URL_HOST);

        // Skip if it's the main app domain
        if ($host === $appDomain || $host === 'localhost' || $host === '127.0.0.1') {
            return $next($request);
        }

        // Check if this is a custom domain for a status page
        $statusPage = StatusPage::where('custom_domain', $host)
            ->where('custom_domain_verified', true)
            ->first();

        if ($statusPage) {
            // Force HTTPS if enabled
            if ($statusPage->force_https && ! $request->secure()) {
                return redirect()->secure($request->getRequestUri());
            }

            // Store the status page in the request for later use
            $request->attributes->set('custom_domain_status_page', $statusPage);

            // Override the route to point to the status page
            $request->merge(['path' => $statusPage->path]);
            $request->server->set('REQUEST_URI', '/status/'.$statusPage->path);
        }

        return $next($request);
    }
}
