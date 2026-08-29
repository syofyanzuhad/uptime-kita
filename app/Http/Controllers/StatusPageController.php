<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStatusPageRequest;
use App\Http\Requests\UpdateStatusPageRequest;
use App\Http\Resources\StatusPageCollection;
use App\Http\Resources\StatusPageResource;
use App\Models\StatusPage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatusPageController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $statusPages = auth()->user()->statusPages()
            ->withCount('monitors')
            ->latest()
            ->paginate(9);

        return Inertia::render('status-pages/Index', [
            'statusPages' => new StatusPageCollection($statusPages),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('status-pages/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStatusPageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $statusPage = auth()->user()->statusPages()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon' => $validated['icon'],
            'path' => $validated['path'] ?? StatusPage::generateUniquePath($validated['title']),
            'custom_domain' => $validated['custom_domain'] ?? null,
            'force_https' => $validated['force_https'] ?? true,
        ]);

        // Generate verification token if custom domain is provided
        if ($statusPage->custom_domain) {
            $statusPage->generateVerificationToken();
        }

        return redirect()->route('status-pages.show', $statusPage)
            ->with('success', 'Status page created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(StatusPage $statusPage): Response
    {
        $this->authorize('view', $statusPage);

        return Inertia::render('status-pages/Show', [
            'statusPage' => (new StatusPageResource($statusPage->load([
                'monitors' => function ($query) {
                    $query->orderBy('status_page_monitor.order', 'asc')
                        ->with(['uptimeDaily']);
                },
            ])))->toArray(request()),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StatusPage $statusPage): Response
    {
        $this->authorize('update', $statusPage);

        return Inertia::render('status-pages/Edit', [
            'statusPage' => $statusPage->only([
                'id',
                'title',
                'description',
                'icon',
                'path',
                'custom_domain',
                'custom_domain_verified',
                'force_https',
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStatusPageRequest $request, StatusPage $statusPage): RedirectResponse
    {
        $this->authorize('update', $statusPage);

        $validated = $request->validated();

        $statusPage->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon' => $validated['icon'],
            'path' => $validated['path'] ?? $statusPage->path,
        ]);

        return redirect()->route('status-pages.show', $statusPage)
            ->with('success', 'Status page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StatusPage $statusPage): RedirectResponse
    {
        $this->authorize('delete', $statusPage);

        $statusPage->delete();

        return redirect()->route('status-pages.index')
            ->with('success', 'Status page deleted successfully.');
    }
}
