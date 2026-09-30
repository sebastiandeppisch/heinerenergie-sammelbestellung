<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\MapPointSpreadsheetField;
use App\Models\Group;
use App\Services\MapPointFieldService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Columns may only hold fields of the categories the group can use, so an import never writes fields of other
 * initiatives. A saved template can refer to a field that was deleted since, it has to be assigned anew.
 */
class UsableCategoryFieldColumns implements ValidationRule
{
    public function __construct(private readonly ?Group $group) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || $this->group === null) {
            return;
        }

        $requestedIds = collect($value)
            ->filter(fn (mixed $column): bool => is_array($column) && ($column['field'] ?? null) === MapPointSpreadsheetField::CATEGORY_FIELD->value)
            ->pluck('category_field_id');

        if ($requestedIds->isEmpty()) {
            return;
        }

        $usableIds = app(MapPointFieldService::class)->fieldsUsableInGroup($this->group)->pluck('uuid');

        if ($requestedIds->diff($usableIds)->isNotEmpty()) {
            $fail('Ein zugeordnetes Zusatzfeld gibt es in den Kategorien dieser Initiative nicht. Bitte ordne die Spalte neu zu.');
        }
    }
}
