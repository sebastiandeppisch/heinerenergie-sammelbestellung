<?php

declare(strict_types=1);

namespace App\Data;

readonly class MailCredentialsData
{
    public function __construct(
        public string $imapHost,
        public int $imapPort,
        public string $smtpHost,
        public int $smtpPort,
        public string $username,
        public string $password,
    ) {}
}
