<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MailCredentialsRepository;
use App\Data\MailCredentialsData;
use App\Services\UserEncryptionService;
use RuntimeException;

class SessionMailCredentialsRepository implements MailCredentialsRepository
{
    private const string SESSION_KEY = 'mail_credentials';

    public function __construct(private readonly UserEncryptionService $encryptionService) {}

    public function get(): ?MailCredentialsData
    {
        $encrypted = session()->get(self::SESSION_KEY);

        if (! $encrypted) {
            return null;
        }

        try {
            $key = base64_encode((string) app('user.enc_key'));
            $json = $this->encryptionService->decrypt($encrypted, $key);
            $data = json_decode($json, true);

            return new MailCredentialsData(
                imapHost: $data['imap_host'],
                imapPort: $data['imap_port'],
                smtpHost: $data['smtp_host'],
                smtpPort: $data['smtp_port'],
                username: $data['username'],
                password: $data['password'],
            );
        } catch (RuntimeException) {
            $this->clear();

            return null;
        }
    }

    public function store(MailCredentialsData $data): void
    {
        $json = json_encode([
            'imap_host' => $data->imapHost,
            'imap_port' => $data->imapPort,
            'smtp_host' => $data->smtpHost,
            'smtp_port' => $data->smtpPort,
            'username' => $data->username,
            'password' => $data->password,
        ]);

        $key = base64_encode((string) app('user.enc_key'));
        $encrypted = $this->encryptionService->encrypt($json, $key);

        session()->put(self::SESSION_KEY, $encrypted);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
