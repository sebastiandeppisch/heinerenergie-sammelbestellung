<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FormSubmission;
use App\Models\SubmissionField;
use App\Models\SubmissionFieldOption;
use App\Services\ImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes submissions whose e-mail address was never confirmed. Disabled unless a retention period is configured.
 */
class PruneUnconfirmedFormSubmissionsCommand extends Command
{
    protected $signature = 'form-submissions:prune-unconfirmed';

    protected $description = 'Deletes unconfirmed form submissions whose confirmation link has expired';

    public function handle(ImageStorage $imageStorage): int
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
            ->each(function (FormSubmission $submission) use ($imageStorage, &$count): void {
                $this->delete($submission, $imageStorage);
                $count++;
            });

        $this->info("Deleted {$count} unconfirmed submissions.");

        return self::SUCCESS;
    }

    private function delete(FormSubmission $submission, ImageStorage $imageStorage): void
    {
        DB::transaction(function () use ($submission): void {
            $fieldIds = SubmissionField::where('form_submission_id', $submission->id)->pluck('id');
            SubmissionFieldOption::whereIn('submission_field_id', $fieldIds)->delete();
            SubmissionField::whereIn('id', $fieldIds)->delete();
            $submission->delete();
        });

        $imageStorage->deleteDirectory('form-images/'.$submission->uuid);
    }
}
