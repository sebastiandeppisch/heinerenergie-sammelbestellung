<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class MailBodyData
{
    public function __construct(
        public string $uid,
        public string $folder,
        public string $subject,
        public string $from,
        public string $to,
        public string $date,
        public string $body,
    ) {}
}
