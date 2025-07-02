<?php

namespace App\Policies;

use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatusPagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StatusPage $statusPage): bool
    {
        return $user->id === $statusPage->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StatusPage $statusPage): bool
    {
        return $user->id === $statusPage->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StatusPage $statusPage): bool
    {
        return $user->id === $statusPage->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StatusPage $statusPage): bool
    {
        return $user->id === $statusPage->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StatusPage $statusPage): bool
    {
        return $user->id === $statusPage->user_id;
    }
}
