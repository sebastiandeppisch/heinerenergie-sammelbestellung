<?php

namespace App\Http\Controllers\Api;

use App\Data\AdviceStatusData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupAdviceStatusRequest;
use App\Http\Requests\UpdateGroupAdviceStatusRequest;
use App\Models\AdviceStatus;
use App\Models\AdviceStatusGroup;
use App\Models\Group;

class GroupAdviceStatusController extends Controller
{
    public function index(Group $group)
    {
        $this->authorize('viewAny', [AdviceStatusGroup::class, $group]);

        $statuses = AdviceStatus::whereIn('group_id', $group->getHierarchyIds())->get();

        return $statuses->map(fn (AdviceStatus $status) => AdviceStatusData::fromModel($status, $group));
    }

    public function store(StoreGroupAdviceStatusRequest $request, Group $group)
    {
        $advicestatus = $group->ownStatuses()->create($request->validated());

        return AdviceStatusData::fromModel($advicestatus, $group);
    }

    public function update(UpdateGroupAdviceStatusRequest $request, Group $group, AdviceStatus $advicestatus)
    {
        if ($request->isOnlySettingVisibility()) {
            $advicestatus->usingGroups()->syncWithoutDetaching([
                $group->id => ['visible_in_group' => $request->visible_in_group],
            ]);
        } else {
            $advicestatus->fill($request->validated());
            $advicestatus->save();
        }

        $advicestatus->refresh();

        return AdviceStatusData::fromModel($advicestatus, $group);
    }

    public function destroy(Group $group, AdviceStatus $advicestatus)
    {
        $this->authorize('delete', $advicestatus);
        // Only allow deleting group-specific statuses
        if ($group->is($advicestatus->ownerGroup)) {
            $advicestatus->delete();

            return response()->noContent();
        }

        return response()->json(['message' => 'Cannot delete status from other groups'], 403);
    }
}
