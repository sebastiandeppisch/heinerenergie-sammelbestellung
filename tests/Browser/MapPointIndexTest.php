<?php

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
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

test('the detail row shows internal and former field values', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();
    $formDefinition = $category->findOrCreateFormDefinition();
    $phone = $formDefinition->fields()->create(['type' => FieldType::PHONE, 'label' => 'Telefon', 'sort_order' => 0]);
    $removed = $formDefinition->fields()->create(['type' => FieldType::TEXT, 'label' => 'Altes Feld', 'sort_order' => 1]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $category->id]);
    $phone->createMapPointField($mapPoint, '06151 123456');
    $removed->createMapPointField($mapPoint, 'alter Wert');
    $removed->delete();

    visit(route('mappoints.index'))
        ->assertDontSee('06151 123456')
        ->click('[data-test=toggle-point-details]')
        ->assertSee('06151 123456')
        ->assertSee('Frühere Angaben')
        ->assertSee('alter Wert')
        ->assertNoJavaScriptErrors();
});

test('the popup of the backend map shows public and internal field values', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create();
    $formDefinition = $category->findOrCreateFormDefinition();
    $power = $formDefinition->fields()->create(['type' => FieldType::NUMBER, 'label' => 'PV-Leistung (kWp)', 'sort_order' => 0]);
    $phone = $formDefinition->fields()->create(['type' => FieldType::PHONE, 'label' => 'Telefon', 'sort_order' => 1]);
    $category->syncPublicFields([$power->id]);
    // The backend map opens at this position, so the marker can be clicked.
    $mapPoint = MapPoint::factory()->for($this->group)->create(['lat' => 49.8728, 'lng' => 8.6512, 'category_id' => $category->id]);
    $power->createMapPointField($mapPoint, 9.9);
    $phone->createMapPointField($mapPoint, '06151 123456');

    visit(route('map-points-map'))
        ->click('.leaflet-marker-icon')
        ->assertSeeIn('.leaflet-popup-content', '9,9')
        ->assertSeeIn('.leaflet-popup-content', '06151 123456')
        ->assertPresent('.leaflet-popup-content [aria-label="intern"]')
        ->assertNoJavaScriptErrors();
});

test('all points of a category and its sub categories are deleted with a few clicks', function (): void {
    $solar = MapPointCategory::factory()->for($this->group)->create(['name' => 'Photovoltaik']);
    $balcony = MapPointCategory::factory()->childOf($solar)->create(['name' => 'Balkonkraftwerke']);
    $heatPumps = MapPointCategory::factory()->for($this->group)->create(['name' => 'Wärmepumpen']);
    MapPoint::factory()->for($this->group)->create(['category_id' => $solar->id]);
    MapPoint::factory()->for($this->group)->create(['category_id' => $balcony->id]);
    $kept = MapPoint::factory()->for($this->group)->create(['category_id' => $heatPumps->id]);

    visit(route('mappoints.index'))
        ->click('[data-test="category-filter"]')
        ->click("#index-category-{$solar->uuid}")
        ->click('[data-test="category-filter"]')
        ->assertSee('2 Punkte')
        ->click('[data-test="select-all-points"]')
        ->click('[data-test="delete-selected"]')
        ->click('[data-test="confirm-delete-selected"]')
        ->assertSee('2 Kartenpunkte wurden gelöscht')
        ->assertNoJavaScriptErrors();

    expect(MapPoint::pluck('id')->all())->toBe([$kept->id]);
});

test('the form wizard opens from the map point table with the filtered category', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create(['name' => 'Photovoltaik']);
    $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::NUMBER, 'label' => 'PV-Leistung (kWp)', 'sort_order' => 0]);

    visit(route('mappoints.index', ['category' => $category->uuid]))
        ->click('Formular erstellen')
        ->assertSee('Kartenpunkt-Formular erstellen')
        ->assertSeeIn('[data-test="template-category"]', 'Photovoltaik')
        ->click('Erstellen')
        ->assertSee('Formular bearbeiten')
        ->assertNoJavaScriptErrors();

    $form = FormDefinition::where('name', 'Formular für Photovoltaik')->sole();
    expect($form->mapPointCreator->map_point_category_id)->toBe($category->id);
});

test('selected points are moved into another category', function (): void {
    $heatPumps = MapPointCategory::factory()->for($this->group)->create(['name' => 'Wärmepumpen']);
    [$first, $second] = MapPoint::factory()->count(2)->for($this->group)->create(['category_id' => null]);

    visit(route('mappoints.index'))
        ->click('[data-test="select-all-points"]')
        ->click('[data-test="change-category"]')
        ->click('[data-test="target-category"]')
        ->click('Wärmepumpen')
        ->click('[data-test="confirm-change-category"]')
        ->assertSee('2 Kartenpunkte in „Wärmepumpen“ verschoben')
        ->assertNoJavaScriptErrors();

    expect([$first->refresh()->category_id, $second->refresh()->category_id])->toBe([$heatPumps->id, $heatPumps->id]);
});

test('selected points are published at once', function (): void {
    [$first, $second] = MapPoint::factory()->count(2)->for($this->group)->create(['published' => false]);

    visit(route('mappoints.index'))
        ->click('[data-test="select-all-points"]')
        ->click('[data-test="publish-selected"]')
        ->assertSee('2 Kartenpunkte sind jetzt veröffentlicht')
        ->assertNoJavaScriptErrors();

    expect([$first->refresh()->published, $second->refresh()->published])->toBe([true, true]);
});
