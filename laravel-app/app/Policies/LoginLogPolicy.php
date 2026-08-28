<?php

namespace App\Policies;

use App\Models\LoginLog;
use App\Models\User;

class LoginLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function view(User $user, LoginLog $log): bool
    {
        if (!$user->canManageUsers()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $rolePemilik = strtolower(trim((string) $log->role));
        return in_array($rolePemilik, ['kepegawaian', 'bagian kepegawaian', 'user'], true);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LoginLog $log): bool
    {
        return false;
    }

    public function delete(User $user, LoginLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
