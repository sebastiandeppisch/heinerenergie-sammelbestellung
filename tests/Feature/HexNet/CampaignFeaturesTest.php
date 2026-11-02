<?php

use App\Events\MapPointCreatedByFormSubmission;
use App\HexNet\HexNetDefaults;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetGridService;
use App\HexNet\Services\HexNetSetupService;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    $this->group = Group::factory()->create();
    $this->hexNet = HexNet::factory()->active()->withPlayArea()->create(['group_id' => $this->group->id]);
    $this->category = app(HexNetSetupService::class)->createCategories($this->hexNet, 'Trittsteine', 'Trittsteine 2027', array_slice(HexNetDefaults::MEASURES, 0, 3));
    $this->measures = $this->category->parent->characteristics()->with('formDefinition.fields')->get();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function hexNetStone(MapPointCategory $category, array $attributes = []): MapPoint
{
    return MapPoint::factory()->create([
        'group_id' => $category->group_id,
        'category_id' => $category->id,
        'lat' => 49.8728,
        'lng' => 8.6512,
        ...$attributes,
    ]);
}

test('measures from the template get a placeholder icon and an optional public photo field', function (): void {
    $measure = $this->measures->first();
    $photo = $measure->formDefinition->fields->first();

    expect($measure->icon_path)->toBe('characteristics/hexnet-wheat.svg')
        ->and(Storage::disk('public')->exists('characteristics/hexnet-wheat.svg'))->toBeTrue()
        ->and($photo->type->value)->toBe('image')
        ->and($photo->max_images)->toBe(1)
        ->and($photo->required)->toBeFalse()
        ->and($measure->publicFields()->pluck('id')->all())->toBe([$photo->id]);
});

test('the join form asks whether others may get in contact', function (): void {
    $form = app(HexNetSetupService::class)->createForm($this->hexNet->refresh());

    expect($form->mapPointCreator->contactEnabledField?->label)->toBe('Kontakt');
});

test('stepping stones are counted per measure', function (): void {
    [$a, $b] = [$this->measures[0], $this->measures[1]];
    hexNetStone($this->category)->characteristics()->attach([$a->id, $b->id]);
    hexNetStone($this->category)->characteristics()->attach([$a->id]);

    $counts = app(HexNetGridService::class)->measureCounts($this->hexNet->fresh());

    expect(array_sum($counts[$a->uuid]))->toBe(2)
        ->and(array_sum($counts[$b->uuid]))->toBe(1)
        ->and($counts)->not->toHaveKey($this->measures[2]->uuid);
});

test('a HexaNetz with auto publish publishes new stepping stones', function (bool $autoPublish): void {
    $this->hexNet->update(['auto_publish' => $autoPublish]);
    $point = hexNetStone($this->category, ['published' => false]);

    event(new MapPointCreatedByFormSubmission($point, FormSubmission::factory()->create()));

    expect($point->fresh()->published)->toBe($autoPublish);
})->with([true, false]);

test('visible stepping stones with contact can be contacted through the campaign', function (): void {
    $withContact = hexNetStone($this->category, ['published' => true, 'contact_email' => 'owner@example.com', 'contact_email_verified_at' => now(), 'contact_enabled' => true]);
    $hidden = hexNetStone($this->category, ['published' => false, 'contact_email' => 'owner@example.com', 'contact_email_verified_at' => now(), 'contact_enabled' => true]);
    $noContact = hexNetStone($this->category, ['published' => true]);

    $this->get(route('hexnet.contact.create', [$this->hexNet->slug, $withContact]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Create')->where('backUrl', route('hexnet.public', $this->hexNet->slug)));
    $this->get(route('hexnet.contact.create', [$this->hexNet->slug, $hidden]))->assertNotFound();
    $this->get(route('hexnet.contact.create', [$this->hexNet->slug, $noContact]))->assertNotFound();

    $this->post(route('hexnet.contact.store', [$this->hexNet->slug, $withContact]), ['name' => 'Anna', 'email' => 'anna@example.com', 'message' => 'Hallo, schöner Garten!'])
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Sent'));

    expect($withContact->contactRequests()->count())->toBe(1);
});

test('the public page lists the measures and their counts', function (): void {
    hexNetStone($this->category, ['published' => true])->characteristics()->attach([$this->measures[0]->id]);

    $this->get(route('hexnet.public', $this->hexNet->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('measures', 3)->has('measureCounts.'.$this->measures[0]->uuid));
});
