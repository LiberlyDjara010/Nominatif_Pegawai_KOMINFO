<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adaSuperAdmin = DB::table('user')
            ->whereRaw("LOWER(role) IN ('super admin','superadmin','aptika','admin') OR LOWER(role) LIKE '%super%' OR LOWER(role) LIKE '%aptika%'")
            ->exists();

        if ($adaSuperAdmin) {
            $this->command?->info('Super admin sudah ada — seeder dilewati.');
            return;
        }

        $username = env('SEED_SUPERADMIN_USERNAME', 'aptika');
        $password = env('SEED_SUPERADMIN_PASSWORD');

        if (empty($password) || strlen($password) < 8) {
            throw new \RuntimeException('SEED_SUPERADMIN_PASSWORD belum diatur/minimal 8 karakter di .env');
        }

        DB::table('user')->insertOrIgnore([
            'username' => $username,
            'nama'     => 'Super Admin APTIKA',
            'role'     => 'super admin',
            'password' => Hash::make($password),
        ]);
    }
}
