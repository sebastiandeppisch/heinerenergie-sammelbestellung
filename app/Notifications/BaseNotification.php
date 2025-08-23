<?php

namespace App\Notifications;

use App\Mail\BaseNotificationMail;
use App\Models\Advice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BaseNotification extends Notification
{
    use Queueable;

    public ?Advice $advice = null;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new BaseNotificationMail)
            ->greeting('Hallo '.$notifiable->first_name.',')
            ->salutation('Viele Grüße, das heiner*energie Team');

        if ($this->advice) {
            $mail->storeClassName()->associateWith($this->advice);

        }

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
