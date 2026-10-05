<?php

declare(strict_types=1);

namespace App\Mail;

use App\Context\FixedGroupContext;
use App\Context\GroupContextContract;
use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Wnx\Sends\Support\StoreMailables;

/**
 * Carries the plain confirmation link, so the queued payload is encrypted and the content is not stored as a send.
 */
class FormSubmissionConfirmation extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;
    use StoreMailables;

    public function __construct(public FormSubmission $submission, public string $confirmationUrl) {}

    public function build(): self
    {
        $previousContext = app()->bound(GroupContextContract::class)
            ? app(GroupContextContract::class)
            : null;

        app()->instance(GroupContextContract::class, new FixedGroupContext($this->submission->group));

        $this->withSymfonyMessage(function () use ($previousContext): void {
            if ($previousContext !== null) {
                app()->instance(GroupContextContract::class, $previousContext);
            } else {
                app()->forgetInstance(GroupContextContract::class);
            }
        });

        $this->storeClassName();

        return $this->markdown('emails.formconfirmation')->subject(app_name().': Bitte bestätige deine E-Mail-Adresse');
    }
}
