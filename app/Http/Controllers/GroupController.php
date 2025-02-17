<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Group::class);
    }

    /**
     * Show the groups management page
     */
    public function index()
    {
        return Inertia::render('Groups/Index', [
            'groups' => Group::with(['parent', 'children', 'users'])
                ->withCount(['users', 'advices'])
                ->get()
                ->map(fn (Group $group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'logo_path' => $group->logo_path ? $group->logo_path : null,
                    'parent_id' => $group->parent_id,
                    'accepts_transfers' => $group->accepts_transfers,
                    'users_count' => $group->users_count,
                    'advices_count' => $group->advices_count,
                ]),
        ]);
    }

    /**
     * Show a specific group
     */
    public function show(Group $group)
    {
        return Inertia::render('Groups/Index', [
            'groups' => Group::with(['parent', 'children', 'users'])
                ->withCount(['users', 'advices'])
                ->get()
                ->map(fn (Group $group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'logo_path' => $group->logo_path ? $group->logo_path : null,
                    'parent_id' => $group->parent_id,
                    'accepts_transfers' => $group->accepts_transfers,
                    'users_count' => $group->users_count,
                    'advices_count' => $group->advices_count,
                ]),
            'selectedGroup' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'logo_path' => $group->logo_path ? $group->logo_path : null,
                'parent_id' => $group->parent_id,
                'accepts_transfers' => $group->accepts_transfers,
            ],
        ]);
    }

    /**
     * Delete a group
     */
    public function destroy(Group $group)
    {
        // Delete logo if exists
        if ($group->logo_path) {
            Storage::disk('public')->delete($group->logo_path);
        }

        $group->delete();

        return redirect()->back()->with('success', 'Initiative erfolgreich gelöscht.');
    }
} 