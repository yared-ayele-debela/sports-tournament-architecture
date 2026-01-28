<!-- Footer -->
<footer class="bg-gray-800 text-white py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                        <i class="fas fa-trophy text-white text-sm"></i>
                    </div>
                    <span class="text-lg font-bold">Tournament Hub</span>
                </div>
                <p class="text-gray-400">Your comprehensive platform for sports tournament management and tracking.</p>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="{{ route('home') }}" class="hover:text-white transition">Home</a></li>
                    <li><a href="{{ route('tournaments.index') }}" class="hover:text-white transition">Tournaments</a></li>
                    <li><a href="{{ route('teams.index') }}" class="hover:text-white transition">Teams</a></li>
                    <li><a href="{{ route('matches.index') }}" class="hover:text-white transition">Matches</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Features</h4>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="#" class="hover:text-white transition">Live Scores</a></li>
                    <li><a href="#" class="hover:text-white transition">Tournament Stats</a></li>
                    <li><a href="#" class="hover:text-white transition">Team Rankings</a></li>
                    <li><a href="#" class="hover:text-white transition">Match Schedules</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Follow Us</h4>
                <div class="flex space-x-4">
                    <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center hover:bg-primary transition">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; 2023 Tournament Hub. All rights reserved.</p>
        </div>
    </div>
</footer>
