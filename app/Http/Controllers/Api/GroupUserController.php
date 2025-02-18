<?php

namespace App\Http\Controllers\Api;

use App\Data\GroupUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupUserRequest;
use App\Http\Requests\UpdateGroupUserRequest;
use App\Models\Group;
use App\Models\User;

class GroupUserController extends Controller
{

	private function userToDTO(User $user): GroupUserData
    {
        if (!$user->relationLoaded('groups')) {
            throw new \RuntimeException('User groups relation must be loaded before converting to DTO');
        }

        $group = $user->groups->first();
        if (!$group) {
            throw new \RuntimeException('User must be in the group before converting to DTO');
        }

        return new GroupUserData(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            is_admin: $group->pivot->is_admin,
        );
    }

    /**
     * list users of a group
     */
    public function index(Group $group)
    {
        $this->authorize('manageUsers', $group);

        $users = $group->users;
        $users->load(['groups' => function($query) use ($group) {
            $query->where('groups.id', $group->id);
        }]);

        return response()->json([
            'data' => $users->map(fn (User $user) => $this->userToDTO($user))
        ]);
    }

    /**
     * Add a user to a group
     */
    public function store(StoreGroupUserRequest $request, Group $group)
    {
        $validated = $request->validated();

        $group->users()->attach($validated['id'], [
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        $user = User::findOrFail($validated['id']);
        $user->load(['groups' => function($query) use ($group) {
            $query->where('groups.id', $group->id);
        }]);

        return response()->json($this->userToDTO($user));
    }

    /**
     * Update a user's role in a group
     */
    public function update(UpdateGroupUserRequest $request, Group $group, User $user): GroupUserData
    {
        $validated = $request->validated();

        $group->users()->updateExistingPivot($user->id, [
            'is_admin' => $validated['is_admin'],
        ]);

        $user->load(['groups' => function($query) use ($group) {
            $query->where('groups.id', $group->id);
        }]);

        return $this->userToDTO($user);
    }

    /**
     * Remove a user from a group
     */
    public function destroy(Group $group, User $user)
    {
        $this->authorize('manageUsers', $group);

        $group->users()->detach($user->id);

        return response()->noContent();
    }
} 