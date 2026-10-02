<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\MapPointSpreadsheetField;
use App\Models\Group;
use App\Models\MapPointCharacteristic;
use App\Services\MapPointFieldService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

/**
 * Columns may only hold fields and characteristics of the categories the group can use, so an import never writes
 * those of other initiatives. A saved template can refer to a field or characteristic that was deleted since, it has
 * to be assigned anew.
 */
class UsableCategoryFieldColumns implements ValidationRule
{
    public function __construct(private readonly ?Group $group) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || $this->group === null) {
            return;
        }

        $columns = collect($value)->filter(fn (mixed $column): bool => is_array($column));
        $requestedFieldIds = $columns->where('field', MapPointSpreadsheetField::FIELD->value)->pluck('form_field_id');
        $requestedCharacteristicIds = $columns->where('field', MapPointSpreadsheetField::CHARACTERISTIC->value)->pluck('characteristic_id');

        if ($requestedFieldIds->isNotEmpty()) {
            $fieldService = app(MapPointFieldService::class);
            $usableIds = $fieldService->fieldsUsableInGroup($this->group)->pluck('uuid')
                ->merge($fieldService->characteristicFieldsUsableInGroup($this->group)->pluck('uuid'));

            if ($requestedFieldIds->diff($usableIds)->isNotEmpty()) {
                $fail('Ein zugeordnetes Zusatzfeld gibt es in den Kategorien dieser Initiative nicht. Bitte ordne die Spalte neu zu.');
            }
        }

        if ($requestedCharacteristicIds->isNotEmpty()) {
            $group = $this->group;
            $usableIds = MapPointCharacteristic::whereHas('category', fn (Builder $query) => $query->usableInGroup($group))->pluck('uuid');

            if ($requestedCharacteristicIds->diff($usableIds)->isNotEmpty()) {
                $fail('Eine zugeordnete Maßnahme gibt es in den Kategorien dieser Initiative nicht. Bitte ordne die Spalte neu zu.');
            }
        }
    }
}
