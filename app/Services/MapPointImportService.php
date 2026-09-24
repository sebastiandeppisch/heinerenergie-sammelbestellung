<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MapPointImportResultData;
use App\Data\MapPointSpreadsheetColumnData;
use App\Data\SpreadsheetUploadData;
use App\Enums\MapPointSpreadsheetField;
use App\Enums\SpreadsheetFormat;
use App\Imports\MapPointCategoryResolver;
use App\Imports\MapPointKeyMatcher;
use App\Imports\MapPointsImport;
use App\Imports\SpreadsheetReader;
use App\Models\Group;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Imports map points from spreadsheets into a group.
 *
 * Reading the file, skipping empty rows, validating every row and reporting failures is done by
 * Laravel Excel through MapPointsImport. This service keeps the upload, checks that the mapping still
 * fits the file and wraps the run in a transaction: a preview imports for real and is rolled back,
 * so it reports exactly what an import would do.
 */
class MapPointImportService
{
    private const int PREVIEW_ROW_COUNT = 5;

    public function __construct(
        private readonly SpreadsheetUploadStore $uploads,
        private readonly SpreadsheetReader $reader,
    ) {}

    public function storeUpload(UploadedFile $file, Group $group): SpreadsheetUploadData
    {
        $upload = $this->uploads->store($file, $group);

        try {
            return $this->upload($upload->token, $group);
        } catch (ValidationException $exception) {
            $this->uploads->forget($upload->token);

            throw $exception;
        }
    }

    public function upload(string $token, Group $group): SpreadsheetUploadData
    {
        $upload = $this->uploads->find($token, $group);

        return SpreadsheetUploadData::fromContent(
            $upload,
            $this->reader->read($upload->path, SpreadsheetUploadStore::DISK),
            self::PREVIEW_ROW_COUNT,
        );
    }

    public function forget(string $token): void
    {
        $this->uploads->forget($token);
    }

    /**
     * Imports the uploaded rows. Nothing is kept when a row fails or when this is a dry run.
     *
     * @param  array<int, MapPointSpreadsheetColumnData>  $columns  One entry per spreadsheet column, in the order of the file.
     * @param  MapPointSpreadsheetField  $keyField  The field that identifies existing points. It is never changed by the import.
     *                                              IGNORE means no key field, so every row creates a new point.
     * @param  bool  $defaultPublished  Whether created points are public, used when no column is mapped to the published field.
     */
    public function run(string $token, array $columns, MapPointSpreadsheetField $keyField, bool $defaultPublished, Group $group, bool $dryRun): MapPointImportResultData
    {
        $upload = $this->uploads->find($token, $group);
        $headers = $this->reader->read($upload->path, SpreadsheetUploadStore::DISK)->headers;

        if (count($columns) !== count($headers)) {
            throw ValidationException::withMessages(['columns' => 'Die Spaltenvorlage passt nicht zu den Spalten der Datei.']);
        }

        $categories = new MapPointCategoryResolver($group);
        $import = new MapPointsImport($columns, new MapPointKeyMatcher($keyField, $group), $categories, $group, $defaultPublished);

        DB::beginTransaction();

        try {
            Excel::import($import, $upload->path, SpreadsheetUploadStore::DISK, SpreadsheetFormat::fromFilename($upload->path)?->excelType());
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        $result = new MapPointImportResultData(
            rows: $import->importedRows(),
            created_categories: $categories->createdNames(),
            errors: $import->errors(),
        );

        if ($dryRun || $result->hasErrors()) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        return $result;
    }
}
