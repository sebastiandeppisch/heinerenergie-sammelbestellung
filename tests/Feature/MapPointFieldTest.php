<?php

use App\Data\MapPointData;
use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');
});

function fieldTestGroupAdmin(Group $group): User
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    return $admin;
}

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, string>  $options  option labels keyed by value
 */
function categoryField(MapPointCategory $category, array $attributes, array $options = []): FormField
{
    $field = $category->findOrCreateFormDefinition()->fields()->create([
        'type' => FieldType::TEXT,
        'required' => false,
        'sort_order' => 0,
        ...$attributes,
    ]);

    $sortOrder = 0;
    foreach ($options as $value => $label) {
        $field->options()->create(['value' => $value, 'label' => $label, 'sort_order' => $sortOrder++]);
    }

    return $field;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function fieldTestPointPayload(Group $group, MapPointCategory $category, array $overrides = []): array
{
    return [
        'title' => 'PV-Anlage Schule',
        'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
        'published' => true,
        'group_id' => $group->uuid,
        'category_id' => $category->uuid,
        ...$overrides,
    ];
}

/**
 * @return array<string, mixed>
 */
function indexedPoint(User $admin, MapPoint $mapPoint): array
{
    $mapPoints = test()->actingAs($admin)->get(route('mappoints.index'))->assertOk()->inertiaProps('mapPoints');

    if (! is_array($mapPoints)) {
        throw new UnexpectedValueException('The index page did not return map points.');
    }

    return collect($mapPoints)->firstWhere('id', $mapPoint->uuid);
}

test('a point of a sub category without own fields stores values of the parent category fields', function (): void {
    $root = Group::factory()->create();
    $child = Group::factory()->create(['parent_id' => $root->id]);
    $parentCategory = MapPointCategory::factory()->for($root)->create(['name' => 'Solar']);
    $subCategory = MapPointCategory::factory()->for($child)->create(['parent_id' => $parentCategory->id, 'name' => 'PV-Anlagen']);
    $power = categoryField($parentCategory, ['label' => 'PV-Leistung (kWp)', 'type' => FieldType::NUMBER]);
    $contact = categoryField($parentCategory, ['label' => 'Ansprechperson']);

    $this->actingAs(fieldTestGroupAdmin($child))
        ->post(route('mappoints.store'), fieldTestPointPayload($child, $subCategory, [
            'field_values' => [$power->uuid => '9.9', $contact->uuid => 'Kim Muster'],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $mapPoint = MapPoint::where('title', 'PV-Anlage Schule')->firstOrFail();
    expect($mapPoint->fields()->pluck('value', 'label')->all())->toBe([
        'PV-Leistung (kWp)' => 9.9,
        'Ansprechperson' => 'Kim Muster',
    ]);
});

test('own fields of a sub category replace the parent category fields instead of adding to them', function (): void {
    $group = Group::factory()->create();
    $parentCategory = MapPointCategory::factory()->for($group)->create();
    $subCategory = MapPointCategory::factory()->childOf($parentCategory)->create();
    $parentField = categoryField($parentCategory, ['label' => 'PV-Leistung (kWp)']);
    $ownField = categoryField($subCategory, ['label' => 'Speicher (kWh)']);

    $this->actingAs(fieldTestGroupAdmin($group))
        ->post(route('mappoints.store'), fieldTestPointPayload($group, $subCategory, [
            'field_values' => [$parentField->uuid => '10', $ownField->uuid => '5'],
        ]))
        ->assertSessionHasNoErrors();

    expect(MapPoint::firstOrFail()->fields()->pluck('label')->all())->toBe(['Speicher (kWh)']);
});

test('saving keeps former values and values of fields that are not sent', function (): void {
    $group = Group::factory()->create();
    $solar = MapPointCategory::factory()->for($group)->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $former = categoryField($garden, ['label' => 'Beetfläche']);
    $power = categoryField($solar, ['label' => 'PV-Leistung (kWp)']);
    $contact = categoryField($solar, ['label' => 'Ansprechperson']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $solar->id]);
    $former->createMapPointField($mapPoint, '20');
    $contact->createMapPointField($mapPoint, 'Kim Muster');

    $this->actingAs(fieldTestGroupAdmin($group))
        ->put(route('mappoints.update', $mapPoint), fieldTestPointPayload($group, $solar, [
            'field_values' => [$power->uuid => '10'],
        ]))
        ->assertSessionHasNoErrors();

    expect($mapPoint->fields()->orderBy('label')->pluck('value', 'label')->all())->toBe([
        'Ansprechperson' => 'Kim Muster',
        'Beetfläche' => '20',
        'PV-Leistung (kWp)' => '10',
    ]);
});

test('resending an unchanged value keeps its snapshot', function (FieldType $type, array $options, mixed $storedValue, mixed $sentValue): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $field = categoryField($category, ['label' => 'Leistung', 'type' => $type], $options);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $category->id]);
    $snapshot = $field->createMapPointField($mapPoint, $storedValue);
    $field->update(['label' => 'Neue Bezeichnung']);

    $this->actingAs(fieldTestGroupAdmin($group))
        ->put(route('mappoints.update', $mapPoint), fieldTestPointPayload($group, $category, [
            'field_values' => [$field->uuid => $sentValue],
        ]))
        ->assertSessionHasNoErrors();

    expect($mapPoint->fields()->sole()->only(['id', 'label']))->toBe(['id' => $snapshot->id, 'label' => 'Leistung']);
})->with([
    'number sent as decimal text' => [FieldType::NUMBER, [], 10, '10.0'],
    'checkboxes in another order' => [FieldType::CHECKBOX, ['bat' => 'Batteriespeicher', 'wb' => 'Wallbox'], ['bat', 'wb'], ['wb', 'bat']],
]);

