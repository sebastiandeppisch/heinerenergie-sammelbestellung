<?php

use App\Enums\FieldType;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormSubmission;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Services\FormSubmissionConfirmationService;
use App\Services\MapPointEditLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

test('a person consents to the point only and later shows the details on the manage page', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $form->update(['is_active' => true, 'requires_email_confirmation' => true]);
    $form->group->update(['name' => 'Gartenkreis']);
    $category = MapPointCategory::factory()->for($form->group)->withoutImage()->create(['name' => 'Naturgarten']);
    $email = $form->fields()->create(['type' => FieldType::EMAIL, 'label' => 'E-Mail', 'required' => true, 'sort_order' => 10]);
    $config->category()->associate($category);
    $config->contactEmailField()->associate($email);
    $config->save();
    $embed = MapEmbed::factory()->for($form->group)->create(['show_table' => true]);
    $embed->mapPointCategories()->sync([$category->id]);

    visit(route('form.show', $form))
        ->assertDontSee('zusätzlich diese Angaben zeigen')
        ->click('Meinen Eintrag auf der Karte zeigen')
        ->assertSee('zusätzlich diese Angaben zeigen: Titel, Beschreibung')
        ->assertSee('auf der Karte von Gartenkreis öffentlich angezeigt wird, mit folgenden Angaben: Titel, Beschreibung sowie dem Standort')
        ->assertNoJavaScriptErrors();

    // Placing the pin depends on the map tiles, so the form is sent without the browser.
    $this->post(route('form.submit', $form), [
        $config->titleField->uuid => 'Garten Müller',
        $config->descriptionField->uuid => 'Mit Teich und Totholz',
        $config->coordinateField->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $email->uuid => 'erika@example.com',
        '_consent_show' => true,
    ])->assertSessionHasNoErrors();

    visit(route('form.confirm.show', app(FormSubmissionConfirmationService::class)->issueToken(FormSubmission::sole())))
        ->click('E-Mail-Adresse bestätigen')
        ->assertSee('Dein Eintrag ist angelegt.')
        ->assertNoJavaScriptErrors();

    $mapPoint = MapPoint::sole();
    $mapPoint->update(['published' => true]);

    visit(route('map.public', $embed))
        ->click('Tabelle')
        ->assertSee('Naturgarten')
        ->assertDontSee('Garten Müller')
        ->assertNoJavaScriptErrors();

    visit(route('map-point.manage.show', app(MapPointEditLinkService::class)->issue($mapPoint)))
        ->assertSee('Garten Müller')
        ->click('#manage-details')
        ->click('Auswahl speichern')
        ->assertSee('Deine Auswahl ist gespeichert.')
        ->assertNoJavaScriptErrors();

    visit(route('map.public', $embed))
        ->click('Tabelle')
        ->assertSee('Garten Müller')
        ->assertNoJavaScriptErrors();
});
