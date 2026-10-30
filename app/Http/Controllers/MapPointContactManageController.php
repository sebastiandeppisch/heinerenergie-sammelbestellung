<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MapPoint;
use App\Services\CurrentGroupService;
use App\Services\MapPointContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

    public function unsubscribe(MapPoint $mapPoint): Response
    {
        app(CurrentGroupService::class)->setGroup($mapPoint->group);

        return Inertia::render('MapPointContact/Unsubscribe', [
            'mapPointId' => $mapPoint->uuid,
            'linkRequested' => session('linkRequested', false),
        ]);
    }

    /**
     * Answers the same way for every point, so nobody learns whether a point has a contact address.
     */
    public function requestEditLink(Request $request, MapPoint $mapPoint): RedirectResponse
    {
        $this->contact->sendEditLink($mapPoint, (string) $request->ip());

        return redirect()->route('map-contact.unsubscribe', $mapPoint)->with('linkRequested', true);
    }

    public function manage(string $token): Response
    {
        return $this->managePage($token, $this->contact->findPointByEditToken($token));
    }

    public function disable(string $token): Response
    {
        return $this->setContactEnabled($token, false);
    }

    public function enable(string $token): Response
    {
        return $this->setContactEnabled($token, true);
    }

    private function setContactEnabled(string $token, bool $enabled): Response
    {
        $mapPoint = $this->contact->findPointByEditToken($token);

        $mapPoint?->forceFill(['contact_enabled' => $enabled])->save();

        return $this->managePage($token, $mapPoint);
    }

    private function managePage(string $token, ?MapPoint $mapPoint): Response
    {
        if ($mapPoint !== null) {
            app(CurrentGroupService::class)->setGroup($mapPoint->group);
        }

        return Inertia::render('MapPointContact/Manage', [
            'token' => $mapPoint !== null ? $token : null,
            'title' => $mapPoint?->title,
            'contactEnabled' => $mapPoint?->contact_enabled,
        ]);
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
