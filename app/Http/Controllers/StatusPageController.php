<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatusPageCollection;
use App\Http\Resources\StatusPageResource;
use App\Models\StatusPage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $statusPages = auth()->user()->statusPages()->latest()->paginate(9);

        return Inertia::render('StatusPages/Index', [
            'statusPages' => new StatusPageCollection($statusPages),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('StatusPages/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'icon' => 'required|string|max:255',
            'path' => [
                'nullable',
                'string',
                'max:255',
                'unique:status_pages,path',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
        ]);

        $statusPage = auth()->user()->statusPages()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'icon' => $validated['icon'],
            'path' => $validated['path'] ?? StatusPage::generateUniquePath($validated['title']),
        ]);

        return redirect()->route('status-pages.show', $statusPage)
            ->with('success', 'Status page created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(StatusPage $statusPage): Response
    {
        $this->authorize('view', $statusPage);

        return Inertia::render('StatusPages/Show', [
            'statusPage' => (new StatusPageResource($statusPage->load('monitors.uptimeDaily')))->toArray(request()),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StatusPage $statusPage): Response
    {
        $this->authorize('update', $statusPage);

        return Inertia::render('StatusPages/Edit', [
            'statusPage' => $statusPage,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StatusPage $statusPage)
    {
        $this->authorize('update', $statusPage);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'icon' => 'required|string|max:255',
            'path' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('status_pages', 'path')->ignore($statusPage->id),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
        ]);

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
    public function destroy(StatusPage $statusPage)
    {
        $this->authorize('delete', $statusPage);

        $statusPage->delete();

        return redirect()->route('status-pages.index')
            ->with('success', 'Status page deleted successfully.');
    }
}
