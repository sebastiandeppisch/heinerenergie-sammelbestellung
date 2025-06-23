<?php

namespace App\Rules;

use App\ValueObjects\Coordinate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GeographicLongitude implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Coordinate::isValidLongitude($value)) {
            $fail('Geographische Längenangaben müssen zwischen -180° und 180° liegen.');
        }
    }
}
