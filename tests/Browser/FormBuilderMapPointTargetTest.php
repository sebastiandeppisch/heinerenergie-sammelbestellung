<?php

use App\Enums\FieldType;
use App\Models\FormDefinitionToMapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the form builder maps a form field to a field of the map point category', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $admin = User::factory()->create();
    $form->group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($form->group, true);
    $this->actingAs($admin);

    $category = MapPointCategory::factory()->for($form->group)->create(['name' => 'Solaranlagen']);
    $power = $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::NUMBER, 'label' => 'PV-Leistung (kWp)', 'sort_order' => 0]);
    $formPower = $form->fields()->create(['type' => FieldType::NUMBER, 'label' => 'Leistung der Anlage', 'sort_order' => 10]);

    visit(route('form-definitions.edit', $form))
        ->click('Ziele')
        ->click('[data-test=map-point-category]')
        ->click('Solaranlagen')
        ->click("[data-test=map-point-field-source-{$power->uuid}]")
        ->click('Leistung der Anlage')
        ->click('Speichern')
        ->assertSee('Formular wurde erfolgreich aktualisiert')
        ->assertNoJavaScriptErrors();

    $config->refresh();
    expect($config->map_point_category_id)->toBe($category->id)
        ->and($config->fieldMappings()->sole()->only(['target_field_id', 'source_field_id']))
        ->toBe(['target_field_id' => $power->id, 'source_field_id' => $formPower->id]);
});
