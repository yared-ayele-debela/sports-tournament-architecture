import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { sportsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft } from 'lucide-react';

export default function SportForm() {
  const { id } = useParams();
  const isEdit = !!id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();

  const [formData, setFormData] = useState({
    name: '',
    team_based: true,
    rules: '',
    description: '',
  });
  const [errors, setErrors] = useState({});

  // Fetch sport if editing
  const { data: sportData, isLoading: loadingSport } = useQuery({
    queryKey: ['sport', id],
    queryFn: () => sportsService.get(id),
    enabled: isEdit,
  });

  // Populate form when sport data loads
  useEffect(() => {
    if (sportData && isEdit) {
      setFormData({
        name: sportData.name || '',
        team_based: sportData.team_based !== undefined ? sportData.team_based : true,
        rules: sportData.rules || '',
        description: sportData.description || '',
      });
    }
  }, [sportData, isEdit]);

  const mutation = useMutation({
    mutationFn: (data) => {
      if (isEdit) {
        return sportsService.update(id, data);
      }
      return sportsService.create(data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['sports']);
      toast.success(isEdit ? 'Sport updated successfully' : 'Sport created successfully');
      navigate('/sports');
    },
    onError: (error) => {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        toast.error(
          error?.response?.data?.message ||
            (isEdit ? 'Failed to update sport' : 'Failed to create sport')
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
      team_based: formData.team_based === true || formData.team_based === 'true',
    };

    mutation.mutate(submitData);
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  if (loadingSport) {
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
          onClick={() => navigate('/sports')}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-5 h-5 mr-2" />
          Back to Sports
        </button>
        <h1 className="text-3xl font-bold text-gray-900">
          {isEdit ? 'Edit Sport' : 'Create New Sport'}
        </h1>
      </div>

      <div className="card max-w-2xl">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Name */}
          <div>
            <label htmlFor="name" className="label">
              Sport Name <span className="text-red-500">*</span>
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

          {/* Team Based */}
          {/* <div>
            <label className="label">
              Team Based <span className="text-red-500">*</span>
            </label>
            <div className="flex items-center space-x-4">
              <label className="flex items-center">
                <input
                  type="radio"
                  name="team_based"
                  value="true"
                  checked={formData.team_based === true || formData.team_based === 'true'}
                  onChange={() => setFormData((prev) => ({ ...prev, team_based: true }))}
                  className="mr-2"
                />
                Yes
              </label>
              <label className="flex items-center">
                <input
                  type="radio"
                  name="team_based"
                  value="false"
                  checked={formData.team_based === false || formData.team_based === 'false'}
                  onChange={() => setFormData((prev) => ({ ...prev, team_based: false }))}
                  className="mr-2"
                />
                No
              </label>
            </div>
            {errors.team_based && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.team_based) ? errors.team_based[0] : errors.team_based}
              </p>
            )}
          </div> */}

          {/* Description */}
          <div>
            <label htmlFor="description" className="label">Description</label>
            <textarea
              id="description"
              name="description"
              value={formData.description}
              onChange={handleChange}
              rows={4}
              className={`input ${errors.description ? 'border-red-500' : ''}`}
              placeholder="Enter sport description..."
              maxLength={1000}
            />
            <p className="mt-1 text-sm text-gray-500">
              {formData.description.length}/1000 characters
            </p>
            {errors.description && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.description) ? errors.description[0] : errors.description}
              </p>
            )}
          </div>

          {/* Rules */}
          <div>
            <label htmlFor="rules" className="label">Rules</label>
            <textarea
              id="rules"
              name="rules"
              value={formData.rules}
              onChange={handleChange}
              rows={6}
              className={`input ${errors.rules ? 'border-red-500' : ''}`}
              placeholder="Enter sport rules..."
            />
            {errors.rules && (
              <p className="mt-1 text-sm text-red-600">
                {Array.isArray(errors.rules) ? errors.rules[0] : errors.rules}
              </p>
            )}
          </div>

          {/* Submit Button */}
          <div className="flex justify-end space-x-4 pt-4">
            <button
              type="button"
              onClick={() => navigate('/sports')}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button type="submit" disabled={mutation.isLoading} className="btn btn-primary">
              {mutation.isLoading
                ? 'Saving...'
                : isEdit
                ? 'Update Sport'
                : 'Create Sport'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
