<?php

namespace App\Notifications;

use App\Models\Advice;

class NewAdviceNearby extends BaseNotification
{
    public function __construct(public Advice $advice, public float $distance) {}

    #[\Override]
    public function toMail($notifiable)
    {

        $distance = (int) $this->distance;

        $mail = parent::toMail($notifiable);
        $mail->subject('Neue heiner*energie Beratungsanfrage in deiner Nähe');
        $mail->line(sprintf('Es gibt eine neue Beratungsanfrage von %s in deiner Nähe (%u m entfernt).', $this->advice->name, $distance));
        $mail->line('Adresse: '.$this->advice->address);
        $mail->action('Zur Beratungsanfrage', url('/advices/'.$this->advice->id));

        $mail->line('Wenn Du keine Benachrichtigungen mehr erhalten möchtest, kannst Du dies in Deinem Profil einstellen: '.url('/profile'));

        return $mail;
    }
}
