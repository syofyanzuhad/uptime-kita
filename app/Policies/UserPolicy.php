<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Grant all abilities to admin users.
     */
    public function before(User $user, $ability)
    {
        return $user->is_admin ? true : null;
    }

    public function viewAny(User $user) { return false; }
    public function view(User $user) { return false; }
    public function create(User $user) { return false; }
    public function update(User $user) { return false; }
    public function delete(User $user) { return false; }
}
