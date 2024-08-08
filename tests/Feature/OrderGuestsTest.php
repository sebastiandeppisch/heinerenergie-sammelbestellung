<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('order can be created', function () {
    Setting::set('orderFormPassword', 'password');

    $advisor = User::factory()->create();

    $mail = fake()->email;

    $response = $this->post('api/orders', [
        'firstName' => fake()->name,
        'lastName' => fake()->name,
        'email' => $mail,
        'email_confirmation' => $mail,
        'phone' => fake()->phoneNumber,
        'street' => fake()->streetName,
        'streetNumber' => fake()->buildingNumber,
        'zip' => fake()->postcode,
        'city' => fake()->city,
        'orderItems' => [],
        'advisorEmail' => $advisor->email,
        'commentary' => fake()->text,
        'password' => 'password',
    ]);
    $response->assertStatus(200);
});

test('order can not be stored with incorrect password', function () {
    Setting::set('orderFormPassword', 'some password');
    $advisor = User::factory()->create();

    $mail = fake()->email;

    $response = $this->postJson('api/orders', [
        'firstName' => fake()->name,
        'lastName' => fake()->name,
        'email' => $mail,
        'email_confirmation' => $mail,
        'phone' => fake()->phoneNumber,
        'street' => fake()->streetName,
        'streetNumber' => fake()->buildingNumber,
        'zip' => fake()->postcode,
        'city' => fake()->city,
        'orderItems' => [],
        'advisorEmail' => $advisor->email,
        'commentary' => fake()->text,
        'password' => 'password',
    ]);
    $response->assertStatus(422);
});

test('products can not be seen without password', function () {
    $response = $this->get('api/products');
    $response->assertStatus(302);
});

test('products can be seen with password', function () {
    Setting::set('orderFormPassword', 'password');

    $response = $this->get('api/products?password=password');
    $response->assertStatus(200);
});
