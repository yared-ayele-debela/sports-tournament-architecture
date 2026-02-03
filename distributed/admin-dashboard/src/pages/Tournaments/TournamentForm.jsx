import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { tournamentsService, sportsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft } from 'lucide-react';

const STATUS_OPTIONS = [
  { value: 'planned', label: 'Planned' },
  { value: 'ongoing', label: 'Ongoing' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

export default function TournamentForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    sport_id: '',
    name: '',
    location: '',
    start_date: '',
    end_date: '',
    status: 'planned',
  });
  const [errors, setErrors] = useState({});

  // Fetch tournament if editing
  const { data: tournamentData, isLoading: loadingTournament } = useQuery({
    queryKey: ['tournament', id],
    queryFn: () => tournamentsService.get(id),
    enabled: isEdit,
  });

  // Fetch sports
  const { data: sportsData } = useQuery({
    queryKey: ['sports'],
    queryFn: () => sportsService.list(),
  });

  const sports = Array.isArray(sportsData) ? sportsData : sportsData?.data || [];

  // Populate form when tournament data loads
  useEffect(() => {
    if (tournamentData && isEdit) {
      setFormData({
        sport_id: tournamentData.sport_id || tournamentData.sport?.id || '',
        name: tournamentData.name || '',
        location: tournamentData.location || '',
        start_date: tournamentData.start_date
          ? new Date(tournamentData.start_date).toISOString().split('T')[0]
          : '',
        end_date: tournamentData.end_date
          ? new Date(tournamentData.end_date).toISOString().split('T')[0]
          : '',
        status: tournamentData.status || 'planned',
      });
    }
  }, [tournamentData, isEdit]);

  const mutation = useMutation({
    mutationFn: (data) => {
      if (isEdit) {
        return tournamentsService.update(id, data);
      }
      return tournamentsService.create(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['tournaments']);
      toast.success(isEdit ? 'Tournament updated successfully' : 'Tournament created successfully');
      navigate('/tournaments');
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(
          error?.response?.data?.message ||
            (isEdit ? 'Failed to update tournament' : 'Failed to create tournament')
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
      sport_id: parseInt(formData.sport_id),
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

  if (loadingTournament) {
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
          onClick={() => navigate('/tournaments')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Tournaments
        </button>
        <h1 className="text-3xl font-bold text-gray-900">
          {isEdit ? 'Edit Tournament' : 'Create New Tournament'}
        </h1>
      </div>

      <div className="card max-w-2xl">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Sport */}
          <div>
            <label htmlFor="sport_id" className="label">
              Sport <span className="text-red-500">*</span>
            </label>
            <select
              id="sport_id"
              name="sport_id"
              value={formData.sport_id}
              onChange={handleChange}
              className={`input ${errors.sport_id ? 'border-red-500' : ''}`}
              required
            >
              <option value="">Select a sport</option>
              {sports.map((sport) => (
                <option key={sport.id} value={sport.id}>
                  {sport.name}
                </option>
              ))}
            </select>
            {errors.sport_id && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.sport_id) ? errors.sport_id[0] : errors.sport_id}
              </p>
            )}
          </div>

          {/* Name */}
          <div>
            <label htmlFor="name" className="label">
              Tournament Name <span className="text-red-500">*</span>
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

          {/* Location */}
          <div>
            <label htmlFor="location" className="label">Location</label>
            <input
              id="location"
              name="location"
              type="text"
              value={formData.location}
              onChange={handleChange}
              className={`input ${errors.location ? 'border-red-500' : ''}`}
              placeholder="Tournament location"
            />
            {errors.location && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.location) ? errors.location[0] : errors.location}
              </p>
            )}
          </div>

          {/* Start Date */}
          <div>
            <label htmlFor="start_date" className="label">
              Start Date <span className="text-red-500">*</span>
            </label>
            <input
              id="start_date"
              name="start_date"
              type="date"
              value={formData.start_date}
              onChange={handleChange}
              className={`input ${errors.start_date ? 'border-red-500' : ''}`}
              required
              min={new Date().toISOString().split('T')[0]}
            />
            {errors.start_date && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.start_date) ? errors.start_date[0] : errors.start_date}
              </p>
            )}
          </div>

          {/* End Date */}
          <div>
            <label htmlFor="end_date" className="label">
              End Date <span className="text-red-500">*</span>
            </label>
            <input
              id="end_date"
              name="end_date"
              type="date"
              value={formData.end_date}
              onChange={handleChange}
              className={`input ${errors.end_date ? 'border-red-500' : ''}`}
              required
              min={formData.start_date || new Date().toISOString().split('T')[0]}
            />
            {errors.end_date && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.end_date) ? errors.end_date[0] : errors.end_date}
              </p>
            )}
          </div>

          {/* Status */}
          <div>
            <label htmlFor="status" className="label">Status</label>
            <select
              id="status"
              name="status"
              value={formData.status}
              onChange={handleChange}
              className={`input ${errors.status ? 'border-red-500' : ''}`}
            >
              {STATUS_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
            {errors.status && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.status) ? errors.status[0] : errors.status}
              </p>
            )}
          </div>

          {/* Submit Button */}
          <div className="flex justify-end space-x-4 pt-4">
            <button
              type="button"
              onClick={() => navigate('/tournaments')}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={mutation.isLoading}
              className="btn btn-primary"
            >
              {mutation.isLoading
                ? 'Saving...'
                : isEdit
                ? 'Update Tournament'
                : 'Create Tournament'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
