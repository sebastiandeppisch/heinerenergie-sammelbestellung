<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A stored image. The url checks access on each request; append ?w=400 or ?w=800 for a smaller variant.
 */
#[TypeScript]
class ImageData extends Data
{
    public function __construct(
        /** The file name, which keeps the image when the field is saved again. */
        public string $name,
        public string $url,
    ) {}
}
