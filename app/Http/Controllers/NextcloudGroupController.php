<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GroupData;
use App\Http\Requests\ImportNextcloudUserRequest;
use App\Models\Group;
use App\Services\NextcloudGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class NextcloudGroupController extends Controller
{
    public function __construct(
        private readonly NextcloudGroupService $service,
    ) {}

    public function index(Group $group, Request $request): Response
    {
        $this->authorize('update', $group);

        if (! $group->nextcloud_group_name) {
            return Inertia::render('Groups/Nextcloud', [
                'group' => GroupData::fromModel($group),
                'items' => [],
                'nextcloudConfigured' => false,
            ]);
        }

        try {
            $items = $this->service->getComparisonItems($group);
        } catch (RuntimeException $e) {
            return Inertia::render('Groups/Nextcloud', [
                'group' => GroupData::fromModel($group),
                'items' => [],
                'nextcloudConfigured' => true,
                'error' => 'Nextcloud-Gruppe konnte nicht abgerufen werden: '.$e->getMessage(),
            ]);
        }

        return Inertia::render('Groups/Nextcloud', [
            'group' => GroupData::fromModel($group),
            'items' => $items->values()->all(),
            'nextcloudConfigured' => true,
        ]);
    }

    public function import(Group $group, string $ncUser, ImportNextcloudUserRequest $request): RedirectResponse
    {
        try {
            $ncUserData = $this->service->getNcUser($ncUser);
        } catch (RuntimeException $e) {
            return back()->withErrors(['nc_user' => $e->getMessage()]);
        }

        if ($this->service->crmUserExists($ncUserData->email)) {
            return back()->withErrors(['email' => 'Ein Benutzer mit dieser E-Mail-Adresse existiert bereits.']);
        }

        $user = $this->service->createAndAttachUser(
            $group,
            $ncUserData,
            $request->firstName(),
            $request->lastName(),
            $request->sendEmail(),
        );

        $message = "{$user->first_name} {$user->last_name} wurde importiert.";
        if ($request->sendEmail()) {
            $message .= ' Eine E-Mail zum Passwort-Setzen wurde versandt.';
        }

        return redirect()->route('groups.nextcloud', $group)->with('success', $message);
    }

    public function addToGroup(Group $group, string $ncUser, Request $request): RedirectResponse
    {
        $this->authorize('update', $group);

        try {
            $ncUserData = $this->service->getNcUser($ncUser);
        } catch (RuntimeException $e) {
            return back()->withErrors(['nc_user' => $e->getMessage()]);
        }

        $crmUser = $this->service->findCrmUserByEmail($ncUserData->email);

        if (! $crmUser) {
            return back()->withErrors(['nc_user' => 'Kein CRM-Benutzer mit dieser E-Mail gefunden.']);
        }

        if ($this->service->isGroupMember($group, $crmUser)) {
            return back()->withErrors(['nc_user' => 'Dieser Benutzer ist bereits Mitglied der Gruppe.']);
        }

        $this->service->attachUserToGroup($group, $crmUser);

        return redirect()->route('groups.nextcloud', $group)
            ->with('success', "{$crmUser->first_name} {$crmUser->last_name} wurde zur Gruppe hinzugefügt.");
    }
}
