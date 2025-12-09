<?php

namespace App\Http\Controllers;

use App\Services\ServerResourceService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServerResourceController extends Controller
{
    public function __construct(
        protected ServerResourceService $serverResourceService
    ) {}

    /**
     * Display the server resources page.
     */
    public function index(): Response
    {
        return Inertia::render('settings/ServerResources', [
            'initialMetrics' => $this->serverResourceService->getMetrics(),
        ]);
    }

    /**
     * Get server resource metrics as JSON (for polling).
     */
    public function metrics(): JsonResponse
    {
        return response()->json($this->serverResourceService->getMetrics());
    }
}
