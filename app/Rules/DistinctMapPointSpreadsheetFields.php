<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\MapPointSpreadsheetField;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every map point field may be assigned to at most one spreadsheet column.
 */
class DistinctMapPointSpreadsheetFields implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $fields = [];

        foreach ($value as $column) {
            $field = is_array($column) && is_string($column['field'] ?? null)
                ? MapPointSpreadsheetField::tryFrom($column['field'])
                : null;

            if ($field !== null && $field !== MapPointSpreadsheetField::IGNORE) {
                $fields[] = $field->value;
            }
        }

        $duplicateLabels = collect($fields)
            ->duplicates()
            ->unique()
            ->map(fn (string $field): string => MapPointSpreadsheetField::from($field)->label());

        if ($duplicateLabels->isNotEmpty()) {
            $fail('Diese Felder sind mehreren Spalten zugeordnet: '.$duplicateLabels->implode(', ').'.');
        }
    }
}
