<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\MapPointSpreadsheetMappingData;
use App\Exports\MapPointsExport;
use App\Http\Controllers\Concerns\RequiresCurrentGroup;
use App\Http\Requests\ExportMapPointsRequest;
use App\Models\FormField;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\MapPointSpreadsheetMapping;
use App\Services\MapPointFieldService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Map point spreadsheets are small, so the export is built synchronously within the request,
 * without queued exports or chunked queries.
 */
class MapPointExportController extends Controller
{
    use RequiresCurrentGroup;

    /**
     * Exports the points visible to the current group, using a saved mapping or all fields. Either the points of all
     * categories with their category fields, or the points of one category and its sub categories with their
     * characteristics as well.
     */
    public function __invoke(ExportMapPointsRequest $request, GroupContextContract $groupContext, MapPointFieldService $fieldService): BinaryFileResponse
    {
        $group = $this->currentGroup($groupContext);
        $mappingUuid = $request->mappingUuid();
        $category = $request->category();
        $tree = MapPointCategory::tree();
        $mapPoints = MapPoint::query()->visibleFromGroup($group);
        $fields = $fieldService->fieldsUsableInGroup($group);
        $characteristics = new EloquentCollection;

        if ($category !== null) {
            $subtreeIds = $tree->subtreeIds([$category->id]);
            $fieldsCategoryIds = array_map($tree->fieldsCategoryId(...), $subtreeIds);
            $characteristics = MapPointCharacteristic::selectableFor($category->id, $tree)->with('category')->get();
            $mapPoints->whereIn('category_id', $subtreeIds);
            $fields = $fields
                ->filter(fn (FormField $field): bool => in_array($field->formDefinition?->mapPointCategory?->id, $fieldsCategoryIds, true))
                ->merge($fieldService->characteristicFieldsUsableInGroup($group)->filter(
                    fn (FormField $field): bool => $characteristics->contains('form_definition_id', $field->form_definition_id),
                ))
                ->values();
        }

        $columns = $mappingUuid === null
            ? MapPointsExport::defaultColumns($fields, $characteristics)
            : MapPointSpreadsheetMappingData::fromModel(
                MapPointSpreadsheetMapping::query()->ownedByGroup($group)->where('uuid', $mappingUuid)->firstOrFail(),
            )->columns;

        $format = $request->spreadsheetFormat();

        return Excel::download(
            new MapPointsExport(
                $mapPoints->with(['category', 'characteristics', 'fields.formField', 'fields.options'])->orderBy('id')->get(),
                $columns,
                $format,
                $fields,
                $tree,
                $fieldService,
                $characteristics,
            ),
            'kartenpunkte-'.now()->format('Y-m-d').'.'.$format->value,
            $format->excelType(),
        );
    }
}
