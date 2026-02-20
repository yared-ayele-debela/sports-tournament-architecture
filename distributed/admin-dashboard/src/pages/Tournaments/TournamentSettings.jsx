import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { tournamentsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { Settings, Clock, Calendar, Save } from 'lucide-react';

export default function TournamentSettings({ tournamentId }) {
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    match_duration: '',
    win_rest_time: '',
    daily_start_time: '',
    daily_end_time: '',
  });
  const [errors, setErrors] = useState({});

  // Fetch tournament settings
  const { data: settings, isLoading, error } = useQuery({
    queryKey: ['tournament', tournamentId, 'settings'],
    queryFn: () => tournamentsService.getSettings(tournamentId),
    retry: false, // Don't retry if settings don't exist yet
  });

  // Populate form when settings load
  useEffect(() => {
    if (settings) {
      setFormData({
        match_duration: settings.match_duration || '',
        win_rest_time: settings.win_rest_time || '',
        daily_start_time: settings.daily_start_time
          ? formatTimeForInput(settings.daily_start_time)
          : '',
        daily_end_time: settings.daily_end_time
          ? formatTimeForInput(settings.daily_end_time)
          : '',
      });
    }
  }, [settings]);

  // Helper function to format time for input (HH:mm format)
  function formatTimeForInput(time) {
    if (!time) return '';
    // Handle different time formats
    if (typeof time === 'string') {
      // If it's already in HH:mm format
      if (time.match(/^\d{2}:\d{2}$/)) {
        return time;
      }
      // If it's a datetime string, extract time
      const date = new Date(time);
      if (!isNaN(date.getTime())) {
        return date.toTimeString().slice(0, 5); // HH:mm
      }
    }
    return '';
  }

  const mutation = useMutation({
    mutationFn: (data) => tournamentsService.updateSettings(tournamentId, data),
    onSuccess: () => {
      queryClient.invalidateQueries(['tournament', tournamentId, 'settings']);
      toast.success('Tournament settings saved successfully');
      setErrors({});
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(
          error?.response?.data?.message || 'Failed to save tournament settings'
        );
      }
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    // Prepare data - convert empty strings to null for optional fields
    const submitData = {
      match_duration: formData.match_duration ? parseInt(formData.match_duration) : null,
      win_rest_time: formData.win_rest_time ? parseInt(formData.win_rest_time) : null,
      daily_start_time: formData.daily_start_time || null,
      daily_end_time: formData.daily_end_time || null,
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

  if (isLoading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error && error?.response?.status !== 404) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        <p className="font-medium mb-1">Failed to load tournament settings</p>
        <p className="text-sm">
          {error?.response?.data?.message || error?.message || 'An error occurred'}
        </p>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <div className="flex items-center space-x-2 mb-2">
          <Settings className="w-6 h-6 text-primary-600" />
          <h2 className="text-xl font-semibold text-gray-900">Tournament Settings</h2>
        </div>
        <p className="text-sm text-gray-600">
          Configure match duration, rest times, and daily scheduling constraints for this tournament.
        </p>
      </div>

      <div className="card max-w-3xl">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Match Duration */}
          <div>
            <label htmlFor="match_duration" className="label flex items-center">
              <Clock className="w-4 h-4 mr-2 text-gray-500" />
              Match Duration (minutes)
            </label>
            <input
              id="match_duration"
              name="match_duration"
              type="number"
              min="1"
              max="480"
              value={formData.match_duration}
              onChange={handleChange}
              className={`input ${errors.match_duration ? 'border-red-500' : ''}`}
              placeholder="e.g., 90 (for 90 minutes)"
            />
            <p className="mt-1 text-xs text-gray-500">
              Duration of each match in minutes (1-480 minutes)
            </p>
            {errors.match_duration && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.match_duration)
                  ? errors.match_duration[0]
                  : errors.match_duration}
              </p>
            )}
          </div>

          {/* Win Rest Time */}
          <div>
            <label htmlFor="win_rest_time" className="label flex items-center">
              <Clock className="w-4 h-4 mr-2 text-gray-500" />
              Rest Time After Win (minutes)
            </label>
            <input
              id="win_rest_time"
              name="win_rest_time"
              type="number"
              min="0"
              max="1440"
              value={formData.win_rest_time}
              onChange={handleChange}
              className={`input ${errors.win_rest_time ? 'border-red-500' : ''}`}
              placeholder="e.g., 60 (for 1 hour)"
            />
            <p className="mt-1 text-xs text-gray-500">
              Minimum rest time in minutes for a team after winning a match (0-1440 minutes / 0-24 hours)
            </p>
            {errors.win_rest_time && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.win_rest_time)
                  ? errors.win_rest_time[0]
                  : errors.win_rest_time}
              </p>
            )}
          </div>

          {/* Daily Start Time */}
          <div>
            <label htmlFor="daily_start_time" className="label flex items-center">
              <Calendar className="w-4 h-4 mr-2 text-gray-500" />
              Daily Start Time
            </label>
            <input
              id="daily_start_time"
              name="daily_start_time"
              type="time"
              value={formData.daily_start_time}
              onChange={handleChange}
              className={`input ${errors.daily_start_time ? 'border-red-500' : ''}`}
            />
            <p className="mt-1 text-xs text-gray-500">
              Earliest time matches can start each day (24-hour format)
            </p>
            {errors.daily_start_time && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.daily_start_time)
                  ? errors.daily_start_time[0]
                  : errors.daily_start_time}
              </p>
            )}
          </div>

          {/* Daily End Time */}
          <div>
            <label htmlFor="daily_end_time" className="label flex items-center">
              <Calendar className="w-4 h-4 mr-2 text-gray-500" />
              Daily End Time
            </label>
            <input
              id="daily_end_time"
              name="daily_end_time"
              type="time"
              value={formData.daily_end_time}
              onChange={handleChange}
              className={`input ${errors.daily_end_time ? 'border-red-500' : ''}`}
              min={formData.daily_start_time || undefined}
            />
            <p className="mt-1 text-xs text-gray-500">
              Latest time matches can start each day (must be after start time)
            </p>
            {errors.daily_end_time && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.daily_end_time)
                  ? errors.daily_end_time[0]
                  : errors.daily_end_time}
              </p>
            )}
          </div>

          {/* Submit Button */}
          <div className="flex justify-end space-x-4 pt-4 border-t border-gray-200">
            <button
              type="submit"
              disabled={mutation.isLoading}
              className="btn btn-primary flex items-center"
            >
              <Save className="w-4 h-4 mr-2" />
              {mutation.isLoading ? 'Saving...' : 'Save Settings'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
