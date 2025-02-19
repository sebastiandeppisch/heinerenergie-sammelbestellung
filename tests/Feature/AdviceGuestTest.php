<?php

use App\Models\Advice;
use App\Enums\HouseType;
use App\Enums\AdviceType;
use App\Mail\AdviceCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('advice can be saved', function () {
    Mail::fake();
    $data = [
        'helpType_place' => fake()->boolean(),
        'helpType_technical' => fake()->boolean(),
        'helpType_bureaucracy' => fake()->boolean(),
        'helpType_other' => fake()->boolean(),
        'houseType' => fake()->numberBetween(0, 2),
        'firstName' => fake()->firstName(),
        'lastName' => fake()->lastName(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
        'zip' => (int) fake()->postcode(),
        'city' => fake()->city(),
        'street' => fake()->streetName(),
        'streetNumber' => fake()->buildingNumber(),
        'placeNotes' => fake()->text(),
        'landlordExists' => fake()->boolean(),
    ];
    $response = $this->post('api/newadvice', $data);
    $response->assertStatus(200);

    $advice = Advice::firstOrFail();

    foreach ($data as $key => $value) {
        if ($key === 'houseType') {
            expect($advice->$key)->toBe(HouseType::cases()[$value]);

            continue;
        }
        expect($advice->$key)->toBe($value);
    }

    Mail::assertQueued(fn(AdviceCreated $mail) => $mail->hasTo($advice->email));
})->skip("TODO determine the advices group from the form");

test('direct order advice can be saved', function () {
    Mail::fake();
    $data = [
        'firstName' => fake()->firstName(),
        'lastName' => fake()->lastName(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
        'zip' => (int) fake()->postcode(),
        'city' => fake()->city(),
        'street' => fake()->streetName(),
        'streetNumber' => fake()->buildingNumber(),
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

    Mail::assertQueued(fn(AdviceCreated $mail) => $mail->hasTo($advice->email));
})->skip("TODO determine the advices group from the form");
