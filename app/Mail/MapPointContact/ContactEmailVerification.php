<?php

declare(strict_types=1);

namespace App\Mail\MapPointContact;

use App\Models\MapPoint;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;

class ContactEmailVerification extends MapPointContactMail implements ShouldBeEncrypted
{
    public function __construct(MapPoint $mapPoint, public string $verificationUrl)
    {
        parent::__construct($mapPoint);
    }

    protected function compose(): static
    {
        return $this->markdown('emails.map-contact.verification')
            ->subject($this->initiative.': Bitte bestätige deine Kontaktadresse');
    }
}
