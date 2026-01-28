<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request): View
    {
        $query = Permission::orderBy('name');
        
        // Search by permission name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        $permissions = $query->paginate(20);

        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create(): View
    {
        return view('admin.permissions.create');
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                'alpha_dash', 
                Rule::unique('permissions')
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            Permission::create($validated);

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', 'Permission created successfully.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission): View
    {
        $permission->load('roles');
        return view('admin.permissions.show', compact('permission'));
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission): View
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                'alpha_dash', 
                Rule::unique('permissions')->ignore($permission->id)
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $permission->update($validated);

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', 'Permission updated successfully.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update permission: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete permission "' . $permission->name . '" because it is assigned to ' . $permission->roles()->count() . ' role(s). Please remove from roles first.');
        }

        try {
            $permission->delete();

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', 'Permission deleted successfully.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }
}
