import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Calendar, Trophy, Users, MapPin } from 'lucide-react';
import { tournamentService } from '../../api/tournaments';
import { resultsService } from '../../api/results';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import Badge from '../../components/common/Badge';
import StandingsTable from '../../components/standings/StandingsTable';
import MatchCard from '../../components/match/MatchCard';
import TeamCard from '../../components/team/TeamCard';
import { formatDate } from '../../utils/dateUtils';
import { STATUS_COLORS } from '../../utils/constants';

const TournamentDetails = () => {
  const { id } = useParams();

  // Fetch tournament details
  const { data: tournamentData, isLoading: isLoadingTournament, error: tournamentError } = useQuery({
    queryKey: ['tournament', id],
    queryFn: () => tournamentService.getById(id),
  });

  // Fetch standings
  const { data: standingsData, isLoading: isLoadingStandings } = useQuery({
    queryKey: ['tournamentStandings', id],
    queryFn: () => resultsService.getStandings(id),
    enabled: !!id,
  });

  // Fetch matches
  const { data: matchesData, isLoading: isLoadingMatches } = useQuery({
    queryKey: ['tournamentMatches', id],
    queryFn: () => tournamentService.getMatches(id, { limit: 20 }),
    enabled: !!id,
  });

  // Fetch teams
  const { data: teamsData, isLoading: isLoadingTeams } = useQuery({
    queryKey: ['tournamentTeams', id],
    queryFn: () => tournamentService.getTeams(id, { limit: 20 }),
    enabled: !!id,
  });

  if (isLoadingTournament) {
    return <Loading />;
  }

  if (tournamentError || !tournamentData) {
    return <ErrorMessage message="Failed to load tournament details" />;
  }

  const tournament = tournamentData?.data || tournamentData;
  const standings = standingsData?.data?.standings || standingsData?.data || [];
  const matches = matchesData?.data?.matches || matchesData?.data?.data?.matches || [];
  const teams = teamsData?.data?.teams || teamsData?.data?.data?.teams || teamsData?.data?.data || [];

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Tournament Header */}
        <div className="bg-white rounded-lg shadow-md overflow-hidden mb-6">
          <div className="p-6 md:p-8">
            <div className="flex flex-col md:flex-row md:items-start gap-6">
              {/* Logo */}
              {tournament.logo && (
                <img
                  src={tournament.logo}
                  alt={tournament.name}
                  className="h-24 w-24 md:h-32 md:w-32 object-contain rounded-lg bg-white p-2 shadow-md"
                />
              )}

              {/* Tournament Info */}
              <div className="flex-1">
                <div className="flex flex-wrap items-center gap-3 mb-4">
                  <h1 className="text-3xl md:text-4xl font-bold text-gray-900">
                    {tournament.name}
                  </h1>
                  {tournament.sport && (
                    <Badge color="blue">{tournament.sport.name || tournament.sport}</Badge>
                  )}
                  {tournament.status && (
                    <Badge color={STATUS_COLORS[tournament.status] || 'gray'}>
                      {tournament.status.charAt(0).toUpperCase() + tournament.status.slice(1)}
                    </Badge>
                  )}
                </div>

                {tournament.description && (
                  <p className="text-gray-600 mb-4">
                    {tournament.description}
                  </p>
                )}

                {/* Key Info */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                  {tournament.start_date && (
                    <div className="flex items-center gap-2 text-gray-600">
                      <Calendar className="h-5 w-5" />
                      <div>
                        <div className="text-sm font-medium">Start Date</div>
                        <div className="text-sm">{formatDate(tournament.start_date)}</div>
                      </div>
                    </div>
                  )}
                  {tournament.end_date && (
                    <div className="flex items-center gap-2 text-gray-600">
                      <Calendar className="h-5 w-5" />
                      <div>
                        <div className="text-sm font-medium">End Date</div>
                        <div className="text-sm">{formatDate(tournament.end_date)}</div>
                      </div>
                    </div>
                  )}
                  {tournament.format && (
                    <div className="flex items-center gap-2 text-gray-600">
                      <Trophy className="h-5 w-5" />
                      <div>
                        <div className="text-sm font-medium">Format</div>
                        <div className="text-sm capitalize">{tournament.format}</div>
                      </div>
                    </div>
                  )}
                  {tournament.venue && (
                    <div className="flex items-center gap-2 text-gray-600">
                      <MapPin className="h-5 w-5" />
                      <div>
                        <div className="text-sm font-medium">Venue</div>
                        <div className="text-sm">
                          {tournament.venue.name || tournament.venue}
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Standings Section */}
        {standings.length > 0 && (
          <div className="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 className="text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
              <Trophy className="h-6 w-6" />
              Standings
            </h2>
            {isLoadingStandings ? (
              <Loading />
            ) : (
              <StandingsTable standings={standings} />
            )}
          </div>
        )}

        {/* Teams Section */}
        {teams.length > 0 && (
          <div className="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 className="text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
              <Users className="h-6 w-6" />
              Teams
            </h2>
            {isLoadingTeams ? (
              <Loading />
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {teams.map((team) => (
                  <TeamCard key={team.id} team={team} />
                ))}
              </div>
            )}
          </div>
        )}

        {/* Matches Section */}
        {matches.length > 0 && (
          <div className="bg-white rounded-lg shadow-md p-6">
            <h2 className="text-2xl font-bold text-gray-900 mb-4">Matches</h2>
            {isLoadingMatches ? (
              <Loading />
            ) : (
              <div className="grid grid-cols-1 gap-4">
                {matches.map((match) => (
                  <MatchCard key={match.id} match={match} />
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default TournamentDetails;
