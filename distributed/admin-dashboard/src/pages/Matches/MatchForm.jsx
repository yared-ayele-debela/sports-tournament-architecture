import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { matchesService } from '../../api/matches';
import { tournamentsService, venuesService } from '../../api/tournaments';
import { teamsService } from '../../api/teams';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft } from 'lucide-react';

export default function MatchForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    tournament_id: '',
    venue_id: '',
    home_team_id: '',
    away_team_id: '',
    referee_id: '',
    match_date: '',
    round_number: '',
  });
  const [errors, setErrors] = useState({});
  const [tournamentFilter, setTournamentFilter] = useState('');

  // Fetch match if editing
  const { data: matchData, isLoading: loadingMatch } = useQuery({
    queryKey: ['match', id],
    queryFn: () => matchesService.get(id),
    enabled: isEdit,
  });

  // Fetch tournaments
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournaments', 'list'],
    queryFn: () => tournamentsService.list({ per_page: 100 }),
  });

  const tournaments = tournamentsData?.data || tournamentsData || [];

  // Fetch teams for the selected tournament
  const { data: teamsData } = useQuery({
    queryKey: ['teams', 'tournament', tournamentFilter || formData.tournament_id],
    queryFn: () => {
      const tournamentId = tournamentFilter || formData.tournament_id;
      if (!tournamentId) return { data: [] };
      return teamsService.list({ tournament_id: tournamentId, per_page: 100 });
    },
    enabled: !!(tournamentFilter || formData.tournament_id),
  });

  const teams = teamsData?.data || teamsData || [];

  // Fetch venues
  const { data: venuesData } = useQuery({
    queryKey: ['venues', 'list'],
    queryFn: () => venuesService.list({ per_page: 100 }),
  });

  const venues = venuesData?.data || venuesData || [];

  // Populate form when match data loads
  useEffect(() => {
    if (matchData && isEdit) {
      const matchDate = matchData.match_date
        ? new Date(matchData.match_date).toISOString().slice(0, 16)
        : '';
      
      setFormData({
        tournament_id: matchData.tournament_id || matchData.tournament?.id || '',
        venue_id: matchData.venue_id || matchData.venue?.id || '',
        home_team_id: matchData.home_team_id || matchData.home_team?.id || '',
        away_team_id: matchData.away_team_id || matchData.away_team?.id || '',
        referee_id: matchData.referee_id || '',
        match_date: matchDate,
        round_number: matchData.round_number || '',
      });
      
      if (matchData.tournament_id || matchData.tournament?.id) {
        setTournamentFilter(matchData.tournament_id || matchData.tournament?.id);
      }
    }
  }, [matchData, isEdit]);

  const mutation = useMutation({
    mutationFn: (data) => {
      if (isEdit) {
        return matchesService.update(id, data);
      }
      return matchesService.create(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['matches']);
      toast.success(isEdit ? 'Match updated successfully' : 'Match created successfully');
      navigate('/matches');
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(
          error?.response?.data?.message ||
            (isEdit ? 'Failed to update match' : 'Failed to create match')
        );
      }
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    // Prepare data
    const submitData = {
      ...formData,
      tournament_id: parseInt(formData.tournament_id),
      venue_id: parseInt(formData.venue_id),
      home_team_id: parseInt(formData.home_team_id),
      away_team_id: parseInt(formData.away_team_id),
      referee_id: parseInt(formData.referee_id),
      round_number: parseInt(formData.round_number),
      match_date: new Date(formData.match_date).toISOString(),
    };

    mutation.mutate(submitData);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
    
    // Update tournament filter when tournament changes
    if (name === 'tournament_id') {
      setTournamentFilter(value);
      // Reset teams when tournament changes
      setFormData((prev) => ({ ...prev, home_team_id: '', away_team_id: '' }));
    }
  };

  if (loadingMatch) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate('/matches')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Matches
        </button>
        <h1 className="text-3xl font-bold text-gray-900">
          {isEdit ? 'Edit Match' : 'Create New Match'}
        </h1>
      </div>

      <div className="card max-w-2xl">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Tournament */}
          <div>
            <label htmlFor="tournament_id" className="label">
              Tournament <span className="text-red-500">*</span>
            </label>
            <select
              id="tournament_id"
              name="tournament_id"
              value={formData.tournament_id}
              onChange={handleChange}
              className={`input ${errors.tournament_id ? 'border-red-500' : ''}`}
              required
              disabled={isEdit}
            >
              <option value="">Select a tournament</option>
              {tournaments.map((tournament) => (
                <option key={tournament.id} value={tournament.id}>
                  {tournament.name}
                </option>
              ))}
            </select>
            {errors.tournament_id && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.tournament_id) ? errors.tournament_id[0] : errors.tournament_id}
              </p>
            )}
            {isEdit && (
              <p className="mt-1 text-sm text-gray-500">Tournament cannot be changed after creation</p>
            )}
          </div>

          {/* Home Team */}
          <div>
            <label htmlFor="home_team_id" className="label">
              Home Team <span className="text-red-500">*</span>
            </label>
            <select
              id="home_team_id"
              name="home_team_id"
              value={formData.home_team_id}
              onChange={handleChange}
              className={`input ${errors.home_team_id ? 'border-red-500' : ''}`}
              required
              disabled={!formData.tournament_id}
            >
              <option value="">Select home team</option>
              {teams
                .filter((team) => team.id !== parseInt(formData.away_team_id))
                .map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                  </option>
                ))}
            </select>
            {!formData.tournament_id && (
              <p className="mt-1 text-sm text-gray-500">Select a tournament first</p>
            )}
            {errors.home_team_id && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.home_team_id) ? errors.home_team_id[0] : errors.home_team_id}
              </p>
            )}
          </div>

          {/* Away Team */}
          <div>
            <label htmlFor="away_team_id" className="label">
              Away Team <span className="text-red-500">*</span>
            </label>
            <select
              id="away_team_id"
              name="away_team_id"
              value={formData.away_team_id}
              onChange={handleChange}
              className={`input ${errors.away_team_id ? 'border-red-500' : ''}`}
              required
              disabled={!formData.tournament_id}
            >
              <option value="">Select away team</option>
              {teams
                .filter((team) => team.id !== parseInt(formData.home_team_id))
                .map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                  </option>
                ))}
            </select>
            {errors.away_team_id && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.away_team_id) ? errors.away_team_id[0] : errors.away_team_id}
              </p>
            )}
          </div>

          {/* Venue */}
          <div>
            <label htmlFor="venue_id" className="label">
              Venue <span className="text-red-500">*</span>
            </label>
            <select
              id="venue_id"
              name="venue_id"
              value={formData.venue_id}
              onChange={handleChange}
              className={`input ${errors.venue_id ? 'border-red-500' : ''}`}
              required
            >
              <option value="">Select a venue</option>
              {venues.map((venue) => (
                <option key={venue.id} value={venue.id}>
                  {venue.name} {venue.location ? `(${venue.location})` : ''}
                </option>
              ))}
            </select>
            {errors.venue_id && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.venue_id) ? errors.venue_id[0] : errors.venue_id}
              </p>
            )}
          </div>

          {/* Referee ID */}
          <div>
            <label htmlFor="referee_id" className="label">
              Referee ID <span className="text-red-500">*</span>
            </label>
            <input
              id="referee_id"
              name="referee_id"
              type="number"
              min="1"
              value={formData.referee_id}
              onChange={handleChange}
              className={`input ${errors.referee_id ? 'border-red-500' : ''}`}
              required
            />
            {errors.referee_id && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.referee_id) ? errors.referee_id[0] : errors.referee_id}
              </p>
            )}
          </div>

          {/* Match Date */}
          <div>
            <label htmlFor="match_date" className="label">
              Match Date & Time <span className="text-red-500">*</span>
            </label>
            <input
              id="match_date"
              name="match_date"
              type="datetime-local"
              value={formData.match_date}
              onChange={handleChange}
              className={`input ${errors.match_date ? 'border-red-500' : ''}`}
              required
            />
            {errors.match_date && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.match_date) ? errors.match_date[0] : errors.match_date}
              </p>
            )}
          </div>

          {/* Round Number */}
          <div>
            <label htmlFor="round_number" className="label">
              Round Number <span className="text-red-500">*</span>
            </label>
            <input
              id="round_number"
              name="round_number"
              type="number"
              min="1"
              value={formData.round_number}
              onChange={handleChange}
              className={`input ${errors.round_number ? 'border-red-500' : ''}`}
              required
            />
            {errors.round_number && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.round_number) ? errors.round_number[0] : errors.round_number}
              </p>
            )}
          </div>

          {/* Submit Button */}
          <div className="flex justify-end space-x-4 pt-4">
            <button
              type="button"
              onClick={() => navigate('/matches')}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button type="submit" disabled={mutation.isLoading} className="btn btn-primary">
              {mutation.isLoading
                ? 'Saving...'
                : isEdit
                ? 'Update Match'
                : 'Create Match'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
