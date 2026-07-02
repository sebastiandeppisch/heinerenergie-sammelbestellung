<?php

namespace App\Data;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class MailConfigData
{
    public function __construct(
        public string $imapHost,
        public int $imapPort,
        public string $smtpHost,
        public int $smtpPort,
        public string $username = '',
    ) {}
}
