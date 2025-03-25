<?php

namespace App\Http\Controllers;

use App\Data\GroupData;
use App\Data\Pages\GroupsIndexData;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Http\Requests\UpdateGroupConsultingAreaRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function __construct()
    {
        // there is no authorization here, as its done in the methods themself or in the Request classes
    }

    private function showPage(iterable $groups, bool $canCreateRootGroup, ?Group $selectedGroup)
    {
        $polygon = $selectedGroup?->consulting_area;

        $user = request()->user();

        $canEditGroup = $selectedGroup ? $user->can('update', $selectedGroup) : false;

        return Inertia::render('Groups/Index', new GroupsIndexData(
            groups: $groups,
            canCreateRootGroup: $canCreateRootGroup,
            selectedGroup: $selectedGroup ? GroupData::fromModel($selectedGroup) : null,
            polygon: $polygon,
            canEditGroup: $canEditGroup,
            canCreateGroups: $user->can('createAny', Group::class),
        ));
    }

    public function index(Request $request, #[CurrentUser] User $user)
    {
        if (! $request->user()->can('viewAny', Group::class)) {
            return redirect()->route('groups.index');
        }

        $groups = $this->listGroups($user)
            ->map(fn (Group $group) => GroupData::fromModel($group));

        return $this->showPage(
            $groups,
            $request->user()->can('create', Group::class),
            null
        );
    }

    private function listGroups(User $user): Collection
    {
        return Group::with(['parent', 'children', 'users'])
            ->withCount(['users', 'advices'])
            ->get()
            ->filter(fn (Group $group) => $user->can('view', $group));
    }

    public function show(Group $group, Request $request)
    {
        if (! $request->user()->can('view', $group)) {
            return redirect()->route('groups.index');
        }

        $expandGroups = collect();
        $currentGroup = $group;

        while ($currentGroup->parent_id) {
            $expandGroups->push($currentGroup->parent_id);
            $currentGroup = $currentGroup->parent;
        }

        $groups = $this->listGroups($request->user())
            ->map(fn (Group $group) => GroupData::fromModel($group));

        $groups = $groups->map(function (GroupData $groupData) use ($expandGroups, $group) {
            $groupData = $groupData->toArray();
            $groupData['isExpanded'] = $expandGroups->contains($groupData['id']);
            $groupData['isSelected'] = $groupData['id'] === $group->id;

            return $groupData;
        });

        return $this->showPage(
            $groups,
            $request->user()->can('create', Group::class),
            $group
        );
    }

    public function store(StoreGroupRequest $request)
    {
        $validated = $request->validated();

        $group = Group::create($validated);

        return redirect()->route('groups.show', $group)->with('success', 'Initiative erfolgreich erstellt.');
    }

    public function update(UpdateGroupRequest $request, Group $group)
    {
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            if ($group->logo_path) {
                Storage::disk('public')->delete($group->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('group-logos', 'public');
        }

        if ($request->remove_logo) {
            if ($group->logo_path) {
                Storage::disk('public')->delete($group->logo_path);
            }
            $validated['logo_path'] = null;
        }

        $group->update($validated);

        return redirect()->back()->with('success', 'Initiative erfolgreich aktualisiert.');
    }

    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);
        $parent = $group->parent;

        $group->delete();

        if ($parent) {
            $route = redirect()->route('groups.show', $parent);
        } else {
            $route = redirect()->route('groups.index');
        }

        return $route->with('success', 'Initiative erfolgreich gelöscht.');
    }

    public function updateConsultingArea(UpdateGroupConsultingAreaRequest $request, Group $group)
    {

        $group->consulting_area = $request->validated('polygon.coordinates');
        $group->save();

        return redirect()->back()->with('success', 'Beratungsgebiet wurde erfolgreich gespeichert.');
    }

    public function deleteConsultingArea(Group $group)
    {
        $this->authorize('manageArea', $group);

        $group->consulting_area = null;
        $group->save();

        return redirect()->back()->with('warning', 'Beratungsgebiet wurde erfolgreich gelöscht.');
    }
}
