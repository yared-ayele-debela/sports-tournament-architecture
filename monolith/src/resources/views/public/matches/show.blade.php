@extends('layouts.app')

@section('title', $match->homeTeam->name . ' vs ' . $match->awayTeam->name . ' - Match Details')

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }} - Tournament Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#64748b',
                        accent: '#f59e0b',
                        success: '#10b981',
                        danger: '#ef4444',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
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

    <!-- Match Header -->
    <section class="bg-gradient-to-r from-primary to-blue-600 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="text-center mb-6">
                <div class="inline-block px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm font-semibold mb-4">
                    {{ $match->tournament->name }}
                </div>
                <h1 class="text-4xl font-bold mb-4">Match Details</h1>
            </div>
            
            <div class="bg-white bg-opacity-10 rounded-2xl p-8 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div class="text-center flex-1">
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-3xl font-bold">{{ substr($match->homeTeam->name, 0, 1) }}</span>
                        </div>
                        <h3 class="text-xl font-bold">{{ $match->homeTeam->name }}</h3>
                        <div class="text-sm opacity-75">Home</div>
                    </div>
                    
                    <div class="px-8">
                        <div class="text-5xl font-bold text-center">
                            {{ $match->home_score ?? '-' }} : {{ $match->away_score ?? '-' }}
                        </div>
                        <div class="text-center mt-2">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                {{ $match->status === 'completed' ? 'bg-success text-white' : 
                                   ($match->status === 'in_progress' ? 'bg-accent text-white' : 
                                   ($match->status === 'cancelled' ? 'bg-danger text-white' : 
                                   'bg-white text-primary')) }}">
                                {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="text-center flex-1">
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-3xl font-bold">{{ substr($match->awayTeam->name, 0, 1) }}</span>
                        </div>
                        <h3 class="text-xl font-bold">{{ $match->awayTeam->name }}</h3>
                        <div class="text-sm opacity-75">Away</div>
                    </div>
                </div>
                
                <div class="flex justify-center items-center gap-6 mt-6 text-sm">
                    <span><i class="far fa-calendar mr-2"></i>{{ $match->match_date->format('M j, Y') }}</span>
                    <span><i class="far fa-clock mr-2"></i>{{ $match->match_date->format('H:i') }}</span>
                    <span><i class="fas fa-map-marker-alt mr-2"></i>{{ $match->venue->name ?? 'TBD' }}</span>
                    <span><i class="fas fa-tag mr-2"></i>Round {{ $match->round_number }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Match Content -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Match Statistics -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold mb-6">Match Statistics</h2>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold mb-4">{{ $match->homeTeam->name }}</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Possession</span>
                                        <span class="font-semibold">{{ $match->home_possession ?? 50 }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Shots</span>
                                        <span class="font-semibold">{{ $match->home_shots ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Shots on Target</span>
                                        <span class="font-semibold">{{ $match->home_shots_on_target ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Corners</span>
                                        <span class="font-semibold">{{ $match->home_corners ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Fouls</span>
                                        <span class="font-semibold">{{ $match->home_fouls ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="font-semibold mb-4">{{ $match->awayTeam->name }}</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Possession</span>
                                        <span class="font-semibold">{{ $match->away_possession ?? 50 }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Shots</span>
                                        <span class="font-semibold">{{ $match->away_shots ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Shots on Target</span>
                                        <span class="font-semibold">{{ $match->away_shots_on_target ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Corners</span>
                                        <span class="font-semibold">{{ $match->away_corners ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Fouls</span>
                                        <span class="font-semibold">{{ $match->away_fouls ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Match Events -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold mb-6">Match Events</h2>
                        @if($match->events && $match->events->count() > 0)
                            <div class="space-y-4">
                                @foreach($match->events as $event)
                                    <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                        <div class="text-sm font-semibold text-gray-500 w-12">
                                            {{ $event->minute }}'
                                        </div>
                                        <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                            <i class="fas fa-futbol text-white text-xs"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-semibold">{{ $event->type }}</div>
                                            <div class="text-sm text-gray-600">{{ $event->player->name }}</div>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $event->team->name }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                No match events recorded yet.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-8">
                    <!-- Match Info -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-bold mb-4">Match Information</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-600 text-sm">Tournament</span>
                                <div class="font-semibold">{{ $match->tournament->name }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Venue</span>
                                <div class="font-semibold">{{ $match->venue->name ?? 'TBD' }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Date & Time</span>
                                <div class="font-semibold">{{ $match->match_date->format('M j, Y - H:i') }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Round</span>
                                <div class="font-semibold">Round {{ $match->round_number }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Status</span>
                                <div class="font-semibold">{{ ucfirst(str_replace('_', ' ', $match->status)) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-bold mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="{{ route('tournaments.show', $match->tournament) }}" class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-blue-600 transition">
                                <i class="fas fa-trophy mr-2"></i>View Tournament
                            </a>
                            <a href="{{ route('teams.show', $match->homeTeam) }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-users mr-2"></i>{{ $match->homeTeam->name }}
                            </a>
                            <a href="{{ route('teams.show', $match->awayTeam) }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-users mr-2"></i>{{ $match->awayTeam->name }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>
</body>
</html>
@endsection
