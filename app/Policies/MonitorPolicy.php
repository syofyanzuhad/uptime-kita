<?php

namespace App\Policies;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MonitorPolicy
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
    public function view(User $user, Monitor $monitor): bool
    {
        // User can view if they are subscribed to the monitor
        return $monitor->users()->where('user_id', $user->id)->exists();
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
    public function update(User $user, Monitor $monitor): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // For public monitors, only admins can update
        if ($monitor->is_public) {
            return false;
        }

        // For private monitors, only the owner can update
        return $monitor->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Monitor $monitor): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // For public monitors, only admins can delete
        if ($monitor->is_public) {
            return false;
        }

        // For private monitors, only the owner can delete
        return $monitor->isOwnedBy($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Monitor $monitor): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $monitor->isOwnedBy($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Monitor $monitor): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $monitor->isOwnedBy($user);
    }
}
