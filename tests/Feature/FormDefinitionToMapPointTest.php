<?php

use App\Data\FormDefinitionData;
use App\Data\FormToMapPointMappingData;
use App\Enums\FieldType;
use App\Events\MapPointCreatedByFormSubmission;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

test('can be created by factory', function (): void {
    FormDefinitionToMapPoint::factory()->create();
    $this->assertTrue(true);
});

test('submitting the form produces a map point', function (): void {

    $this->withoutExceptionHandling();

    $config = FormDefinitionToMapPoint::factory()->create();

    $form = $config->formDefinition;

    $response = $this->post(route('form.submit', $form), [
        $config->titleField->uuid => fake()->sentence(3),
        $config->descriptionField->uuid => fake()->sentence(10), // Shorter description
        $config->coordinateField->uuid => [
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
        ],
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertEquals(1, MapPoint::count());

    $mapPoint = MapPoint::first();
    $this->assertFalse($mapPoint->published); // should be unpublished initially
    expect($mapPoint->group_id)->toBe($form->group_id);
});

test('submitting the form fires map point created event', function (): void {
    $this->withoutExceptionHandling();
    $config = FormDefinitionToMapPoint::factory()->create();
    Event::fake();

    $form = $config->formDefinition;

    $this->post(route('form.submit', $form), [
        $config->titleField->uuid => 'Test Titel',
        $config->descriptionField->uuid => 'Test Beschreibung',
        $config->coordinateField->uuid => [
            'lat' => 49.8728,
            'lng' => 8.6510,
        ],
    ]);

    Event::assertDispatched(MapPointCreatedByFormSubmission::class);
});

test('form can be created with seeder', function (): void {
    $this->artisan('db:seed --class=CreateMapPointForm');
    $this->assertTrue(true);
});

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, string>  $options  option labels keyed by value
 */
function mappingField(FormDefinition $formDefinition, array $attributes, array $options = []): FormField
{
    $field = $formDefinition->fields()->create(['type' => FieldType::TEXT, 'required' => false, 'sort_order' => 10, ...$attributes]);

    $sortOrder = 0;
    foreach ($options as $value => $label) {
        $field->options()->create(['value' => $value, 'label' => $label, 'sort_order' => $sortOrder++]);
    }

    return $field;
}

/**
 * @param  array<string, mixed>  $values  submitted values keyed by form field uuid
 * @return array<string, mixed>
 */
function mapPointSubmission(FormDefinitionToMapPoint $config, array $values = []): array
{
    return [
        $config->titleField->uuid => 'PV-Anlage Schule',
        $config->descriptionField->uuid => 'Auf dem Dach',
        $config->coordinateField->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        ...$values,
    ];
}

function mapPointFormAdmin(Group $group): User
{
    $admin = User::factory()->create();
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    return $admin;
}

test('a submitted form creates the point in the category with the values of the mapped fields', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $category = MapPointCategory::factory()->for($form->group)->create();
    $categoryFields = $category->findOrCreateFormDefinition();
    $power = mappingField($categoryFields, ['label' => 'PV-Leistung (kWp)', 'type' => FieldType::NUMBER]);
    $roof = mappingField($categoryFields, ['label' => 'Anlagenart', 'type' => FieldType::SELECT], ['dach' => 'Dachanlage', 'frei' => 'Freifläche']);
    $equipment = mappingField($categoryFields, ['label' => 'Ausstattung', 'type' => FieldType::CHECKBOX], ['bat' => 'Speicher', 'wb' => 'Wallbox']);
    $formPower = mappingField($form, ['label' => 'Leistung', 'type' => FieldType::NUMBER]);
    $formRoof = mappingField($form, ['label' => 'Art der Anlage', 'type' => FieldType::RADIO], ['a' => 'Dachanlage', 'b' => 'Freifläche']);
    $formEquipment = mappingField($form, ['label' => 'Ausstattung', 'type' => FieldType::CHECKBOX], ['x' => 'Wallbox', 'y' => 'Speicher']);
    $config->category()->associate($category)->save();
    $config->fieldMappings()->createMany([
        ['target_field_id' => $power->id, 'source_field_id' => $formPower->id],
        ['target_field_id' => $roof->id, 'source_field_id' => $formRoof->id],
        ['target_field_id' => $equipment->id, 'source_field_id' => $formEquipment->id],
    ]);

    $this->post(route('form.submit', $form), mapPointSubmission($config, [
        $formPower->uuid => '9.9',
        $formRoof->uuid => 'a',
        $formEquipment->uuid => ['x', 'y'],
    ]))->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    expect($mapPoint->category_id)->toBe($category->id)
        ->and($mapPoint->fields()->pluck('value', 'label')->all())->toBe([
            'PV-Leistung (kWp)' => 9.9,
            'Anlagenart' => 'dach',
            'Ausstattung' => ['wb', 'bat'],
        ]);
});

test('the submitted option picks the sub category, other options keep the category', function (?string $option, string $expectedCategory): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $solar = MapPointCategory::factory()->for($form->group)->create(['name' => 'Solar']);
    $balcony = MapPointCategory::factory()->childOf($solar)->create(['name' => 'Balkonkraftwerk']);
    $kind = mappingField($form, ['label' => 'Art', 'type' => FieldType::SELECT], ['bk' => 'Balkonkraftwerk', 'other' => 'Sonstiges']);
    $config->category()->associate($solar);
    $config->subcategoryField()->associate($kind)->save();
    $config->subcategories()->create(['option_value' => 'bk', 'map_point_category_id' => $balcony->id]);

    $this->post(route('form.submit', $form), mapPointSubmission($config, [$kind->uuid => $option]))->assertSessionHasNoErrors();

    expect(MapPoint::sole()->category->name)->toBe($expectedCategory);
})->with([
    'mapped option' => ['bk', 'Balkonkraftwerk'],
    'option without sub category' => ['other', 'Solar'],
    'no option chosen' => [null, 'Solar'],
]);

