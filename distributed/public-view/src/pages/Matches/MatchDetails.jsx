import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeft } from 'lucide-react';
import { matchService } from '../../api/matches';
import MatchHeader from '../../components/match/MatchHeader';
import MatchTimeline from '../../components/match/MatchTimeline';
import MatchStatistics from '../../components/match/MatchStatistics';
import Lineups from '../../components/match/Lineups';
import MatchReport from '../../components/match/MatchReport';
import RelatedMatches from '../../components/match/RelatedMatches';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import Breadcrumbs from '../../components/layout/Breadcrumbs';
import Button from '../../components/common/Button';

const MatchDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('overview');

  // Fetch match details
  const { data: matchData, isLoading: matchLoading, error: matchError } = useQuery({
    queryKey: ['match', id],
    queryFn: () => matchService.getById(id),
    refetchInterval: (data) => {
      // Auto-refresh if match is live
      const match = data?.data || data;
      return match?.status === 'live' || match?.status === 'in_progress' ? 30000 : false;
    },
  });

  // Fetch match events
  const { data: eventsData, isLoading: eventsLoading } = useQuery({
    queryKey: ['matchEvents', id],
    queryFn: () => matchService.getEvents(id),
    enabled: !!id,
  });

  const match = matchData?.data || matchData;
  const events = eventsData?.data?.events || eventsData?.data || [];
  const isLive = match?.status === 'live' || match?.status === 'in_progress';
  const isCompleted = match?.status === 'completed';

  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'timeline', label: 'Timeline', show: isLive || isCompleted },
    { id: 'statistics', label: 'Statistics', show: isLive || isCompleted },
    { id: 'lineups', label: 'Lineups', show: match?.home_team?.players || match?.away_team?.players },
    { id: 'report', label: 'Report', show: match?.report },
  ].filter(tab => tab.show !== false);

  if (matchLoading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <Loading fullScreen={false} />
        </div>
      </div>
    );
  }

  if (matchError || !match) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <ErrorMessage
            message="Failed to load match details. The match may not exist."
            onRetry={() => navigate('/matches')}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Breadcrumbs
          items={[
            { label: 'Matches', path: '/matches' },
            { label: `${match.home_team?.name || 'Home'} vs ${match.away_team?.name || 'Away'}`, path: `/matches/${id}` },
          ]}
        />

        {/* Back Button */}
        <Button
          variant="ghost"
          onClick={() => navigate(-1)}
          className="mb-4 flex items-center gap-2"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Matches
        </Button>

        {/* Match Header */}
        <MatchHeader match={match} />

        {/* Tabs */}
        <div className="bg-white rounded-lg shadow-md mb-6">
          <div className="border-b border-gray-200">
            <nav className="flex overflow-x-auto">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${
                    activeTab === tab.id
                      ? 'border-primary-600 text-primary-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  {tab.label}
                </button>
              ))}
            </nav>
          </div>
        </div>

        {/* Tab Content */}
        <div className="space-y-6">
          {/* Overview Tab */}
          {activeTab === 'overview' && (
            <div className="space-y-6">
              {/* Score Display (if completed or live) */}
              {(isCompleted || isLive) && (
                <div className="bg-white rounded-lg shadow-md p-8 text-center">
                  <div className="text-6xl font-bold text-gray-900 mb-4">
                    {match.home_score ?? 0} - {match.away_score ?? 0}
                  </div>
                  {isLive && match.current_minute && (
                    <div className="flex items-center justify-center gap-2">
                      <span className="h-3 w-3 bg-red-600 rounded-full animate-pulse"></span>
                      <span className="text-lg font-semibold text-red-600">
                        Live - {match.current_minute}'
                      </span>
                    </div>
                  )}
                  {isCompleted && (
                    <p className="text-gray-600 mt-2">Full Time</p>
                  )}
                </div>
              )}

              {/* Match Info */}
              <div className="bg-white rounded-lg shadow-md p-6">
                <h3 className="text-xl font-bold text-gray-900 mb-4">Match Information</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <span className="text-sm text-gray-600">Tournament:</span>
                    <p className="font-medium text-gray-900">
                      {match.tournament?.name || 'N/A'}
                    </p>
                  </div>
                  {match.round_number && (
                    <div>
                      <span className="text-sm text-gray-600">Round:</span>
                      <p className="font-medium text-gray-900">Round {match.round_number}</p>
                    </div>
                  )}
                  {match.venue && (
                    <div>
                      <span className="text-sm text-gray-600">Venue:</span>
                      <p className="font-medium text-gray-900">{match.venue.name}</p>
                      {match.venue.address && (
                        <p className="text-sm text-gray-600">{match.venue.address}</p>
                      )}
                    </div>
                  )}
                  <div>
                    <span className="text-sm text-gray-600">Status:</span>
                    <p className="font-medium text-gray-900 capitalize">
                      {match.status === 'in_progress' ? 'Live' : match.status}
                    </p>
                  </div>
                </div>
              </div>

              {/* Related Matches */}
              <RelatedMatches match={match} />
            </div>
          )}

          {/* Timeline Tab */}
          {activeTab === 'timeline' && (
            <MatchTimeline events={events} />
          )}

          {/* Statistics Tab */}
          {activeTab === 'statistics' && (
            <MatchStatistics
              statistics={match.statistics}
              homeTeam={match.home_team}
              awayTeam={match.away_team}
            />
          )}

          {/* Lineups Tab */}
          {activeTab === 'lineups' && (
            <Lineups match={match} />
          )}

          {/* Report Tab */}
          {activeTab === 'report' && (
            <MatchReport report={match.report} />
          )}
        </div>
      </div>
    </div>
  );
};

export default MatchDetails;
