<?php

use App\Filament\Resources\LoginLogs\LoginLogResource;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buatAkun(string $username, string $role): User
{
    return User::create([
        'username' => $username,
        'nama' => ucfirst($username),
        'role' => $role,
        'password' => bcrypt('rahasia123'),
    ]);
}

it('superadmin dapat melihat semua riwayat login', function () {
    $super = buatAkun('aptika_test', 'superadmin');
    $userKepeg = buatAkun('kepeg_test', 'kepegawaian');

    LoginLog::create([
        'username' => 'aptika_test', 'role' => 'superadmin', 'status' => 'sukses',
        'lokasi_text' => 'Jakarta, Indonesia', 'waktu' => now(),
    ]);
    LoginLog::create([
        'username' => 'kepeg_test', 'role' => 'kepegawaian', 'status' => 'gagal',
        'lokasi_text' => 'Bandung, Indonesia', 'waktu' => now(),
    ]);

    $this->actingAs($super)
        ->get('/admin/riwayat-login')
        ->assertOk()
        ->assertSee('Jakarta, Indonesia')
        ->assertSee('Bandung, Indonesia');
});

it('admin hanya dapat melihat riwayat login dari user di bawahnya', function () {
    $admin = buatAkun('admin_test', 'admin');
    $userKepeg = buatAkun('kepeg_test', 'kepegawaian');

    LoginLog::create([
        'username' => 'kepeg_test', 'role' => 'kepegawaian', 'status' => 'sukses',
        'lokasi_text' => 'Bandung, Indonesia', 'waktu' => now(),
    ]);
    LoginLog::create([
        'username' => 'admin_test', 'role' => 'admin', 'status' => 'sukses',
        'lokasi_text' => 'Cimahi, Indonesia', 'waktu' => now(),
    ]);

    $log = LoginLog::untukPemirsa($admin)->get();

    // admin hanya melihat role kepegawaian (user), bukan milik admin lain
    expect($log->pluck('role')->all())->toBe(['kepegawaian']);
    expect($log->pluck('lokasi_text')->all())->toBe(['Bandung, Indonesia']);
});

it('user kepegawaian tidak dapat mengakses halaman riwayat login', function () {
    $userKepeg = buatAkun('kepeg_test', 'kepegawaian');

    $this->actingAs($userKepeg)
        ->get('/admin/riwayat-login')
        ->assertForbidden();
});

it('badge navigasi menampilkan jumlah belum dibaca sesuai hierarki', function () {
    $super = buatAkun('aptika_test', 'superadmin');
    $admin = buatAkun('admin_test', 'admin');
    $userKepeg = buatAkun('kepeg_test', 'kepegawaian');

    LoginLog::create(['username' => 'kepeg_test', 'role' => 'kepegawaian', 'status' => 'sukses', 'dibaca' => false, 'waktu' => now()]);
    LoginLog::create(['username' => 'kepeg_test', 'role' => 'kepegawaian', 'status' => 'sukses', 'dibaca' => false, 'waktu' => now()]);
    LoginLog::create(['username' => 'admin_test', 'role' => 'admin', 'status' => 'sukses', 'dibaca' => false, 'waktu' => now()]);

    $this->actingAs($super);

    // superadmin melihat semua (3 belum dibaca)
    $this->assertEquals('3', LoginLogResource::getNavigationBadge());

    // admin hanya melihat dari user kepegawaian (2), bukan milik admin lain
    $this->actingAs($admin);
    $this->assertEquals('2', LoginLogResource::getNavigationBadge());
});

it('aksi tandai dibaca memperbarui kolom dibaca', function () {
    $super = buatAkun('aptika_test', 'superadmin');
    $log = LoginLog::create([
        'username' => 'aptika_test', 'role' => 'superadmin', 'status' => 'sukses',
        'dibaca' => false, 'waktu' => now(),
    ]);

    expect($log->dibaca)->toBeFalse();

    $log->update(['dibaca' => true]);

    expect($log->fresh()->dibaca)->toBeTrue();
});