test('values of fields that do not belong to the category are ignored', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $otherCategory = MapPointCategory::factory()->for($group)->create();
    $foreignField = categoryField($otherCategory, ['label' => 'Fremdes Feld']);

    $this->actingAs(fieldTestGroupAdmin($group))
        ->post(route('mappoints.store'), fieldTestPointPayload($group, $category, [
            'field_values' => [$foreignField->uuid => 'wird ignoriert'],
        ]))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('map_point_fields', 0);
});

test('a field value is validated against the type of the field', function (FieldType $type, array $options, mixed $value, string $message): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $field = categoryField($category, ['label' => 'Leistung', 'type' => $type], $options);

    $this->actingAs(fieldTestGroupAdmin($group))
        ->post(route('mappoints.store'), fieldTestPointPayload($group, $category, [
            'field_values' => [$field->uuid => $value],
        ]))
        ->assertSessionHasErrors(["field_values.{$field->uuid}" => $message]);

    $this->assertDatabaseCount('map_points', 0);
})->with([
    'text in a number field' => [FieldType::NUMBER, [], 'viel', 'Leistung muss eine Zahl sein.'],
    'unknown option of a select field' => [FieldType::SELECT, ['ja' => 'Ja'], 'vielleicht', 'Der gewählte Wert für Leistung ist ungültig.'],
]);

test('clearing a value removes it from the point', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $field = categoryField($category, ['label' => 'Ansprechperson']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $category->id]);
    $field->createMapPointField($mapPoint, 'Kim Muster');

    $this->actingAs(fieldTestGroupAdmin($group))
        ->put(route('mappoints.update', $mapPoint), fieldTestPointPayload($group, $category, [
            'field_values' => [$field->uuid => ''],
        ]))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('map_point_fields', ['map_point_id' => $mapPoint->id]);
});

test('an unchanged value keeps the old label of a renamed field and a changed value gets the new one', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $power = categoryField($category, ['label' => 'Leistung', 'type' => FieldType::NUMBER]);
    $contact = categoryField($category, ['label' => 'Kontakt']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $category->id]);
    $power->createMapPointField($mapPoint, 10);
    $contact->createMapPointField($mapPoint, 'Kim');
    $power->update(['label' => 'PV-Leistung (kWp)']);
    $contact->update(['label' => 'Ansprechperson']);

    $this->actingAs(fieldTestGroupAdmin($group))
        ->put(route('mappoints.update', $mapPoint), fieldTestPointPayload($group, $category, [
            'field_values' => [$power->uuid => '10', $contact->uuid => 'Alex'],
        ]))
        ->assertSessionHasNoErrors();

    expect($mapPoint->fields()->pluck('label')->sort()->values()->all())->toBe(['Ansprechperson', 'Leistung']);
});

test('values of another category become former values and are active again after changing back', function (): void {
    $group = Group::factory()->create();
    $solar = MapPointCategory::factory()->for($group)->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $power = categoryField($solar, ['label' => 'PV-Leistung (kWp)']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $solar->id]);
    $power->createMapPointField($mapPoint, '10');
    $admin = fieldTestGroupAdmin($group);

    $this->actingAs($admin)->put(route('mappoints.update', $mapPoint), fieldTestPointPayload($group, $garden))->assertSessionHasNoErrors();

    $point = indexedPoint($admin, $mapPoint);
    expect($point['fields'])->toBeEmpty()
        ->and($point['former_fields'][0]['label'])->toBe('PV-Leistung (kWp)');

    $this->actingAs($admin)->put(route('mappoints.update', $mapPoint), fieldTestPointPayload($group, $solar))->assertSessionHasNoErrors();

    $point = indexedPoint($admin, $mapPoint);
    expect($point['fields'][0]['display_value'])->toBe('10')
        ->and($point['former_fields'])->toBeEmpty();
});

