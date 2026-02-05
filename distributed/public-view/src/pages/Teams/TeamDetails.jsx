import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeft } from 'lucide-react';
import { teamService } from '../../api/teams';
import { tournamentService } from '../../api/tournaments';
import TeamHeader from '../../components/team/TeamHeader';
import Loading from '../../components/common/Loading';
import ErrorMessage from '../../components/common/ErrorMessage';
import Breadcrumbs from '../../components/layout/Breadcrumbs';
import Button from '../../components/common/Button';
import TeamOverview from '../../components/team/TeamOverview';
import TeamPlayers from '../../components/team/TeamPlayers';
import TeamMatches from '../../components/team/TeamMatches';
import TeamStatistics from '../../components/team/TeamStatistics';

const TeamDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('overview');

  // Fetch team details
  const { data: teamData, isLoading: teamLoading, error: teamError } = useQuery({
    queryKey: ['team', id],
    queryFn: () => teamService.getById(id),
  });

  const team = teamData?.data || teamData;

  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'players', label: 'Players' },
    { id: 'matches', label: 'Matches' },
    { id: 'statistics', label: 'Statistics' },
  ];

  if (teamLoading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <Loading fullScreen={false} />
        </div>
      </div>
    );
  }

  if (teamError || !team) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <ErrorMessage
            message="Failed to load team details. The team may not exist."
            onRetry={() => navigate('/teams')}
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
            { label: 'Teams', path: '/teams' },
            { label: team.name, path: `/teams/${id}` },
          ]}
        />

        {/* Back Button */}
        <Button
          variant="ghost"
          onClick={() => navigate(-1)}
          className="mb-4 flex items-center gap-2"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to Teams
        </Button>

        {/* Team Header */}
        <TeamHeader team={team} />

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
        <div>
          {activeTab === 'overview' && <TeamOverview team={team} />}
          {activeTab === 'players' && <TeamPlayers teamId={id} />}
          {activeTab === 'matches' && <TeamMatches teamId={id} team={team} />}
          {activeTab === 'statistics' && <TeamStatistics teamId={id} team={team} />}
        </div>
      </div>
    </div>
  );
};

export default TeamDetails;
