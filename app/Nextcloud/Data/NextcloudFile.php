<?php

declare(strict_types=1);

namespace App\Nextcloud\Data;

use Carbon\Carbon;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class NextcloudFile
{
    public function __construct(
        public string $fileId,
        public string $path,
        public string $name,
        public int $size,
        public string $mimeType,
        public Carbon $lastModified,
    ) {}
}
