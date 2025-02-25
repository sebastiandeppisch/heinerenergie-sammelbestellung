<?php

namespace App\Notifications;

use App\Models\Advice;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Override;

class AdviceTransferred extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Advice $advice,
        private Group $oldGroup,
        private Group $newGroup,
        private ?string $reason
    ) {}

    #[Override]
    public function toMail($notifiable): MailMessage
    {
        $mail = parent::toMail($notifiable);

        $mail->subject('Deine Beratung wurde übertragen')
            ->greeting('Hallo '.$this->advice->firstName)
            ->line('Deine Beratung wurde an eine andere Initiative übertragen:')
            ->line('Von: '.$this->oldGroup->name)
            ->line('An: '.$this->newGroup->name);

        if ($this->reason) {
            $mail->line('Grund: '.$this->reason);
        }

        $mail->line('Ein:e Berater:in der neuen Initiative wird sich in Kürze bei dir melden.');

        return $mail;
    }
}
