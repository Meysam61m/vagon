<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wagon;

class WagonPolicy
{
    public function update(User $user, Wagon $wagon): bool
    {
        return (bool) $user->is_active;
    }

    public function delete(User $user, Wagon $wagon): bool
    {
        return (bool) $user->is_active && ! $wagon->is_settled;
    }
}
