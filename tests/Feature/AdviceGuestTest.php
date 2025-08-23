<?php

use App\Enums\AdviceType;
use App\Enums\HouseType;
use App\Mail\AdviceCreated;
use App\Models\Advice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('advice can be saved', function () {
    Mail::fake();
    $data = [
        'help_type_place' => fake()->boolean(),
        'help_type_technical' => fake()->boolean(),
        'help_type_bureaucracy' => fake()->boolean(),
        'helpType_other' => fake()->boolean(),
        'house_type' => fake()->numberBetween(0, 2),
        'first_name' => fake()->first_name(),
        'last_name' => fake()->last_name(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
        'zip' => (int) fake()->postcode(),
        'city' => fake()->city(),
        'street' => fake()->streetName(),
        'street_number' => fake()->buildingNumber(),
        'place_notes' => fake()->text(),
        'landlord_exists' => fake()->boolean(),
    ];
    $response = $this->post('api/newadvice', $data);
    $response->assertStatus(200);

    $advice = Advice::firstOrFail();

    foreach ($data as $key => $value) {
        if ($key === 'house_type') {
            expect($advice->$key)->toBe(HouseType::cases()[$value]);

            continue;
        }
        expect($advice->$key)->toBe($value);
    }

    Mail::assertQueued(fn (AdviceCreated $mail) => $mail->hasTo($advice->email));
})->skip('TODO determine the advices group from the form');

test('direct order advice can be saved', function () {
    Mail::fake();
    $data = [
        'first_name' => fake()->first_name(),
        'last_name' => fake()->last_name(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
        'zip' => (int) fake()->postcode(),
        'city' => fake()->city(),
        'street' => fake()->streetName(),
        'street_number' => fake()->buildingNumber(),
        'type' => AdviceType::DirectOrder->value,
    ];
    $response = $this->post('api/newadvice', $data);
    $response->assertStatus(200);

    $advice = Advice::firstOrFail();

    foreach ($data as $key => $value) {
        if ($key === 'type') {
            expect($advice->$key)->toBe(AdviceType::cases()[$value]);

            continue;
        }
        expect($advice->$key)->toBe($value);
    }

    Mail::assertQueued(fn (AdviceCreated $mail) => $mail->hasTo($advice->email));
})->skip('TODO determine the advices group from the form');
