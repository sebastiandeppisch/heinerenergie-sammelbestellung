<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\MapPointCreated;
use App\Mail\MapPointEditLinks;
use App\Models\MapPoint;
use App\Support\TokenHash;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Links that let the person behind a point manage it. They are requested by mail and not kept: each point has one
 * token of its own, a new link replaces the old one and it expires after a few days.
 */
class MapPointEditLinkService
{
    private const int DECAY_SECONDS = 3600;

    public function issue(MapPoint $mapPoint): string
    {
        $token = Str::random(64);

        $mapPoint->forceFill([
            'edit_token_hash' => TokenHash::make($token),
            'edit_token_expires_at' => now()->addDays((int) config('forms.map_point_edit.expires_days')),
        ])->save();

        return $token;
    }

    public function findByToken(string $token): ?MapPoint
    {
        return MapPoint::where('edit_token_hash', TokenHash::make($token))
            ->where('edit_token_expires_at', '>', now())
            ->first();
    }

    public function invalidate(MapPoint $mapPoint): void
    {
        $mapPoint->forceFill(['edit_token_hash' => null, 'edit_token_expires_at' => null])->save();
    }

    /**
     * Sent once after a form created the point.
     */
    public function sendCreated(MapPoint $mapPoint): void
    {
        if ($mapPoint->contact_email === null) {
            return;
        }

        Mail::to($mapPoint->contact_email)->send(new MapPointCreated($mapPoint, $this->url($this->issue($mapPoint))));
    }

    public function send(MapPoint $mapPoint): void
    {
        if ($mapPoint->contact_email === null) {
            return;
        }

        Mail::to($mapPoint->contact_email)->send(new MapPointEditLinks($mapPoint->group, [$this->link($mapPoint)]));
    }

    /**
     * Whether a point exists is not revealed: the limits count every request and nothing is sent without an address.
     */
    public function requestForPoint(MapPoint $mapPoint, string $ip): void
    {
        $this->ensureNotRateLimited($ip, $mapPoint->contact_email, $mapPoint);
        $this->send($mapPoint);
    }

    /**
     * Sends one mail per group with the links of all points of the address.
     */
    public function requestForAddress(string $email, string $ip): void
    {
        $this->ensureNotRateLimited($ip, $email);

        MapPoint::whereRaw('LOWER(contact_email) = ?', [Str::lower(trim($email))])
            ->with('group')
            ->get()
            ->groupBy('group_id')
            ->each(fn (Collection $mapPoints) => Mail::to($email)->send(new MapPointEditLinks(
                $mapPoints->first()->group,
                $mapPoints->map(fn (MapPoint $mapPoint): array => $this->link($mapPoint))->values()->all(),
            )));
    }

    public function url(string $token): string
    {
        return route('map-point.manage.show', $token);
    }

    /**
     * @return array{title: string, url: string}
     */
    private function link(MapPoint $mapPoint): array
    {
        return ['title' => $mapPoint->title, 'url' => $this->url($this->issue($mapPoint))];
    }

    private function ensureNotRateLimited(string $ip, ?string $email, ?MapPoint $mapPoint = null): void
    {
        $limits = array_filter([
            'map-point-edit-ip:'.$ip => (int) config('forms.map_point_edit.max_per_ip_per_hour'),
            'map-point-edit-mail:'.sha1(Str::lower(trim((string) $email))) => $email === null ? null : (int) config('forms.map_point_edit.max_per_address_per_hour'),
            'map-point-edit-point:'.$mapPoint?->uuid => $mapPoint === null ? null : (int) config('forms.map_point_edit.max_per_point_per_hour'),
        ], fn (?int $maxAttempts): bool => $maxAttempts !== null);

        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new ThrottleRequestsException('Zu viele Anfragen, bitte versuche es später erneut.');
            }
        }

        foreach (array_keys($limits) as $key) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
        }
    }
}
