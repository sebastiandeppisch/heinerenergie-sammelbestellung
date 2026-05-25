<?php

namespace App\Nextcloud\Data;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class NextcloudDir
{
    public function __construct(
        public string $fileId,
        public string $path,
        public string $name,
    ) {}
}
