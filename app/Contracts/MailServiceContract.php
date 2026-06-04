<?php

namespace App\Contracts;

use App\Data\MailBodyData;
use App\Data\MailCredentialsData;
use App\Data\MailHeaderData;
use App\Models\Advice;
use Illuminate\Support\Collection;

interface MailServiceContract
{
    /** @return Collection<int, MailHeaderData> */
    public function getMailsForCase(Advice $case): Collection;

    public function getMail(string $uid, string $folder): MailBodyData;

    public function sendMail(Advice $case, string $subject, string $body): void;

    /** @throws \RuntimeException when the connection cannot be established */
    public function testConnection(MailCredentialsData $credentials): void;
}
