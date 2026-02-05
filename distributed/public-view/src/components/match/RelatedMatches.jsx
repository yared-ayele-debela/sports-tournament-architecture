import { useQuery } from '@tanstack/react-query';
import { matchService } from '../../api/matches';
import MatchCard from './MatchCard';
import Loading from '../common/Loading';

const RelatedMatches = ({ match }) => {
  // Fetch other matches in the same tournament
  const { data: tournamentMatchesData, isLoading } = useQuery({
    queryKey: ['tournamentMatches', match.tournament?.id],
    queryFn: () => matchService.getAll({ tournament_id: match.tournament?.id, limit: 6 }),
    enabled: !!match.tournament?.id,
  });

  const tournamentMatches = tournamentMatchesData?.data?.matches || tournamentMatchesData?.data?.data?.matches || tournamentMatchesData?.data || [];
  
  // Filter out current match
  const relatedMatches = tournamentMatches.filter(m => m.id !== match.id).slice(0, 5);

  if (!match.tournament?.id) {
    return null;
  }

  if (isLoading) {
    return <Loading size="sm" />;
  }

  if (relatedMatches.length === 0) {
    return null;
  }

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h3 className="text-xl font-bold text-gray-900 mb-4">
        Other Matches in {match.tournament.name}
      </h3>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {relatedMatches.map((relatedMatch) => (
          <MatchCard key={relatedMatch.id} match={relatedMatch} />
        ))}
      </div>
    </div>
  );
};

export default RelatedMatches;
