import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { playersService } from '../../api/teams';
import { teamsService } from '../../api/teams';
import { tournamentsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft } from 'lucide-react';

const POSITION_OPTIONS = [
  { value: 'Goalkeeper', label: 'Goalkeeper' },
  { value: 'Defender', label: 'Defender' },
  { value: 'Midfielder', label: 'Midfielder' },
  { value: 'Forward', label: 'Forward' },
];

export default function PlayerForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    team_id: '',
    full_name: '',
    position: '',
    jersey_number: '',
  });
  const [errors, setErrors] = useState({});
  const [tournamentFilter, setTournamentFilter] = useState('');

  // Fetch player if editing
  const { data: playerData, isLoading: loadingPlayer } = useQuery({
    queryKey: ['player', id],
    queryFn: () => playersService.get(id),
    enabled: isEdit,
  });

  // Fetch tournaments for filter
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournaments', 'list'],
    queryFn: () => tournamentsService.list({ per_page: 100 }),
  });

  const tournaments = tournamentsData?.data || tournamentsData || [];

  // Fetch teams for the selected tournament
  const { data: teamsData } = useQuery({
    queryKey: ['teams', 'tournament', tournamentFilter],
    queryFn: () => {
      if (!tournamentFilter) return { data: [] };
      return teamsService.list({ tournament_id: tournamentFilter, per_page: 100 });
    },
    enabled: !!tournamentFilter,
  });

  const teams = teamsData?.data || teamsData || [];

  // Populate form when player data loads
  useEffect(() => {
    if (playerData && isEdit) {
      setFormData({
        team_id: playerData.team_id || playerData.team?.id || '',
        full_name: playerData.full_name || playerData.name || '',
        position: playerData.position || '',
        jersey_number: playerData.jersey_number || '',
      });
      // Set tournament filter if player has a team
      if (playerData.team?.tournament_id) {
        setTournamentFilter(playerData.team.tournament_id);
      }
    }
  }, [playerData, isEdit]);

  const mutation = useMutation({
    mutationFn: (data) => {
      if (isEdit) {
        return playersService.update(id, data);
      }
      return playersService.create(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['players']);
      toast.success(isEdit ? 'Player updated successfully' : 'Player created successfully');
      navigate('/players');
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(
          error?.response?.data?.message ||
            (isEdit ? 'Failed to update player' : 'Failed to create player')
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
      team_id: parseInt(formData.team_id),
      jersey_number: parseInt(formData.jersey_number),
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

  if (loadingPlayer) {
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
          onClick={() => navigate('/players')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Players
        </button>
        <h1 className="text-3xl font-bold text-gray-900">
          {isEdit ? 'Edit Player' : 'Create New Player'}
        </h1>
      </div>

      <div className="card max-w-2xl">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Tournament (for filtering teams) */}
          {!isEdit && (
            <div>
              <label htmlFor="tournament" className="label">Tournament</label>
              <select
                id="tournament"
                value={tournamentFilter}
                onChange={(e) => {
                  setTournamentFilter(e.target.value);
                  setFormData((prev) => ({ ...prev, team_id: '' })); // Reset team when tournament changes
                }}
                className="input"
              >
                <option value="">Select a tournament</option>
                {tournaments.map((tournament) => (
                  <option key={tournament.id} value={tournament.id}>
                    {tournament.name}
                  </option>
                ))}
              </select>
            </div>
          )}

          {/* Team */}
          <div>
            <label htmlFor="team_id" className="label">
              Team <span className="text-red-500">*</span>
            </label>
            <select
              id="team_id"
              name="team_id"
              value={formData.team_id}
              onChange={handleChange}
              className={`input ${errors.team_id ? 'border-red-500' : ''}`}
              required
              disabled={!isEdit && !tournamentFilter}
            >
              <option value="">Select a team</option>
              {teams.map((team) => (
                <option key={team.id} value={team.id}>
                  {team.name}
                </option>
              ))}
            </select>
            {!isEdit && !tournamentFilter && (
              <p className="mt-1 text-sm text-gray-500">Select a tournament first</p>
            )}
            {errors.team_id && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.team_id) ? errors.team_id[0] : errors.team_id}
              </p>
            )}
          </div>

          {/* Full Name */}
          <div>
            <label htmlFor="full_name" className="label">
              Full Name <span className="text-red-500">*</span>
            </label>
            <input
              id="full_name"
              name="full_name"
              type="text"
              value={formData.full_name}
              onChange={handleChange}
              className={`input ${errors.full_name ? 'border-red-500' : ''}`}
              required
            />
            {errors.full_name && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.full_name) ? errors.full_name[0] : errors.full_name}
              </p>
            )}
          </div>

          {/* Position */}
          <div>
            <label htmlFor="position" className="label">
              Position <span className="text-red-500">*</span>
            </label>
            <select
              id="position"
              name="position"
              value={formData.position}
              onChange={handleChange}
              className={`input ${errors.position ? 'border-red-500' : ''}`}
              required
            >
              <option value="">Select a position</option>
              {POSITION_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
            {errors.position && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.position) ? errors.position[0] : errors.position}
              </p>
            )}
          </div>

          {/* Jersey Number */}
          <div>
            <label htmlFor="jersey_number" className="label">
              Jersey Number <span className="text-red-500">*</span>
            </label>
            <input
              id="jersey_number"
              name="jersey_number"
              type="number"
              min="1"
              max="99"
              value={formData.jersey_number}
              onChange={handleChange}
              className={`input ${errors.jersey_number ? 'border-red-500' : ''}`}
              required
            />
            <p className="mt-1 text-sm text-gray-500">Must be unique within the team (1-99)</p>
            {errors.jersey_number && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.jersey_number) ? errors.jersey_number[0] : errors.jersey_number}
              </p>
            )}
          </div>

          {/* Submit Button */}
          <div className="flex justify-end space-x-4 pt-4">
            <button
              type="button"
              onClick={() => navigate('/players')}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button type="submit" disabled={mutation.isLoading} className="btn btn-primary">
              {mutation.isLoading
                ? 'Saving...'
                : isEdit
                ? 'Update Player'
                : 'Create Player'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
