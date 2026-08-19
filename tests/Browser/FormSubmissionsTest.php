<?php

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group);
    $this->actingAs($this->user);
});

test('group admin can switch from cards to table view and open detail dialog', function (): void {
    $formDefinition = FormDefinition::factory()->create([
        'group_id' => $this->group->id,
    ]);

    FormSubmission::factory()->create([
        'submitted_at' => now(),
        'form_name' => 'Beratungsanfrage',
        'group_id' => $this->group->id,
        'form_definition_id' => $formDefinition->id,
    ]);

    $page = visit(route('form-submissions.index'));

    $page->assertSee('Kachelansicht')
        ->assertSee('Tabellenansicht')
        ->click('Tabellenansicht')
        ->assertQueryStringHas('view', 'table')
        ->assertSee('Beratungsanfrage')
        ->click('Beratungsanfrage')
        ->assertSee('Als gelesen markieren')
        ->assertNoJavaScriptErrors();
});