test('the value of a deleted field is kept as former value', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $field = categoryField($category, ['label' => 'Ansprechperson']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $category->id]);
    $field->createMapPointField($mapPoint, 'Kim Muster');

    $field->delete();

    $point = indexedPoint(fieldTestGroupAdmin($group), $mapPoint);
    expect($point['fields'])->toBeEmpty()
        ->and($point['former_fields'][0])->toMatchArray(['label' => 'Ansprechperson', 'display_value' => 'Kim Muster', 'field_id' => null]);
});

test('deleting a sub category turns its values into former values and makes the parent values active', function (): void {
    $group = Group::factory()->create();
    $parent = MapPointCategory::factory()->for($group)->create();
    $child = MapPointCategory::factory()->childOf($parent)->create();
    $power = categoryField($parent, ['label' => 'PV-Leistung (kWp)']);
    $storage = categoryField($child, ['label' => 'Speicher']);
    $child->syncPublicFields([$storage->id]);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $child->id]);
    $power->createMapPointField($mapPoint, '10');
    $storage->createMapPointField($mapPoint, 'ja');

    $child->delete();

    $this->assertDatabaseCount('map_point_public_fields', 0);
    $this->assertModelMissing($storage);

    $point = indexedPoint(fieldTestGroupAdmin($group), $mapPoint);
    expect(array_column($point['fields'], 'label'))->toBe(['PV-Leistung (kWp)'])
        ->and(array_column($point['former_fields'], 'label'))->toBe(['Speicher']);
});

test('values are shown with option labels and German number and date formats', function (FieldType $type, array $options, mixed $value, string $displayValue): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $field = categoryField($category, ['label' => 'Feld', 'type' => $type], $options);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $category->id]);
    $field->createMapPointField($mapPoint, $value);

    expect(indexedPoint(fieldTestGroupAdmin($group), $mapPoint)['fields'][0]['display_value'])->toBe($displayValue);
})->with([
    'checkbox' => [FieldType::CHECKBOX, ['bat' => 'Batteriespeicher', 'wb' => 'Wallbox'], ['bat', 'wb'], 'Batteriespeicher, Wallbox'],
    'select' => [FieldType::SELECT, ['dach' => 'Dachanlage'], 'dach', 'Dachanlage'],
    'decimal number' => [FieldType::NUMBER, [], 1234.5, '1.234,5'],
    'whole number' => [FieldType::NUMBER, [], 12000, '12.000'],
    'date' => [FieldType::DATE, [], '2026-09-29', '29.09.2026'],
]);

test('the public map shows only public values of the current fields', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $power = categoryField($category, ['label' => 'PV-Leistung (kWp)']);
    $phone = categoryField($category, ['label' => 'Telefon', 'type' => FieldType::PHONE]);
    $removed = categoryField($category, ['label' => 'Entferntes Feld']);
    $category->syncPublicFields([$power->id, $removed->id]);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $category->id, 'published' => true]);
    $power->createMapPointField($mapPoint, '10');
    $phone->createMapPointField($mapPoint, '06151 123456');
    $removed->createMapPointField($mapPoint, 'alt');
    $removed->delete();
    $mapEmbed = MapEmbed::factory()->for($group)->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $this->get(route('map.public', $mapEmbed))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has("pointsByCategory.{$category->uuid}.0.fields", 1)
            ->where("pointsByCategory.{$category->uuid}.0.fields.0.label", 'PV-Leistung (kWp)')
            ->where("pointsByCategory.{$category->uuid}.0.former_fields", [])
        );
});

test('a point converted without saying otherwise contains only public values', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $power = categoryField($category, ['label' => 'PV-Leistung (kWp)']);
    $phone = categoryField($category, ['label' => 'Telefon', 'type' => FieldType::PHONE]);
    $category->syncPublicFields([$power->id]);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $category->id]);
    $power->createMapPointField($mapPoint, '10');
    $phone->createMapPointField($mapPoint, '06151 123456');

    $data = MapPointData::fromModel($mapPoint);

    expect($data->fields->pluck('label')->all())->toBe(['PV-Leistung (kWp)'])
        ->and($data->former_fields)->toBeEmpty();
});

test('deleting a point deletes its field values and their options', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $field = categoryField($category, ['label' => 'Ausstattung', 'type' => FieldType::CHECKBOX], ['bat' => 'Batteriespeicher']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $category->id]);
    $field->createMapPointField($mapPoint, ['bat']);

    $this->actingAs(fieldTestGroupAdmin($group))
        ->delete(route('mappoints.destroy', $mapPoint))
        ->assertRedirect();

    $this->assertModelMissing($mapPoint);
    $this->assertDatabaseCount('map_point_fields', 0);
    $this->assertDatabaseCount('map_point_field_options', 0);
});

test('deleting a field removes it from the public fields of its category', function (): void {
    $category = MapPointCategory::factory()->create();
    $field = categoryField($category, ['label' => 'PV-Leistung (kWp)']);
    $category->syncPublicFields([$field->id]);

    $field->delete();

    $this->assertModelMissing($field);
    $this->assertDatabaseCount('map_point_public_fields', 0);
});
