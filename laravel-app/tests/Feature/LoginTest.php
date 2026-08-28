<?php

use App\Filament\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('halaman login panel admin menampilkan field username, bukan email', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Username')
        ->assertDontSee('Email');
});

it('login dengan username dan password berhasil mengarahkan ke dashboard', function () {
    $user = User::create([
        'username' => 'APTIKA',
        'nama' => 'Administrator APTIKA',
        'role' => 'superadmin',
        'password' => bcrypt('rahasia123'),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'username' => 'APTIKA',
            'password' => 'rahasia123',
            'remember' => false,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticated();
    $this->assertSame('APTIKA', auth()->user()->username);
});

it('login gagal apabila username atau password salah', function () {
    User::create([
        'username' => 'APTIKA',
        'nama' => 'Administrator APTIKA',
        'role' => 'superadmin',
        'password' => bcrypt('rahasia123'),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'username' => 'APTIKA',
            'password' => 'salah',
            'remember' => false,
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username']);

    $this->assertGuest();
});
