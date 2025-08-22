<?php

namespace App\Notifications;

use App\Models\Advice;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Override;

class NewAdviceAssignedToGroup extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Advice $advice,
        public Group $group
    ) {}

    /**
     * Get the mail representation of the notification.
     */
    #[Override]
    public function toMail($notifiable): MailMessage
    {
        $mail = parent::toMail($notifiable);

        $mail->subject("Neue Beratungsanfrage für {$this->group->name}");
        $mail->line("Eine neue Beratungsanfrage von {$this->advice->name} wurde der Initiative {$this->group->name} zugewiesen.");
        $mail->line("Adresse: {$this->advice->address}");

        $mail->action('Zur Beratungsanfrage', url('/advices/'.$this->advice->id));

        return $mail;
    }
}
