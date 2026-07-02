<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    Route::middleware(['auth', 'enc_key'])->get('/test-enc-key', fn () => response()->json(['key' => base64_encode((string) app('user.enc_key'))]));
});

test('request without enc_key cookie returns 403', function () {
    $this->actingAs($this->user)
        ->withCredentials()
        ->getJson('/test-enc-key')
        ->assertStatus(403);
});

test('request with enc_key cookie binds key to container', function () {
    $key = random_bytes(32);
    $cookieValue = base64_encode($key);

    $this->actingAs($this->user)
        ->withCredentials()
        ->withUnencryptedCookie('enc_key', $cookieValue)
        ->getJson('/test-enc-key')
        ->assertOk()
        ->assertJson(['key' => $cookieValue]);
});

test('key in container is raw bytes decoded from base64 cookie', function () {
    $key = random_bytes(32);

    $this->actingAs($this->user)
        ->withCredentials()
        ->withUnencryptedCookie('enc_key', base64_encode($key))
        ->getJson('/test-enc-key')
        ->assertOk()
        ->assertJson(['key' => base64_encode($key)]);
});
