<?php

namespace App\Notifications;

use App\Models\Advice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class BaseNotification extends Notification
{
    use Queueable;

    //    public ?Advice $advice = null;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        Log::info('Base Notification toMail');
        Log::info('notifiable', ['notifiable' => $notifiable]);

        return (new MailMessage)
            ->greeting('Hallo '.$notifiable->first_name.',')
            ->salutation('Viele Grüße, das '.app_name().' Team');

    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
