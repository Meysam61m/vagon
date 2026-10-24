<?php

namespace App\Policies;

use App\Models\Representative;
use App\Models\User;

class RepresentativePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Representative $representative): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function update(User $user, Representative $representative): bool
    {
        return (bool) $user->is_active;
    }

    public function delete(User $user, Representative $representative): bool
    {
        return (bool) $user->is_active;
    }
}
