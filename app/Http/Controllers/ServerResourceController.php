<?php

namespace App\Http\Controllers;

use App\Services\ServerResourceService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ServerResourceController extends Controller
{
    public function __construct(
        protected ServerResourceService $serverResourceService
    ) {}

    /**
     * Display the server resources page.
     */
    public function index(): Response|JsonResponse
    {
        if (! auth()->user()?->is_admin) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Only administrators can access server resources.');
        }

        return Inertia::render('settings/ServerResources', [
            'initialMetrics' => $this->serverResourceService->getMetrics(),
        ]);
    }

    /**
     * Get server resource metrics as JSON (for polling).
     */
    public function metrics(): JsonResponse
    {
        if (! auth()->user()?->is_admin) {
            return response()->json(['error' => 'Forbidden'], HttpResponse::HTTP_FORBIDDEN);
        }

        return response()->json($this->serverResourceService->getMetrics());
    }
}
