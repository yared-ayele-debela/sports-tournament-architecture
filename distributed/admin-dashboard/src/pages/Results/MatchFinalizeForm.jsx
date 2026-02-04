import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { resultsService } from '../../api/results';
import { matchesService } from '../../api/matches';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';

const EVENT_TYPES = [
  { value: 'goal', label: 'Goal' },
  { value: 'yellow_card', label: 'Yellow Card' },
  { value: 'red_card', label: 'Red Card' },
  { value: 'substitution', label: 'Substitution' },
];

export default function MatchFinalizeForm() {
  const { matchId } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    home_score: '',
    away_score: '',
    completed_at: new Date().toISOString().slice(0, 16),
  });
  const [events, setEvents] = useState([]);
  const [errors, setErrors] = useState({});

  // Fetch match data
  const { data: match, isLoading: loadingMatch } = useQuery({
    queryKey: ['match', matchId],
    queryFn: () => matchesService.get(matchId),
    enabled: !!matchId,
  });

  const mutation = useMutation({
    mutationFn: (data) => resultsService.finalizeMatch(matchId, data),
    onSuccess: () => {
      queryClient.invalidateQueries(['matches']);
      queryClient.invalidateQueries(['results']);
      queryClient.invalidateQueries(['standings']);
      toast.success('Match finalized successfully');
      navigate(`/matches/${matchId}`);
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(error?.response?.data?.message || 'Failed to finalize match');
      }
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    const submitData = {
      home_score: parseInt(formData.home_score),
      away_score: parseInt(formData.away_score),
      completed_at: new Date(formData.completed_at).toISOString(),
      ...(match && {
        tournament_id: match.tournament_id || match.tournament?.id,
        home_team_id: match.home_team_id || match.home_team?.id,
        away_team_id: match.away_team_id || match.away_team?.id,
      }),
    };

    mutation.mutate(submitData);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const addEvent = () => {
    setEvents([
      ...events,
      {
        type: 'goal',
        player_id: '',
        team_id: match?.home_team_id || match?.home_team?.id || '',
        minute: '',
        description: '',
      },
    ]);
  };

  const removeEvent = (index) => {
    setEvents(events.filter((_, i) => i !== index));
  };

  const updateEvent = (index, field, value) => {
    const updatedEvents = [...events];
    updatedEvents[index] = { ...updatedEvents[index], [field]: value };
    setEvents(updatedEvents);
  };

  if (loadingMatch) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!match) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        Match not found
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate(`/matches/${matchId}`)}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Match
        </button>
        <h1 className="text-3xl font-bold text-gray-900">Finalize Match Result</h1>
        {match.home_team && match.away_team && (
          <p className="text-gray-600 mt-2">
            {match.home_team.name || match.home_team_name} vs{' '}
            {match.away_team.name || match.away_team_name}
          </p>
        )}
      </div>

      <div className="card max-w-2xl">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Scores */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="home_score" className="label">
                Home Team Score <span className="text-red-500">*</span>
              </label>
              <input
                id="home_score"
                name="home_score"
                type="number"
                min="0"
                value={formData.home_score}
                onChange={handleChange}
                className={`input ${errors.home_score ? 'border-red-500' : ''}`}
                required
              />
              {errors.home_score && (
                <p className="mt-1 text-sm text-red-600">
                  {Array.isArray(errors.home_score) ? errors.home_score[0] : errors.home_score}
                </p>
              )}
            </div>

            <div>
              <label htmlFor="away_score" className="label">
                Away Team Score <span className="text-red-500">*</span>
              </label>
              <input
                id="away_score"
                name="away_score"
                type="number"
                min="0"
                value={formData.away_score}
                onChange={handleChange}
                className={`input ${errors.away_score ? 'border-red-500' : ''}`}
                required
              />
              {errors.away_score && (
                <p className="mt-1 text-sm text-red-600">
                  {Array.isArray(errors.away_score) ? errors.away_score[0] : errors.away_score}
                </p>
              )}
            </div>
          </div>

          {/* Completed At */}
          <div>
            <label htmlFor="completed_at" className="label">
              Completed At <span className="text-red-500">*</span>
            </label>
            <input
              id="completed_at"
              name="completed_at"
              type="datetime-local"
              value={formData.completed_at}
              onChange={handleChange}
              className={`input ${errors.completed_at ? 'border-red-500' : ''}`}
              required
            />
            {errors.completed_at && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.completed_at) ? errors.completed_at[0] : errors.completed_at}
              </p>
            )}
          </div>

          {/* Match Events (Optional) */}
          <div>
            <div className="flex justify-between items-center mb-4">
              <label className="label">Match Events (Optional)</label>
              <button
                type="button"
                onClick={addEvent}
                className="btn btn-secondary flex items-center text-sm"
              >
                <Plus className="w-4 h-4 mr-1" />
                Add Event
              </button>
            </div>

            {events.length === 0 ? (
              <p className="text-sm text-gray-500">No events added. Click "Add Event" to add match events.</p>
            ) : (
              <div className="space-y-4">
                {events.map((event, index) => (
                  <div key={index} className="p-4 border border-gray-200 rounded-lg">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                      <div>
                        <label className="label text-xs">Event Type</label>
                        <select
                          value={event.type}
                          onChange={(e) => updateEvent(index, 'type', e.target.value)}
                          className="input text-sm"
                        >
                          {EVENT_TYPES.map((type) => (
                            <option key={type.value} value={type.value}>
                              {type.label}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div>
                        <label className="label text-xs">Team ID</label>
                        <input
                          type="number"
                          value={event.team_id}
                          onChange={(e) => updateEvent(index, 'team_id', parseInt(e.target.value))}
                          className="input text-sm"
                          placeholder="Team ID"
                        />
                      </div>
                      <div>
                        <label className="label text-xs">Player ID</label>
                        <input
                          type="number"
                          value={event.player_id}
                          onChange={(e) => updateEvent(index, 'player_id', parseInt(e.target.value))}
                          className="input text-sm"
                          placeholder="Player ID"
                        />
                      </div>
                      <div>
                        <label className="label text-xs">Minute</label>
                        <input
                          type="number"
                          min="0"
                          max="120"
                          value={event.minute}
                          onChange={(e) => updateEvent(index, 'minute', parseInt(e.target.value))}
                          className="input text-sm"
                          placeholder="Minute"
                        />
                      </div>
                    </div>
                    <div className="mt-2">
                      <label className="label text-xs">Description (Optional)</label>
                      <input
                        type="text"
                        value={event.description}
                        onChange={(e) => updateEvent(index, 'description', e.target.value)}
                        className="input text-sm"
                        placeholder="Event description"
                      />
                    </div>
                    <button
                      type="button"
                      onClick={() => removeEvent(index)}
                      className="mt-2 text-red-600 hover:text-red-700 flex items-center text-sm"
                    >
                      <Trash2 className="w-4 h-4 mr-1" />
                      Remove
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Submit Button */}
          <div className="flex justify-end space-x-4 pt-4">
            <button
              type="button"
              onClick={() => navigate(`/matches/${matchId}`)}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button type="submit" disabled={mutation.isLoading} className="btn btn-primary">
              {mutation.isLoading ? 'Finalizing...' : 'Finalize Match'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
