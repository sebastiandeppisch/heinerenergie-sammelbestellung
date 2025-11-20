<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AddressRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_array($value)) {

            $requiredKeys = ['street', 'street_number', 'zip', 'city'];

            $missingKeys = [];
            foreach ($requiredKeys as $key) {
                if (! array_key_exists($key, $value) || $value[$key] === null || $value[$key] === '') {
                    $missingKeys[] = $key;
                }
            }

            // EN => DE
            $keyTranslations = [
                'street' => 'Straße',
                'street_number' => 'Hausnummer',
                'zip' => 'Postleitzahl',
                'city' => 'Stadt',
            ];

            $missingKeys = array_map(fn ($key) => $keyTranslations[$key], $missingKeys);

            if (! empty($missingKeys)) {
                $fail('Bitte die Felder '.implode(', ', $missingKeys).' ausfüllen.');
            }

        }
    }
}
