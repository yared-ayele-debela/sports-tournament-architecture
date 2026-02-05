import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeft, Trophy, Users } from 'lucide-react';
import { teamService } from '../../api/teams';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import MatchCard from '../../components/match/MatchCard';
import PlayerCard from '../../components/team/PlayerCard';
import TeamLogo from '../../components/team/TeamLogo';

const TeamDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  // Fetch team details
  const { data: teamData, isLoading: teamLoading, error: teamError } = useQuery({
    queryKey: ['team', id],
    queryFn: () => teamService.getById(id),
  });

  // Fetch team matches
  const { data: matchesData, isLoading: matchesLoading } = useQuery({
    queryKey: ['teamMatches', id],
    queryFn: () => teamService.getMatches(id, { limit: 10 }),
    enabled: !!id,
  });

  // Fetch team players (separate endpoint)
  const { data: playersData, isLoading: playersLoading } = useQuery({
    queryKey: ['teamPlayers', id],
    queryFn: () => teamService.getPlayers(id, { limit: 50 }),
    enabled: !!id,
  });

  const team = teamData?.data || teamData;
  const matches =
    matchesData?.data?.matches ||
    matchesData?.data?.data ||
    matchesData?.data ||
    [];

  // Players response is: { success, message, data: { players: [...] } }
  const players =
    playersData?.data?.players ||
    playersData?.data?.data || // fallback if wrapped in data.data
    playersData?.players ||
    [];

  if (teamLoading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <Loading />
        </div>
      </div>
    );
  }

  if (teamError || !team) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <ErrorMessage
            message="Failed to load team details."
            onRetry={() => navigate('/teams')}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Back Button */}
        <button
          onClick={() => navigate(-1)}
          className="mb-4 flex items-center gap-2 text-gray-600 hover:text-gray-900"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Teams
        </button>

        {/* Team Header */}
        <div className="bg-white rounded-lg shadow-md p-8 mb-6">
          <div className="flex flex-col md:flex-row items-center md:items-start gap-6">
            <div className="bg-gray-50 p-4 rounded-lg">
              <TeamLogo logo={team.logo} name={team.name} size="xl" />
            </div>
            <div className="flex-1 text-center md:text-left">
              <h1 className="text-3xl font-bold text-gray-900 mb-2">{team.name}</h1>
              {team.tournament && (
                <p className="text-gray-600 mb-4">{team.tournament.name}</p>
              )}
              {team.description && (
                <p className="text-gray-600">{team.description}</p>
              )}
            </div>
          </div>
        </div>

        {/* Team Stats */}
        {team.match_stats && (
          <div className="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
              <Trophy className="h-6 w-6" />
              Statistics
            </h2>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">
                  {team.match_stats.wins || 0}
                </div>
                <div className="text-sm text-gray-600">Wins</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">
                  {team.match_stats.losses || 0}
                </div>
                <div className="text-sm text-gray-600">Losses</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">
                  {team.match_stats.draws || 0}
                </div>
                <div className="text-sm text-gray-600">Draws</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">
                  {team.match_stats.goals_for || 0}
                </div>
                <div className="text-sm text-gray-600">Goals For</div>
              </div>
            </div>
          </div>
        )}

        {/* Players */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <Users className="h-6 w-6" />
            Players
          </h2>
          {playersLoading ? (
            <Loading />
          ) : players.length > 0 ? (
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4">
              {players.map((player) => (
                <PlayerCard key={player.id} player={player} />
              ))}
            </div>
          ) : (
            <p className="text-gray-600">No players found for this team.</p>
          )}
        </div>

        {/* Matches */}
        {matches.length > 0 && (
          <div className="bg-white rounded-lg shadow-md p-6">
            <h2 className="text-xl font-bold text-gray-900 mb-4">Recent Matches</h2>
            {matchesLoading ? (
              <Loading />
            ) : (
              <div className="grid grid-cols-3 gap-4">
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

export default TeamDetails;
