<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        // Retrieve all users with their assigned roles and their direct managers
        $users = User::with(['roles', 'manager'])->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Update the role of the specified user.
     */
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|in:Admin,Manager,Employee',
        ]);

        // Spatie syncRoles method to assign the selected role cleanly
        $user->syncRoles([$request->role]);

        return redirect()->back();
    }

    /**
     * Update the direct manager of the specified user.
     */
    public function updateManager(Request $request, User $user)
    {
        $request->validate([
            'manager_id' => 'nullable|exists:users,id',
        ]);

        // Direct assignment to bypass mass assignment constraints safely
        $user->manager_id = $request->manager_id;
        $user->save();

        return redirect()->back();
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        return redirect()->back();
    }
}
