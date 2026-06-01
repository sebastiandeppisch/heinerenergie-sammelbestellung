<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('remember me sets the remember-cookie', function () {
    $user = User::factory()->create();

    $response = $this->post('/login-form', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $response->assertCookie(Auth::guard()->getRecallerName());
});

test('without remember me the cookie is not set', function () {
    $user = User::factory()->create();

    $response = $this->post('/login-form', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertCookieMissing(Auth::guard()->getRecallerName());
});
