<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): View
    {
        $query = Role::orderBy('name');
        
        // Search by role name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        $roles = $query->paginate(15);

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        return view('admin.roles.create');
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                'alpha_dash', 
                Rule::unique('roles')
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            Role::create($validated);

            return redirect()
                ->route('admin.roles.index')
                ->with('success', 'Role created successfully.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): View
    {
        $role->load('users');
        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): View
    {
        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                'alpha_dash', 
                Rule::unique('roles')->ignore($role->id)
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $role->update($validated);

            return redirect()
                ->route('admin.roles.index')
                ->with('success', 'Role updated successfully.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Prevent deletion of critical roles
        if (in_array($role->name, ['admin', 'referee', 'coach'])) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete critical role: ' . ucfirst($role->name) . '. This role is essential for system functionality.');
        }

        // Check if role has users assigned
        if ($role->users()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete role "' . $role->name . '" because it has ' . $role->users()->count() . ' user(s) assigned to it. Please reassign users first.');
        }

        try {
            $role->delete();

            return redirect()
                ->route('admin.roles.index')
                ->with('success', 'Role deleted successfully.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }
}
