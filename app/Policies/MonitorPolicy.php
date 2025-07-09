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
        if ($monitor->is_public) {
            // Only user with ID 1 can update public monitors
            return $user->id === 1;
        } else {
            // Only the owner can update private monitors
            return $monitor->isOwnedBy($user);
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Monitor $monitor): bool
    {
        if ($monitor->is_public) {
            // Only user with ID 1 can delete public monitors
            return $user->id === 1;
        } else {
            // Only the owner can delete private monitors
            return $monitor->isOwnedBy($user);
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Monitor $monitor): bool
    {
        if ($monitor->is_public) {
            // Only user with ID 1 can restore public monitors
            return $user->id === 1;
        } else {
            // Only the owner can restore private monitors
            return $monitor->isOwnedBy($user);
        }
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Monitor $monitor): bool
    {
        if ($monitor->is_public) {
            // Only user with ID 1 can permanently delete public monitors
            return $user->id === 1;
        } else {
            // Only the owner can permanently delete private monitors
            return $monitor->isOwnedBy($user);
        }
    }
}
