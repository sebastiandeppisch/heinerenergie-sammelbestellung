<?php

declare(strict_types=1);

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
    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        Log::info('Base Notification toMail');
        Log::info('notifiable', ['notifiable' => $notifiable]);

        return (new MailMessage)
            ->greeting('Hallo '.$notifiable->first_name.',')
            ->salutation('Viele Grüße, das '.app_name().' Team');

    }

    /**
     * @return array{}
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            //
        ];
    }
}
