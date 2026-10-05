<?php

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Enums\FormType;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');
});

function categoryFieldGroupAdmin(Group $group): User
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    return $admin;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function ownCategoryField(MapPointCategory $category, array $attributes): FormField
{
    return $category->findOrCreateFormDefinition()->fields()->create([
        'type' => FieldType::TEXT,
        'required' => false,
        'sort_order' => 0,
        ...$attributes,
    ]);
}

test('the category page lists the fields of the parent category when it has no own fields', function (): void {
    $group = Group::factory()->create();
    $parent = MapPointCategory::factory()->for($group)->create(['name' => 'Solar']);
    $category = MapPointCategory::factory()->childOf($parent)->create();
    $power = ownCategoryField($parent, ['label' => 'PV-Leistung (kWp)']);
    ownCategoryField($parent, ['label' => 'Ansprechperson']);
    $parent->syncPublicFields([$power->id]);

    $this->actingAs(categoryFieldGroupAdmin($group))
        ->get(route('mappoint-categories.edit', $category))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('fields', 2)
            ->where('fields.0.label', 'PV-Leistung (kWp)')
            ->where('fields.0.category_name', 'Solar')
            ->where('fields.0.is_public', true)
            ->where('fields.1.label', 'Ansprechperson')
            ->where('fields.1.is_public', false)
        );
});

test('the category page lists only the own fields when the category has some', function (): void {
    $group = Group::factory()->create();
    $parent = MapPointCategory::factory()->for($group)->create();
    $category = MapPointCategory::factory()->childOf($parent)->create();
    ownCategoryField($parent, ['label' => 'PV-Leistung (kWp)']);
    ownCategoryField($category, ['label' => 'Speicher (kWh)']);

    $this->actingAs(categoryFieldGroupAdmin($group))
        ->get(route('mappoint-categories.edit', $category))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('fields', 1)
            ->where('fields.0.label', 'Speicher (kWh)')
            ->where('fields.0.category_id', $category->uuid)
        );
});

test('saving a category stores which of its own fields are public', function (): void {
    $group = Group::factory()->create();
    $parent = MapPointCategory::factory()->for($group)->create();
    $category = MapPointCategory::factory()->childOf($parent)->create();
    $parentField = ownCategoryField($parent, ['label' => 'PV-Leistung (kWp)']);
    $power = ownCategoryField($category, ['label' => 'Speicher (kWh)']);

    $parent->syncPublicFields([$parentField->id]);

    $this->actingAs(categoryFieldGroupAdmin($group))
        ->put(route('mappoint-categories.update', $category), [
            'name' => $category->name,
            'public_field_ids' => [$power->uuid],
        ])
        ->assertSessionHasNoErrors();

    expect($category->publicFields()->pluck('id')->all())->toBe([$power->id])
        ->and($parent->publicFields()->pluck('id')->all())->toBe([$parentField->id]);

    $this->put(route('mappoint-categories.update', $category), ['name' => $category->name])->assertSessionHasNoErrors();

    expect($category->publicFields()->count())->toBe(0)
        ->and($parent->publicFields()->count())->toBe(1);
});

test('a category cannot mark the fields of its parent category as public', function (): void {
    $group = Group::factory()->create();
    $parent = MapPointCategory::factory()->for($group)->create();
    $category = MapPointCategory::factory()->childOf($parent)->create();
    $parentField = ownCategoryField($parent, ['label' => 'PV-Leistung (kWp)']);
    ownCategoryField($category, ['label' => 'Speicher (kWh)']);

    $this->actingAs(categoryFieldGroupAdmin($group))
        ->put(route('mappoint-categories.update', $category), [
            'name' => $category->name,
            'public_field_ids' => [$parentField->uuid],
        ])
        ->assertSessionHasErrors('public_field_ids.0');

    $this->assertDatabaseCount('map_point_public_fields', 0);
});

test('editing the fields creates the form definition once and opens the form builder', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create(['name' => 'Solar']);
    $admin = categoryFieldGroupAdmin($group);

    $this->actingAs($admin)->post(route('mappoint-categories.fields.edit', $category))->assertRedirect();
    $formDefinition = $category->refresh()->formDefinition;

    $this->actingAs($admin)
        ->post(route('mappoint-categories.fields.edit', $category))
        ->assertRedirect(route('form-definitions.edit', $formDefinition));

    expect(FormDefinition::count())->toBe(1)
        ->and($formDefinition->type)->toBe(FormType::MapPointFields)
        ->and($formDefinition->group_id)->toBe($group->id);
});

