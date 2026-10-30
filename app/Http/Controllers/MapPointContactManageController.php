<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MapPoint;
use App\Services\CurrentGroupService;
use App\Services\MapPointContactService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Confirms contact addresses with the links of activation mails that were sent before the manage page confirmed them.
 * Contact requests are switched on and off on the manage page, see MapPointManageController.
 */
class MapPointContactManageController extends Controller
{
    public function __construct(private readonly MapPointContactService $contact) {}

    public function showVerification(string $token): Response
    {
        return $this->verificationPage($token, $this->contact->findPointByVerificationToken($token));
    }

    public function verify(string $token): Response
    {
        $mapPoint = $this->contact->findPointByVerificationToken($token);

        if ($this->verificationStatus($mapPoint) !== 'pending' || $mapPoint === null) {
            return $this->verificationPage($token, $mapPoint);
        }

        $this->contact->verify($mapPoint);

        return $this->verificationPage($token, $mapPoint, 'verified');
    }

    /**
     * Links of mails sent before contact and consent were managed on one page lead there.
     */
    public function unsubscribe(MapPoint $mapPoint): RedirectResponse
    {
        return redirect()->route('map-point.manage.request-for-point', $mapPoint);
    }

    public function manage(string $token): RedirectResponse
    {
        return redirect()->route('map-point.manage.show', $token);
    }

    private function verificationStatus(?MapPoint $mapPoint): string
    {
        return match (true) {
            $mapPoint === null => 'invalid',
            $mapPoint->contact_verification_expires_at === null,
            $mapPoint->contact_verification_expires_at->isPast() => 'expired',
            default => 'pending',
        };
    }

    private function verificationPage(string $token, ?MapPoint $mapPoint, ?string $status = null): Response
    {
        $status ??= $this->verificationStatus($mapPoint);

        if ($mapPoint !== null) {
            app(CurrentGroupService::class)->setGroup($mapPoint->group);
        }

        return Inertia::render('MapPointContact/Verify', [
            'status' => $status,
            'token' => $status === 'pending' ? $token : null,
            'title' => $mapPoint?->title,
            'contactEnabled' => $mapPoint?->contact_enabled,
        ]);
    }
}
