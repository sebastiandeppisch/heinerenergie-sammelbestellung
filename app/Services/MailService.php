<?php

namespace App\Services;

use App\Contracts\MailCredentialsRepository;
use App\Contracts\MailServiceContract;
use App\Data\MailBodyData;
use App\Data\MailCredentialsData;
use App\Data\MailHeaderData;
use App\Exceptions\MailCredentialsMissing;
use App\Models\Advice;
use Illuminate\Support\Collection;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Folder;

class MailService implements MailServiceContract
{
    public function __construct(private readonly MailCredentialsRepository $credentials) {}

    /** @return Collection<int, MailHeaderData> */
    public function getMailsForCase(Advice $case): Collection
    {
        $email = $case->email;
        $client = $this->makeImapClient();
        $client->connect();

        try {
            $inbox = $client->getFolder('INBOX');

            $inboxMessages = $inbox->query()
                ->leaveUnread()
                ->where('OR')->from($email)->to($email)
                ->get();

            $sentMessages = collect();
            $sentFolder = $this->findSentFolder($client);

            if ($sentFolder) {
                $sentMessages = $sentFolder->query()
                    ->leaveUnread()
                    ->to($email)
                    ->get();
            }

            return $inboxMessages
                ->map(fn ($m) => $this->messageToHeader($m, 'INBOX'))
                ->merge($sentMessages->map(fn ($m) => $this->messageToHeader($m, $sentFolder->path)))
                ->sortByDesc(fn (MailHeaderData $h) => $h->dateTimestamp)
                ->values();
        } finally {
            $client->disconnect();
        }
    }

    public function getMail(string $uid, string $folder): MailBodyData
    {
        $client = $this->makeImapClient();
        $client->connect();

        try {
            $imapFolder = $client->getFolderByPath($folder);
            $message = $imapFolder->query()->getMessageByUid((int) $uid);

            return new MailBodyData(
                uid: $uid,
                folder: $folder,
                subject: (string) ($message->subject->first() ?? ''),
                from: $this->formatAddress($message->getFrom()),
                to: $this->formatAddress($message->getTo()),
                date: $message->getDate()->first()?->format('d.m.Y H:i') ?? '',
                body: $message->getTextBody(),
            );
        } finally {
            $client->disconnect();
        }
    }

    private function messageToHeader(mixed $message, string $folderPath): MailHeaderData
    {
        $date = $message->getDate()->first();

        return new MailHeaderData(
            uid: (string) $message->getUid(),
            folder: $folderPath,
            subject: (string) ($message->subject->first() ?? ''),
            from: $this->formatAddress($message->getFrom()),
            date: $date?->format('d.m.Y H:i') ?? '',
            hasBeenRead: (bool) $message->flags?->contains('\\Seen'),
            dateTimestamp: $date?->timestamp ?? 0,
        );
    }

    public function sendMail(Advice $case, string $subject, string $body): void
    {
        $creds = $this->credentials->get();

        if (! $creds) {
            throw new MailCredentialsMissing;
        }

        $transport = new EsmtpTransport($creds->smtpHost, $creds->smtpPort);
        $transport->setUsername($creds->username);
        $transport->setPassword($creds->password);

        $email = (new Email)
            ->from($creds->username)
            ->to($case->email)
            ->subject($subject)
            ->text($body);

        $sentMessage = $transport->send($email);

        $this->copyToSentFolder($sentMessage->toString());
    }

    private function copyToSentFolder(string $rawMessage): void
    {
        $client = $this->makeImapClient();
        $client->connect();

        try {
            $folder = $this->findSentFolder($client);
            $folder?->appendMessage($rawMessage, ['\\Seen']);
        } finally {
            $client->disconnect();
        }
    }

    private function findSentFolder(Client $client): ?Folder
    {
        foreach (['Sent', 'Sent Items', 'Sent Messages', 'INBOX.Sent'] as $name) {
            $folder = $client->getFolderByName($name, true);
            if ($folder !== null) {
                return $folder;
            }
        }

        return null;
    }

    public function testConnection(MailCredentialsData $credentials): void
    {
        $client = $this->makeImapClient($credentials);
        $client->connect();
        $client->disconnect();
    }

    private function makeImapClient(?MailCredentialsData $credentials = null): Client
    {
        $creds = $credentials ?? $this->credentials->get();

        if (! $creds) {
            throw new MailCredentialsMissing;
        }

        $manager = new ClientManager;

        return $manager->make([
            'host' => $creds->imapHost,
            'port' => $creds->imapPort,
            'encryption' => 'ssl',
            'validate_cert' => true,
            'username' => $creds->username,
            'password' => $creds->password,
            'protocol' => 'imap',
        ]);
    }

    private function formatAddress(mixed $attribute): string
    {
        if (! $attribute) {
            return '';
        }

        $first = is_iterable($attribute) ? collect($attribute)->first() : $attribute;
        if (! $first) {
            return '';
        }

        $name = method_exists($first, 'personal') ? $first->personal : (property_exists($first, 'personal') ? $first->personal : '');
        $mail = method_exists($first, 'mail') ? $first->mail : (property_exists($first, 'mail') ? $first->mail : (string) $first);

        return $name ? "{$name} <{$mail}>" : $mail;
    }
}
