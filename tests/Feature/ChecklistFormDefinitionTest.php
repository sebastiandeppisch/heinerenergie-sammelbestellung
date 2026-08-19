<?php

use App\Enums\FieldType;
use App\Enums\FormType;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group);
    $this->actingAs($this->user);
});

test('checklist can be created via form builder', function (): void {
    $this->withoutExceptionHandling();

    $formData = [
        'name' => 'Meine Checkliste',
        'description' => 'Eine Checkliste',
        'is_active' => true,
        'type' => FormType::Checklist->value,
        'id' => 'temp',
        'group_id' => $this->group->uuid,
        'fields' => [
            [
                'type' => FieldType::CHECKBOX->value,
                'label' => 'Punkt 1',
                'required' => false,
                'id' => 'temp',
                'options' => [
                    ['label' => 'Erledigt', 'value' => 'done', 'is_default' => false, 'sort_order' => 0, 'id' => 'temp', 'is_required' => false],
                ],
            ],
        ],
    ];

    $response = $this->post(route('form-definitions.store'), $formData);
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $fd = FormDefinition::firstOrFail();
    expect($fd->type)->toBe(FormType::Checklist);
    expect($fd->name)->toBe('Meine Checkliste');
});

test('form builder index shows forms and checklists separately', function (): void {
    FormDefinition::factory()->create(['group_id' => $this->group->id, 'type' => FormType::Form]);
    FormDefinition::factory()->checklist()->create(['group_id' => $this->group->id]);

    $this->get(route('form-definitions.index'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('FormBuilder/Index')
            ->has('formDefinitions', 1)
            ->has('checklists', 1)
        );
});

test('form builder index shows only forms in formDefinitions prop', function (): void {
    FormDefinition::factory()->count(2)->create(['group_id' => $this->group->id, 'type' => FormType::Form]);
    FormDefinition::factory()->checklist()->create(['group_id' => $this->group->id]);

    $this->get(route('form-definitions.index'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('FormBuilder/Index')
            ->has('formDefinitions', 2)
            ->has('checklists', 1)
        );
});
