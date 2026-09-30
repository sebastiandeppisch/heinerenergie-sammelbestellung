<?php

use App\Enums\FieldType;
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

test('the point form shows the fields of the chosen category and saves their values', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();
    $power = $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::NUMBER, 'label' => 'PV-Leistung (kWp)', 'sort_order' => 0]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $category->id]);

    visit(route('mappoints.edit', $mapPoint))
        ->assertSee('Zusatzfelder der Kategorie')
        ->fill("#field_{$power->uuid}", '12.5')
        ->click('Kartenpunkt aktualisieren')
        ->assertSee('Der Kartenpunkt wurde aktualisiert')
        ->assertNoJavaScriptErrors();

    expect($mapPoint->fields()->value('value'))->toBe(12.5);
});

test('the form builder for category fields hides the form settings', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create(['name' => 'Solar']);

    visit(route('form-definitions.edit', $category->findOrCreateFormDefinition()))
        ->assertSee('Felder der Kategorie Solar')
        ->assertDontSee('Erfolgsmeldung')
        ->assertDontSee('Formular öffnen')
        ->assertNoJavaScriptErrors();
});

test('a long category name is cut with an ellipsis in the category list', function (): void {
    MapPointCategory::factory()->for($this->group)->create(['name' => str_repeat('Sehr langer Kategoriename ', 20)]);

    $page = visit(route('mappoint-categories.index'))->assertNoJavaScriptErrors();

    $name = $page->script('(() => { const el = document.querySelector("[data-test=category-name]"); return { scrollWidth: el.scrollWidth, clientWidth: el.clientWidth, overflow: getComputedStyle(el).textOverflow }; })()');

    expect($name['overflow'])->toBe('ellipsis')
        ->and($name['scrollWidth'])->toBeGreaterThan($name['clientWidth']);
});