test('the fields of a category inherited from a parent initiative cannot be edited by a sub initiative', function (): void {
    $root = Group::factory()->create();
    $child = Group::factory()->create(['parent_id' => $root->id]);
    $category = MapPointCategory::factory()->for($root)->create();

    $this->actingAs(categoryFieldGroupAdmin($child))
        ->post(route('mappoint-categories.fields.edit', $category))
        ->assertForbidden();

    expect($category->refresh()->form_definition_id)->toBeNull();
});

test('category fields are always optional and cannot become a form', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $field = ownCategoryField($category, ['label' => 'PV-Leistung (kWp)']);
    $formDefinition = $category->formDefinition;
    $admin = categoryFieldGroupAdmin($group);

    $data = FormDefinitionData::fromModel($formDefinition->load('fields.options'))->toArray();
    $data['fields'][0]['required'] = true;

    $this->actingAs($admin)->put(route('form-definitions.update', $formDefinition), $data)->assertSessionHasNoErrors();
    expect($field->refresh()->required)->toBeFalse();

    $this->put(route('form-definitions.update', $formDefinition), [...$data, 'type' => FormType::Form->value])
        ->assertSessionHasErrors(['type' => 'Der Typ von Kategorie-Feldern kann nicht geändert werden.']);
    expect($formDefinition->refresh()->type)->toBe(FormType::MapPointFields);
});

test('category fields cannot use files or locations', function (FieldType $type): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    ownCategoryField($category, ['label' => 'Ort']);
    $formDefinition = $category->formDefinition;

    $data = FormDefinitionData::fromModel($formDefinition->load('fields.options'))->toArray();
    $data['fields'][0]['type'] = $type->value;

    $this->actingAs(categoryFieldGroupAdmin($group))
        ->put(route('form-definitions.update', $formDefinition), $data)
        ->assertSessionHasErrors('fields.0.type');
})->with([FieldType::FILE, FieldType::GEO_COORDINATE, FieldType::ADDRESS]);

test('category fields can be images with a maximum number of images', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $field = ownCategoryField($category, ['label' => 'Foto']);
    $formDefinition = $category->formDefinition;

    $data = FormDefinitionData::fromModel($formDefinition->load('fields.options'))->toArray();
    $data['fields'][0]['type'] = FieldType::IMAGE->value;
    $data['fields'][0]['max_images'] = 3;

    $this->actingAs(categoryFieldGroupAdmin($group))
        ->put(route('form-definitions.update', $formDefinition), $data)
        ->assertSessionHasNoErrors();

    expect($field->refresh())->type->toBe(FieldType::IMAGE)->max_images->toBe(3);
});

test('category fields cannot be filled in as a public form', function (): void {
    $category = MapPointCategory::factory()->create();

    $this->get(route('form.show', $category->findOrCreateFormDefinition()))->assertNotFound();
});

test('a sub initiative cannot open or save the fields of an inherited category in the form builder', function (): void {
    $root = Group::factory()->create();
    $child = Group::factory()->create(['parent_id' => $root->id]);
    $category = MapPointCategory::factory()->for($root)->create();
    $field = ownCategoryField($category, ['label' => 'PV-Leistung (kWp)']);
    $formDefinition = $category->formDefinition;
    $data = FormDefinitionData::fromModel($formDefinition->load('fields.options'))->toArray();
    $data['fields'][0]['label'] = 'Umbenannt';

    $this->actingAs(categoryFieldGroupAdmin($child))
        ->get(route('form-definitions.edit', $formDefinition))
        ->assertForbidden();

    $this->put(route('form-definitions.update', $formDefinition), $data)->assertForbidden();

    expect($field->refresh()->label)->toBe('PV-Leistung (kWp)');
});

test('category fields cannot be deleted as a form, only together with the category', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create();
    $formDefinition = $category->findOrCreateFormDefinition();

    $this->actingAs(categoryFieldGroupAdmin($group))
        ->delete(route('form-definitions.destroy', $formDefinition))
        ->assertNotFound();

    $this->assertModelExists($formDefinition);
});
