<!-- Referee Sidebar Navigation -->
<nav class="bg-gray-800 text-white w-64 min-h-screen p-4">
    <div class="mb-8">
        <h2 class="text-xl font-bold">Referee Panel</h2>
        <p class="text-gray-400 text-sm">{{ Auth::user()->name }}</p>
    </div>

    <ul class="space-y-2">
        <li>
            <a href="{{ route('referee.dashboard') }}"
               class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('referee.dashboard') ? 'bg-gray-700' : 'hover:bg-gray-700' }} transition-colors">
                <i class="fas fa-tachometer-alt w-5 h-5"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="{{ route('referee.matches.index') }}"
               class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('referee.matches.*') ? 'bg-gray-700' : 'hover:bg-gray-700' }} transition-colors">
                <i class="fas fa-futbol w-5 h-5"></i>
                <span>Matches</span>
            </a>
        </li>

        <li>
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-cog w-5 h-5"></i>
                <span>Admin Panel</span>
            </a>
        </li>

        <li>
            <a href="{{ route('coach.dashboard') }}"
               class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-chalkboard-teacher w-5 h-5"></i>
                <span>Coach Panel</span>
            </a>
        </li>

        <li class="pt-4 mt-4 border-t border-gray-700">
            <a href="{{ route('logout') }}"
               method="POST"
               class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-sign-out-alt w-5 h-5"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>
