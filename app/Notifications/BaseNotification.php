<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BaseNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting('Hallo '.$notifiable->firstName.',')
            ->salutation('Viele Grüße, das heiner*energie Team');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
