import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { sportsService } from '../../api/tournaments';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft, Edit, Trash2 } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { useState } from 'react';

export default function SportDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const [deleteConfirm, setDeleteConfirm] = useState(false);

  const { data: sport, isLoading, error } = useQuery({
    queryKey: ['sport', id],
    queryFn: () => sportsService.get(id),
  });

  const deleteMutation = useMutation({
    mutationFn: () => sportsService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['sports']);
      toast.success('Sport deleted successfully');
      navigate('/sports');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete sport');
    },
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error || !sport) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {error?.response?.data?.message || 'Sport not found'}
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
        <div className="flex justify-between items-start">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">{sport.name}</h1>
          </div>
          <div className="flex items-center space-x-2">
            <button
              onClick={() => navigate(`/sports/${id}/edit`)}
              className="btn btn-secondary flex items-center"
            >
              <Edit className="w-4 h-4 mr-2" />
              Edit
            </button>
            <button
              onClick={() => setDeleteConfirm(true)}
              className="btn btn-danger flex items-center"
            >
              <Trash2 className="w-4 h-4 mr-2" />
              Delete
            </button>
          </div>
        </div>
      </div>

      <div className="card">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Sport Information</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p className="text-sm text-gray-500 mb-1">ID</p>
            <p className="font-medium text-gray-900">{sport.id}</p>
          </div>
          {/* <div>
            <p className="text-sm text-gray-500 mb-1">Team Based</p>
            <span
              className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${
                sport.team_based
                  ? 'bg-green-100 text-green-800'
                  : 'bg-gray-100 text-gray-800'
              }`}
            >
              {sport.team_based ? 'Yes' : 'No'}
            </span>
          </div> */}
          {sport.description && (
            <div className="md:col-span-2">
              <p className="text-sm text-gray-500 mb-1">Description</p>
              <p className="text-gray-900 whitespace-pre-wrap">{sport.description}</p>
            </div>
          )}
          {sport.rules && (
            <div className="md:col-span-2">
              <p className="text-sm text-gray-500 mb-1">Rules</p>
              <p className="text-gray-900 whitespace-pre-wrap">{sport.rules}</p>
            </div>
          )}
          {sport.created_at && (
            <div>
              <p className="text-sm text-gray-500 mb-1">Created At</p>
              <p className="text-gray-900">
                {new Date(sport.created_at).toLocaleString()}
              </p>
            </div>
          )}
          {sport.updated_at && (
            <div>
              <p className="text-sm text-gray-500 mb-1">Updated At</p>
              <p className="text-gray-900">
                {new Date(sport.updated_at).toLocaleString()}
              </p>
            </div>
          )}
        </div>
      </div>

      <ConfirmDialog
        isOpen={deleteConfirm}
        title="Delete Sport"
        message="Are you sure you want to delete this sport? This action cannot be undone."
        onConfirm={() => {
          deleteMutation.mutate();
          setDeleteConfirm(false);
        }}
        onCancel={() => setDeleteConfirm(false)}
        isLoading={deleteMutation.isLoading}
      />
    </div>
  );
}
