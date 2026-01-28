<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class SecureUserController extends Controller
{
    /**
     * Display a listing of users.
     * Requires 'view_users' permission.
     */
    public function index(Request $request): View
    {
        // Gate check - will abort with 403 if user doesn't have permission
        Gate::authorize('view_users');

        $users = User::with('roles')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     * Requires 'create_users' permission.
     */
    public function create(): View
    {
        // Gate check
        Gate::authorize('create_users');

        $roles = \App\Models\Role::orderBy('name')->get();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     * Requires 'create_users' permission.
     */
    public function store(Request $request): RedirectResponse
    {
        // Gate check
        Gate::authorize('create_users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            ]);

            // Assign roles if provided
            if (!empty($validated['roles'])) {
                $user->roles()->attach($validated['roles']);
            }

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User created successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified user.
     * Requires 'edit_users' permission.
     */
    public function edit(User $user): View
    {
        // Gate check
        Gate::authorize('edit_users');

        $user->load('roles');
        $roles = \App\Models\Role::orderBy('name')->get();
        $userRoleIds = $user->roles->pluck('id')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'userRoleIds'));
    }

    /**
     * Update the specified user in storage.
     * Requires 'edit_users' permission.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Gate check
        Gate::authorize('edit_users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        try {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Update password if provided
            if (!empty($validated['password'])) {
                $user->update([
                    'password' => \Illuminate\Support\Facades\Hash::make($validated['password'])
                ]);
            }

            // Sync roles
            if (isset($validated['roles'])) {
                $user->roles()->sync($validated['roles']);
            }

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user from storage.
     * Requires 'delete_users' permission.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Gate check
        Gate::authorize('delete_users');

        // Prevent deletion of self
        if ($user->id === \Illuminate\Support\Facades\Auth::id()) {
            return redirect()
                ->back()
                ->with('error', 'You cannot delete your own account.');
        }

        try {
            $user->roles()->detach();
            $user->delete();

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User deleted successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Display user management dashboard.
     * Requires 'manage_users' permission (full access).
     */
    public function dashboard(): View
    {
        // Gate check for full management access
        Gate::authorize('manage_users');

        $stats = [
            'total_users' => User::count(),
            'admin_users' => User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->count(),
            'referee_users' => User::whereHas('roles', function ($query) {
                $query->where('name', 'referee');
            })->count(),
            'coach_users' => User::whereHas('roles', function ($query) {
                $query->where('name', 'coach');
            })->count(),
            'recent_users' => User::orderBy('created_at', 'desc')->take(5)->get(),
        ];

        return view('admin.users.dashboard', compact('stats'));
    }

    /**
     * Example of using can() helper in Blade context
     * This method shows how to check permissions without aborting
     */
    public function showPermissionExamples(): View
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        $permissions = [
            'can_view_users' => Gate::allows('view_users'),
            'can_create_users' => Gate::allows('create_users'),
            'can_edit_users' => Gate::allows('edit_users'),
            'can_delete_users' => Gate::allows('delete_users'),
            'can_manage_users' => Gate::allows('manage_users'),
            'can_view_reports' => Gate::allows('view_reports'),
            'can_manage_tournaments' => Gate::allows('manage_tournaments'),
        ];

        return view('admin.permissions.examples', compact('permissions'));
    }
}
