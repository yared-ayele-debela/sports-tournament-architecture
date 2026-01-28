<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Add model policies here
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
    }

    /**
     * Register the application's gates.
     */
    protected function registerGates(): void
    {
        // Super admin gate - bypass all checks
        Gate::before(function (User $user) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // User management gates
        Gate::define('manage_users', function (User $user) {
            return $user->hasPermission('manage_users');
        });

        Gate::define('view_users', function (User $user) {
            return $user->hasPermission('view_users');
        });

        Gate::define('create_users', function (User $user) {
            return $user->hasPermission('create_users');
        });

        Gate::define('edit_users', function (User $user) {
            return $user->hasPermission('edit_users');
        });

        Gate::define('delete_users', function (User $user) {
            return $user->hasPermission('delete_users');
        });

        // Match management gates
        Gate::define('manage_matches', function (User $user) {
            return $user->hasPermission('manage_matches');
        });

        Gate::define('view_matches', function (User $user) {
            return $user->hasPermission('view_matches');
        });

        Gate::define('create_matches', function (User $user) {
            return $user->hasPermission('create_matches');
        });

        Gate::define('edit_matches', function (User $user) {
            return $user->hasPermission('edit_matches');
        });

        Gate::define('delete_matches', function (User $user) {
            return $user->hasPermission('delete_matches');
        });

        // Tournament management gates
        Gate::define('manage_tournaments', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });

        Gate::define('view_tournaments', function (User $user) {
            return $user->hasPermission('view_tournaments');
        });

        Gate::define('create_tournaments', function (User $user) {
            return $user->hasPermission('create_tournaments');
        });

        Gate::define('edit_tournaments', function (User $user) {
            return $user->hasPermission('edit_tournaments');
        });

        Gate::define('delete_tournaments', function (User $user) {
            return $user->hasPermission('delete_tournaments');
        });

        // Team management gates
        Gate::define('manage_teams', function (User $user) {
            return $user->hasPermission('manage_teams');
        });

        Gate::define('view_teams', function (User $user) {
            return $user->hasPermission('view_teams');
        });

        Gate::define('create_teams', function (User $user) {
            return $user->hasPermission('create_teams');
        });

        Gate::define('edit_teams', function (User $user) {
            return $user->hasPermission('edit_teams');
        });

        Gate::define('delete_teams', function (User $user) {
            return $user->hasPermission('delete_teams');
        });

        // Report viewing gates
        Gate::define('view_reports', function (User $user) {
            return $user->hasPermission('view_reports');
        });

        Gate::define('view_analytics', function (User $user) {
            return $user->hasPermission('view_analytics');
        });

        // Role and permission management gates
        Gate::define('manage_roles', function (User $user) {
            return $user->hasPermission('manage_roles');
        });

        Gate::define('manage_permissions', function (User $user) {
            return $user->hasPermission('manage_permissions');
        });

        Gate::define('assign_permissions', function (User $user) {
            return $user->hasPermission('assign_permissions');
        });

        // Content moderation gates
        Gate::define('moderate_content', function (User $user) {
            return $user->hasPermission('moderate_content');
        });

        // Data operation gates
        Gate::define('export_data', function (User $user) {
            return $user->hasPermission('export_data');
        });

        Gate::define('import_data', function (User $user) {
            return $user->hasPermission('import_data');
        });

        // System management gates
        Gate::define('manage_settings', function (User $user) {
            return $user->hasPermission('manage_settings');
        });

        Gate::define('view_logs', function (User $user) {
            return $user->hasPermission('view_logs');
        });

        Gate::define('backup_system', function (User $user) {
            return $user->hasPermission('backup_system');
        });

        // Dynamic permission gates for any custom permissions
        Gate::define('access_api', function (User $user) {
            return $user->hasPermission('access_api');
        });
    }
}
