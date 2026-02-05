import { useState, useMemo } from 'react';
import { Trophy, AlertCircle, Users, Clock, Filter } from 'lucide-react';
import Badge from '../common/Badge';
import Button from '../common/Button';

const MatchTimeline = ({ events = [] }) => {
  const [filterType, setFilterType] = useState('all');

  const eventTypes = [
    { value: 'all', label: 'All Events' },
    { value: 'goal', label: 'Goals' },
    { value: 'yellow_card', label: 'Yellow Cards' },
    { value: 'red_card', label: 'Red Cards' },
    { value: 'substitution', label: 'Substitutions' },
  ];

  const filteredEvents = useMemo(() => {
    if (filterType === 'all') return events;
    return events.filter(event => event.event_type === filterType);
  }, [events, filterType]);

  const getEventIcon = (eventType) => {
    switch (eventType) {
      case 'goal':
        return <Trophy className="h-5 w-5 text-green-600" />;
      case 'yellow_card':
        return <AlertCircle className="h-5 w-5 text-yellow-600" />;
      case 'red_card':
        return <AlertCircle className="h-5 w-5 text-red-600" />;
      case 'substitution':
        return <Users className="h-5 w-5 text-blue-600" />;
      default:
        return <Clock className="h-5 w-5 text-gray-600" />;
    }
  };

  const getEventColor = (eventType) => {
    switch (eventType) {
      case 'goal':
        return 'bg-green-100 border-green-300';
      case 'yellow_card':
        return 'bg-yellow-100 border-yellow-300';
      case 'red_card':
        return 'bg-red-100 border-red-300';
      case 'substitution':
        return 'bg-blue-100 border-blue-300';
      default:
        return 'bg-gray-100 border-gray-300';
    }
  };

  const formatEventDescription = (event) => {
    switch (event.event_type) {
      case 'goal':
        const assist = event.event_data?.assist_player_name;
        return assist
          ? `Goal by ${event.player_name} (Assist: ${assist})`
          : `Goal by ${event.player_name}`;
      case 'yellow_card':
        return `Yellow card for ${event.player_name}`;
      case 'red_card':
        return `Red card for ${event.player_name}`;
      case 'substitution':
        const playerOut = event.event_data?.player_out_name;
        const playerIn = event.event_data?.player_in_name;
        return playerOut && playerIn
          ? `${playerOut} → ${playerIn}`
          : `Substitution`;
      default:
        return event.event_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
  };

  if (events.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow-md p-8 text-center">
        <Clock className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <p className="text-gray-600">No events recorded for this match yet.</p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-md p-6">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-xl font-bold text-gray-900">Match Timeline</h3>
        
        {/* Event Type Filter */}
        <div className="flex items-center gap-2">
          <Filter className="h-4 w-4 text-gray-500" />
          <select
            value={filterType}
            onChange={(e) => setFilterType(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            {eventTypes.map((type) => (
              <option key={type.value} value={type.value}>
                {type.label}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Timeline */}
      <div className="relative">
        {/* Timeline Line */}
        <div className="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

        {/* Events */}
        <div className="space-y-4">
          {filteredEvents.map((event, index) => (
            <div key={event.id || index} className="relative flex items-start gap-4">
              {/* Timeline Dot */}
              <div className={`relative z-10 flex items-center justify-center w-12 h-12 rounded-full border-2 ${getEventColor(event.event_type)}`}>
                {getEventIcon(event.event_type)}
              </div>

              {/* Event Content */}
              <div className="flex-1 bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-3">
                    <Badge variant="info" size="sm">
                      {event.minute}'
                    </Badge>
                    <span className="font-medium text-gray-900">
                      {event.team_name || 'Unknown Team'}
                    </span>
                  </div>
                  <Badge
                    variant={
                      event.event_type === 'goal' ? 'success' :
                      event.event_type === 'red_card' ? 'danger' :
                      event.event_type === 'yellow_card' ? 'warning' : 'default'
                    }
                    size="sm"
                  >
                    {event.event_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                  </Badge>
                </div>
                <p className="text-gray-700">{formatEventDescription(event)}</p>
                {event.event_data && Object.keys(event.event_data).length > 0 && (
                  <div className="mt-2 text-xs text-gray-500">
                    {JSON.stringify(event.event_data, null, 2)}
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default MatchTimeline;
