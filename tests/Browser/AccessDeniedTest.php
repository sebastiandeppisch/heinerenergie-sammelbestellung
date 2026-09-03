<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create(['is_admin' => true]);
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group);
    $this->actingAs($this->user);
});

test('opening a forbidden page directly shows the error page with a role switch', function (): void {
    $page = visit(route('system-admin'));

    $page->assertSee('Keine Berechtigung')
        ->assertSee('Du bist kein Systemadministrator.')
        ->assertSee('Vielleicht fehlt Dir nur die passende Rolle')
        ->assertSee('Systemadministrator')
        ->assertSee('Zurück')
        ->assertNoJavaScriptErrors();
});

test('switching the role from the error page opens the intended page', function (): void {
    $page = visit(route('system-admin'));

    $page->click('Systemadministrator')
        ->assertSee('System-Administration')
        ->assertDontSee('Du bist kein Systemadministrator.')
        ->assertNoJavaScriptErrors();
});

test('an authorization error from the spa is shown as a modal on top of the current page', function (): void {
    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'Referer' => route('dashboard'),
    ])->get(route('system-admin'))->assertRedirect(route('dashboard'));

    $this->flushHeaders();

    $page = visit(route('dashboard'));

    $page->assertSee('Keine Berechtigung')
        ->assertSee('Du bist kein Systemadministrator.')
        ->assertUrlIs(route('dashboard'))
        ->assertNoJavaScriptErrors();
});

test('switching the role from the modal closes it and opens the intended page', function (): void {
    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'Referer' => route('dashboard'),
    ])->get(route('system-admin'));

    $this->flushHeaders();

    $page = visit(route('dashboard'));

    $page->assertSee('Keine Berechtigung')
        ->click('Systemadministrator')
        ->assertDontSee('Keine Berechtigung')
        ->assertSee('System-Administration')
        ->assertNoJavaScriptErrors();
});

test('a user without any other role only sees the message and the back button', function (): void {
    $plainUser = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($plainUser, ['is_admin' => false]);
    $this->actingAs($plainUser);
    app(SessionService::class)->actAsGroup($this->group);

    $page = visit(route('system-admin'));

    $page->assertSee('Du bist kein Systemadministrator.')
        ->assertDontSee('Vielleicht fehlt Dir nur die passende Rolle')
        ->assertSee('Zurück')
        ->assertNoJavaScriptErrors();
});
