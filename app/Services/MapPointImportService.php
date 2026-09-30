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
use App\Models\MapPointCategory;
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
        private readonly MapPointFieldService $fieldService,
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

    /**
     * Creates a category field for every column that asks for one and assigns the column to it. The fields go where
     * the fields of the main category live, which is a parent category when the main category inherits its fields.
     * Adding them to the main category itself would replace the inherited fields instead.
     *
     * @param  array<int, MapPointSpreadsheetColumnData>  $columns
     * @return array{0: array<int, MapPointSpreadsheetColumnData>, 1: array<int, string>}
     */
    private function createNewFields(array $columns, ?MapPointCategory $mainCategory): array
    {
        $createdFields = [];

        if ($mainCategory === null) {
            return [$columns, $createdFields];
        }

        $fieldsCategory = MapPointCategory::findOrFail(MapPointCategory::tree()->fieldsCategoryId($mainCategory->id) ?? $mainCategory->id);
        $formDefinition = $fieldsCategory->findOrCreateFormDefinition();
        $sortOrder = (int) $formDefinition->fields()->max('sort_order');

        foreach ($columns as $index => $column) {
            if ($column->field !== MapPointSpreadsheetField::NEW_CATEGORY_FIELD || $column->new_field_type === null) {
                continue;
            }

            $field = $formDefinition->fields()->create([
                'type' => $column->new_field_type,
                'label' => $column->header,
                'required' => false,
                'sort_order' => ++$sortOrder,
            ]);

            $columns[$index] = new MapPointSpreadsheetColumnData($column->header, MapPointSpreadsheetField::CATEGORY_FIELD, $field->uuid);
            $createdFields[] = $column->header;
        }

        return [$columns, $createdFields];
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
     * @param  MapPointCategory|null  $mainCategory  Category names in the file are its sub categories, new points without a category go here.
     * @param  string|null  $newMainCategoryName  Creates the main category with this name instead.
     */
    public function run(
        string $token,
        array $columns,
        MapPointSpreadsheetField $keyField,
        bool $defaultPublished,
        Group $group,
        bool $dryRun,
        ?MapPointCategory $mainCategory = null,
        ?string $newMainCategoryName = null,
    ): MapPointImportResultData {
        $upload = $this->uploads->find($token, $group);
        $headers = $this->reader->read($upload->path, SpreadsheetUploadStore::DISK)->headers;

        if (count($columns) !== count($headers)) {
            throw ValidationException::withMessages(['columns' => 'Die Spaltenvorlage passt nicht zu den Spalten der Datei.']);
        }

        // The main category and new fields are created in the same transaction, so a preview leaves nothing behind.
        DB::beginTransaction();

        try {
            if ($newMainCategoryName !== null) {
                $mainCategory = MapPointCategory::create(['group_id' => $group->id, 'name' => $newMainCategoryName]);
            }

            $categories = new MapPointCategoryResolver($group, $mainCategory);

            if ($newMainCategoryName !== null && $mainCategory !== null) {
                $categories->reportCreated($mainCategory);
            }

            [$columns, $createdFields] = $this->createNewFields($columns, $mainCategory);

            $import = new MapPointsImport(
                $columns,
                new MapPointKeyMatcher($keyField, $group),
                $categories,
                $group,
                $this->fieldService,
                MapPointCategory::tree(),
                $defaultPublished,
                $mainCategory,
            );

            Excel::import($import, $upload->path, SpreadsheetUploadStore::DISK, SpreadsheetFormat::fromFilename($upload->path)?->excelType());
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        $result = new MapPointImportResultData(
            rows: $import->importedRows(),
            created_categories: $categories->createdNames(),
            errors: $import->errors(),
            warnings: $import->warnings(),
            created_fields: $createdFields,
        );

        if ($dryRun || $result->hasErrors()) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        return $result;
    }
}
