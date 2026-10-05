<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Advice;
use App\Models\FormSubmission;
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
        public ?Advice $advice = null,
        public ?FormSubmission $formSubmission = null,
    ) {}

    /**
     * Get the mail representation of the notification.
     */
    #[Override]
    public function toMail(mixed $notifiable): MailMessage
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

        if ($this->formSubmission) {
            $mail->line('Betroffene Formular-Einsendung:');
            $mail->line("ID: {$this->formSubmission->id}");
            $mail->line("Formular: {$this->formSubmission->form_name}");
            $mail->line("Eingesendet am: {$this->formSubmission->submitted_at->format('d.m.Y H:i')}");
            $mail->action('Formulareinträge anzeigen', route('form-submissions.index'));
        }

        return $mail;
    }
}
