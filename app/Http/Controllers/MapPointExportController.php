<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\MapPointSpreadsheetMappingData;
use App\Exports\MapPointsExport;
use App\Http\Controllers\Concerns\RequiresCurrentGroup;
use App\Http\Requests\ExportMapPointsRequest;
use App\Models\MapPoint;
use App\Models\MapPointSpreadsheetMapping;
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
     * Exports the points visible to the current group, using a saved mapping or all fields.
     */
    public function __invoke(ExportMapPointsRequest $request, GroupContextContract $groupContext): BinaryFileResponse
    {
        $group = $this->currentGroup($groupContext);
        $mappingUuid = $request->mappingUuid();

        $columns = $mappingUuid === null
            ? MapPointsExport::defaultColumns()
            : MapPointSpreadsheetMappingData::fromModel(
                MapPointSpreadsheetMapping::query()->ownedByGroup($group)->where('uuid', $mappingUuid)->firstOrFail(),
            )->columns;

        $mapPoints = MapPoint::query()->visibleFromGroup($group)->with('category')->orderBy('id')->get();
        $format = $request->spreadsheetFormat();

        return Excel::download(
            new MapPointsExport($mapPoints, $columns, $format),
            'kartenpunkte-'.now()->format('Y-m-d').'.'.$format->value,
            $format->excelType(),
        );
    }
}
