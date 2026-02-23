import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { venuesService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft, Save, MapPin, Users, Building2 } from 'lucide-react';

export default function VenueForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    name: '',
    location: '',
    capacity: '',
  });
  const [errors, setErrors] = useState({});

  // Fetch venue if editing
  const { data: venueData, isLoading: loadingVenue } = useQuery({
    queryKey: ['venue', id],
    queryFn: () => venuesService.get(id),
    enabled: isEdit,
  });

  // Populate form when venue data loads
  useEffect(() => {
    if (venueData && isEdit) {
      setFormData({
        name: venueData.name || '',
        location: venueData.location || '',
        capacity: venueData.capacity || '',
      });
    }
  }, [venueData, isEdit]);

  const mutation = useMutation({
    mutationFn: (data) => {
      if (isEdit) {
        return venuesService.update(id, data);
      }
      return venuesService.create(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['venues']);
      toast.success(isEdit ? 'Venue updated successfully' : 'Venue created successfully');
      navigate('/venues');
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(error?.response?.data?.message || (isEdit ? 'Failed to update venue' : 'Failed to create venue'));
      }
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    // Prepare data
    const submitData = {
      name: formData.name.trim(),
      location: formData.location.trim() || null,
      capacity: formData.capacity ? parseInt(formData.capacity, 10) : null,
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

  if (loadingVenue && isEdit) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-500">Loading venue...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button
            onClick={() => navigate('/venues')}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <ArrowLeft className="w-5 h-5 text-gray-600" />
          </button>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              {isEdit ? 'Edit Venue' : 'Create New Venue'}
            </h1>
            <p className="text-gray-600 mt-1">
              {isEdit ? 'Update venue information' : 'Add a new tournament venue'}
            </p>
          </div>
        </div>
      </div>

      {/* Form */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <form onSubmit={handleSubmit} className="p-6 space-y-6">
          {/* Name Field */}
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
              <div className="flex items-center space-x-2">
                <Building2 className="w-4 h-4" />
                <span>Venue Name *</span>
              </div>
            </label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              required
              className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 ${
                errors.name ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="Enter venue name"
            />
            {errors.name && (
              <p className="mt-1 text-sm text-red-600">{errors.name[0]}</p>
            )}
          </div>

          {/* Location Field */}
          <div>
            <label htmlFor="location" className="block text-sm font-medium text-gray-700 mb-2">
              <div className="flex items-center space-x-2">
                <MapPin className="w-4 h-4" />
                <span>Location</span>
              </div>
            </label>
            <input
              type="text"
              id="location"
              name="location"
              value={formData.location}
              onChange={handleChange}
              className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 ${
                errors.location ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="Enter venue location (e.g., City, Country)"
            />
            {errors.location && (
              <p className="mt-1 text-sm text-red-600">{errors.location[0]}</p>
            )}
            <p className="mt-1 text-xs text-gray-500">Optional: Specify the city, address, or general location</p>
          </div>

          {/* Capacity Field */}
          <div>
            <label htmlFor="capacity" className="block text-sm font-medium text-gray-700 mb-2">
              <div className="flex items-center space-x-2">
                <Users className="w-4 h-4" />
                <span>Capacity</span>
              </div>
            </label>
            <input
              type="number"
              id="capacity"
              name="capacity"
              value={formData.capacity}
              onChange={handleChange}
              min="1"
              className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 ${
                errors.capacity ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="Enter maximum capacity"
            />
            {errors.capacity && (
              <p className="mt-1 text-sm text-red-600">{errors.capacity[0]}</p>
            )}
            <p className="mt-1 text-xs text-gray-500">Optional: Maximum number of spectators</p>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              onClick={() => navigate('/venues')}
              className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              disabled={mutation.isLoading}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
              disabled={mutation.isLoading}
            >
              <Save className="w-4 h-4" />
              <span>{mutation.isLoading ? 'Saving...' : isEdit ? 'Update Venue' : 'Create Venue'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
