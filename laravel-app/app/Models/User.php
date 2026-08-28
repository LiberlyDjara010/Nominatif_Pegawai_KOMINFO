<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'user';

    public $timestamps = false;

    protected $fillable = ['username', 'nama', 'role', 'password'];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->levelRole() >= 3;
    }

    public function isAdmin(): bool
    {
        return $this->levelRole() >= 2;
    }

    public function isUser(): bool
    {
        return $this->levelRole() >= 1;
    }

    public function canManageData(): bool
    {
        return $this->levelRole() >= 1;
    }

    public function canManageUsers(): bool
    {
        return $this->levelRole() >= 2;
    }

    public function canManageAdmins(): bool
    {
        return $this->levelRole() >= 3;
    }

    protected function levelRole(): int
    {
        $role = $this->normalizedRole();

        $super = in_array($role, ['super admin', 'superadmin', 'aptika'], true)
            || str_contains($role, 'super')
            || str_contains($role, 'aptika');

        if ($super) {
            return 3;
        }

        if (in_array($role, ['admin', 'administrator'], true)) {
            return 2;
        }

        if (in_array($role, ['kepegawaian', 'bagian kepegawaian', 'user'], true)) {
            return 1;
        }

        return 0;
    }

    protected function normalizedRole(): string
    {
        return strtolower(trim((string) $this->role));
    }
}
