<?php

use App\Enums\FieldType;
use App\Enums\SpreadsheetFormat;
use App\Exports\MapPointsExport;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointSpreadsheetMapping;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');
    Excel::fake();
    $this->freezeTime();

    $this->group = Group::factory()->create();
});

function actingAsExportAdmin(TestCase $test, Group $group): void
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);
    $test->actingAs($admin);
}

function exportFilename(SpreadsheetFormat $format): string
{
    return 'kartenpunkte-'.now()->format('Y-m-d').'.'.$format->value;
}

test('the export uses the columns of a saved mapping with the id column first', function (): void {
    actingAsExportAdmin($this, $this->group);
    $category = MapPointCategory::factory()->for($this->group)->create(['name' => 'Photovoltaik']);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['title' => 'Balkonkraftwerk Musterweg 5', 'category_id' => $category->id, 'published' => true]);
    $mapping = MapPointSpreadsheetMapping::factory()->for($this->group)->create([
        'columns' => [
            ['header' => 'Bezeichnung', 'field' => 'title'],
            ['header' => 'Leistung', 'field' => 'ignore'],
            ['header' => 'Art der Anlage', 'field' => 'category'],
            ['header' => 'Nummer', 'field' => 'id'],
            ['header' => 'Öffentlich', 'field' => 'published'],
        ],
    ]);

    $this->get(route('mappoints.export', ['mapping' => $mapping->uuid, 'format' => 'csv']))->assertOk();

    Excel::assertDownloaded(exportFilename(SpreadsheetFormat::CSV), fn (MapPointsExport $export): bool => $export->headings() === ['Nummer', 'Bezeichnung', 'Art der Anlage', 'Öffentlich']
        && $export->map($export->collection()->sole()) === [$mapPoint->uuid, 'Balkonkraftwerk Musterweg 5', 'Photovoltaik', 'ja']);
});

test('without a mapping all fields are exported', function (): void {
    actingAsExportAdmin($this, $this->group);

    $this->get(route('mappoints.export', ['format' => 'xlsx']))->assertOk();

    Excel::assertDownloaded(exportFilename(SpreadsheetFormat::XLSX), fn (MapPointsExport $export): bool => $export->headings() === [
        'ID', 'Titel', 'Beschreibung', 'Breitengrad', 'Längengrad', 'Ort', 'Kategorie', 'Veröffentlicht',
    ]);
});

test('the export contains the points of the group and its descendants only', function (): void {
    $childGroup = Group::factory()->create(['parent_id' => $this->group->id]);
    MapPoint::factory()->for($this->group)->create(['title' => 'Eigene Anlage']);
    MapPoint::factory()->for($childGroup)->create(['title' => 'Anlage der Untergruppe']);
    MapPoint::factory()->create(['title' => 'Fremde Anlage']);
    actingAsExportAdmin($this, $this->group);

    $this->get(route('mappoints.export', ['format' => 'ods']))->assertOk();

    Excel::assertDownloaded(exportFilename(SpreadsheetFormat::ODS), fn (MapPointsExport $export): bool => $export->collection()->pluck('title')->sort()->values()->all() === ['Anlage der Untergruppe', 'Eigene Anlage']);
});

test('the file extension follows the chosen format', function (SpreadsheetFormat $format): void {
    actingAsExportAdmin($this, $this->group);

    $this->get(route('mappoints.export', ['format' => $format->value]))->assertOk();

    Excel::assertDownloaded(exportFilename($format));
})->with(SpreadsheetFormat::cases());

test('mappings of other groups cannot be used for the export', function (): void {
    actingAsExportAdmin($this, $this->group);
    $foreignMapping = MapPointSpreadsheetMapping::factory()->create();

    $this->get(route('mappoints.export', ['mapping' => $foreignMapping->uuid, 'format' => 'csv']))->assertNotFound();
});

test('an unknown format is rejected', function (): void {
    actingAsExportAdmin($this, $this->group);

    $this->get(route('mappoints.export', ['format' => 'pdf']))->assertSessionHasErrors(['format']);
});

test('group members without admin rights cannot export', function (): void {
    $member = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($member, ['is_admin' => false]);
    app(SessionService::class)->actAsGroup($this->group, false);

    $this->actingAs($member)
        ->get(route('mappoints.export', ['format' => 'csv']))
        ->assertForbidden();
});

test('without a mapping the fields of the categories follow, named after their category', function (): void {
    actingAsExportAdmin($this, $this->group);
    $category = MapPointCategory::factory()->for($this->group)->create(['name' => 'Photovoltaik']);
    $fields = $category->findOrCreateFormDefinition()->fields();
    $power = $fields->create(['type' => FieldType::NUMBER, 'label' => 'PV-Leistung (kWp)', 'sort_order' => 0]);
    $kind = $fields->create(['type' => FieldType::SELECT, 'label' => 'Anlagenart', 'sort_order' => 1]);
    $kind->options()->create(['value' => 'dach', 'label' => 'Dachanlage', 'sort_order' => 0]);
    $contact = $fields->create(['type' => FieldType::PHONE, 'label' => 'Telefon', 'sort_order' => 2]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $category->id]);
    $power->createMapPointField($mapPoint, 9.9);
    $kind->createMapPointField($mapPoint, 'dach');
    $contact->createMapPointField($mapPoint, '06151 123456');

    $this->get(route('mappoints.export', ['format' => 'xlsx']))->assertOk();

    Excel::assertDownloaded(exportFilename(SpreadsheetFormat::XLSX), fn (MapPointsExport $export): bool => array_slice($export->headings(), -3) === ['Photovoltaik › PV-Leistung (kWp)', 'Photovoltaik › Anlagenart', 'Photovoltaik › Telefon']
        && array_slice($export->map($export->collection()->sole()), -3) === [9.9, 'Dachanlage', '06151 123456']);
});

test('a template column of a category field that was deleted since is left out', function (): void {
    actingAsExportAdmin($this, $this->group);
    $category = MapPointCategory::factory()->for($this->group)->create();
    $field = $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::TEXT, 'label' => 'Ansprechperson', 'sort_order' => 0]);
    $mapping = MapPointSpreadsheetMapping::factory()->for($this->group)->create([
        'columns' => [
            ['header' => 'Bezeichnung', 'field' => 'title'],
            ['header' => 'Kontakt', 'field' => 'category_field', 'category_field_id' => $field->uuid],
        ],
    ]);
    $field->delete();

    $this->get(route('mappoints.export', ['mapping' => $mapping->uuid, 'format' => 'csv']))->assertOk();

    Excel::assertDownloaded(exportFilename(SpreadsheetFormat::CSV), fn (MapPointsExport $export): bool => $export->headings() === ['ID', 'Bezeichnung']);
});
