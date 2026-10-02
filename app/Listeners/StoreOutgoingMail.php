<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Mail\FormSubmissionConfirmation;
use Illuminate\Mail\Events\MessageSent;
use Override;
use Wnx\Sends\Listeners\StoreOutgoingMailListener;

/**
 * Keeps secrets such as confirmation links out of the stored mail content.
 */
class StoreOutgoingMail extends StoreOutgoingMailListener
{
    private const array WITHOUT_CONTENT = [
        FormSubmissionConfirmation::class,
    ];

    /**
     * @param  array<string, mixed>  $defaultAttributes
     * @return array<string, mixed>
     */
    #[Override]
    protected function getSendAttributes(MessageSent $event, array $defaultAttributes): array
    {
        if (in_array($defaultAttributes['mail_class'], self::WITHOUT_CONTENT, true)) {
            $defaultAttributes['content'] = null;
        }

        return $defaultAttributes;
    }
}
