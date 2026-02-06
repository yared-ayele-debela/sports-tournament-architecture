<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        $this->checkPermission('manage_users');

        // Use service to search users
        $query = $this->userService->searchUsers(
            $request->input('search'),
            $request->input('role')
        );

        $users = $query->paginate(15);

        // Get available roles for filter dropdown
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $this->checkPermission('manage_users');
        $roles = Role::orderBy('name')->get();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkPermission('manage_users');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        try {
            // Use service to create user
            $this->userService->createUser(
                [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                ],
                $validated['role']
            );

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User created successfully.');

        } catch (\App\Exceptions\BusinessLogicException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getUserMessage());
        } catch (\App\Exceptions\ResourceNotFoundException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): View
    {
        $this->checkPermission('manage_users');
        $user->load('roles');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $this->checkPermission('manage_users');
        $user->load('roles');
        $roles = Role::orderBy('name')->get();
        $userRole = $user->roles->first()?->name;

        return view('admin.users.edit', compact('user', 'roles', 'userRole'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->checkPermission('manage_users');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'role' => ['required', 'exists:roles,name'],
        ]);

        try {
            // Validate password if provided
            $password = null;
            if ($request->filled('password')) {
                $request->validate([
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                ]);
                $password = $request->password;
            }

            // Use service to update user
            $this->userService->updateUser(
                $user,
                [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                ],
                $validated['role'],
                $password
            );

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User updated successfully.');

        } catch (\App\Exceptions\BusinessLogicException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getUserMessage());
        } catch (\App\Exceptions\ResourceNotFoundException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->checkPermission('manage_users');

        try {
            // Use service to delete user
            $this->userService->deleteUser($user);

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User deleted successfully.');

        } catch (\App\Exceptions\BusinessLogicException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }
}
