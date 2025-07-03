<?php

namespace App\Http\Controllers;

use App\Models\StatusPage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StatusPageAssociateMonitorController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, StatusPage $statusPage)
    {
        try {
            $this->authorize('update', $statusPage);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        try {
            $validated = $request->validate([
                'monitor_ids' => 'required|array',
                'monitor_ids.*' => 'exists:monitors,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        }

        $statusPage->monitors()->syncWithoutDetaching($validated['monitor_ids']);

        return redirect()->route('status-pages.show', $statusPage)
            ->with('success', 'Monitor berhasil dihubungkan.');
    }
}
