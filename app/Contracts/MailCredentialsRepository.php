<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\MailCredentialsData;

interface MailCredentialsRepository
{
    public function get(): ?MailCredentialsData;

    public function store(MailCredentialsData $data): void;

    public function clear(): void;
}
