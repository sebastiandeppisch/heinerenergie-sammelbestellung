<?php

namespace App\Notifications;

use App\Models\Advice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Override;

class SystemErrorNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $errorMessage,
        public ?Advice $advice = null
    ) {}

    /**
     * Get the mail representation of the notification.
     */
    #[Override]
    public function toMail($notifiable): MailMessage
    {
        $mail = parent::toMail($notifiable);

        $mail->subject("Systemfehler: {$this->title}");
        $mail->line('Ein Fehler ist im System aufgetreten:');
        $mail->line($this->errorMessage);

        if ($this->advice) {
            $mail->line('Betroffene Beratung:');
            $mail->line("ID: {$this->advice->id}");
            $mail->line("Name: {$this->advice->name}");
            $mail->line("Adresse: {$this->advice->address}");
            $mail->action('Beratung anzeigen', url('/advices/'.$this->advice->id));
        }

        return $mail;
    }
}
