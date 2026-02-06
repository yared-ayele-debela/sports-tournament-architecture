<div class="h-full flex flex-col">
  <!-- Header -->
  <div class="flex items-center justify-between px-4 py-4 border-b border-slate-700">
    <div class="flex items-center gap-3">
      <div class="bg-gradient-to-br from-indigo-600 to-sky-500 text-white rounded-md w-10 h-10 flex items-center justify-center">
        <img src="{{ asset('build/assets/logo.png') }}" alt="Logo" class="w-10 h-10">
      </div>
      <div class="label">
        <div class="text-sm font-semibold">Dashboard</div>
        <div class="text-xs text-slate-400 -mt-0.5">Control panel</div>
      </div>
    </div>

    <!-- Close for mobile -->
    <button id="sidebar-close" class="md:hidden p-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-md">
      <i class="fas fa-times text-lg"></i>
    </button>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 overflow-auto px-2 py-4 space-y-2">
    @if(auth()->user()->hasPermission('view_admin_dashboard'))
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-slate-700">
      <i class="fas fa-tachometer-alt w-5 h-5 text-slate-200"></i>
      <span class="label text-sm">Dashboard</span>
    </a>
    @endif


    @if(auth()->user()->hasPermission('manage_users'))
    <a href="{{route('admin.users.index')}}" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-slate-700">
      <i class="fas fa-users w-5 h-5 text-slate-200"></i>
      <span class="label text-sm">Users</span>
    </a>
    @endif

    @if(auth()->user()->hasPermission('manage_roles'))
    <a href="{{ route('admin.role-permissions.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-slate-700">
      <i class="fas fa-shield-alt w-5 h-5 text-slate-200"></i>
      <span class="label text-sm">Role Permissions</span>
    </a>
    @endif

    @if(auth()->user()->hasPermission('manage_sports'))
    <a href="{{route('admin.sports.index')}}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
            <i class="fas fa-futbol w-5 h-5 flex-shrink-0"></i>
            <span class="sidebar-text">Sports</span>
        </a>
    @endif

    @if(auth()->user()->hasPermission('manage_tournaments'))
        <a href="{{ route('admin.tournaments.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.tournaments.*') ? 'bg-gray-800 text-white' : '' }}">
            <i class="fas fa-trophy w-5 h-5 flex-shrink-0"></i>
            <span class="sidebar-text">Tournaments</span>
        </a>
    @endif

    @if(auth()->user()->hasPermission('manage_tournaments'))
        <a href="{{ route('admin.tournament-settings.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.tournament-settings.*') ? 'bg-gray-800 text-white' : '' }}">
            <i class="fas fa-cog w-5 h-5 flex-shrink-0"></i>
            <span class="sidebar-text">Tournament Settings</span>
        </a>
    @endif

    @if(auth()->user()->hasPermission('manage_venues'))
        <a href="{{ route('admin.venues.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.venues.*') ? 'bg-gray-800 text-white' : '' }}">
            <i class="fas fa-map-marker-alt w-5 h-5 flex-shrink-0"></i>
            <span class="sidebar-text">Venues</span>
        </a>
    @endif

    @if(auth()->user()->hasPermission('manage_teams'))
        <a href="{{ route('admin.teams.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.teams.*') ? 'bg-gray-800 text-white' : '' }}">
            <i class="fas fa-users w-5 h-5 flex-shrink-0"></i>
            <span class="sidebar-text">Teams</span>
        </a>
    @endif

    @if(auth()->user()->hasPermission('manage_matches'))
        <a href="{{ route('admin.matches.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.matches.*') ? 'bg-gray-800 text-white' : '' }}">
            <i class="fas fa-calendar-alt w-5 h-5 flex-shrink-0"></i>
            <span class="sidebar-text">Matches</span>
        </a>
    @endif

    @auth
        @if(auth()->user()->hasPermission('view_coach_dashboard'))
            <div class="text-xs text-slate-400 px-3 mt-3 mb-1 uppercase">Coach Section</div>

            <a href="{{ route('admin.coach-dashboard') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.coach-dashboard') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-chalkboard-teacher w-5 h-5 flex-shrink-0"></i>
                <span class="sidebar-text">Coach Dashboard</span>
            </a>

            @if(auth()->user()->hasPermission('manage_own_teams'))
            <a href="{{ url('admin/coach/teams') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.teams.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-user-friends w-5 h-5 flex-shrink-0"></i>
                <span class="sidebar-text">My Teams</span>
            </a>
            @endif
        @endif

        @if(auth()->user()->hasPermission('view_referee_dashboard'))
        <div class="text-xs text-slate-400 px-3 mt-3 mb-1 uppercase">Referee Section</div>

            <a href="{{ route('admin.referee.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.coach-dashboard') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-flag w-5 h-5 flex-shrink-0"></i>
                <span class="sidebar-text">Referee Dashboard</span>
            </a>

            @if(auth()->user()->hasPermission('manage_my_matches'))
            <a href="{{ route('admin.referee.matches.index')}}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.teams.*') ? 'bg-gray-800 text-white' : '' }}">
                <i class="fas fa-futbol w-5 h-5 flex-shrink-0"></i>
                <span class="sidebar-text">My Matches</span>
            </a>
            @endif
        @endif
    @endauth

    <div class="text-xs text-slate-400 px-3 mt-3 mb-1 uppercase">Account</div>

    <!-- Profile Dropdown -->
    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" class="w-full flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('admin.profile.*') ? 'bg-gray-800 text-white' : '' }}">
            <i class="fas fa-user-circle w-5 h-5 flex-shrink-0"></i>
            <span class="sidebar-text">Profile</span>
            <i class="fas fa-chevron-down w-4 h-4 ml-auto transition-transform" :class="open ? 'rotate-180' : ''"></i>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             @click.away="open = false"
             class="absolute left-0 right-0 mt-1 bg-slate-700 rounded-lg shadow-lg z-50">
            <div class="py-1">
                <a href="{{ route('admin.profile.edit') }}"
                   class="flex items-center px-3 py-2 text-sm text-gray-300 hover:bg-slate-600 hover:text-white">
                    <i class="fas fa-edit w-4 h-4 mr-3"></i>
                    Edit Profile
                </a>

                <a href="{{ route('admin.profile.activity') }}"
                   class="flex items-center px-3 py-2 text-sm text-gray-300 hover:bg-slate-600 hover:text-white">
                    <i class="fas fa-clock w-4 h-4 mr-3"></i>
                    Account Activity
                </a>

                <hr class="border-slate-600 my-1">

                <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="w-full flex items-center px-3 py-2 text-sm text-gray-300 hover:bg-slate-600 hover:text-white">
                    <i class="fas fa-sign-out-alt w-4 h-4 mr-3"></i>
                    Logout
                </button>
            </div>
        </div>
    </div>



  <!-- Logout Form (Hidden) -->
  <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
  </form>
</div>
