<?php
use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('order can be created', function () {
    Setting::set('orderFormPassword', 'password');

    $adivsor = User::factory()->create();

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
        'advisorEmail' => $adivsor->email,
        'commentary' => fake()->text,
        'password' => 'password'
    ]);
    $response->assertStatus(200);
});

test('order can not be stored with incorrect password', function () {
    $adivsor = User::factory()->create();

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
        'advisorEmail' => $adivsor->email,
        'commentary' => fake()->text,
    ]);
    $response->assertStatus(433);
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