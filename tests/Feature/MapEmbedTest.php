<?php

use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->regularUser = User::factory()->create(['is_admin' => false]);

    $this->group = Group::create([
        'name' => 'Test Group',
        'description' => 'Test Description',
    ]);

    app(SessionService::class)->actAsGroup($this->group);
    Config::set('app.group_context', 'global');
});

test('admin can view map embeds index', function (): void {
    MapEmbed::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)
        ->get(route('map-embeds.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/Embeds/Index')
        ->has('mapEmbeds', 2)
    );
});

test('regular user cannot access map embeds', function (): void {
    $response = $this->actingAs($this->regularUser)
        ->get(route('map-embeds.index'));

    $response->assertStatus(403);
});

test('admin can create a map embed with categories', function (): void {
    $categories = MapPointCategory::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)
        ->post(route('map-embeds.store'), [
            'name' => 'Startseite Sidebar',
            'category_ids' => $categories->pluck('uuid')->all(),
            'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
            'zoom' => 15,
            'aspect_ratio_width' => 16,
            'aspect_ratio_height' => 9,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('map_embeds', ['name' => 'Startseite Sidebar']);

    $mapEmbed = MapEmbed::where('name', 'Startseite Sidebar')->firstOrFail();
    expect($mapEmbed->mapPointCategories)->toHaveCount(2);
});

test('map embed requires at least one category', function (): void {
    $response = $this->actingAs($this->admin)
        ->post(route('map-embeds.store'), [
            'name' => 'Ohne Kategorien',
            'category_ids' => [],
        ]);

    $response->assertSessionHasErrors(['category_ids']);
});

test('map embed rejects unknown category ids', function (): void {
    $response = $this->actingAs($this->admin)
        ->post(route('map-embeds.store'), [
            'name' => 'Ungueltig',
            'category_ids' => ['not-a-real-uuid'],
        ]);

    $response->assertSessionHasErrors(['category_ids.0']);
});

test('map embed rejects zoom levels outside the allowed range', function (): void {
    $category = MapPointCategory::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('map-embeds.store'), [
            'name' => 'Zoom zu hoch',
            'category_ids' => [$category->uuid],
            'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
            'zoom' => 25,
        ]);

    $response->assertSessionHasErrors(['zoom']);
});

test('map embed rejects invalid coordinates', function (): void {
    $category = MapPointCategory::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('map-embeds.store'), [
            'name' => 'Ungueltige Koordinate',
            'category_ids' => [$category->uuid],
            'coordinate' => ['lat' => 200, 'lng' => 8.6512],
            'zoom' => 15,
        ]);

    $response->assertSessionHasErrors(['coordinate']);
});

test('admin can set the center and zoom of a map embed', function (): void {
    $category = MapPointCategory::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('map-embeds.store'), [
            'name' => 'Individueller Ausschnitt',
            'category_ids' => [$category->uuid],
            'coordinate' => ['lat' => 52.52, 'lng' => 13.405],
            'zoom' => 12,
            'aspect_ratio_width' => 16,
            'aspect_ratio_height' => 9,
        ]);

    $response->assertRedirect();

    $mapEmbed = MapEmbed::where('name', 'Individueller Ausschnitt')->firstOrFail();
    expect($mapEmbed->coordinate->lat)->toEqualWithDelta(52.52, 0.0001);
    expect($mapEmbed->coordinate->lng)->toEqualWithDelta(13.405, 0.0001);
    expect($mapEmbed->zoom)->toBe(12);
});

test('admin can change categories of a map embed without changing its link', function (): void {
    $categoryA = MapPointCategory::factory()->create();
    $categoryB = MapPointCategory::factory()->create();

    $mapEmbed = MapEmbed::factory()->create();
    $mapEmbed->mapPointCategories()->sync([$categoryA->id]);

    $originalUuid = $mapEmbed->uuid;

    $response = $this->actingAs($this->admin)
        ->put(route('map-embeds.update', $mapEmbed), [
            'name' => $mapEmbed->name,
            'category_ids' => [$categoryA->uuid, $categoryB->uuid],
            'coordinate' => ['lat' => $mapEmbed->lat, 'lng' => $mapEmbed->lng],
            'zoom' => $mapEmbed->zoom,
            'aspect_ratio_width' => $mapEmbed->aspect_ratio_width,
            'aspect_ratio_height' => $mapEmbed->aspect_ratio_height,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $mapEmbed->refresh();
    expect($mapEmbed->uuid)->toBe($originalUuid);
    expect($mapEmbed->mapPointCategories()->pluck('map_point_categories.id'))
        ->toContain($categoryA->id, $categoryB->id);
});

test('group admin can create a map embed for their group', function (): void {
    Config::set('app.group_context', 'group');

    $groupAdmin = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($groupAdmin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);

    $category = MapPointCategory::factory()->create();

    $response = $this->actingAs($groupAdmin)
        ->post(route('map-embeds.store'), [
            'name' => 'Gruppen-Einbettung',
            'category_ids' => [$category->uuid],
            'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
            'zoom' => 15,
            'aspect_ratio_width' => 16,
            'aspect_ratio_height' => 9,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('map_embeds', ['name' => 'Gruppen-Einbettung']);
});

test('group member without admin rights cannot create a map embed', function (): void {
    Config::set('app.group_context', 'group');

    $groupMember = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($groupMember, ['is_admin' => false]);
    app(SessionService::class)->actAsGroup($this->group, false);

    $category = MapPointCategory::factory()->create();

    $response = $this->actingAs($groupMember)
        ->post(route('map-embeds.store'), [
            'name' => 'Sollte fehlschlagen',
            'category_ids' => [$category->uuid],
        ]);

    $response->assertStatus(403);
});

test('admin can delete a map embed', function (): void {
    $mapEmbed = MapEmbed::factory()->create();

    $response = $this->actingAs($this->admin)
        ->delete(route('map-embeds.destroy', $mapEmbed));

    $response->assertRedirect();
    $response->assertSessionHas('info');

    $this->assertDatabaseMissing('map_embeds', ['id' => $mapEmbed->id]);
});
