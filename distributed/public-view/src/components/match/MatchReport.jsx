const MatchReport = ({ report }) => {
  if (!report) {
    return (
      <div className="bg-white rounded-lg shadow-md p-8 text-center">
        <p className="text-gray-600">Match report not available yet.</p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <h3 className="text-xl font-bold text-gray-900 mb-6">Match Report</h3>

      {/* Summary */}
      {report.summary && (
        <div className="mb-6">
          <h4 className="text-lg font-semibold text-gray-900 mb-3">Summary</h4>
          <p className="text-gray-700 leading-relaxed whitespace-pre-line">
            {report.summary}
          </p>
        </div>
      )}

      {/* Key Moments */}
      {report.key_moments && report.key_moments.length > 0 && (
        <div className="mb-6">
          <h4 className="text-lg font-semibold text-gray-900 mb-3">Key Moments</h4>
          <ul className="space-y-2">
            {report.key_moments.map((moment, index) => (
              <li key={index} className="flex items-start gap-3">
                <span className="flex-shrink-0 w-6 h-6 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center text-sm font-semibold">
                  {index + 1}
                </span>
                <span className="text-gray-700">{moment}</span>
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Player Ratings */}
      {report.player_ratings && Object.keys(report.player_ratings).length > 0 && (
        <div>
          <h4 className="text-lg font-semibold text-gray-900 mb-3">Player Ratings</h4>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {Object.entries(report.player_ratings).map(([playerName, rating]) => (
              <div key={playerName} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span className="font-medium text-gray-900">{playerName}</span>
                <div className="flex items-center gap-2">
                  <div className="w-24 bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-primary-600 h-2 rounded-full"
                      style={{ width: `${(rating / 10) * 100}%` }}
                    ></div>
                  </div>
                  <span className="text-sm font-semibold text-gray-900 w-8">{rating}/10</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default MatchReport;
