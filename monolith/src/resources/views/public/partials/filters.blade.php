<!-- Filters Section -->
@if(isset($filters))
<section class="py-8 bg-white border-b">
    <div class="container mx-auto px-4">
        <form method="GET" action="{{ $filters['action'] ?? request()->url() }}" class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex flex-wrap gap-3">
                @if(isset($filters['round']))
                    <select name="round" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Rounds</option>
                        @foreach($filters['rounds'] ?? [] as $round)
                            <option value="{{ $round }}" {{ request('round') == $round ? 'selected' : '' }}>
                                Round {{ $round }}
                            </option>
                        @endforeach
                    </select>
                @endif
                
                @if(isset($filters['status']))
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Status</option>
                        <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="live" {{ request('status') == 'live' ? 'selected' : '' }}>Live</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                @endif
                
                @if(isset($filters['tournament']))
                    <select name="tournament" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Tournaments</option>
                        @foreach($filters['tournaments'] ?? [] as $tournament)
                            <option value="{{ $tournament->id }}" {{ request('tournament') == $tournament->id ? 'selected' : '' }}>
                                {{ $tournament->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
                
                @if(isset($filters['sport']))
                    <select name="sport" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Sports</option>
                        @foreach($filters['sports'] ?? [] as $sport)
                            <option value="{{ $sport->id }}" {{ request('sport') == $sport->id ? 'selected' : '' }}>
                                {{ $sport->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
            
            <div class="flex items-center gap-3">
                @if(isset($filters['search']))
                    <div class="relative">
                        <input type="text" name="search" placeholder="{{ $filters['search_placeholder'] ?? 'Search...' }}" value="{{ request('search') }}" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                @endif
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>
</section>
@endif