test('a value that does not fit its category field is left out and the point is still created', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $category = MapPointCategory::factory()->for($form->group)->create();
    $power = mappingField($category->findOrCreateFormDefinition(), ['label' => 'PV-Leistung (kWp)', 'type' => FieldType::NUMBER, 'max_value' => 100]);
    $formPower = mappingField($form, ['label' => 'Leistung', 'type' => FieldType::NUMBER]);
    $config->category()->associate($category)->save();
    $config->fieldMappings()->create(['target_field_id' => $power->id, 'source_field_id' => $formPower->id]);

    $this->post(route('form.submit', $form), mapPointSubmission($config, [$formPower->uuid => '500']))->assertSessionHasNoErrors();

    expect(MapPoint::sole()->fields()->count())->toBe(0);
});

test('the form builder saves the category, the sub category options and the field mappings', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $solar = MapPointCategory::factory()->for($form->group)->create();
    $balcony = MapPointCategory::factory()->childOf($solar)->create();
    $power = mappingField($solar->findOrCreateFormDefinition(), ['label' => 'PV-Leistung (kWp)', 'type' => FieldType::NUMBER]);
    $kind = mappingField($form, ['label' => 'Art', 'type' => FieldType::SELECT], ['bk' => 'Balkonkraftwerk']);
    $formPower = mappingField($form, ['label' => 'Leistung', 'type' => FieldType::NUMBER]);

    $payload = FormDefinitionData::fromModel($form->load('fields.options'))->toArray();
    $payload['map_point_mapping'] = [...$payload['map_point_mapping'],
        'category_id' => $solar->uuid,
        'subcategory_field_id' => $kind->uuid,
        'subcategory_options' => [['option_value' => 'bk', 'category_id' => $balcony->uuid]],
        'field_mappings' => [['target_field_id' => $power->uuid, 'source_field_id' => $formPower->uuid]],
    ];

    $this->actingAs(mapPointFormAdmin($form->group))
        ->put(route('form-definitions.update', $form), $payload)
        ->assertSessionHasNoErrors();

    $mapping = FormToMapPointMappingData::fromModel($config->fresh());
    expect($mapping->category_id)->toBe($solar->uuid)
        ->and($mapping->subcategory_field_id)->toBe($kind->uuid)
        ->and($mapping->subcategory_options->toArray())->toBe([['option_value' => 'bk', 'category_id' => $balcony->uuid]])
        ->and($mapping->field_mappings->toArray())->toBe([['target_field_id' => $power->uuid, 'source_field_id' => $formPower->uuid]]);
});

