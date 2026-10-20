<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Mail\MapPointContact\ContactEmailVerification;
use App\Mail\MapPointContact\ContactRequestConfirmation;
use App\Mail\MapPointContact\EditLink;
use Illuminate\Mail\Events\MessageSent;
use Override;
use Wnx\Sends\Listeners\StoreOutgoingMailListener;

/**
 * Keeps the content of mails carrying a one-time link out of the stored sends.
 */
class StoreOutgoingMail extends StoreOutgoingMailListener
{
    private const array WITHOUT_CONTENT = [
        ContactRequestConfirmation::class,
        ContactEmailVerification::class,
        EditLink::class,
    ];

    #[Override]
    protected function getContent(MessageSent $event): ?string
    {
        if (in_array($this->getMailClassHeaderValue($event), self::WITHOUT_CONTENT, true)) {
            return null;
        }

        return parent::getContent($event);
    }
}
