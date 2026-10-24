<?php

namespace App\Policies;

use App\Models\Billing;
use App\Models\User;

class BillingPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Billing $billing): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function settle(User $user, Billing $billing): bool
    {
        return (bool) $user->is_active && ! $billing->isCancelled();
    }

    public function print(User $user, Billing $billing): bool
    {
        return (bool) $user->is_active && ! $billing->isCancelled();
    }

    public function cancel(User $user, Billing $billing): bool
    {
        return (bool) $user->is_active && $user->isAdmin() && ! $billing->isCancelled();
    }
}
