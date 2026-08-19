<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login sets enc_key cookie', function (): void {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $response = $this->post('/login-form', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect($response->headers->getCookies())->not->toBeEmpty();

    $cookieNames = array_map(fn ($c) => $c->getName(), $response->headers->getCookies());
    expect($cookieNames)->toContain('enc_key');
});

test('enc_key cookie is session-based (no expiry)', function (): void {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $response = $this->post('/login-form', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $encKeyCookie = collect($response->headers->getCookies())
        ->first(fn ($c): bool => $c->getName() === 'enc_key');

    expect($encKeyCookie)->not->toBeNull();
    // Session cookies have expires = 0 (no explicit expiry)
    expect($encKeyCookie->getExpiresTime())->toBe(0);
});

test('enc_key cookie is HttpOnly', function (): void {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $response = $this->post('/login-form', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $encKeyCookie = collect($response->headers->getCookies())
        ->first(fn ($c): bool => $c->getName() === 'enc_key');

    expect($encKeyCookie->isHttpOnly())->toBeTrue();
});

test('login stores enc_salt in session', function (): void {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $this->post('/login-form', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    expect(session()->has('enc_salt'))->toBeTrue();
});
