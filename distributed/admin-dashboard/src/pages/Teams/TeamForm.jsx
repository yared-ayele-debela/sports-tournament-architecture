import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { teamsService } from '../../api/teams';
import { tournamentsService } from '../../api/tournaments';
import { usersService } from '../../api/users';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import { ArrowLeft, Upload, X } from 'lucide-react';

export default function TeamForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { isCoach } = usePermissions();

  // Determine the back navigation path based on user role
  const getBackPath = () => {
    return isCoach() ? '/teams/my-teams' : '/teams';
  };

  const [formData, setFormData] = useState({
    tournament_id: '',
    name: '',
    coach_id: '',
  });
  const [logoFile, setLogoFile] = useState(null);
  const [logoPreview, setLogoPreview] = useState('');
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
    if (teamData) {
      setFormData({
        tournament_id: teamData.tournament_id || '',
        name: teamData.name || '',
        coach_id: teamData.coaches?.[0]?.id || teamData.coaches?.[0] || '',
      });
      // Set logo preview for existing logo
      if (teamData.logo_url) {
        setLogoPreview(teamData.logo_url);
      }
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
      navigate(getBackPath());
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

    // Validate required fields
    if (!formData.name || formData.name.trim() === '') {
      setErrors({ name: 'Team name is required' });
      return;
    }

    if (!isEdit && (!formData.tournament_id || !formData.coach_id)) {
      setErrors({ 
        tournament_id: !formData.tournament_id ? 'Tournament is required' : undefined,
        coach_id: !formData.coach_id ? 'Coach is required' : undefined
      });
      return;
    }

    // Prepare form data with file upload
    const submitData = new FormData();
    
    if (isEdit) {
      // For updates, only send name and logo (tournament_id and coach_id cannot be changed)
      submitData.append('name', formData.name.trim());
      
      // Only append logo if a new file was selected
      if (logoFile) {
        submitData.append('logo', logoFile);
      }
      // If logoFile is null but logoPreview was cleared, we need to handle logo removal
      // Note: To remove logo, user would need to upload a new one or we'd need a separate "remove logo" endpoint
    } else {
      // For creation, send all required fields
      submitData.append('tournament_id', parseInt(formData.tournament_id));
      submitData.append('name', formData.name.trim());
      submitData.append('coach_id', parseInt(formData.coach_id));
      
      if (logoFile) {
        submitData.append('logo', logoFile);
      }
    }

    mutation.mutate(submitData);
  };

  const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file type and size
      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
      const maxSize = 4 * 1024 * 1024; // 2MB

      if (!allowedTypes.includes(file.type)) {
        setErrors({ logo: 'Please upload a valid image file (JPEG, PNG, JPG, GIF)' });
        return;
      }

      if (file.size > maxSize) {
        setErrors({ logo: 'File size must be less than 2MB' });
        return;
      }

      setLogoFile(file);
      setErrors({});

      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        setLogoPreview(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleRemoveLogo = () => {
    setLogoFile(null);
    setLogoPreview('');
    setErrors({});
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
          onClick={() => navigate(getBackPath())}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to {isCoach() ? 'My Teams' : 'Teams'}
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
            <label htmlFor="logo" className="label">Team Logo</label>
            <div className="mt-1">
              <div className="flex items-center space-x-4">
                <label className="cursor-pointer">
                  <input
                    id="logo"
                    name="logo"
                    type="file"
                    accept="image/jpeg,image/png,image/jpg,image/gif"
                    onChange={handleLogoChange}
                    className="hidden"
                  />
                  <div className="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer">
                    <Upload className="w-4 h-4" />
                    <span>Choose Logo</span>
                  </div>
                </label>
                {logoPreview && (
                  <button
                    type="button"
                    onClick={handleRemoveLogo}
                    className="flex items-center space-x-2 px-3 py-2 text-red-600 border border-red-300 rounded-md hover:bg-red-50"
                  >
                    <X className="w-4 h-4" />
                    <span>Remove</span>
                  </button>
                )}
              </div>
              {errors.logo && (
                <p className="mt-1 text-sm text-red-600">
                  {Array.isArray(errors.logo) ? errors.logo[0] : errors.logo}
                </p>
              )}
              <p className="mt-1 text-sm text-gray-500">
                Accepted formats: JPEG, PNG, JPG, GIF (Max 2MB)
              </p>
              {logoPreview && (
                <div className="mt-3">
                  <img
                    src={logoPreview}
                    alt="Team logo preview"
                    className="w-20 h-20 rounded-full object-cover border border-gray-300"
                  />
                </div>
              )}
            </div>
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
              onClick={() => navigate(getBackPath())}
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
