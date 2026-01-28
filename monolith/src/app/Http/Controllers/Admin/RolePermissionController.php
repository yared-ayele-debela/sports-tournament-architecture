<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    /**
     * Display the role permissions management page.
     */
    public function index(): View
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        
        // Group permissions by category
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            if (str_starts_with($permission->name, 'manage_')) {
                return 'Management';
            } elseif (str_starts_with($permission->name, 'view_')) {
                return 'Viewing';
            } elseif (str_starts_with($permission->name, 'create_')) {
                return 'Creation';
            } elseif (str_starts_with($permission->name, 'edit_')) {
                return 'Editing';
            } elseif (str_starts_with($permission->name, 'delete_')) {
                return 'Deletion';
            } elseif (str_starts_with($permission->name, 'moderate_')) {
                return 'Moderation';
            } elseif (str_starts_with($permission->name, 'export_') || str_starts_with($permission->name, 'import_')) {
                return 'Data Operations';
            } else {
                return 'Other';
            }
        });

        return view('admin.role-permissions.index', compact('roles', 'groupedPermissions'));
    }

    /**
     * Show the form for editing permissions for a specific role.
     */
    public function edit(Role $role): View
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('name')->get();
        
        // Group permissions by category
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            if (str_starts_with($permission->name, 'manage_')) {
                return 'Management';
            } elseif (str_starts_with($permission->name, 'view_')) {
                return 'Viewing';
            } elseif (str_starts_with($permission->name, 'create_')) {
                return 'Creation';
            } elseif (str_starts_with($permission->name, 'edit_')) {
                return 'Editing';
            } elseif (str_starts_with($permission->name, 'delete_')) {
                return 'Deletion';
            } elseif (str_starts_with($permission->name, 'moderate_')) {
                return 'Moderation';
            } elseif (str_starts_with($permission->name, 'export_') || str_starts_with($permission->name, 'import_')) {
                return 'Data Operations';
            } else {
                return 'Other';
            }
        });

        // Get role's current permission IDs for checkbox checking
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();

        return view('admin.role-permissions.edit', compact('role', 'groupedPermissions', 'rolePermissionIds'));
    }

    /**
     * Update permissions for a specific role.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        try {
            // Sync permissions to role
            $role->permissions()->sync($validated['permissions'] ?? []);

            return redirect()
                ->route('admin.role-permissions.index')
                ->with('success', 'Permissions updated for role: ' . $role->name);
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update permissions: ' . $e->getMessage());
        }
    }

    /**
     * Show permissions for a specific role.
     */
    public function show(Role $role): View
    {
        $role->load('permissions');
        
        // Group permissions by category
        $groupedPermissions = $role->permissions->groupBy(function ($permission) {
            if (str_starts_with($permission->name, 'manage_')) {
                return 'Management';
            } elseif (str_starts_with($permission->name, 'view_')) {
                return 'Viewing';
            } elseif (str_starts_with($permission->name, 'create_')) {
                return 'Creation';
            } elseif (str_starts_with($permission->name, 'edit_')) {
                return 'Editing';
            } elseif (str_starts_with($permission->name, 'delete_')) {
                return 'Deletion';
            } elseif (str_starts_with($permission->name, 'moderate_')) {
                return 'Moderation';
            } elseif (str_starts_with($permission->name, 'export_') || str_starts_with($permission->name, 'import_')) {
                return 'Data Operations';
            } else {
                return 'Other';
            }
        });

        return view('admin.role-permissions.show', compact('role', 'groupedPermissions'));
    }
}
