<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\ImageStorage;
use App\ValueObjects\StoredImage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * An entry of an image field of a map point: the file name of an image the field already holds, which keeps it,
 * a new upload, or an image to copy.
 */
class MapPointImageItem implements ValidationRule
{
    /**
     * @param  array<int, string>  $storedNames  the file names of the images the field holds now
     */
    public function __construct(private readonly array $storedNames = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile) {
            $validator = Validator::make(['image' => $value], ['image' => ['image', 'mimes:jpg,jpeg,png', 'max:10240', new MaxImagePixels]]);

            foreach ($validator->errors()->all() as $message) {
                $fail($message);
            }

            return;
        }

        if ($value instanceof StoredImage) {
            if (! app(ImageStorage::class)->exists($value->path)) {
                $fail('Das Bild existiert nicht mehr.');
            }

            return;
        }

        if (! is_string($value) || ! in_array($value, $this->storedNames, true)) {
            $fail('Das Bild gehört nicht zu diesem Feld.');
        }
    }
}
