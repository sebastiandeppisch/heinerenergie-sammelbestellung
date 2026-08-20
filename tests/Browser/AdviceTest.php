<?php

use App\Contracts\NextcloudFileClientContract;
use App\Enums\FieldType;
use App\Models\Advice;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MockNextcloudFileClient;

uses(RefreshDatabase::class);

beforeEach(function (): void {

    app()->bind(NextcloudFileClientContract::class, MockNextcloudFileClient::class);

    $this->user = User::factory()->create();
    $this->group = Group::factory()->create([
        'name' => 'Test Initiative',
        'nextcloud_search_path' => '/Beratungen',
    ]);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('advice no smoke & no js errors', function (): void {
    $advice = Advice::factory()->create([
        'advisor_id' => $this->user->id,
        'group_id' => $this->group->id,
    ]);

    visit(route('advices.show', $advice))
        ->assertNoSmoke()
        ->assertNoJavaScriptErrors();
});

test('standard advisor can create a new advice', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['name' => 'Test Initiative']);
    $group->users()->attach($user, ['is_admin' => false]);
    app(SessionService::class)->actAsGroup($group, false);
    $this->actingAs($user);

    visit(route('advices'))
        ->assertNoSmoke()
        ->click('@new-advice-button')
        ->assertSee('Neue Beratung')
        ->assertSee('Test Initiative')
        ->fill('first_name', 'Max')
        ->fill('last_name', 'Mustermann')
        ->fill('phone', '01234567890')
        ->fill('email', 'max@mustermann.de')
        ->fill('street', 'Musterstraße')
        ->fill('street_number', '1')
        ->fill('zip', '12345')
        ->fill('city', 'Musterstadt')
        ->click('Speichern')
        ->waitForEvent('networkidle')
        ->assertPathBeginsWith('/advices/')
        ->assertSee('Beratung erfolgreich angelegt');

    expect(Advice::where('first_name', 'Max')->exists())->toBeTrue();
});

test('admin advisor can create a new advice', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['name' => 'Test Initiative']);
    $group->users()->attach($user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);
    $this->actingAs($user);

    visit(route('advices'))
        ->assertNoSmoke()
        ->click('@new-advice-button')
        ->assertSee('Neue Beratung')
        ->assertSee('Test Initiative')
        ->fill('first_name', 'Erika')
        ->fill('last_name', 'Musterfrau')
        ->fill('phone', '09876543210')
        ->fill('email', 'erika@musterfrau.de')
        ->fill('street', 'Hauptstraße')
        ->fill('street_number', '42')
        ->fill('zip', '54321')
        ->fill('city', 'Beispielstadt')
        ->click('Speichern')
        ->waitForEvent('networkidle')
        ->assertPathBeginsWith('/advices/')
        ->assertSee('Beratung erfolgreich angelegt');

    expect(Advice::where('first_name', 'Erika')->exists())->toBeTrue();
});

test('system admin sees group select and can create a new advice', function (): void {
    $user = User::factory()->admin()->create();
    $group = Group::factory()->create(['name' => 'Test Initiative']);
    app(SessionService::class)->actAsSystemAdmin();
    $this->actingAs($user);

    visit(route('advices'))
        ->assertNoSmoke()
        ->click('@new-advice-button')
        ->assertSee('Neue Beratung')
        ->assertSee('Gruppe')
        ->click('Gruppe auswählen')
        ->click('Test Initiative')
        ->fill('first_name', 'Hans')
        ->fill('last_name', 'Beispiel')
        ->fill('phone', '01111111111')
        ->fill('email', 'hans@beispiel.de')
        ->fill('street', 'Testgasse')
        ->fill('street_number', '7')
        ->fill('zip', '99999')
        ->fill('city', 'Testort')
        ->click('Speichern')
        ->waitForEvent('networkidle')
        ->assertPathBeginsWith('/advices/')
        ->assertSee('Beratung erfolgreich angelegt');

    expect(Advice::where('first_name', 'Hans')->exists())->toBeTrue();
});

test('an empty optional address is rendered as a hint instead of a blank line', function (): void {
    $creator = FormDefinitionToAdvice::factory()->withAdvice()->create();
    $formDefinition = $creator->formDefinition;
    $formDefinition->update(['group_id' => $this->group->id]);

    $submission = FormSubmission::where('form_definition_id', $formDefinition->id)->firstOrFail();
    $submission->update(['group_id' => $this->group->id]);

    $advice = Advice::findOrFail($submission->advice_id);
    $advice->update(['group_id' => $this->group->id, 'advisor_id' => $this->user->id]);

    $secondAddress = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::ADDRESS,
        'label' => 'Zweite Adresse',
        'required' => false,
    ]);
    $secondAddress->createSubmissionField($submission, null);

    visit(route('advices.show', $advice))
        ->assertSee('Zweite Adresse')
        ->assertSee('Keine Adresse angegeben')
        ->assertNoJavaScriptErrors();
});
