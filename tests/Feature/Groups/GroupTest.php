<?php

use App\Enums\AdviceStatusResult;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');

    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->group = Group::create([
        'name' => 'Test Group',
        'description' => 'Test Description',
    ]);

    app(SessionService::class)->actWithoutSelectingGroup();
    Config::set('app.group_context', 'global');
});

it('can create group via factory', function (): void {
    $group = Group::factory()->create();
    expect($group->name)->not->toBeEmpty();
});

test('can create group', function (): void {
    actingAs($this->admin);

    $response = post(route('groups.store'), [
        'name' => 'New Group',
        'description' => 'New Description',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(Group::where('name', 'New Group')->first())
        ->name->toBe('New Group')
        ->description->toBe('New Description');
});

test('a created group gets one status per result', function (): void {
    actingAs($this->admin);

    post(route('groups.store'), [
        'name' => 'New Group',
        'description' => 'New Description',
    ])->assertRedirect();

    $statuses = Group::where('name', 'New Group')->firstOrFail()->ownStatuses;

    expect($statuses->pluck('result')->all())->toEqualCanonicalizing(AdviceStatusResult::cases())
        ->and($statuses->pluck('name')->all())->toEqualCanonicalizing([
            'Offen',
            'In Bearbeitung',
            'Fertig - erfolgreich',
            'Fertig - nicht erfolgreich',
        ]);
});

test('can update group with new logo', function (): void {
    actingAs($this->admin);

    $oldLogo = UploadedFile::fake()->image('old-logo.jpg');
    $newLogo = UploadedFile::fake()->image('new-logo.jpg');

    // First, create a group with a logo
    $this->group->update([
        'logo_path' => $oldLogo->store('group-logos', 'public'),
    ]);
    $oldLogoPath = $this->group->logo_path;

    // Then update the group with a new logo
    $response = put(route('groups.update', $this->group), [
        'name' => 'Updated Group',
        'description' => 'Updated Description',
        'accepts_transfers' => true,
        'logo' => $newLogo,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->group->refresh();

    // Assert old logo is deleted
    Storage::disk('public')->assertMissing($oldLogoPath);

    // Assert new logo exists
    expect($this->group)
        ->logo_path->not->toBeNull()
        ->logo_path->not->toBe($oldLogoPath);

    Storage::disk('public')->assertExists($this->group->logo_path);
});

test('validates logo file size and type', function (): void {
    actingAs($this->admin);

    // Test file too large (over 1MB)
    $largeLogo = UploadedFile::fake()->image('large-logo.jpg')->size(2000);

    $response = put(route('groups.update', $this->group), [
        'name' => 'New Group',
        'description' => 'New Description',
        'logo' => $largeLogo,
    ]);

    $response->assertSessionHasErrors('logo');

    // Test invalid file type
    $invalidFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response = put(route('groups.update', $this->group), [
        'name' => 'New Group',
        'description' => 'New Description',
        'logo' => $invalidFile,
    ]);

    $response->assertSessionHasErrors('logo');
});

test('can update primary hue', function (): void {
    actingAs($this->admin);

    $response = put(route('groups.update', $this->group), [
        'name' => 'Test Group',
        'primary_hue' => 180.5,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($this->group->refresh()->primary_hue)->toBe(180.5);
});

test('can update primary lightness and chroma', function (): void {
    actingAs($this->admin);

    $response = put(route('groups.update', $this->group), [
        'name' => 'Test Group',
        'primary_hue' => 200.0,
        'primary_lightness' => 0.65,
        'primary_chroma' => 0.18,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->group->refresh();
    expect($this->group->primary_hue)->toBe(200.0)
        ->and($this->group->primary_lightness)->toBe(0.65)
        ->and($this->group->primary_chroma)->toBe(0.18);
});

test('can reset primary color to null', function (): void {
    $this->group->update(['primary_hue' => 120.0, 'primary_lightness' => 0.7, 'primary_chroma' => 0.2]);

    actingAs($this->admin);

    $response = put(route('groups.update', $this->group), [
        'name' => 'Test Group',
        'primary_hue' => null,
        'primary_lightness' => null,
        'primary_chroma' => null,
    ]);

    $response->assertRedirect();

    $this->group->refresh();
    expect($this->group->primary_hue)->toBeNull()
        ->and($this->group->primary_lightness)->toBeNull()
        ->and($this->group->primary_chroma)->toBeNull();
});

test('rejects primary_hue outside 0-360 range', function (mixed $invalidHue): void {
    actingAs($this->admin);

    $response = put(route('groups.update', $this->group), [
        'name' => 'Test Group',
        'primary_hue' => $invalidHue,
    ]);

    $response->assertSessionHasErrors('primary_hue');
})->with([
    'above max' => 361,
    'negative' => -1,
]);

test('rejects primary_lightness outside 0-1 range', function (mixed $invalidValue): void {
    actingAs($this->admin);

    $response = put(route('groups.update', $this->group), [
        'name' => 'Test Group',
        'primary_lightness' => $invalidValue,
    ]);

    $response->assertSessionHasErrors('primary_lightness');
})->with([
    'above max' => 1.1,
    'negative' => -0.1,
]);

test('rejects primary_chroma outside 0-0.4 range', function (mixed $invalidValue): void {
    actingAs($this->admin);

    $response = put(route('groups.update', $this->group), [
        'name' => 'Test Group',
        'primary_chroma' => $invalidValue,
    ]);

    $response->assertSessionHasErrors('primary_chroma');
})->with([
    'above max' => 0.41,
    'negative' => -0.01,
]);

test('theme props contain primary color values when group is selected', function (): void {
    $this->group->update(['primary_hue' => 120.5, 'primary_lightness' => 0.65, 'primary_chroma' => 0.18]);

    $user = User::factory()->create();
    $this->group->users()->attach($user, ['is_admin' => true]);

    actingAs($user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true])
        ->assertSessionHasNoErrors();

    $response = get(route('groups.index'));

    $response->assertInertia(
        fn ($page) => $page
            ->where('theme.primaryHue', 120.5)
            ->where('theme.primaryLightness', 0.65)
            ->where('theme.primaryChroma', 0.18)
    );
});

test('theme props are null when group has no color set', function (): void {
    $user = User::factory()->create();
    $this->group->users()->attach($user, ['is_admin' => true]);

    actingAs($user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true])
        ->assertSessionHasNoErrors();

    $response = get(route('groups.index'));

    $response->assertInertia(
        fn ($page) => $page
            ->where('theme.primaryHue', null)
            ->where('theme.primaryLightness', null)
            ->where('theme.primaryChroma', null)
    );
});

test('deleting group removes logo', function (): void {
    actingAs($this->admin);

    $logo = UploadedFile::fake()->image('logo.jpg');
    $this->group->update([
        'logo_path' => $logo->store('group-logos', 'public'),
    ]);
    $logoPath = $this->group->logo_path;

    Storage::disk('public')->assertExists($logoPath);

    $response = delete(route('groups.destroy', $this->group));

    $response->assertRedirectToRoute('groups.index');
    $response->assertSessionHas('success');

    // Assert logo file is deleted
    Storage::disk('public')->assertMissing($logoPath);
    expect(Group::find($this->group->id))->toBeNull();
});
