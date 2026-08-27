<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('halaman login panel admin merespons 200', function () {
    $this->get('/admin/login')->assertStatus(200);
});

test('halaman root panel admin mengalihkan ke login', function () {
    $this->get('/admin')->assertRedirect();
});
