<?php

namespace App\Policies;

use App\Models\User;

class PegawaiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageData();
    }

    public function view(User $user): bool
    {
        return $user->canManageData();
    }

    public function create(User $user): bool
    {
        return $user->canManageData();
    }

    public function update(User $user): bool
    {
        return $user->canManageData();
    }

    public function delete(User $user): bool
    {
        return $user->canManageData();
    }

    public function export(User $user): bool
    {
        return $user->canManageData();
    }
}
