<?php

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('the header shows the breadcrumbs of the current page and links to parent pages', function (): void {
    visit(route('mappoint-categories.index'))
        ->assertNoJavaScriptErrors()
        ->assertSeeIn('[data-slot=breadcrumb-page]', 'Kategorien')
        ->click('[data-slot=breadcrumb-link]')
        ->assertPathIs('/mappoints')
        ->assertSeeIn('[data-slot=breadcrumb-page]', 'Tabelle')
        ->assertDontSeeIn('[data-slot=breadcrumb]', 'Kategorien');
});
