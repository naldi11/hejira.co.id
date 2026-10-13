<?php

namespace App\Policies;

use App\Models\CashierShift;
use App\Models\User;

class CashierShiftPolicy
{
    public function view(User $user, CashierShift $shift): bool
    {
        if (!in_array($user->entity, [$shift->entity, 'all'], true)) {
            return false;
        }

        if ($user->hasRole(['super_admin', 'owner', "admin_{$shift->entity}"])) {
            return true;
        }

        return $user->hasRole("kasir_{$shift->entity}") && $shift->user_id === $user->id;
    }
}
