<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;

class AdminProfileController extends Controller
{
    /**
     * Display the admin's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Get all permissions through roles
        $permissions = collect();
        foreach ($user->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }

        return view('admin.profile.edit', [
            'user' => $user,
            'roles' => $user->roles,
            'permissions' => $permissions->unique('id'),
        ]);
    }

    /**
     * Update the admin's profile information.
     */
    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
        ]);

        $user = $request->user();
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('admin.profile.edit')
            ->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update the admin's password.
     */
    public function updatePassword(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return Redirect::route('admin.profile.edit')
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Delete the admin's account.
     */
    public function destroy(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'string', 'in:DELETE'],
        ]);

        $user = $request->user();

        // Prevent deletion if this is the only admin
        $adminCount = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->count();

        if ($adminCount <= 1) {
            return Redirect::route('admin.profile.edit')
                ->with('error', 'Cannot delete account. At least one admin must remain.');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('login')->with('success', 'Account deleted successfully.');
    }

    }
