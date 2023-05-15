<?php

use App\HouseType;
use App\Models\User;
use App\Models\Advice;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('advice can be saved', function () {
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

    
    foreach($data as $key => $value) {
        if($key === 'houseType'){
            expect($advice->$key)->toBe(HouseType::cases()[$value]);
            continue;
        }
        expect($advice->$key)->toBe($value);
    }
});
