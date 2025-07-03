<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckboxRequiredValidator implements ValidationRule
{

    public function __construct(
        /**
         * The field options that are required.
         *
         * @var array<string, string>
         */
        protected array $requiredOptions = []
    ) {
    }

    private function values(): array
    {
        return collect($this->requiredOptions)->keys()->values()->toArray();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_array($value)) {
            $missing = array_diff($this->values(), $value);

            $missingNames = collect($this->requiredOptions)
                ->filter(fn($option, $key) => in_array($key, $missing))
                ->values()
                ->toArray();

            if (!empty($missing)) {
                $fail('Es müssen die folgenden Optionen ausgewählt werden: ' . implode(', ', $missingNames));
            }
        } else {
            $fail('Der Wert muss ein Array sein.');
        }
    }
}
