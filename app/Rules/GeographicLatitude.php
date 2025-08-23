<?php

namespace App\Rules;

use App\ValueObjects\Coordinate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class GeographicLatitude implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Coordinate::isValidLatitude($value)) {
            $fail('Geographische Breitenangaben müssen zwischen -90° und 90° liegen.');
        }
    }
}