test('the form builder rejects category mappings that cannot work', function (string $case, string $errorKey, string $message): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $solar = MapPointCategory::factory()->for($form->group)->create(['name' => 'Solar']);
    $withOwnFields = MapPointCategory::factory()->childOf($solar)->create(['name' => 'Speicher']);
    $foreign = MapPointCategory::factory()->create();
    $power = mappingField($solar->findOrCreateFormDefinition(), ['label' => 'PV-Leistung (kWp)', 'type' => FieldType::NUMBER]);
    mappingField($withOwnFields->findOrCreateFormDefinition(), ['label' => 'Kapazität']);
    $kind = mappingField($form, ['label' => 'Art', 'type' => FieldType::SELECT], ['sp' => 'Speicher']);
    $formText = mappingField($form, ['label' => 'Freitext']);

    $mappings = [
        'category of another initiative' => ['category_id' => $foreign->uuid],
        'sub category with own fields' => [
            'category_id' => $solar->uuid,
            'subcategory_field_id' => $kind->uuid,
            'subcategory_options' => [['option_value' => 'sp', 'category_id' => $withOwnFields->uuid]],
        ],
        'form field of another type' => [
            'category_id' => $solar->uuid,
            'field_mappings' => [['target_field_id' => $power->uuid, 'source_field_id' => $formText->uuid]],
        ],
    ];

    $payload = FormDefinitionData::fromModel($form->load('fields.options'))->toArray();
    $payload['map_point_mapping'] = [...$payload['map_point_mapping'], ...$mappings[$case]];

    $this->actingAs(mapPointFormAdmin($form->group))
        ->put(route('form-definitions.update', $form), $payload)
        ->assertSessionHasErrors([$errorKey => $message]);

    expect($config->fresh()->map_point_category_id)->toBeNull();
})->with([
    'category of another initiative' => ['category of another initiative', 'map_point_mapping.category_id', 'Diese Kategorie ist für die Initiative des Formulars nicht verfügbar.'],
    'sub category with own fields' => ['sub category with own fields', 'map_point_mapping.subcategory_options.0.category_id', 'Die Unterkategorie Speicher muss unter der Kategorie Solar liegen und deren Felder übernehmen.'],
    'form field of another type' => ['form field of another type', 'map_point_mapping.field_mappings.0.source_field_id', 'Das Formularfeld passt nicht zum Typ des Kategoriefelds PV-Leistung (kWp).'],
]);

test('deleting fields and categories removes the parts of the mapping that refer to them', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $root = MapPointCategory::factory()->for($form->group)->create();
    $solar = MapPointCategory::factory()->childOf($root)->create();
    $balcony = MapPointCategory::factory()->childOf($solar)->create();
    $power = mappingField($solar->findOrCreateFormDefinition(), ['label' => 'PV-Leistung (kWp)']);
    $kind = mappingField($form, ['label' => 'Art', 'type' => FieldType::SELECT], ['bk' => 'Balkonkraftwerk']);
    $formPower = mappingField($form, ['label' => 'Leistung']);
    $config->category()->associate($solar);
    $config->subcategoryField()->associate($kind)->save();
    $config->subcategories()->create(['option_value' => 'bk', 'map_point_category_id' => $balcony->id]);
    $config->fieldMappings()->create(['target_field_id' => $power->id, 'source_field_id' => $formPower->id]);

    $formPower->delete();
    $balcony->delete();
    $solar->delete();

    $config->refresh();
    expect($config->map_point_category_id)->toBe($root->id)
        ->and($config->fieldMappings()->count())->toBe(0)
        ->and($config->subcategories()->count())->toBe(0);

    $kind->delete();
    expect($config->refresh()->subcategory_field_id)->toBeNull();

    $form->delete();
    $this->assertModelMissing($config);
});

