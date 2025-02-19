<?php

namespace App\Http\Controllers;

use App\Data\GroupData;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Group::class);
    }

    private function showPage(iterable $groups, bool $canCreateRootGroup, ?GroupData $selectedGroup)
    {
        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'canCreateRootGroup' => $canCreateRootGroup,
            'selectedGroup' => $selectedGroup,
        ]);
    }

    public function index(Request $request)
    {
        return $this->showPage(
            Group::with(['parent', 'children', 'users'])
                ->withCount(['users', 'advices'])
                ->get()
                ->map(fn (Group $group) => GroupData::fromModel($group)),
            $request->user()->can('create', [Group::class]),
            null
        );
    }

    public function show(Group $group, Request $request)
    {
        $expandGroups = collect();
        $currentGroup = $group;

        while ($currentGroup->parent_id) {
            $expandGroups->push($currentGroup->parent_id);
            $currentGroup = $currentGroup->parent;
        }

        $groups = Group::with(['parent', 'children', 'users'])
            ->withCount(['users', 'advices'])
            ->get()
            ->map(fn (Group $group) => GroupData::fromModel($group));

        $groups = $groups->map(function (GroupData $groupData) use ($expandGroups, $group) {
            $groupData = $groupData->toArray();
            $groupData['isExpanded'] = $expandGroups->contains($groupData['id']);
            $groupData['isSelected'] = $groupData['id'] === $group->id;

            return $groupData;
        });

        return $this->showPage(
            $groups,
            $request->user()->can('create', [Group::class]),
            GroupData::fromModel($group)
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
        $parent = $group->parent;

        $group->delete();

        if ($parent) {
            $route = redirect()->route('groups.show', $parent);
        } else {
            $route = redirect()->route('groups.index');
        }

        return $route->with('success', 'Initiative erfolgreich gelöscht.');
    }
}
