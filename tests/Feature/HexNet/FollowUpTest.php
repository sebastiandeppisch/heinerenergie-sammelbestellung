<?php

use App\HexNet\HexNetDefaults;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetSetupService;
use App\Mail\FormSubmissionConfirmation;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Storage::fake('public');
    Mail::fake();
    $this->group = Group::factory()->create();
    $this->hexNet = HexNet::factory()->active()->withPlayArea()->create(['group_id' => $this->group->id, 'name' => 'Darmstadt 2027']);
    $this->category = app(HexNetSetupService::class)->createCategories($this->hexNet, 'Trittsteine', 'Trittsteine 2027', array_slice(HexNetDefaults::MEASURES, 0, 2));
    $this->form = app(HexNetSetupService::class)->createForm($this->hexNet->refresh());
    $this->followUp = $this->form->mapPointCreator->followUpFormDefinition;
});

test('the category gets the story fields, only the meeting stays private', function (): void {
    $category = MapPointCategory::find($this->category->id);
    $fields = $category->formDefinition->fields()->orderBy('sort_order')->get();

    expect($fields->pluck('label')->all())->toBe(array_column(HexNetDefaults::STORY_FIELDS, 'label'))
        ->and($fields->pluck('type')->map->value->all())->toBe(['image', 'image', 'image', 'textarea', 'checkbox'])
        ->and($category->publicFields()->pluck('label')->all())->not->toContain('Ich möchte ein Stadtnatur-Treffen in meinem Garten anbieten')
        ->and($category->publicFields()->count())->toBe(4)
        ->and($fields->last()->help_text)->toBe('Wir melden uns mit Terminvorschlägen');

    app(HexNetSetupService::class)->createCategories($this->hexNet, 'Trittsteine', 'Trittsteine 2027', []);
    expect($category->formDefinition->fields()->count())->toBe(5);
});

test('the join form leaves the story fields to the linked follow-up form', function (): void {
    $storyLabels = array_column(HexNetDefaults::STORY_FIELDS, 'label');

    expect($this->followUp)->toBeInstanceOf(FormDefinition::class)
        ->and($this->followUp->name)->toBe('Erzähl mehr – Darmstadt 2027')
        ->and($this->followUp->isMapPointFollowUp())->toBeTrue()
        ->and($this->form->mapPointCreator->follow_up_button_text)->toBe('Erzähl mehr')
        ->and(array_intersect($this->form->fields()->pluck('label')->all(), $storyLabels))->toBe([])
        ->and(array_values(array_intersect($this->followUp->fields()->pluck('label')->all(), $storyLabels)))->toBe($storyLabels);
});

test('after the confirmation the highlight links to the follow-up form with the token of the mail', function (): void {
    $fields = $this->form->fields()->get()->keyBy('label');
    $measure = $this->category->parent->characteristics()->first();

    $this->post(route('form.submit', $this->form), [
        $fields['Titel']->uuid => 'Garten Müller',
        $fields['Beschreibung']->uuid => 'Mit Teich',
        $fields['Standort']->uuid => ['lat' => 49.8728, 'lng' => 8.6512],
        $fields['E-Mail-Adresse']->uuid => 'erika@example.org',
        $fields['Maßnahmen']->uuid => [$measure->uuid],
        '_consent_show' => true,
        '_consent_details' => true,
    ])->assertSessionHasNoErrors();

    $token = basename((string) parse_url(Mail::queued(FormSubmissionConfirmation::class)->sole()->confirmationUrl, PHP_URL_PATH));

    $props = $this->post(route('form.confirm', $token))->viewData('page')['props'];

    expect($props['hexNetHighlight']['follow_up_label'])->toBe('Erzähl mehr')
        ->and($props['hexNetHighlight']['follow_up_url'])->toBe($props['notices'][0]['url'])
        ->and($props['notices'][0]['url'])->toEndWith('/ergaenzen');
});
