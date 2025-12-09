<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\SendTelemetryPingJob;
use App\Services\InstanceIdService;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TelemetryController extends Controller
{
    public function __construct(
        protected TelemetryService $telemetryService,
        protected InstanceIdService $instanceIdService
    ) {}

    /**
     * Display the telemetry settings page.
     */
    public function index(): Response
    {
        if (! auth()->user()?->is_admin) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Only administrators can access telemetry settings.');
        }

        return Inertia::render('settings/Telemetry', [
            'settings' => $this->telemetryService->getSettings(),
            'previewData' => $this->telemetryService->previewData(),
        ]);
    }

    /**
     * Preview telemetry data (for transparency).
     */
    public function preview(): JsonResponse
    {
        if (! auth()->user()?->is_admin) {
            return response()->json(['error' => 'Forbidden'], HttpResponse::HTTP_FORBIDDEN);
        }

        return response()->json($this->telemetryService->previewData());
    }

    /**
     * Send a test telemetry ping.
     */
    public function testPing(): JsonResponse
    {
        if (! auth()->user()?->is_admin) {
            return response()->json(['error' => 'Forbidden'], HttpResponse::HTTP_FORBIDDEN);
        }

        if (! $this->telemetryService->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Telemetry is disabled. Enable it first to send a test ping.',
            ], 400);
        }

        SendTelemetryPingJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Test ping queued successfully.',
        ]);
    }

    /**
     * Regenerate instance ID.
     */
    public function regenerateInstanceId(): JsonResponse
    {
        if (! auth()->user()?->is_admin) {
            return response()->json(['error' => 'Forbidden'], HttpResponse::HTTP_FORBIDDEN);
        }

        $newId = $this->instanceIdService->regenerateInstanceId();

        return response()->json([
            'success' => true,
            'instance_id' => $newId,
            'message' => 'Instance ID regenerated successfully.',
        ]);
    }
}
