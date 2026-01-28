<div class="flex items-center justify-between px-4 py-3">
  <div class="flex items-center gap-4">
    <!-- Sidebar toggle (always visible) -->
    <button id="sidebar-toggle" aria-label="Toggle sidebar"
            class="p-2 rounded-md text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300">
      <!-- minimalist menu icon -->
      <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/>
      </svg>
    </button>

    <!-- Page title / breadcrumb -->
    <div class="hidden sm:block">
      <h1 class="text-lg font-semibold text-slate-900">@yield('title', 'Dashboard')</h1>
      <p class="text-sm text-slate-500 mt-0.5">@yield('subtitle','Welcome back')</p>
    </div>
  </div>

  <div class="flex items-center gap-3">
    <!-- Search (desktop) -->
    

    <!-- User -->
    <div class="relative">
      <button id="user-menu-btn" class="flex items-center gap-2 p-1 rounded-md hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300">
        <img class="h-8 w-8 rounded-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'User') }}&bold=true&background=111827&color=fff" alt="avatar">
        <span class="hidden md:block text-sm font-medium text-slate-700">{{ Auth::user()->name ?? 'Admin' }}</span>
        <svg class="hidden md:block w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <!-- Dropdown -->
      <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-md ring-1 ring-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100">
          <p class="text-sm font-medium text-slate-900">{{ Auth::user()->name ?? 'Admin' }}</p>
          <p class="text-xs text-slate-500">{{ Auth::user()->email ?? 'you@example.com' }}</p>
        </div>
        <a href="" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Sign out</button>
        </form>
      </div>
    </div>
  </div>
</div>