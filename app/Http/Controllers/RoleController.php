<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(): Response
    {
        // Safety: Ensure only admin has access
        $user = Auth::user();
        $isAdmin = $user->email === 'admin@test.com' || (method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        if (!$isAdmin) {
            abort(403, 'Unauthorized access to roles management.');
        }

        $roles = Role::with('permissions')->orderBy('id', 'asc')->get();
        $permissions = Permission::orderBy('name', 'asc')->get();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = $user->email === 'admin@test.com' || (method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        if (!$isAdmin) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    /**
     * Update the specified role in database.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = $user->email === 'admin@test.com' || (method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        if (!$isAdmin) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        // Safety: Prevent renaming core default roles (Admin, Manager, Employee)
        $isCoreRole = in_array($role->name, ['Admin', 'Manager', 'Employee']);
        if ($isCoreRole && $role->name !== $request->name) {
            return redirect()->back()->withErrors(['name' => 'Core roles (Admin, Manager, Employee) cannot be renamed for system stability.']);
        }

        $role->update([
            'name' => $request->name,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from database.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = $user->email === 'admin@test.com' || (method_exists($user, 'hasRole') && $user->hasRole('Admin'));
        if (!$isAdmin) {
            abort(403);
        }

        // Safety: Prevent deleting core default roles
        if (in_array($role->name, ['Admin', 'Manager', 'Employee'])) {
            return redirect()->back()->withErrors(['status' => 'Core roles (Admin, Manager, Employee) are vital to system structure and cannot be deleted.']);
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }
}
