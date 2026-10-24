<?php

namespace App\Policies;

use App\Models\CargoRequest;
use App\Models\User;

class CargoRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, CargoRequest $cargo): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function update(User $user, CargoRequest $cargo): bool
    {
        return (bool) $user->is_active;
    }

    public function print(User $user, CargoRequest $cargo): bool
    {
        return (bool) $user->is_active;
    }
}
