<?php

namespace App\Support;

class Role
{
    public const SUPERADMIN = 'superadmin';
    public const ADMIN = 'admin';
    public const USER = 'kepegawaian';

    public const SEMUA = [
        self::SUPERADMIN => 'Super Admin (APTIKA)',
        self::ADMIN      => 'Admin',
        self::USER       => 'User (Bagian Kepegawaian)',
    ];

    public static function label(string $role): string
    {
        $role = strtolower(trim($role));

        if (str_contains($role, 'super') || str_contains($role, 'aptika')) {
            return self::SEMUA[self::SUPERADMIN];
        }

        if (in_array($role, ['admin', 'administrator'], true)) {
            return self::SEMUA[self::ADMIN];
        }

        if (in_array($role, ['kepegawaian', 'bagian kepegawaian', 'user'], true)) {
            return self::SEMUA[self::USER];
        }

        return ucfirst($role);
    }
}
