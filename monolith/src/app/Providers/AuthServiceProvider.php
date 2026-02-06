<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
     * Only uses permissions that exist in the database.
     */
    protected function registerGates(): void
    {
        // Super admin gate - bypass all checks
        Gate::before(function (User $user) {
            // Check for both 'admin' and 'Administrator' role names
            if ($user->hasRole('admin') || $user->hasRole('Administrator')) {
                return true;
            }
        });

        // ============================================
        // Direct Permission Gates (using actual permissions from DB)
        // ============================================

        // User management
        Gate::define('manage_users', function (User $user) {
            return $user->hasPermission('manage_users');
        });

        // Tournament management
        Gate::define('manage_tournaments', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });

        // Sports management
        Gate::define('manage_sports', function (User $user) {
            return $user->hasPermission('manage_sports');
        });

        // Venues management
        Gate::define('manage_venues', function (User $user) {
            return $user->hasPermission('manage_venues');
        });

        // Teams management
        Gate::define('manage_teams', function (User $user) {
            return $user->hasPermission('manage_teams');
        });

        // Players management
        Gate::define('manage_players', function (User $user) {
            return $user->hasPermission('manage_players');
        });

        // Matches management
        Gate::define('manage_matches', function (User $user) {
            return $user->hasPermission('manage_matches');
        });

        // Referee: Manage own assigned matches only
        Gate::define('manage_my_matches', function (User $user) {
            return $user->hasPermission('manage_my_matches');
        });

        // Match events
        Gate::define('record_events', function (User $user) {
            return $user->hasPermission('record_events');
        });

        // Reports
        Gate::define('submit_reports', function (User $user) {
            return $user->hasPermission('submit_reports');
        });

        // Public access
        Gate::define('view_public', function (User $user) {
            return $user->hasPermission('view_public');
        });

        // Role and permission management
        Gate::define('manage_roles', function (User $user) {
            return $user->hasPermission('manage_roles');
        });

        Gate::define('manage_permissions', function (User $user) {
            return $user->hasPermission('manage_permissions');
        });

        // Coach-specific permissions
        Gate::define('manage_own_teams', function (User $user) {
            return $user->hasPermission('manage_own_teams');
        });

        Gate::define('manage_own_players', function (User $user) {
            return $user->hasPermission('manage_own_players');
        });

        // ============================================
        // Resource.action format gates (for controllers and views)
        // ============================================

        // Users - all actions use manage_users
        Gate::define('users.view', function (User $user) {
            return $user->hasPermission('manage_users');
        });
        Gate::define('users.create', function (User $user) {
            return $user->hasPermission('manage_users');
        });
        Gate::define('users.edit', function (User $user) {
            return $user->hasPermission('manage_users');
        });
        Gate::define('users.delete', function (User $user) {
            return $user->hasPermission('manage_users');
        });

        // Tournaments - all actions use manage_tournaments
        Gate::define('tournaments.view', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });
        Gate::define('tournaments.create', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });
        Gate::define('tournaments.edit', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });
        Gate::define('tournaments.delete', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });
        Gate::define('tournaments.schedule-matches', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });
        Gate::define('tournaments.recalculate-standings', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });

        // Sports - all actions use manage_sports
        Gate::define('sports.view', function (User $user) {
            return $user->hasPermission('manage_sports');
        });
        Gate::define('sports.create', function (User $user) {
            return $user->hasPermission('manage_sports');
        });
        Gate::define('sports.edit', function (User $user) {
            return $user->hasPermission('manage_sports');
        });
        Gate::define('sports.delete', function (User $user) {
            return $user->hasPermission('manage_sports');
        });

        // Venues - all actions use manage_venues
        Gate::define('venues.view', function (User $user) {
            return $user->hasPermission('manage_venues');
        });
        Gate::define('venues.create', function (User $user) {
            return $user->hasPermission('manage_venues');
        });
        Gate::define('venues.edit', function (User $user) {
            return $user->hasPermission('manage_venues');
        });
        Gate::define('venues.delete', function (User $user) {
            return $user->hasPermission('manage_venues');
        });

        // Teams - all actions use manage_teams
        Gate::define('teams.view', function (User $user) {
            return $user->hasPermission('manage_teams');
        });
        Gate::define('teams.create', function (User $user) {
            return $user->hasPermission('manage_teams');
        });
        Gate::define('teams.edit', function (User $user) {
            return $user->hasPermission('manage_teams');
        });
        Gate::define('teams.delete', function (User $user) {
            return $user->hasPermission('manage_teams');
        });

        // Matches - all actions use manage_matches
        Gate::define('matches.view', function (User $user) {
            return $user->hasPermission('manage_matches');
        });
        Gate::define('matches.create', function (User $user) {
            return $user->hasPermission('manage_matches');
        });
        Gate::define('matches.edit', function (User $user) {
            return $user->hasPermission('manage_matches');
        });
        Gate::define('matches.delete', function (User $user) {
            return $user->hasPermission('manage_matches');
        });

        // Roles - all actions use manage_roles
        Gate::define('roles.view', function (User $user) {
            return $user->hasPermission('manage_roles');
        });
        Gate::define('roles.create', function (User $user) {
            return $user->hasPermission('manage_roles');
        });
        Gate::define('roles.edit', function (User $user) {
            return $user->hasPermission('manage_roles');
        });
        Gate::define('roles.delete', function (User $user) {
            return $user->hasPermission('manage_roles');
        });

        // Permissions - all actions use manage_permissions
        Gate::define('permissions.view', function (User $user) {
            return $user->hasPermission('manage_permissions');
        });
        Gate::define('permissions.create', function (User $user) {
            return $user->hasPermission('manage_permissions');
        });
        Gate::define('permissions.edit', function (User $user) {
            return $user->hasPermission('manage_permissions');
        });
        Gate::define('permissions.delete', function (User $user) {
            return $user->hasPermission('manage_permissions');
        });

        // Role Permissions - uses manage_roles
        Gate::define('role-permissions.view', function (User $user) {
            return $user->hasPermission('manage_roles');
        });
        Gate::define('role-permissions.edit', function (User $user) {
            return $user->hasPermission('manage_roles');
        });

        // Tournament Settings - uses manage_tournaments
        Gate::define('tournament-settings.view', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });
        Gate::define('tournament-settings.create', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });
        Gate::define('tournament-settings.edit', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });
        Gate::define('tournament-settings.delete', function (User $user) {
            return $user->hasPermission('manage_tournaments');
        });

        // ============================================
        // Dashboard Gates
        // ============================================

        // Admin Dashboard
        Gate::define('dashboard.view', function (User $user) {
            return $user->hasPermission('view_admin_dashboard');
        });
        Gate::define('admin.dashboard.view', function (User $user) {
            return $user->hasPermission('view_admin_dashboard');
        });

        // Coach Dashboard
        Gate::define('view_coach_dashboard', function (User $user) {
            return $user->hasPermission('view_coach_dashboard');
        });

        // Referee Dashboard
        Gate::define('view_referee_dashboard', function (User $user) {
            return $user->hasPermission('view_referee_dashboard');
        });
    }
}
