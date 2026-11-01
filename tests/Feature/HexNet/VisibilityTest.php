<?php

use App\Enums\PublicConsent;
use App\HexNet\Models\HexNet;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->group = Group::factory()->create();
    $this->category = MapPointCategory::create(['group_id' => $this->group->id, 'name' => 'Trittsteine']);
    $this->hexNet = HexNet::factory()->active()->withPlayArea()->create(['group_id' => $this->group->id, 'jitter_m' => 40]);
    $this->hexNet->categories()->sync([$this->category->id]);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function hexNetVisibilityPoint(MapPointCategory $category, array $attributes): MapPoint
{
    return MapPoint::factory()->create([
        'group_id' => $category->group_id,
        'category_id' => $category->id,
        'lat' => 49.8731234,
        'lng' => 8.6514321,
        'contact_email' => 'secret@example.com',
        ...$attributes,
    ]);
}

test('only published points without refused consent are shown', function (): void {
    $visible = hexNetVisibilityPoint($this->category, ['published' => true, 'public_consent' => PublicConsent::NOT_REQUIRED]);
    hexNetVisibilityPoint($this->category, ['published' => false, 'public_consent' => PublicConsent::NOT_REQUIRED]);
    hexNetVisibilityPoint($this->category, ['published' => true, 'public_consent' => PublicConsent::REFUSED]);

    $this->get(route('hexnet.public', $this->hexNet->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('HexNet/Public')
            ->has('points', 1)
            ->where('points.0.point.id', $visible->uuid)
            ->where('stats.total_points', 3));
});

test('neither the contact address nor the exact coordinates reach the browser', function (): void {
    hexNetVisibilityPoint($this->category, ['published' => true]);

    $content = $this->get(route('hexnet.public', $this->hexNet->slug))->assertOk()->getContent();

    expect($content)->not->toContain('secret@example.com')
        ->and($content)->not->toContain('49.8731234')
        ->and($content)->not->toContain('8.6514321');
});

test('drafts are not public', function (): void {
    $this->hexNet->update(['status' => 'draft']);

    $this->get(route('hexnet.public', $this->hexNet->slug))->assertNotFound();
});
