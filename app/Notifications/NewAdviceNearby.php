<?php

namespace App\Notifications;

use App\Models\Advice;
use App\ValueObjects\Meter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Override;

class NewAdviceNearby extends BaseNotification implements ShouldQueue
{
    public function __construct(public ?Advice $advice, public Meter $distance)
    {
        if ($this->advice === null) {
            throw new InvalidArgumentException('Advice must not be null');
        }
    }

    #[Override]
    public function toMail($notifiable)
    {
        Log::info('Notification to Mail');
        $distance = $this->distance->__toString();

        $mail = parent::toMail($notifiable);
        $mail->subject('Neue '.app_name().' Beratungsanfrage in deiner Nähe');
        $mail->line(sprintf('Es gibt eine neue Beratungsanfrage von %s in deiner Nähe (%s entfernt).', $this->advice->name, $distance));
        $mail->line('Adresse: '.$this->advice->address);
        $mail->action('Zur Beratungsanfrage', url('/advices/'.$this->advice->id));

        $mail->line('Wenn Du keine Benachrichtigungen mehr erhalten möchtest, kannst Du dies in Deinem Profil einstellen: '.url('/profile'));

        Log::info('Notification to Mail fertig');

        return $mail;
    }
}
