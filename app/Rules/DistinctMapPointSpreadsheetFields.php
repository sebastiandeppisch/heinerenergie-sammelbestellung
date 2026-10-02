<?php

declare(strict_types=1);

namespace App\Rules;

use App\Data\MapPointSpreadsheetColumnData;
use App\Enums\MapPointSpreadsheetField;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every map point field, and every category field, may be assigned to at most one spreadsheet column.
 */
class DistinctMapPointSpreadsheetFields implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $targets = [];

        foreach ($value as $column) {
            $field = is_array($column) && is_string($column['field'] ?? null)
                ? MapPointSpreadsheetField::tryFrom($column['field'])
                : null;

            // New fields are told apart by the header of their column, which is unique in a file.
            if ($field === MapPointSpreadsheetField::FIELD) {
                $targets[] = MapPointSpreadsheetColumnData::fieldTarget((string) ($column['form_field_id'] ?? ''));
            } elseif ($field !== null && ! in_array($field, [MapPointSpreadsheetField::IGNORE, MapPointSpreadsheetField::NEW_CATEGORY_FIELD], true)) {
                $targets[] = $field->value;
            }
        }

        // Category fields are named by their id, which means nothing to people.
        $duplicateLabels = collect($targets)
            ->duplicates()
            ->unique()
            ->map(fn (string $target): string => MapPointSpreadsheetField::tryFrom($target)?->label() ?? MapPointSpreadsheetField::FIELD->label())
            ->unique();

        if ($duplicateLabels->isNotEmpty()) {
            $fail('Diese Felder sind mehreren Spalten zugeordnet: '.$duplicateLabels->implode(', ').'.');
        }
    }
}
