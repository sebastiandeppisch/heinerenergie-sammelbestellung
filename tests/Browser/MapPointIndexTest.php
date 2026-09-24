<?php

use App\Models\Group;
use App\Models\MapPoint;
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

test('many map points scroll inside the table instead of the page', function (): void {
    MapPoint::factory()->count(40)->for($this->group)->create();

    $page = visit(route('mappoints.index'))
        ->assertNoJavaScriptErrors();

    assertFillsViewport($page, '"[data-test=mappoints-root]"');

    $table = $page->script(
        '(() => {
            const container = document.querySelector("[data-test=mappoints-root] [data-slot=table-container]");
            return { scrollHeight: container.scrollHeight, clientHeight: container.clientHeight };
        })()'
    );

    expect($table['scrollHeight'])->toBeGreaterThan($table['clientHeight']);
});
