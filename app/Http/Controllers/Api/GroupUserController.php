<?php

namespace App\Http\Controllers\Api;

use App\Data\GroupUserData;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

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
    public function store(Request $request, Group $group)
    {
        $this->authorize('manageUsers', $group);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($group) {
                    if ($group->users()->where('users.id', $value)->exists()) {
                        $fail('The user is already a member of this group.');
                    }
                },
            ],
            'is_admin' => 'boolean',
        ]);

        $group->users()->attach($validated['user_id'], [
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->load(['groups' => function($query) use ($group) {
            $query->where('groups.id', $group->id);
        }]);

        return response()->json($this->userToDTO($user));
    }

    /**
     * Update a user's role in a group
     */
    public function update(Request $request, Group $group, User $user): GroupUserData
    {
        $this->authorize('manageUsers', $group);

        $validated = $request->validate([
            'is_admin' => 'required|boolean',
        ]);

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