const Lineups = ({ match }) => {
  const homePlayers = match.home_team?.players || [];
  const awayPlayers = match.away_team?.players || [];

  const renderPlayerList = (players, teamName) => {
    if (!players || players.length === 0) {
      return (
        <div className="text-center py-8 text-gray-500">
          <p>Lineup information not available</p>
        </div>
      );
    }

    // Separate starting XI and substitutes
    const startingXI = players.filter(p => p.is_starting || p.position);
    const substitutes = players.filter(p => !p.is_starting && !p.position);

    return (
      <div className="space-y-6">
        {/* Starting XI */}
        {startingXI.length > 0 && (
          <div>
            <h4 className="text-sm font-semibold text-gray-700 mb-3 uppercase">Starting XI</h4>
            <div className="space-y-2">
              {startingXI.map((player, index) => (
                <div
                  key={player.id || index}
                  className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    {player.jersey_number && (
                      <span className="w-8 h-8 flex items-center justify-center bg-primary-600 text-white rounded-full text-sm font-bold">
                        {player.jersey_number}
                      </span>
                    )}
                    <div>
                      <p className="font-medium text-gray-900">{player.name || player.full_name || 'Unknown'}</p>
                      {player.position && (
                        <p className="text-xs text-gray-600">{player.position}</p>
                      )}
                    </div>
                  </div>
                  {player.photo && (
                    <img
                      src={player.photo}
                      alt={player.name || player.full_name}
                      className="h-10 w-10 rounded-full object-cover"
                    />
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Substitutes */}
        {substitutes.length > 0 && (
          <div>
            <h4 className="text-sm font-semibold text-gray-700 mb-3 uppercase">Substitutes</h4>
            <div className="space-y-2">
              {substitutes.map((player, index) => (
                <div
                  key={player.id || index}
                  className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    {player.jersey_number && (
                      <span className="w-8 h-8 flex items-center justify-center bg-gray-400 text-white rounded-full text-sm font-bold">
                        {player.jersey_number}
                      </span>
                    )}
                    <div>
                      <p className="font-medium text-gray-900">{player.name || player.full_name || 'Unknown'}</p>
                      {player.position && (
                        <p className="text-xs text-gray-600">{player.position}</p>
                      )}
                    </div>
                  </div>
                  {player.photo && (
                    <img
                      src={player.photo}
                      alt={player.name || player.full_name}
                      className="h-10 w-10 rounded-full object-cover"
                    />
                  )}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    );
  };

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h3 className="text-xl font-bold text-gray-900 mb-6">Lineups</h3>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Home Team Lineup */}
        <div>
          <h4 className="text-lg font-semibold text-gray-900 mb-4">
            {match.home_team?.name || 'Home Team'}
          </h4>
          {renderPlayerList(homePlayers, match.home_team?.name)}
        </div>

        {/* Away Team Lineup */}
        <div>
          <h4 className="text-lg font-semibold text-gray-900 mb-4">
            {match.away_team?.name || 'Away Team'}
          </h4>
          {renderPlayerList(awayPlayers, match.away_team?.name)}
        </div>
      </div>
    </div>
  );
};

export default Lineups;
