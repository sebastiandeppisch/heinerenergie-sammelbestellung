<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\MapPointContact\ContactEmailVerification;
use App\Mail\MapPointContact\ContactRequestConfirmation;
use App\Mail\MapPointContact\ContactRequestForward;
use App\Mail\MapPointContact\EditLink;
use App\Models\MapPoint;
use App\Models\MapPointContactRequest;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class MapPointContactService
{
    /**
     * All limits are checked before any is counted, so a blocked request uses up none of them.
     */
    public function ensureContactNotRateLimited(string $ip, string $senderEmail, MapPoint $mapPoint): void
    {
        $limits = [
            'map-contact-ip:'.$ip => [(int) config('map_contact.limits.per_ip_per_hour'), 3600],
            'map-contact-sender:'.sha1(Str::lower(trim($senderEmail))) => [(int) config('map_contact.limits.per_sender_per_day'), 86400],
            'map-contact-point:'.$mapPoint->id => [(int) config('map_contact.limits.per_point_per_week'), 604800],
        ];

        foreach ($limits as $key => [$maxAttempts]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new ThrottleRequestsException('Zu viele Kontaktanfragen. Bitte versuche es später erneut.');
            }
        }

        foreach ($limits as $key => [, $decaySeconds]) {
            RateLimiter::hit($key, $decaySeconds);
        }
    }

    public function createRequest(MapPoint $mapPoint, string $senderName, string $senderEmail, string $message): MapPointContactRequest
    {
        $token = Str::random(64);

        $contactRequest = $mapPoint->contactRequests()->create([
            'sender_name' => $senderName,
            'sender_email' => $senderEmail,
            'message' => $message,
            'confirmation_token_hash' => $this->hash($token),
            'confirmation_expires_at' => now()->addHours((int) config('map_contact.confirmation_expires_hours')),
        ]);

        Mail::to($senderEmail)->send(new ContactRequestConfirmation(
            $contactRequest,
            route('map-contact.confirm.show', $token),
        ));

        return $contactRequest;
    }

    public function findRequestByToken(string $token): ?MapPointContactRequest
    {
        return MapPointContactRequest::where('confirmation_token_hash', $this->hash($token))->first();
    }

    /**
     * Confirms the request exactly once and forwards it while the point still accepts contact.
     * Returns false if it was confirmed before.
     */
    public function confirm(MapPointContactRequest $contactRequest): bool
    {
        $confirmed = DB::transaction(function () use ($contactRequest): ?MapPointContactRequest {
            $locked = MapPointContactRequest::whereKey($contactRequest->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isConfirmed()) {
                return null;
            }

            $locked->update(['confirmed_at' => now()]);

            return $locked;
        });

        if ($confirmed === null) {
            return false;
        }

        $mapPoint = $confirmed->mapPoint;

        if ($mapPoint->hasContact()) {
            Mail::to($mapPoint->contact_email)->send(new ContactRequestForward($confirmed));
            $confirmed->update(['forwarded_at' => now()]);
        }

        $contactRequest->refresh();

        return true;
    }

    /**
     * The address only counts as confirmed once its owner follows the link.
     */
    public function requestVerification(MapPoint $mapPoint): void
    {
        if ($mapPoint->contact_email === null) {
            return;
        }

        $token = Str::random(64);

        $mapPoint->forceFill([
            'contact_email_verified_at' => null,
            'contact_verification_token_hash' => $this->hash($token),
            'contact_verification_expires_at' => now()->addDays((int) config('map_contact.verification_expires_days')),
        ])->save();

        Mail::to($mapPoint->contact_email)->send(new ContactEmailVerification(
            $mapPoint,
            route('map-contact.verify.show', $token),
        ));
    }

    public function findPointByVerificationToken(string $token): ?MapPoint
    {
        return MapPoint::where('contact_verification_token_hash', $this->hash($token))->first();
    }

    public function verify(MapPoint $mapPoint): void
    {
        $mapPoint->forceFill([
            'contact_email_verified_at' => now(),
            'contact_verification_token_hash' => null,
            'contact_verification_expires_at' => null,
        ])->save();
    }

    /**
     * Silently does nothing for points without a confirmed address or once the limit is hit,
     * so the response never tells whether a point has a contact.
     */
    public function sendEditLink(MapPoint $mapPoint, string $ip): void
    {
        $key = 'map-contact-edit-link:'.$ip.':'.$mapPoint->id;

        if (RateLimiter::tooManyAttempts($key, (int) config('map_contact.limits.edit_link_per_ip_and_point_per_hour'))) {
            return;
        }

        RateLimiter::hit($key, 3600);

        if ($mapPoint->contact_email === null || $mapPoint->contact_email_verified_at === null) {
            return;
        }

        $token = Str::random(64);

        $mapPoint->forceFill([
            'edit_token_hash' => $this->hash($token),
            'edit_token_expires_at' => now()->addDays((int) config('map_contact.edit_token_expires_days')),
        ])->save();

        Mail::to($mapPoint->contact_email)->send(new EditLink(
            $mapPoint,
            route('map-contact.manage.show', $token),
        ));
    }

    public function findPointByEditToken(string $token): ?MapPoint
    {
        $mapPoint = MapPoint::where('edit_token_hash', $this->hash($token))->first();

        if ($mapPoint === null || $mapPoint->edit_token_expires_at === null || $mapPoint->edit_token_expires_at->isPast()) {
            return null;
        }

        return $mapPoint;
    }

    public function hash(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
