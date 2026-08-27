<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('koneksi DB dan migrasi berjalan', function () {
    $this->assertTrue(DB::connection()->getPdo() instanceof PDO);
    $this->assertTrue(Schema::hasTable('pegawai'));
    $this->assertTrue(Schema::hasTable('user'));
    $this->assertTrue(Schema::hasTable('checklist_pangkat'));
});
