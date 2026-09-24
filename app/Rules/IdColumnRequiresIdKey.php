<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\MapPointSpreadsheetField;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A column holding our ids may only be imported as the key field. Otherwise the ids would be read and then
 * dropped, which silently turns an import meant as an update into a second copy of every point.
 *
 * Points are copied from one initiative to another by setting the id column to "ignore" on purpose.
 */
class IdColumnRequiresIdKey implements ValidationRule
{
    /**
     * @param  mixed  $columns  The column mapping as it was sent, one entry per spreadsheet column.
     */
    public function __construct(private readonly mixed $columns) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($this->columns) || $value === MapPointSpreadsheetField::ID->value) {
            return;
        }

        foreach ($this->columns as $column) {
            if (is_array($column) && ($column['field'] ?? null) === MapPointSpreadsheetField::ID->value) {
                $fail('Ist eine Spalte dem Feld „ID“ zugeordnet, werden vorhandene Punkte an der ID erkannt. Sollen die Zeilen neu angelegt werden, stelle die ID-Spalte auf „Ignorieren“.');

                return;
            }
        }
    }
}
