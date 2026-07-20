<?php

use App\Contracts\NextcloudFileClientContract;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MockNextcloudFileClient;

uses(RefreshDatabase::class);

beforeEach(function () {

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

test('unlinked advice shows nextcloud action buttons', function () {
    $advice = Advice::factory()->create([
        'advisor_id' => $this->user->id,
        'group_id' => $this->group->id,
    ]);

    visit(route('advices.show', $advice))
        ->assertNoSmoke()
        ->assertSee('Dateien (Nextcloud)')
        ->assertSee('Ordner anlegen')
        ->assertSee('Ordner verknüpfen');
});

test('create folder dialog opens and links a folder', function () {
    $advice = Advice::factory()->create([
        'advisor_id' => $this->user->id,
        'group_id' => $this->group->id,
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
    ]);

    visit(route('advices.show', $advice))
        ->assertNoSmoke()
        ->click('Ordner anlegen')
        ->assertSee('Nextcloud-Ordner anlegen')
        ->clear('Ordnername')
        ->type('Ordnername', 'beratung-mustermann-test')
        ->press('Anlegen')
        ->assertSee('Dateien (Nextcloud)')
        ->assertDontSee('Ordner anlegen');

    $advice->refresh();
    expect($advice->nextcloud_folder_id)->not->toBeNull();
});

test('link folder dialog opens and shows search results', function () {
    $advice = Advice::factory()->create([
        'advisor_id' => $this->user->id,
        'group_id' => $this->group->id,
        'last_name' => 'mueller',
    ]);

    visit(route('advices.show', $advice))
        ->assertNoSmoke()
        ->click('Ordner verknüpfen')
        ->assertSee('Nextcloud-Ordner verknüpfen')
        ->assertSee('Suche')
        ->assertSee('Browser');
});

test('linked advice shows file list and unlink button', function () {
    $advice = Advice::factory()->create([
        'advisor_id' => $this->user->id,
        'group_id' => $this->group->id,
        'nextcloud_folder_id' => '10',
        'nextcloud_folder_path' => '/Beratungen/Offen/2024-01-15_beratung-mueller',
    ]);

    visit(route('advices.show', $advice))
        ->assertNoSmoke()
        ->assertSee('Dateien (Nextcloud)')
        ->assertSee('dokument.pdf')
        ->assertSee('foto.jpg')
        ->assertSee('Datei hochladen');
});

test('unlink removes folder association', function () {
    $advice = Advice::factory()->create([
        'advisor_id' => $this->user->id,
        'group_id' => $this->group->id,
        'nextcloud_folder_id' => '10',
        'nextcloud_folder_path' => '/Beratungen/Offen/2024-01-15_beratung-mueller',
    ]);

    visit(route('advices.show', $advice))
        ->assertNoSmoke()
        ->assertSee('dokument.pdf')
        ->click('Verknüpfung aufheben')
        ->wait(1)
        ->assertSee('Ordner anlegen')
        ->assertSee('Ordner verknüpfen')
        ->assertDontSee('dokument.pdf');

    $advice->refresh();
    expect($advice->nextcloud_folder_id)->toBeNull();
});
