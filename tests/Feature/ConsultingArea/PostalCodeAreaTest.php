<?php

declare(strict_types=1);

namespace Tests\Feature\ConsultingArea;

use App\Actions\FetchPolygonsByPostalCode;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use App\ValueObjects\Polygon;
use Closure;
use Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Squares of one degree, laid out side by side from west to east. Every postal
 * code that is not part of the map is unknown.
 *
 * @param  array<array-key, int>  $columns  postal code => column of the square
 */
function fakePostalCodeBoundaries(array $columns): void
{
    app()->bind(FetchPolygonsByPostalCode::class, fn (): Closure => function (string $postalCode) use ($columns): array {
        expect($columns)->toHaveKey($postalCode);

        $left = $columns[$postalCode];

        return [new Polygon([
            ['lat' => 0, 'lng' => $left],
            ['lat' => 0, 'lng' => $left + 1],
            ['lat' => 1, 'lng' => $left + 1],
            ['lat' => 1, 'lng' => $left],
        ])];
    });
}

beforeEach(function (): void {
    $this->group = Group::factory()->create();
    $this->admin = User::factory()->create();
    $this->group->users()->attach($this->admin, ['is_admin' => true]);

    app(SessionService::class)->actWithoutSelectingGroup();
    Config::set('app.group_context', 'global');
});

test('group admin can build an area out of adjacent postal codes', function (): void {
    fakePostalCodeBoundaries(['64283' => 0, '64285' => 1]);

    $response = actingAs($this->admin)
        ->postJson(route('api.groups.postal-code-area', $this->group), [
            'postal_codes' => ['64283', '64285'],
        ])
        ->assertOk();

    expect($response->json('polygon.coordinates'))->toHaveCount(6);
});

test('building an area does not save it', function (): void {
    fakePostalCodeBoundaries(['64283' => 0]);

    actingAs($this->admin)
        ->postJson(route('api.groups.postal-code-area', $this->group), [
            'postal_codes' => ['64283'],
        ])
        ->assertOk();

    expect($this->group->fresh()->consulting_area)->toBeNull();
});

test('postal codes that are not adjacent are rejected', function (): void {
    fakePostalCodeBoundaries(['64283' => 0, '10115' => 10]);

    actingAs($this->admin)
        ->postJson(route('api.groups.postal-code-area', $this->group), [
            'postal_codes' => ['64283', '10115'],
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'zusammenhängendes Gebiet'));
});

test('it validates the postal codes', function (): void {
    actingAs($this->admin)
        ->postJson(route('api.groups.postal-code-area', $this->group), ['postal_codes' => []])
        ->assertJsonValidationErrors('postal_codes');

    actingAs($this->admin)
        ->postJson(route('api.groups.postal-code-area', $this->group), ['postal_codes' => ['darmstadt']])
        ->assertJsonValidationErrors('postal_codes.0');
});

test('users without area permission cannot build an area', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('api.groups.postal-code-area', $this->group), [
            'postal_codes' => ['64283'],
        ])
        ->assertForbidden();
});
