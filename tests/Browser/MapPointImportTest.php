<?php

use App\Data\SpreadsheetUploadData;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\User;
use App\Services\MapPointImportService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');

    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

/**
 * The browser plugin's HTTP server does not forward uploaded files yet, so uploads are created directly.
 * Uploading itself is covered by the feature tests.
 */
function uploadInstallations(Group $group): SpreadsheetUploadData
{
    return app(MapPointImportService::class)->storeUpload(UploadedFile::fake()->createWithContent('anlagen.csv', implode("\r\n", [
        'ID;Bezeichnung;kurze Beschreibung;Art der Anlage;Ortsbezeichnung;Y-Koordinate;X-Koordinate',
        '4;Balkonkraftwerk Musterweg 5;Steckersolargerät mit 800 Watt an der Südfassade;Photovoltaik;Musterstadt Nord;49,123456;8,654321',
    ])), $group);
}

test('an uploaded spreadsheet is mapped by its headers, previewed and imported', function (): void {
    $upload = uploadInstallations($this->group);

    visit(route('mappoints.import.create', ['token' => $upload->token]))
        ->assertNoJavaScriptErrors()
        ->assertSee('2. Spalten zuordnen')
        ->assertSee('Breitengrad')
        ->assertSee('Längengrad')
        ->click('Vorschau erstellen')
        ->assertSee('1 neu')
        ->assertSee('Balkonkraftwerk Musterweg 5')
        ->click('Import ausführen')
        ->assertPathIs('/mappoints')
        ->assertNoJavaScriptErrors();

    $mapPoint = MapPoint::sole();

    expect($mapPoint->title)->toBe('Balkonkraftwerk Musterweg 5')
        ->and($mapPoint->group_id)->toBe($this->group->id)
        ->and($mapPoint->location)->toBe('Musterstadt Nord')
        ->and($mapPoint->description)->toBe('Steckersolargerät mit 800 Watt an der Südfassade')
        ->and($mapPoint->coordinate->lat)->toEqualWithDelta(49.123456, 0.000001)
        ->and($mapPoint->published)->toBeFalse();
});

test('a spreadsheet copied from another initiative is refused with a message', function (): void {
    $foreignPoint = MapPoint::factory()->create(['title' => 'Wärmepumpe Musterhof']);

    $upload = app(MapPointImportService::class)->storeUpload(UploadedFile::fake()->createWithContent('anlagen.csv', implode("\r\n", [
        'ID;Bezeichnung;Y-Koordinate;X-Koordinate',
        "{$foreignPoint->uuid};Wärmepumpe Musterhof;49,123456;8,654321",
    ])), $this->group);

    visit(route('mappoints.import.create', ['token' => $upload->token]))
        ->click('Vorschau erstellen')
        ->assertSee('Dieser Kartenpunkt gehört zu einer anderen Initiative.')
        ->assertButtonDisabled('Import ausführen')
        ->assertNoJavaScriptErrors();

    expect(MapPoint::count())->toBe(1)
        ->and($foreignPoint->refresh()->group_id)->not->toBe($this->group->id);
});

test('new points can be published right away', function (): void {
    $upload = uploadInstallations($this->group);

    visit(route('mappoints.import.create', ['token' => $upload->token]))
        ->assertNoJavaScriptErrors()
        ->click('Nicht veröffentlicht')
        ->click('Öffentlich')
        ->click('Vorschau erstellen')
        ->assertSee('1 neu')
        ->click('Import ausführen')
        ->assertPathIs('/mappoints')
        ->assertNoJavaScriptErrors();

    expect(MapPoint::sole()->published)->toBeTrue();
});

test('a mapping the server refuses is explained on the page', function (): void {
    $upload = app(MapPointImportService::class)->storeUpload(UploadedFile::fake()->createWithContent('anlagen.csv', implode("\r\n", [
        'Titel;Nord;Ost',
        'Balkonkraftwerk Musterweg 5;49,123456;8,654321',
    ])), $this->group);

    visit(route('mappoints.import.create', ['token' => $upload->token]))
        ->click('Vorschau erstellen')
        ->assertSee('Diese Felder müssen einer Spalte zugeordnet sein: Breitengrad, Längengrad.')
        ->assertButtonDisabled('Import ausführen');

    expect(MapPoint::count())->toBe(0);
});
