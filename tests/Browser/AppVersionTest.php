<?php

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->admin()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('hovering the version explains the version scheme', function (): void {
    $version = app(VersionService::class)->version();

    visit(route('dashboard'))
        ->assertNoJavaScriptErrors()
        ->hover('text=Version '.$version)
        ->assertSee('Jahr-Monat.Ausgabe')
        ->assertNoJavaScriptErrors();
});

test('the sidebar shows the version and opens the changelog', function (): void {
    $version = app(VersionService::class)->version();

    visit(route('dashboard'))
        ->assertNoJavaScriptErrors()
        ->assertSee('Version '.$version)
        ->click('Version '.$version)
        ->assertSee('Was ist neu?')
        ->assertSee('Neue Funktionen')
        ->assertNoJavaScriptErrors();
});
