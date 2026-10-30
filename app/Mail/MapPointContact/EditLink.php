<?php

declare(strict_types=1);

namespace App\Mail\MapPointContact;

use App\Models\MapPoint;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;

class EditLink extends MapPointContactMail implements ShouldBeEncrypted
{
    public function __construct(MapPoint $mapPoint, public string $manageUrl)
    {
        parent::__construct($mapPoint);
    }

    protected function compose(): static
    {
        return $this->markdown('emails.map-contact.edit-link')
            ->subject($this->initiative.': Kontaktanfragen zu deinem Eintrag verwalten');
    }
}
