<?php

declare(strict_types=1);

namespace App\Mail\MapPointContact;

use App\Models\MapPointContactRequest;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Carbon;

class ContactRequestConfirmation extends MapPointContactMail implements ShouldBeEncrypted
{
    public Carbon $expiresAt;

    public function __construct(MapPointContactRequest $contactRequest, public string $confirmationUrl)
    {
        parent::__construct($contactRequest->mapPoint);

        $this->expiresAt = $contactRequest->confirmation_expires_at;
    }

    protected function compose(): static
    {
        return $this->markdown('emails.map-contact.confirmation')
            ->subject($this->initiative.': Bitte bestätige deine Kontaktanfrage');
    }
}
