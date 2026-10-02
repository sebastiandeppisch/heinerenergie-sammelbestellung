<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FormTargetNoticeData;
use App\Mail\FormSubmissionConfirmation;
use App\Models\FormSubmission;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class FormSubmissionConfirmationService
{
    private const int DECAY_SECONDS = 3600;

    /**
     * Each submission sends a mail, so the attempts are limited per sender and per recipient.
     */
    public function ensureNotRateLimited(string $ip, string $email): void
    {
        $limits = [
            'form-confirmation-ip:'.$ip => (int) config('forms.confirmation.max_per_ip_per_hour'),
            'form-confirmation-mail:'.sha1(Str::lower(trim($email))) => (int) config('forms.confirmation.max_per_address_per_hour'),
        ];

        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new ThrottleRequestsException('Zu viele Anfragen, bitte versuche es später erneut.');
            }
        }

        foreach (array_keys($limits) as $key) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
        }
    }

    /**
     * Only a keyed hash is stored, so the database alone is not enough to confirm a submission.
     */
    public function issueToken(FormSubmission $submission): string
    {
        $token = Str::random(64);

        $submission->update([
            'confirmation_token_hash' => $this->hash($token),
            'confirmation_expires_at' => now()->addDays((int) config('forms.confirmation.expires_days')),
        ]);

        return $token;
    }

    public function sendMail(FormSubmission $submission, string $email, string $token): void
    {
        Mail::to($email)->send(new FormSubmissionConfirmation($submission, route('form.confirm.show', $token)));
    }

    public function findByToken(string $token): ?FormSubmission
    {
        return FormSubmission::where('confirmation_token_hash', $this->hash($token))->first();
    }

    /**
     * Confirms the submission and runs its targets exactly once.
     *
     * @return Collection<int, FormTargetNoticeData>|null null when the submission was already confirmed
     */
    public function confirm(FormSubmission $submission): ?Collection
    {
        return DB::transaction(function () use ($submission): ?Collection {
            $locked = FormSubmission::whereKey($submission->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->confirmed_at !== null) {
                return null;
            }

            $locked->update(['confirmed_at' => now()]);

            return $locked->handleCreators();
        });
    }

    private function hash(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
