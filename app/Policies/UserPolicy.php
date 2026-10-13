<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $actor, User $target): bool
    {
        if ($target->hasRole(User::ROLE_OWNER)) {
            return $actor->id === $target->id && $actor->hasRole(User::ROLE_OWNER);
        }

        return $actor->hasRole('super_admin');
    }

    public function delete(User $actor, User $target): bool
    {
        return !$target->hasRole(User::ROLE_OWNER) && $actor->hasRole('super_admin');
    }
}
