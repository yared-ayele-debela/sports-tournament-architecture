import { Link } from 'react-router-dom';
import { Users, Trophy } from 'lucide-react';

const TeamHeader = ({ team }) => {
  return (
    <div className="bg-white rounded-lg shadow-md p-6 mb-6">
      <div className="flex flex-col md:flex-row md:items-center gap-6">
        {/* Team Logo */}
        <div className="flex-shrink-0">
          {team.logo ? (
            <img
              src={team.logo}
              alt={team.name}
              className="h-32 w-32 object-contain"
            />
          ) : (
            <div className="h-32 w-32 bg-gray-200 rounded-lg flex items-center justify-center">
              <Users className="h-16 w-16 text-gray-400" />
            </div>
          )}
        </div>

        {/* Team Info */}
        <div className="flex-1">
          <h1 className="text-4xl font-bold text-gray-900 mb-2">{team.name}</h1>
          
          {team.tournament && (
            <div className="mb-3">
              <Link
                to={`/tournaments/${team.tournament.id}`}
                className="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-2"
              >
                <Trophy className="h-5 w-5" />
                {team.tournament.name}
              </Link>
            </div>
          )}

          {team.founded_year && (
            <p className="text-gray-600 mb-2">
              Founded: {team.founded_year}
            </p>
          )}

          {team.description && (
            <p className="text-gray-700 leading-relaxed max-w-3xl">
              {team.description}
            </p>
          )}
        </div>
      </div>
    </div>
  );
};

export default TeamHeader;
