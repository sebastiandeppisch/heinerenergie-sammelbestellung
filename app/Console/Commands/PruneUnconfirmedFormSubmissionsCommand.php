<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FormSubmission;
use Illuminate\Console\Command;

/**
 * Removes submissions whose e-mail address was never confirmed. Disabled unless a retention period is configured.
 */
class PruneUnconfirmedFormSubmissionsCommand extends Command
{
    protected $signature = 'form-submissions:prune-unconfirmed';

    protected $description = 'Deletes unconfirmed form submissions whose confirmation link has expired';

    public function handle(): int
    {
        $days = config('forms.confirmation.prune_after_days');

        if ($days === null) {
            $this->info('Pruning is disabled, set FORM_CONFIRMATION_PRUNE_AFTER_DAYS to enable it.');

            return self::SUCCESS;
        }

        $count = 0;

        FormSubmission::unconfirmed()
            ->where('submitted_at', '<', now()->subDays((int) $days))
            ->where('confirmation_expires_at', '<', now())
            ->each(function (FormSubmission $submission) use (&$count): void {
                $submission->delete();
                $count++;
            });

        $this->info("Deleted {$count} unconfirmed submissions.");

        return self::SUCCESS;
    }
}
