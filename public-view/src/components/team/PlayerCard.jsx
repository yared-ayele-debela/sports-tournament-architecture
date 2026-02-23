import { Users } from 'lucide-react';

const PlayerCard = ({ player }) => {
  return (
    <div className="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
      <div className="flex items-center gap-4">
        {/* Player Photo or Placeholder */}
        {player.photo ? (
          <img
            src={player.photo}
            alt={player.name || player.full_name}
            className="h-16 w-16 rounded-full object-cover"
          />
        ) : (
          <div className="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center">
            <Users className="h-8 w-8 text-gray-400" />
          </div>
        )}

        {/* Player Info */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            {player.jersey_number && (
              <span className="w-8 h-8 flex items-center justify-center bg-primary-600 text-white rounded-full text-sm font-bold">
                {player.jersey_number}
              </span>
            )}
            <h3 className="font-bold text-gray-900 truncate">
              {player.name || player.full_name || 'Unknown Player'}
            </h3>
          </div>
          {player.position && (
            <p className="text-sm text-gray-600">{player.position}</p>
          )}
        </div>
      </div>
    </div>
  );
};

export default PlayerCard;
