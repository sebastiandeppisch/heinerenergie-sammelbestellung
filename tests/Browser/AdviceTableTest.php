<?php

use App\Enums\AdviceStatusResult;
use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->admin()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('advices table renders without errors', function (): void {
    visit(route('advices'))
        ->assertNoSmoke()
        ->assertNoJavaScriptErrors();
});

test('advices table shows create button', function (): void {
    visit(route('advices'))
        ->assertNoSmoke()
        ->assertVisible('@new-advice-button')
        ->assertNoJavaScriptErrors();
});

test('advices table search filters rows', function (): void {
    Advice::factory()->create([
        'group_id' => $this->group->id,
        'advisor_id' => $this->user->id,
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
    ]);
    Advice::factory()->create([
        'group_id' => $this->group->id,
        'advisor_id' => $this->user->id,
        'first_name' => 'Erika',
        'last_name' => 'Musterfrau',
    ]);

    visit(route('advices'))
        ->assertNoSmoke()
        ->assertSee('Max')
        ->assertSee('Erika')
        ->fill('input[placeholder="Suchen..."]', 'Max')
        ->assertSee('Max')
        ->assertDontSee('Erika')
        ->assertNoJavaScriptErrors();
});

test('advices table inline edit buttons are visible', function (): void {
    $status = AdviceStatus::create([
        'name' => 'Test Status',
        'result' => AdviceStatusResult::New,
        'group_id' => $this->group->id,
    ]);

    Advice::factory()->create([
        'group_id' => $this->group->id,
        'advisor_id' => $this->user->id,
        'first_name' => 'Test',
        'last_name' => 'User',
        'advice_status_id' => $status->id,
    ]);

    visit(route('advices'))
        ->assertNoSmoke()
        ->assertSee('Test')
        ->assertVisible('[data-test="edit-status"]')
        ->assertVisible('[data-test="edit-advisor"]')
        ->assertNoJavaScriptErrors();
});

/**
 * The page must fill the viewport without overflowing it: a stale, JS-computed pixel
 * height used to freeze after the window was made smaller and never grew back.
 */
function assertFillsViewport(object $page, string $selector): void
{
    $measured = $page->script(
        '(() => {
            const rect = document.querySelector('.$selector.').getBoundingClientRect();
            return {
                scrollHeight: document.documentElement.scrollHeight,
                innerHeight: window.innerHeight,
                bottom: Math.round(rect.bottom),
            };
        })()'
    );

    expect($measured['scrollHeight'])->toBeLessThanOrEqual($measured['innerHeight']);
    expect($measured['bottom'])->toBeGreaterThan($measured['innerHeight'] - 24);
}

test('advices page fills the viewport at every window size', function (): void {
    Advice::factory()->create([
        'group_id' => $this->group->id,
        'advisor_id' => $this->user->id,
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
    ]);

    $page = visit(route('advices'))
        ->assertSee('Mustermann')
        ->assertNoJavaScriptErrors();

    assertFillsViewport($page, '"[data-test=advices-root]"');

    $page->resize(1280, 600)->assertSee('Mustermann');
    assertFillsViewport($page, '"[data-test=advices-root]"');

    // Growing again is the case that used to stay stuck at the smaller height
    $page->resize(1280, 1000)->assertSee('Mustermann');
    assertFillsViewport($page, '"[data-test=advices-root]"');

    $page->assertNoJavaScriptErrors();
});

test('advices map fills the viewport at every window size', function (): void {
    Advice::factory()->create([
        'group_id' => $this->group->id,
        'advisor_id' => $this->user->id,
    ]);

    $page = visit(route('advices.map'))
        ->assertVisible('.leaflet-container')
        ->assertNoJavaScriptErrors();

    assertFillsViewport($page, '"[data-test=advices-map-root]"');

    $page->resize(1280, 600)->assertVisible('.leaflet-container');
    assertFillsViewport($page, '"[data-test=advices-map-root]"');

    $page->resize(1280, 1000)->assertVisible('.leaflet-container');
    assertFillsViewport($page, '"[data-test=advices-map-root]"');

    $page->assertNoJavaScriptErrors();
});
