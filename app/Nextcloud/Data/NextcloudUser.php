<?php

namespace App\Nextcloud\Data;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class NextcloudUser
{
    public function __construct(
        public string $id,
        public string $email,
        public string $displayname,
        public bool $enabled,
        /** @var string[] */
        public array $groups,
    ) {}
}
