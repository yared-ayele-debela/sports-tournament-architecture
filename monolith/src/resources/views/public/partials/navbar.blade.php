<!-- Navbar -->
<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                        <i class="fas fa-trophy text-white"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-800">Tournament Hub</span>
                </a>
            </div>
            
            <div class="hidden md:flex items-center space-x-6">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-primary transition font-medium">Home</a>
                <a href="{{ route('tournaments.index') }}" class="text-gray-700 hover:text-primary transition font-medium">Tournaments</a>
                <a href="{{ route('teams.index') }}" class="text-gray-700 hover:text-primary transition font-medium">Teams</a>
                <a href="{{ route('matches.index') }}" class="text-gray-700 hover:text-primary transition font-medium">Matches</a>
                <a href="{{ route('login') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-user-circle mr-2"></i>Sign In
                </a>
            </div>
            
            <button class="md:hidden text-gray-700" onclick="toggleMobileMenu()">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        
        <div class="hidden md:hidden pb-4" id="mobile-menu">
            <div class="flex flex-col space-y-3">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-primary transition font-medium">Home</a>
                <a href="{{ route('tournaments.index') }}" class="text-gray-700 hover:text-primary transition font-medium">Tournaments</a>
                <a href="{{ route('teams.index') }}" class="text-gray-700 hover:text-primary transition font-medium">Teams</a>
                <a href="{{ route('matches.index') }}" class="text-gray-700 hover:text-primary transition font-medium">Matches</a>
                <a href="{{ route('login') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-user-circle mr-2"></i>Sign In
                </a>
            </div>
        </div>
    </div>
</nav>
