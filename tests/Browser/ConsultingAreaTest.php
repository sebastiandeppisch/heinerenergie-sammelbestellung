<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

/**
 * @param  array<int, string>  $postalCodes
 */
function withConsultingArea(Group $group, array $postalCodes = []): Group
{
    $group->consulting_area = Polygon::createSquare(new Coordinate(49.8728, 8.6512), 0.01);
    $group->consulting_area_postal_codes = $postalCodes === [] ? null : $postalCodes;
    $group->save();

    return $group;
}

test('the consulting area map renders without javascript errors', function (): void {
    visit(route('groups.show', $this->group))
        ->click('Beratungsgebiet')
        ->assertPresent('.leaflet-container')
        ->assertNoJavaScriptErrors();
});

test('both ways to create an area are offered as long as there is none', function (): void {
    visit(route('groups.show', $this->group))
        ->click('Beratungsgebiet')
        ->assertSee('Aus Postleitzahlen erstellen')
        ->assertSee('Selbst zeichnen')
        ->assertNoJavaScriptErrors();
});

test('the postal code input is opened from the choice', function (): void {
    visit(route('groups.show', $this->group))
        ->click('Beratungsgebiet')
        ->click('Aus Postleitzahlen erstellen')
        ->assertSee('Mehrere Postleitzahlen müssen aneinandergrenzen')
        ->assertPresent('input[placeholder*="PLZ eingeben"]')
        ->assertDontSee('Selbst zeichnen')
        ->click('Zurück zur Auswahl')
        ->assertSee('Selbst zeichnen')
        ->assertNoJavaScriptErrors();
});

test('choosing to draw starts the drawing tool right away', function (): void {
    visit(route('groups.show', $this->group))
        ->click('Beratungsgebiet')
        ->click('Selbst zeichnen')
        ->assertDontSee('Aus Postleitzahlen erstellen')
        // The drawing tooltip only shows up while the draw handler is enabled.
        ->assertSee('Klicke, um das Gebiet zu zeichnen.')
        ->assertNoJavaScriptErrors();
});

test('an existing area shows the postal code tool above the map', function (): void {
    withConsultingArea($this->group);

    visit(route('groups.show', $this->group))
        ->click('Beratungsgebiet')
        ->assertDontSee('Aus Postleitzahlen erstellen')
        ->click('Gebiet aus Postleitzahlen laden')
        ->assertPresent('input[placeholder*="PLZ eingeben"]')
        ->assertNoJavaScriptErrors();
});

test('an existing area can be edited on the map', function (): void {
    withConsultingArea($this->group);

    $page = visit(route('groups.show', $this->group));

    $page->click('Beratungsgebiet')
        // leaflet-draw only enables the edit button when the feature group holds
        // a layer, so this proves the area is editable.
        ->assertPresent('.leaflet-draw-edit-edit:not(.leaflet-disabled)')
        ->click('.leaflet-draw-edit-edit')
        ->assertSee('Ziehe die Punkte, um das Gebiet anzupassen.');

    // A square gets a handle on each of its four corners plus one between them.
    expect($page->script('document.querySelectorAll(".leaflet-editing-icon").length'))->toBe(8);

    $page->click('.leaflet-draw-actions a[title="Änderungen übernehmen"]')
        ->assertNoJavaScriptErrors();
});

test('the postal codes an area was built from are shown again', function (): void {
    withConsultingArea($this->group, ['64283', '64285']);

    visit(route('groups.show', $this->group))
        ->click('Beratungsgebiet')
        ->click('Gebiet aus Postleitzahlen laden')
        ->assertSee('Dieses Beratungsgebiet beruht auf diesen Postleitzahlen')
        ->assertSee('64283')
        ->assertSee('64285')
        ->assertNoJavaScriptErrors();
});

test('the postal codes survive reshaping the area by hand', function (): void {
    withConsultingArea($this->group, ['64283']);

    $before = $this->group->consulting_area;

    $page = visit(route('groups.show', $this->group));

    $page->click('Beratungsgebiet')
        ->click('.leaflet-draw-edit-edit')
        // Moving a corner reshapes the area without replacing it.
        ->drag('.leaflet-editing-icon >> nth=0', '.leaflet-control-zoom-in')
        ->click('.leaflet-draw-actions a[title="Änderungen übernehmen"]')
        ->click('Beratungsgebiet speichern')
        ->assertNoJavaScriptErrors();

    $group = $this->group->fresh();

    // The area really was reshaped, but its basis is still known.
    expect($group->consulting_area?->getCoordinates())->not->toEqual($before?->getCoordinates())
        ->and($group->consulting_area_postal_codes)->toBe(['64283']);
});

test('drawing a new area drops the postal codes it no longer matches', function (): void {
    withConsultingArea($this->group, ['64283']);

    $page = visit(route('groups.show', $this->group));

    $page->click('Beratungsgebiet')
        ->click('.leaflet-draw-draw-polygon');

    // leaflet-draw picks up the corners through an invisible marker that follows
    // the mouse, so every corner needs a move before its press and release.
    $page->script(<<<'JS'
        (async () => {
            const container = document.querySelector('.leaflet-container');
            const bounds = container.getBoundingClientRect();

            const dispatch = (target, type, x, y) => target.dispatchEvent(new MouseEvent(type, {
                bubbles: true,
                clientX: bounds.left + x,
                clientY: bounds.top + y,
            }));

            for (const [x, y] of [[100, 100], [300, 100], [300, 300]]) {
                dispatch(container, 'mousemove', x, y);
                const mouseMarker = document.querySelector('.leaflet-mouse-marker');
                dispatch(mouseMarker, 'mousedown', x, y);
                dispatch(mouseMarker, 'mouseup', x, y);
                // The handler ignores further corners for a moment after each one.
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
        })()
    JS);

    $page->click('.leaflet-draw-actions a[title="Zeichnen beenden"]')
        ->click('Beratungsgebiet speichern')
        ->assertNoJavaScriptErrors();

    $group = $this->group->fresh();

    expect($group->consulting_area?->getCoordinates())->toHaveCount(3)
        ->and($group->consulting_area_postal_codes)->toBeNull();
});

test('the edit button is disabled without an area', function (): void {
    visit(route('groups.show', $this->group))
        ->click('Beratungsgebiet')
        ->assertPresent('.leaflet-draw-edit-edit.leaflet-disabled')
        ->assertNoJavaScriptErrors();
});
