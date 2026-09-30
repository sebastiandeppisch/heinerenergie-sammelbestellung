<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The outcome of an import run. A dry run reports the same result without keeping any change.
 */
#[TypeScript]
class MapPointImportResultData extends Data
{
    #[Computed]
    public int $created_count;

    #[Computed]
    public int $updated_count;

    /**
     * @param  array<int, MapPointImportRowData>  $rows
     * @param  array<int, string>  $created_categories
     * @param  array<int, SpreadsheetRowErrorData>  $errors
     * @param  array<int, SpreadsheetRowErrorData>  $warnings  Values left out without stopping the import.
     * @param  array<int, string>  $created_fields  Labels of the category fields created for columns.
     */
    public function __construct(
        public array $rows,
        public array $created_categories,
        public array $errors,
        public array $warnings = [],
        public array $created_fields = [],
    ) {
        $this->updated_count = count(array_filter($rows, fn (MapPointImportRowData $row): bool => $row->is_update));
        $this->created_count = count($rows) - $this->updated_count;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
