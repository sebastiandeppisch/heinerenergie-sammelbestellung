<?php

declare(strict_types=1);

namespace App\Mail\MapPointContact;

use App\Models\MapPointContactRequest;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;

class ContactRequestForward extends MapPointContactMail implements ShouldBeEncrypted
{
    public string $senderName;

    public string $senderMessage;

    public string $unsubscribeUrl;

    public function __construct(MapPointContactRequest $contactRequest)
    {
        parent::__construct($contactRequest->mapPoint);

        $this->senderName = $contactRequest->sender_name;
        $this->senderMessage = $contactRequest->message;
        $this->unsubscribeUrl = route('map-contact.unsubscribe', $contactRequest->mapPoint);

        $this->replyTo($contactRequest->sender_email, $contactRequest->sender_name);
    }

    protected function compose(): static
    {
        return $this->markdown('emails.map-contact.forward')
            ->subject('Kontaktanfrage zu deinem Eintrag „'.$this->mapPoint->title.'“');
    }
}