/**
 * A form whose checkbox options pick the characteristics "Igeltor" (with a width field) and "Totholz" of a garden
 * category, and whose number field fills the width.
 *
 * @return array{config: FormDefinitionToMapPoint, category: MapPointCategory, hedgehogGate: MapPointCharacteristic, width: FormField, formCharacteristics: FormField, formWidth: FormField}
 */
function characteristicMappingForm(): array
{
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $category = MapPointCategory::factory()->for($form->group)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($category, 'category')->create(['name' => 'Igeltor']);
    $deadwood = MapPointCharacteristic::factory()->for($category, 'category')->create(['name' => 'Totholz']);
    $width = mappingField($hedgehogGate->findOrCreateFormDefinition(), ['label' => 'Breite (cm)', 'type' => FieldType::NUMBER]);
    $formCharacteristics = mappingField($form, ['label' => 'Was gibt es?', 'type' => FieldType::CHECKBOX], ['igel' => 'Igeltor', 'holz' => 'Totholz']);
    $formWidth = mappingField($form, ['label' => 'Breite', 'type' => FieldType::NUMBER]);
    $config->category()->associate($category);
    $config->characteristicsField()->associate($formCharacteristics)->save();
    $config->characteristicMappings()->createMany([
        ['option_value' => 'igel', 'map_point_characteristic_id' => $hedgehogGate->id],
        ['option_value' => 'holz', 'map_point_characteristic_id' => $deadwood->id],
    ]);
    $config->fieldMappings()->create(['target_field_id' => $width->id, 'source_field_id' => $formWidth->id]);

    return compact('config', 'category', 'hedgehogGate', 'width', 'formCharacteristics', 'formWidth');
}

test('checked options give the point characteristics and the fields of chosen characteristics are filled', function (): void {
    ['config' => $config, 'formCharacteristics' => $formCharacteristics, 'formWidth' => $formWidth] = characteristicMappingForm();

    $this->post(route('form.submit', $config->formDefinition), mapPointSubmission($config, [
        $formCharacteristics->uuid => ['igel', 'holz'],
        $formWidth->uuid => '13',
    ]))->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    expect($mapPoint->characteristics()->pluck('name')->sort()->values()->all())->toBe(['Igeltor', 'Totholz'])
        ->and($mapPoint->fields()->pluck('value', 'label')->all())->toBe(['Breite (cm)' => 13]);
});

test('fields of characteristics that were not chosen are skipped', function (): void {
    ['config' => $config, 'formCharacteristics' => $formCharacteristics, 'formWidth' => $formWidth] = characteristicMappingForm();

    $this->post(route('form.submit', $config->formDefinition), mapPointSubmission($config, [
        $formCharacteristics->uuid => ['holz'],
        $formWidth->uuid => '13',
    ]))->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    expect($mapPoint->characteristics()->pluck('name')->all())->toBe(['Totholz'])
        ->and($mapPoint->fields()->count())->toBe(0);
});

test('an option of a characteristic the category does not offer is ignored', function (): void {
    ['config' => $config, 'hedgehogGate' => $hedgehogGate, 'formCharacteristics' => $formCharacteristics] = characteristicMappingForm();
    $hedgehogGate->update(['map_point_category_id' => MapPointCategory::factory()->for($config->formDefinition->group)->create()->id]);

    $this->post(route('form.submit', $config->formDefinition), mapPointSubmission($config, [
        $formCharacteristics->uuid => ['igel'],
    ]))->assertSessionHasNoErrors();

    expect(MapPoint::sole()->characteristics()->count())->toBe(0);
});

