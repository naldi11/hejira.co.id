<?php

namespace App\Policies;

use App\Models\HendhysTransaction;
use App\Models\User;

class HendhysTransactionPolicy
{
    public function view(User $user, HendhysTransaction $transaction): bool
    {
        if (!in_array($user->entity, ['hendhys', 'all'], true)) {
            return false;
        }

        if ($user->hasRole(['super_admin', 'owner'])) {
            return true;
        }

        if (!$user->hasRole(['kasir_hendhys', 'admin_hendhys'])) {
            return false;
        }

        $branchId = $user->branch?->type === 'cabang' ? $user->branch_id : null;

        return $transaction->branch_id === $branchId;
    }
}
