import { useState, useEffect } from 'react';
import { useMutation } from '@tanstack/react-query';
import { playersService } from '../../api/teams';
import { useToast } from '../../context/ToastContext';
import { X } from 'lucide-react';

const POSITION_OPTIONS = [
  'Goalkeeper',
  'Defender',
  'Midfielder',
  'Forward',
];

export default function PlayerFormModal({ teamId, player, onClose, onSuccess }) {
  const [formData, setFormData] = useState({
    full_name: '',
    position: '',
    jersey_number: '',
  });
  const [errors, setErrors] = useState({});
  const toast = useToast();

  useEffect(() => {
    if (player) {
      setFormData({
        full_name: player.full_name || player.name || '',
        position: player.position || '',
        jersey_number: player.jersey_number || '',
      });
    } else {
      setFormData({
        full_name: '',
        position: '',
        jersey_number: '',
      });
    }
    setErrors({});
  }, [player]);

  const createMutation = useMutation({
    mutationFn: (data) => playersService.create({ ...data, team_id: teamId }),
    onSuccess: () => {
      toast.success('Player created successfully');
      onSuccess();
    },
    onError: (error) => {
      const errorMessage = error?.response?.data?.message || 'Failed to create player';
      toast.error(errorMessage);
      if (error?.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data) => playersService.update(player.id, data),
    onSuccess: () => {
      toast.success('Player updated successfully');
      onSuccess();
    },
    onError: (error) => {
      const errorMessage = error?.response?.data?.message || 'Failed to update player';
      toast.error(errorMessage);
      if (error?.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    // Validation
    const newErrors = {};
    if (!formData.full_name.trim()) {
      newErrors.full_name = 'Full name is required';
    }
    if (!formData.position) {
      newErrors.position = 'Position is required';
    }
    if (!formData.jersey_number) {
      newErrors.jersey_number = 'Jersey number is required';
    } else if (isNaN(formData.jersey_number) || parseInt(formData.jersey_number) < 1) {
      newErrors.jersey_number = 'Jersey number must be a positive number';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    const submitData = {
      full_name: formData.full_name.trim(),
      position: formData.position,
      jersey_number: parseInt(formData.jersey_number),
    };

    if (player) {
      updateMutation.mutate(submitData);
    } else {
      createMutation.mutate(submitData);
    }
  };

  const isLoading = createMutation.isLoading || updateMutation.isLoading;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between p-6 border-b">
          <h2 className="text-xl font-semibold text-gray-900">
            {player ? 'Edit Player' : 'Add Player'}
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600"
            disabled={isLoading}
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label htmlFor="full_name" className="label">
              Full Name <span className="text-red-500">*</span>
            </label>
            <input
              id="full_name"
              type="text"
              value={formData.full_name}
              onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
              className={`input ${errors.full_name ? 'border-red-500' : ''}`}
              disabled={isLoading}
            />
            {errors.full_name && (
              <p className="mt-1 text-sm text-red-600">{errors.full_name}</p>
            )}
          </div>

          <div>
            <label htmlFor="position" className="label">
              Position <span className="text-red-500">*</span>
            </label>
            <select
              id="position"
              value={formData.position}
              onChange={(e) => setFormData({ ...formData, position: e.target.value })}
              className={`input ${errors.position ? 'border-red-500' : ''}`}
              disabled={isLoading}
            >
              <option value="">Select Position</option>
              {POSITION_OPTIONS.map((pos) => (
                <option key={pos} value={pos}>
                  {pos}
                </option>
              ))}
            </select>
            {errors.position && (
              <p className="mt-1 text-sm text-red-600">{errors.position}</p>
            )}
          </div>

          <div>
            <label htmlFor="jersey_number" className="label">
              Jersey Number <span className="text-red-500">*</span>
            </label>
            <input
              id="jersey_number"
              type="number"
              min="1"
              value={formData.jersey_number}
              onChange={(e) => setFormData({ ...formData, jersey_number: e.target.value })}
              className={`input ${errors.jersey_number ? 'border-red-500' : ''}`}
              disabled={isLoading}
            />
            {errors.jersey_number && (
              <p className="mt-1 text-sm text-red-600">{errors.jersey_number}</p>
            )}
          </div>

          <div className="flex justify-end space-x-3 pt-4 border-t">
            <button
              type="button"
              onClick={onClose}
              className="btn btn-secondary"
              disabled={isLoading}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="btn btn-primary"
              disabled={isLoading}
            >
              {isLoading ? 'Saving...' : player ? 'Update Player' : 'Add Player'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
