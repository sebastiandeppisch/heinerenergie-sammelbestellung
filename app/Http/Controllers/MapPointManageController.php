<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ConsentChoiceData;
use App\Data\MapPointManageData;
use App\Models\MapPoint;
use App\Services\CurrentGroupService;
use App\Services\MapPointConsentManager;
use App\Services\MapPointContactService;
use App\Services\MapPointEditLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets the person behind a point manage its consent and contact requests without an account. Links are requested
 * by mail, the answer is the same whether a point or address is known or not.
 */
class MapPointManageController extends Controller
{
    public function __construct(
        private readonly MapPointEditLinkService $editLinks,
        private readonly MapPointConsentManager $consents,
        private readonly MapPointContactService $contact,
    ) {}

    public function request(): Response
    {
        return Inertia::render('MapPointManage/Request', ['mapPointId' => null]);
    }

    public function sendForAddress(Request $request): Response
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $this->editLinks->requestForAddress($validated['email'], (string) $request->ip());

        return $this->message('sent');
    }

    public function requestForPoint(MapPoint $mapPoint): Response
    {
        app(CurrentGroupService::class)->setGroup($mapPoint->group);

        return Inertia::render('MapPointManage/Request', ['mapPointId' => $mapPoint->uuid]);
    }

    public function sendForPoint(Request $request, MapPoint $mapPoint): Response
    {
        app(CurrentGroupService::class)->setGroup($mapPoint->group);

        $this->editLinks->requestForPoint($mapPoint, (string) $request->ip());

        return $this->message('sent');
    }

    public function show(string $token): Response
    {
        $mapPoint = $this->editLinks->findByToken($token);

        if ($mapPoint === null) {
            return $this->message('invalid');
        }

        app(CurrentGroupService::class)->setGroup($mapPoint->group);

        return Inertia::render('MapPointManage/Manage', [
            'token' => $token,
            'mapPoint' => MapPointManageData::fromModel($mapPoint),
        ]);
    }

    public function updateConsent(Request $request, string $token): RedirectResponse|Response
    {
        $mapPoint = $this->editLinks->findByToken($token);

        if ($mapPoint === null) {
            return $this->message('invalid');
        }

        $validated = $request->validate(['show' => ['required', 'boolean'], 'details' => ['required', 'boolean']]);

        $this->consents->change($mapPoint, new ConsentChoiceData(show: $validated['show'], details: $validated['show'] && $validated['details']));
        $this->contact->confirmAddress($mapPoint);

        return redirect()->route('map-point.manage.show', $token)->with('success', 'Deine Auswahl ist gespeichert.');
    }

    public function revoke(string $token): RedirectResponse|Response
    {
        $mapPoint = $this->editLinks->findByToken($token);

        if ($mapPoint === null) {
            return $this->message('invalid');
        }

        $this->consents->refuse($mapPoint);
        $this->contact->confirmAddress($mapPoint);

        return redirect()->route('map-point.manage.show', $token)->with('success', 'Deine Einwilligung ist widerrufen. Der Eintrag wird nicht mehr öffentlich angezeigt.');
    }

    public function updateContact(Request $request, string $token): RedirectResponse|Response
    {
        $mapPoint = $this->editLinks->findByToken($token);

        if ($mapPoint === null || $mapPoint->contact_email === null) {
            return $this->message('invalid');
        }

        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];

        $mapPoint->forceFill(['contact_enabled' => $enabled])->save();
        $this->contact->confirmAddress($mapPoint);

        return redirect()->route('map-point.manage.show', $token)->with('success', $enabled
            ? 'Kontaktanfragen sind eingeschaltet. Wir leiten sie an deine E-Mail-Adresse weiter.'
            : 'Kontaktanfragen sind ausgeschaltet.');
    }

    public function destroy(string $token): Response
    {
        $mapPoint = $this->editLinks->findByToken($token);

        if ($mapPoint === null) {
            return $this->message('invalid');
        }

        app(CurrentGroupService::class)->setGroup($mapPoint->group);

        $this->consents->delete($mapPoint);

        return $this->message('deleted');
    }

    private function message(string $status): Response
    {
        return Inertia::render('MapPointManage/Message', ['status' => $status]);
    }
}
