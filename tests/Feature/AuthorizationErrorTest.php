<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Headers that make a request look like it came from the running SPA.
 *
 * @return array<string, string>
 */
function inertiaHeaders(string $referer): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'Referer' => $referer,
    ];
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->group->users()->attach($this->user);
});

test('direct visit to a forbidden page renders the error page', function (): void {
    actingAs($this->user)
        ->get('/system-admin')
        ->assertStatus(403)
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 403)
            ->where('message', 'Du bist kein Systemadministrator.')
            ->where('intendedUrl', config('app.url').'/system-admin')
        );
});

test('inertia visit to a forbidden page redirects back and flashes the error', function (): void {
    actingAs($this->user)
        ->withHeaders(inertiaHeaders(config('app.url').'/dashboard'))
        ->get('/system-admin')
        ->assertRedirect(config('app.url').'/dashboard');

    expect(session('authorizationError'))->toBe([
        'message' => 'Du bist kein Systemadministrator.',
        'intendedUrl' => config('app.url').'/system-admin',
    ]);
});

test('forbidden inertia post redirects back with a 303 and no intended url', function (): void {
    actingAs($this->user)
        ->withHeaders(inertiaHeaders(config('app.url').'/dashboard'))
        ->post('/system-admin/migrate')
        ->assertStatus(303)
        ->assertRedirect(config('app.url').'/dashboard');

    expect(session('authorizationError'))->toBe([
        'message' => 'Du bist kein Systemadministrator.',
        'intendedUrl' => null,
    ]);
});

test('the forbidden url itself is never used as the back target', function (): void {
    actingAs($this->user)
        ->withHeaders(inertiaHeaders(config('app.url').'/system-admin'))
        ->get('/system-admin')
        ->assertRedirect(route('dashboard'));
});

test('the flashed error is shared with the next inertia page', function (): void {
    actingAs($this->user)
        ->withHeaders(inertiaHeaders(config('app.url').'/dashboard'))
        ->get('/system-admin');

    $this->flushHeaders();

    actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('authorizationError.message', 'Du bist kein Systemadministrator.')
        );
});

test('api requests keep the default json error response', function (): void {
    actingAs($this->user)
        ->getJson('/api/users/'.User::factory()->create()->uuid)
        ->assertStatus(403)
        ->assertJsonMissingPath('component');
});

test('a system admin is not affected', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    session(['actAsSystemAdmin' => true]);

    actingAs($admin)
        ->get('/system-admin')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('SystemAdmin'));
});
