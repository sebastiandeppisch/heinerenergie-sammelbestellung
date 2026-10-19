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

uses(RefreshDatabase::class);

test('a person consents to the point only and later shows the details on the manage page', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $form->update(['is_active' => true, 'requires_email_confirmation' => true]);
    $category = MapPointCategory::factory()->for($form->group)->withoutImage()->create(['name' => 'Naturgarten']);
    $email = $form->fields()->create(['type' => FieldType::EMAIL, 'label' => 'E-Mail', 'required' => true, 'sort_order' => 10]);
    $config->category()->associate($category);
    $config->contactEmailField()->associate($email);
    $config->save();
    $embed = MapEmbed::factory()->for($form->group)->create(['show_table' => true]);
    $embed->mapPointCategories()->sync([$category->id]);

    $page = visit(route('form.show', $form))
        ->fill("#field_{$config->titleField->uuid}", 'Garten Müller')
        ->fill("#field_{$config->descriptionField->uuid}", 'Mit Teich und Totholz');

    // Places the pin in the middle of the map once the map listens to clicks, a real click may hit a tile that is still loading.
    $pinIsPlaced = $page->script('(async () => {
        for (let attempt = 0; attempt < 50; attempt++) {
            const map = document.querySelector(".leaflet-container");
            if (map) {
                const box = map.getBoundingClientRect();
                map.dispatchEvent(new MouseEvent("click", { bubbles: true, clientX: box.left + box.width / 2, clientY: box.top + box.height / 2 }));
                await new Promise((resolve) => setTimeout(resolve, 100));
                if (document.querySelectorAll(".leaflet-marker-icon").length > 1) {
                    return true;
                }
            }
            await new Promise((resolve) => setTimeout(resolve, 100));
        }
        return false;
    })()');
    expect($pinIsPlaced)->toBeTrue();

    $page->fill("#field_{$email->uuid}", 'erika@example.com')
        ->click('Meinen Eintrag auf der Karte zeigen')
        ->assertSee('zusätzlich diese Angaben zeigen: Titel, Beschreibung')
        ->click('Absenden')
        ->assertSee('Fast geschafft, bitte bestätige deine E-Mail-Adresse.')
        ->assertNoJavaScriptErrors();

    $token = app(FormSubmissionConfirmationService::class)->issueToken(FormSubmission::sole());
    visit(route('form.confirm.show', $token))
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
