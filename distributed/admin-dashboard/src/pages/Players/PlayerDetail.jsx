import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import { playersService } from '../../api/teams';
import { useToast } from '../../context/ToastContext';
import { ArrowLeft, Edit, Trash2, Users, Shirt } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { useState } from 'react';

export default function PlayerDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const [deleteConfirm, setDeleteConfirm] = useState(false);

  const { data: player, isLoading, error } = useQuery({
    queryKey: ['player', id],
    queryFn: () => playersService.get(id),
  });

  const deleteMutation = useMutation({
    mutationFn: () => playersService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['players']);
      toast.success('Player deleted successfully');
      navigate('/players');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete player');
    },
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error || !player) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        {error?.response?.data?.message || 'Player not found'}
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
        <div className="flex justify-between items-start">
          <div className="flex items-center space-x-4">
            <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
              <Shirt className="w-8 h-8 text-primary-600" />
            </div>
            <div>
              <h1 className="text-3xl font-bold text-gray-900 mb-2">
                {player.full_name || player.name || 'N/A'}
              </h1>
              {player.team && (
                <p className="text-gray-600 flex items-center">
                  <Users className="w-4 h-4 mr-1" />
                  {player.team.name}
                </p>
              )}
            </div>
          </div>
          <div className="flex items-center space-x-2">
            <button
              onClick={() => navigate(`/players/${id}/edit`)}
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
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Player Information</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p className="text-sm text-gray-500 mb-1">ID</p>
            <p className="font-medium text-gray-900">{player.id}</p>
          </div>
          <div>
            <p className="text-sm text-gray-500 mb-1">Full Name</p>
            <p className="font-medium text-gray-900">{player.full_name || player.name || 'N/A'}</p>
          </div>
          {player.team && (
            <div>
              <p className="text-sm text-gray-500 mb-1">Team</p>
              <p className="font-medium text-gray-900">{player.team.name}</p>
            </div>
          )}
          <div>
            <p className="text-sm text-gray-500 mb-1">Position</p>
            <span className="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
              {player.position || 'N/A'}
            </span>
          </div>
          <div>
            <p className="text-sm text-gray-500 mb-1">Jersey Number</p>
            <p className="font-bold text-2xl text-gray-900">#{player.jersey_number || 'N/A'}</p>
          </div>
          {player.created_at && (
            <div>
              <p className="text-sm text-gray-500 mb-1">Created At</p>
              <p className="text-gray-900">
                {new Date(player.created_at).toLocaleString()}
              </p>
            </div>
          )}
          {player.updated_at && (
            <div>
              <p className="text-sm text-gray-500 mb-1">Updated At</p>
              <p className="text-gray-900">
                {new Date(player.updated_at).toLocaleString()}
              </p>
            </div>
          )}
        </div>
      </div>

      <ConfirmDialog
        isOpen={deleteConfirm}
        title="Delete Player"
        message="Are you sure you want to delete this player? This action cannot be undone."
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
