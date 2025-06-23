<?php

namespace App\Rules;

use App\ValueObjects\Coordinate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GeographicCoordinate implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || !array_key_exists("lat", $value) || !array_key_exists("lng", $value)){
            $fail('Geographische Koordinaten müssen in einem Array mit "lat" und "lon" als');
            return;
        }

        if (! Coordinate::isValidLatitude($value["lat"])) {
            $fail('Geographische Breitenangaben müssen zwischen -90° und 90° liegen.');
        }
        if (! Coordinate::isValidLongitude($value["lng"])) {
            $fail('Geographische Längenangaben müssen zwischen -180° und 180° liegen.');
        }
    }
}
