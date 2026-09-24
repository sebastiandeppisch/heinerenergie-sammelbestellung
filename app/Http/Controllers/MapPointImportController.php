<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\MapPointSpreadsheetFieldData;
use App\Data\MapPointSpreadsheetMappingData;
use App\Enums\MapPointSpreadsheetField;
use App\Http\Controllers\Concerns\RequiresCurrentGroup;
use App\Http\Requests\RunMapPointImportRequest;
use App\Http\Requests\UploadMapPointImportRequest;
use App\Models\MapPoint;
use App\Services\MapPointImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Map point spreadsheets are small, so uploads are read and imported synchronously within the request,
 * without queued imports or chunk reading. Imported points belong to the current group.
 */
class MapPointImportController extends Controller
{
    use RequiresCurrentGroup;

    public function create(Request $request, GroupContextContract $groupContext, MapPointImportService $imports): Response
    {
        $this->authorize('import', MapPoint::class);
        $group = $this->currentGroup($groupContext);
        $token = $request->query('token');

        return Inertia::render('MapPoints/Import', [
            'mappings' => MapPointSpreadsheetMappingData::forGroup($group),
            'fields' => array_map(
                MapPointSpreadsheetFieldData::fromEnum(...),
                MapPointSpreadsheetField::cases(),
            ),
            'upload' => is_string($token) ? $imports->upload($token, $group) : null,
        ]);
    }

    public function upload(UploadMapPointImportRequest $request, GroupContextContract $groupContext, MapPointImportService $imports): RedirectResponse
    {
        $upload = $imports->storeUpload($request->uploadedFile(), $this->currentGroup($groupContext));

        return redirect()->route('mappoints.import.create', ['token' => $upload->token]);
    }

    /**
     * Runs the import without keeping any change and reports what it would do.
     * Answers with 200 instead of the 201 laravel-data uses for POST requests, because a preview creates nothing.
     */
    public function preview(RunMapPointImportRequest $request, GroupContextContract $groupContext, MapPointImportService $imports): JsonResponse
    {
        return response()->json(
            $imports->run($request->token(), $request->columns(), $request->keyField(), $request->defaultPublished(), $this->currentGroup($groupContext), dryRun: true),
        );
    }

    public function store(RunMapPointImportRequest $request, GroupContextContract $groupContext, MapPointImportService $imports): RedirectResponse
    {
        $result = $imports->run($request->token(), $request->columns(), $request->keyField(), $request->defaultPublished(), $this->currentGroup($groupContext), dryRun: false);

        if ($result->hasErrors()) {
            throw ValidationException::withMessages([
                'import' => 'Die Datei enthält fehlerhafte Zeilen, deshalb wurde nichts importiert. Bitte prüfe die Vorschau.',
            ]);
        }

        $imports->forget($request->token());

        return redirect()->route('mappoints.index')->with(
            'success',
            "Import abgeschlossen: {$result->created_count} neue und {$result->updated_count} aktualisierte Kartenpunkte",
        );
    }
}
