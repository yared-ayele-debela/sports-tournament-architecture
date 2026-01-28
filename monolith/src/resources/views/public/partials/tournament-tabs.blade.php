<!-- Tournament Navigation Tabs -->
@if(isset($tournament))
<section class="bg-white border-b sticky top-16 z-40">
    <div class="container mx-auto px-4">
        <div class="flex space-x-8 overflow-x-auto">
            <a href="{{ route('tournaments.show', $tournament) }}" 
               class="py-4 border-b-2 {{ request()->routeIs('tournaments.show') ? 'border-primary text-primary' : 'border-transparent text-gray-600' }} hover:text-gray-800 font-medium whitespace-nowrap">
                <i class="fas fa-info-circle mr-2"></i>Overview
            </a>
            <a href="{{ route('tournaments.matches', $tournament) }}" 
               class="py-4 border-b-2 {{ request()->routeIs('tournaments.matches') ? 'border-primary text-primary' : 'border-transparent text-gray-600' }} hover:text-gray-800 font-medium whitespace-nowrap">
                <i class="fas fa-futbol mr-2"></i>Matches
            </a>
            <a href="{{ route('tournaments.standings', $tournament) }}" 
               class="py-4 border-b-2 {{ request()->routeIs('tournaments.standings') ? 'border-primary text-primary' : 'border-transparent text-gray-600' }} hover:text-gray-800 font-medium whitespace-nowrap">
                <i class="fas fa-trophy mr-2"></i>Standings
            </a>
            <a href="{{ route('tournaments.teams', $tournament) }}" 
               class="py-4 border-b-2 {{ request()->routeIs('tournaments.teams') ? 'border-primary text-primary' : 'border-transparent text-gray-600' }} hover:text-gray-800 font-medium whitespace-nowrap">
                <i class="fas fa-users mr-2"></i>Teams
            </a>
        </div>
    </div>
</section>
@endif
