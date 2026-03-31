<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class MaxImagePixels implements ValidationRule
{
    public function __construct(private readonly int $maxPixels = 64_000_000) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $info = @getimagesize($value->getRealPath());

        if ($info === false) {
            return;
        }

        if ($this->maxPixels < $info[0] * $info[1]) {
            $fail('Das Bild ist zu groß (maximal 8000×8000 Pixel).');
        }
    }
}