test('the form builder saves the characteristic options and field mappings of characteristic fields', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $garden = MapPointCategory::factory()->for($form->group)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $width = mappingField($hedgehogGate->findOrCreateFormDefinition(), ['label' => 'Breite (cm)', 'type' => FieldType::NUMBER]);
    $formCharacteristics = mappingField($form, ['label' => 'Was gibt es?', 'type' => FieldType::CHECKBOX], ['igel' => 'Igeltor']);
    $formWidth = mappingField($form, ['label' => 'Breite', 'type' => FieldType::NUMBER]);

    $payload = FormDefinitionData::fromModel($form->load('fields.options'))->toArray();
    $payload['map_point_mapping'] = [...$payload['map_point_mapping'],
        'category_id' => $garden->uuid,
        'characteristics_field_id' => $formCharacteristics->uuid,
        'characteristic_options' => [['option_value' => 'igel', 'characteristic_id' => $hedgehogGate->uuid]],
        'field_mappings' => [['target_field_id' => $width->uuid, 'source_field_id' => $formWidth->uuid]],
    ];

    $this->actingAs(mapPointFormAdmin($form->group))
        ->put(route('form-definitions.update', $form), $payload)
        ->assertSessionHasNoErrors();

    $mapping = FormToMapPointMappingData::fromModel($config->fresh());
    expect($mapping->characteristics_field_id)->toBe($formCharacteristics->uuid)
        ->and($mapping->characteristic_options->toArray())->toBe([['option_value' => 'igel', 'characteristic_id' => $hedgehogGate->uuid]])
        ->and($mapping->field_mappings->toArray())->toBe([['target_field_id' => $width->uuid, 'source_field_id' => $formWidth->uuid]]);
});

test('the form builder rejects characteristics the category does not offer and fields other than checkboxes', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $garden = MapPointCategory::factory()->for($form->group)->create(['name' => 'Garten']);
    $solar = MapPointCategory::factory()->for($form->group)->create();
    $storage = MapPointCharacteristic::factory()->for($solar, 'category')->create();
    $formKind = mappingField($form, ['label' => 'Art', 'type' => FieldType::SELECT], ['sp' => 'Speicher']);

    $payload = FormDefinitionData::fromModel($form->load('fields.options'))->toArray();
    $payload['map_point_mapping'] = [...$payload['map_point_mapping'],
        'category_id' => $garden->uuid,
        'characteristics_field_id' => $formKind->uuid,
        'characteristic_options' => [['option_value' => 'sp', 'characteristic_id' => $storage->uuid]],
    ];

    $this->actingAs(mapPointFormAdmin($form->group))
        ->put(route('form-definitions.update', $form), $payload)
        ->assertSessionHasErrors([
            'map_point_mapping.characteristics_field_id' => 'Die Maßnahmen können nur Checkboxen bestimmen.',
            'map_point_mapping.characteristic_options.0.characteristic_id' => 'Diese Maßnahme ist für die Kategorie Garten nicht verfügbar.',
        ]);
});

test('deleting the characteristics field or a characteristic removes the parts of the mapping that refer to them', function (): void {
    ['config' => $config, 'hedgehogGate' => $hedgehogGate, 'formCharacteristics' => $formCharacteristics] = characteristicMappingForm();

    $hedgehogGate->delete();
    expect($config->characteristicMappings()->pluck('option_value')->all())->toBe(['holz']);

    $formCharacteristics->delete();
    expect($config->refresh()->characteristics_field_id)->toBeNull()
        ->and($config->characteristicMappings()->count())->toBe(0);

    $config->formDefinition->delete();
    $this->assertModelMissing($config);
});
