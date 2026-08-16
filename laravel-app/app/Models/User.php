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
        $role = strtolower(trim((string) $this->role));
        return in_array($role, ['super admin', 'superadmin', 'aptika', 'admin'], true)
            || str_contains($role, 'super')
            || str_contains($role, 'aptika');
    }

    public function canManageData(): bool
    {
        return $this->isSuperAdmin() || in_array(strtolower(trim((string) $this->role)), ['kepegawaian', 'bagian kepegawaian', 'user'], true);
    }
}
