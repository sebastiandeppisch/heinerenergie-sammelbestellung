<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the first registered is logged in and can login again', function () {
    $this->post('/register', [
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max.mustermann@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticated();

    $this->post('/logout');

    $this->assertGuest();

    $response = $this->post('/api/login', [
        'email' => 'max.mustermann@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
})->skip();

test('a user can login', function () {
    User::factory()->create([
        'email' => 'john.doe@example.com',
        'password' => 'password',
    ]);

    $this->post('/api/login', [
        'email' => 'john.doe@example.com',
        'password' => 'password',
    ])->assertStatus(200);
})->skip();
