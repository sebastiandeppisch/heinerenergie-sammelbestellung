<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MapPoint;
use App\Services\MapPointConsentManager;
use App\Services\MapPointEditLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admins may withdraw a consent on behalf of the person, e.g. after a phone call, but never give one.
 */
class MapPointConsentController extends Controller
{
    public function updateContactEmail(Request $request, MapPoint $mappoint): RedirectResponse
    {
        $this->authorize('update', $mappoint);

        $validated = $request->validate(['contact_email' => ['nullable', 'email', 'max:255']]);
        $mappoint->update(['contact_email' => $validated['contact_email'] ?: null]);

        return back()->with('success', 'Die Kontaktadresse ist gespeichert.');
    }

    public function refuse(MapPoint $mappoint, MapPointConsentManager $consents): RedirectResponse
    {
        $this->authorize('update', $mappoint);

        $consents->refuse($mappoint);

        return back()->with('success', 'Der Punkt wird nicht mehr öffentlich angezeigt.');
    }

    public function sendEditLink(MapPoint $mappoint, MapPointEditLinkService $editLinks): RedirectResponse
    {
        $this->authorize('update', $mappoint);

        if ($mappoint->contact_email === null) {
            return back()->with('error', 'Der Punkt hat keine Kontaktadresse.');
        }

        $editLinks->send($mappoint);

        return back()->with('success', 'Der Verwaltungslink ist an die Kontaktadresse verschickt.');
    }
}
