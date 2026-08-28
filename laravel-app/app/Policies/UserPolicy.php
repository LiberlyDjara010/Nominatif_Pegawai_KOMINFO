<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function view(User $user, User $target): bool
    {
        return $user->canManageUsers();
    }

    public function create(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function update(User $user, User $target): bool
    {
        if ($target->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        if ($target->isAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $user->canManageUsers();
    }

    public function delete(User $user, User $target): bool
    {
        if ($target->isSuperAdmin()) {
            return false;
        }

        if ($target->isAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $user->canManageUsers();
    }
}
