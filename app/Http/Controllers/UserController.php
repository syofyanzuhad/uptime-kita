<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->withCount('monitors')
            ->withCount('statusPages')
            ->withCount('notificationChannels');

        // Search functionality
        if ($request->filled('search') && strlen((string) $request->search) >= 3) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        // Per page functionality
        $perPage = $request->get('per_page', 15);
        if (! in_array($perPage, [5, 10, 15, 20, 50, 100])) {
            $perPage = 15;
        }

        $users = $query->paginate($perPage);

        return Inertia::render('users/Index', [
            'users' => $users,
            'search' => $request->search,
            'perPage' => (int) $perPage,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('users/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        $user->load([
            'monitors' => function ($query) {
                $query->select('monitors.id', 'monitors.display_name', 'monitors.url', 'monitors.uptime_status', 'monitors.created_at')
                    ->withPivot(['is_active', 'is_pinned', 'created_at']);
            },
            'statusPages' => function ($query) {
                $query->select('id', 'user_id', 'title', 'description', 'path', 'created_at');
            },
            'notificationChannels' => function ($query) {
                $query->select('id', 'user_id', 'type', 'destination', 'is_enabled', 'created_at');
            },
        ]);

        return Inertia::render('users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response|RedirectResponse
    {
        // Ensure the user is not the default admin user
        if ($user->id === 1) {
            return redirect()->route('users.index')->with('error', 'Cannot edit the default admin user.');
        }

        return Inertia::render('users/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Ensure the user is not the default admin user
        if ($user->id === 1) {
            return redirect()->route('users.index')->with('error', 'Cannot edit the default admin user.');
        }

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $errorMessage = null;

        if ($user->id === 1) {
            $errorMessage = 'Cannot delete the default admin user.';
        } elseif ($user->monitors()->count() > 0) {
            $errorMessage = 'Cannot delete user with associated monitors.';
        } elseif ($user->statusPages()->count() > 0) {
            $errorMessage = 'Cannot delete user with associated status pages.';
        }

        if ($errorMessage) {
            return redirect()->route('users.index')->with('error', $errorMessage);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
