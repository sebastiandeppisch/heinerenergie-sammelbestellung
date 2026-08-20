<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Ensures a form field that feeds a mandatory model attribute is marked as a required field.
 * Other fields of the same type stay untouched, so a form may still offer additional optional ones.
 */
class MappedFormFieldMustBeRequired implements ValidationRule
{
    public function __construct(private readonly string $readableName, private readonly string $reason) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $fields = request()->input('fields', []);

        if (! is_array($fields)) {
            return;
        }

        $field = collect($fields)->firstWhere('id', $value);

        if ($field === null) {
            return;
        }

        if (($field['required'] ?? false) !== true) {
            $fail("Das {$this->readableName} muss ein Pflichtfeld sein, {$this->reason}");
        }
    }
}
