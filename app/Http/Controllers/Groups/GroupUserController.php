<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupUserRequest;
use App\Http\Requests\UpdateGroupUserRequest;
use App\Models\Group;
use App\Models\User;

class GroupUserController extends Controller
{
    /**
     * Add a user to a group
     */
    public function store(StoreGroupUserRequest $request, Group $group)
    {
        $validated = $request->validated();

        $group->users()->attach($validated['id'], [
            'is_admin' => $request->isAdmin(),
        ]);

        return back()->with('success', 'Berater:in wurde zur Initiative hinzugefügt');
    }

    /**
     * Update a user's role in a group
     */
    public function update(UpdateGroupUserRequest $request, Group $group, User $user)
    {
        $group->users()->updateExistingPivot($user->id, [
            'is_admin' => $request->isAdmin(),
        ]);

        return back()->with('success', 'Rolle wurde gespeichert');
    }

    /**
     * Remove a user from a group
     */
    public function destroy(Group $group, User $user)
    {
        $this->authorize('manageUsers', $group);

        $group->users()->detach($user->id);

        return back()->with('Berater:in wurde aus Initiative entfernt');
    }
}
