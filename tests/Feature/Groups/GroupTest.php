<?php

use App\Context\GlobalGroupContext;
use App\Context\GroupContextContract;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->group = Group::create([
        'name' => 'Test Group',
        'description' => 'Test Description',
    ]);

    app(SessionService::class)->actWithoutSelectingGroup();
    app()->bind(GroupContextContract::class, GlobalGroupContext::class);

});

test('can create group', function () {
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

test('can update group with new logo', function () {
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

test('validates logo file size and type', function () {
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

test('deleting group removes logo', function () {
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
