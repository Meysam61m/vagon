<?php

namespace App\Policies;

use App\Models\Owner;
use App\Models\User;

class OwnerPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Owner $owner): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function update(User $user, Owner $owner): bool
    {
        return (bool) $user->is_active;
    }

    public function delete(User $user, Owner $owner): bool
    {
        return (bool) $user->is_active;
    }
}
