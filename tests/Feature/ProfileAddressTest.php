<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // refresh(), because the factory does not set every column and
    // Model::shouldBeStrict throws when UserData reads one that was never
    // loaded. The session guard hands out a complete row in production.
    $this->user = User::factory()->create()->refresh();
});

it('saves the address together with the position the client resolved', function (): void {
    $response = $this->actingAs($this->user)->postJson('/api/profile/address', [
        'street' => 'Luisenplatz',
        'street_number' => '1',
        'zip' => '64283',
        'city' => 'Darmstadt',
        'advice_radius' => 5000,
        'lat' => 49.8728475,
        'lng' => 8.6510204,
    ]);

    $response->assertOk();

    $this->user->refresh();

    expect($this->user->zip)->toBe('64283')
        ->and($this->user->coordinate?->lat)->toBe(49.8728475)
        ->and($this->user->coordinate?->lng)->toBe(8.6510204);
});

it('returns the profile even when the client sends the postal code as a number', function (): void {
    // The zip column is a varchar and UserData types it as ?string, so an
    // unquoted 64283 straight from a numeric input used to hand a plain int to
    // UserData and blow up with a TypeError.
    $response = $this->actingAs($this->user)->postJson('/api/profile/address', [
        'street' => 'Luisenplatz',
        'street_number' => '1',
        'zip' => 64283,
        'city' => 'Darmstadt',
    ]);

    $response->assertOk();
    $response->assertJsonPath('zip', '64283');
});

it('keeps the leading zero of a postal code', function (): void {
    $this->actingAs($this->user)->postJson('/api/profile/address', [
        'street' => 'Schlossstraße',
        'street_number' => '1',
        'zip' => '01067',
        'city' => 'Dresden',
    ])->assertOk();

    expect($this->user->refresh()->zip)->toBe('01067');
});

it('rejects a postal code that is not five digits', function (): void {
    $this->actingAs($this->user)
        ->postJson('/api/profile/address', ['zip' => '123'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('zip');
});

it('does not geocode while saving, so a broken geocoder cannot block the profile', function (): void {
    // The client resolves the position beforehand or the user places the pin,
    // so this endpoint never talks to OpenStreetMap.
    $this->actingAs($this->user)->postJson('/api/profile/address', [
        'street' => 'Luisenplatz',
        'street_number' => '1',
        'zip' => '64283',
        'city' => 'Darmstadt',
    ])->assertOk();

    expect($this->user->refresh()->coordinate)->toBeNull();
});
