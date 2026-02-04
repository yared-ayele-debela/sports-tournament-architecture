import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { teamsService } from '../../api/teams';
import { tournamentsService } from '../../api/tournaments';
import { usersService } from '../../api/users';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft } from 'lucide-react';

export default function TeamForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    tournament_id: '',
    name: '',
    logo: '',
    coach_id: '',
  });
  const [errors, setErrors] = useState({});

  // Fetch team if editing
  const { data: teamData, isLoading: loadingTeam } = useQuery({
    queryKey: ['team', id],
    queryFn: () => teamsService.get(id),
    enabled: isEdit,
  });

  // Fetch tournaments
  const { data: tournamentsData } = useQuery({
    queryKey: ['tournaments', 'list'],
    queryFn: () => tournamentsService.list({ per_page: 100 }),
  });

  const tournaments = tournamentsData?.data || tournamentsData || [];

  // Fetch users for coach selection (filter by coach role if possible)
  const { data: usersData } = useQuery({
    queryKey: ['users', 'coaches'],
    queryFn: () => usersService.list({ per_page: 100 }),
  });

  const users = usersData?.data || usersData || [];
  // Filter users who have coach role (if roles are available)
  const coaches = users.filter((user) => {
    if (!user.roles || !Array.isArray(user.roles)) return false;
    return user.roles.some((role) => 
      (typeof role === 'string' ? role : role.name)?.toLowerCase().includes('coach')
    );
  });

  // Populate form when team data loads
  useEffect(() => {
    if (teamData && isEdit) {
      setFormData({
        tournament_id: teamData.tournament_id || '',
        name: teamData.name || '',
        logo: teamData.logo || '',
        coach_id: teamData.coaches?.[0]?.id || teamData.coaches?.[0] || '',
      });
    }
  }, [teamData, isEdit]);

  const mutation = useMutation({
    mutationFn: (data) => {
      if (isEdit) {
        return teamsService.update(id, data);
      }
      return teamsService.create(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['teams']);
      toast.success(isEdit ? 'Team updated successfully' : 'Team created successfully');
      navigate('/teams');
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(
          error?.response?.data?.message ||
            (isEdit ? 'Failed to update team' : 'Failed to create team')
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
      coach_id: parseInt(formData.coach_id),
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

  if (loadingTeam) {
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
          onClick={() => navigate('/teams')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Teams
        </button>
        <h1 className="text-3xl font-bold text-gray-900">
          {isEdit ? 'Edit Team' : 'Create New Team'}
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

          {/* Name */}
          <div>
            <label htmlFor="name" className="label">
              Team Name <span className="text-red-500">*</span>
            </label>
            <input
              id="name"
              name="name"
              type="text"
              value={formData.name}
              onChange={handleChange}
              className={`input ${errors.name ? 'border-red-500' : ''}`}
              required
            />
            {errors.name && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.name) ? errors.name[0] : errors.name}
              </p>
            )}
          </div>

          {/* Logo */}
          <div>
            <label htmlFor="logo" className="label">Logo URL</label>
            <input
              id="logo"
              name="logo"
              type="url"
              value={formData.logo}
              onChange={handleChange}
              className={`input ${errors.logo ? 'border-red-500' : ''}`}
              placeholder="https://example.com/logo.png"
            />
            {errors.logo && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.logo) ? errors.logo[0] : errors.logo}
              </p>
            )}
            {formData.logo && (
              <div className="mt-2">
                <img
                  src={formData.logo}
                  alt="Team logo preview"
                  className="w-16 h-16 rounded-full object-cover border border-gray-300"
                  onError={(e) => {
                    e.target.style.display = 'none';
                  }}
                />
              </div>
            )}
          </div>

          {/* Coach */}
          {!isEdit && (
            <div>
              <label htmlFor="coach_id" className="label">
                Coach <span className="text-red-500">*</span>
              </label>
              <select
                id="coach_id"
                name="coach_id"
                value={formData.coach_id}
                onChange={handleChange}
                className={`input ${errors.coach_id ? 'border-red-500' : ''}`}
                required
              >
                <option value="">Select a coach</option>
                {coaches.length > 0 ? (
                  coaches.map((coach) => (
                    <option key={coach.id} value={coach.id}>
                      {coach.name} ({coach.email})
                    </option>
                  ))
                ) : (
                  users.map((user) => (
                    <option key={user.id} value={user.id}>
                      {user.name} ({user.email})
                    </option>
                  ))
                )}
              </select>
              {errors.coach_id && (
                <p className="mt-1 text-sm text-red-600">
                  {Array.isArray(errors.coach_id) ? errors.coach_id[0] : errors.coach_id}
                </p>
              )}
            </div>
          )}

          {/* Submit Button */}
          <div className="flex justify-end space-x-4 pt-4">
            <button
              type="button"
              onClick={() => navigate('/teams')}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button type="submit" disabled={mutation.isLoading} className="btn btn-primary">
              {mutation.isLoading
                ? 'Saving...'
                : isEdit
                ? 'Update Team'
                : 'Create Team'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
