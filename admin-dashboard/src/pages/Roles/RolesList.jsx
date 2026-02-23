import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { rolesService } from '../../api/roles';
import { usePermissions } from '../../hooks/usePermissions';
import Unauthorized from '../../components/common/Unauthorized';
import { Search, Plus, Edit, Trash2, Eye } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

export default function RolesList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [deleteRoleId, setDeleteRoleId] = useState(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { isAdmin } = usePermissions();

  // Roles management is admin-only
  if (!isAdmin()) {
    return <Unauthorized message="You do not have permission to manage roles. Administrator access is required." />;
  }

  // Debounce search term to avoid querying on every keystroke
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
      setPage(1); // Reset to page 1 when search changes
    }, 500); // Wait 500ms after user stops typing

    return () => clearTimeout(timer);
  }, [searchTerm]);

  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['roles', page, debouncedSearchTerm],
    queryFn: async () => {
      try {
        const result = await rolesService.list({ per_page: 15, page, search: debouncedSearchTerm || undefined });
        return result;
      } catch (err) {
        console.error('Error in queryFn:', err);
        throw err;
      }
    },
    retry: false,
    placeholderData: (previousData) => previousData,
    onError: (error) => {
      console.error('Roles list error:', error);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => rolesService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['roles']);
      setDeleteRoleId(null);
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    setDebouncedSearchTerm(searchTerm);
    setPage(1);
  };

  const handleDelete = () => {
    if (deleteRoleId) {
      deleteMutation.mutate(deleteRoleId);
    }
  };

  // Handle both paginated and non-paginated responses
  let roles = [];
  let pagination = {};
  
  if (Array.isArray(data)) {
    roles = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    roles = data.data || [];
    
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? roles.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? roles.length,
      };
    } else {
      pagination = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 15,
        total: data.total ?? roles.length,
        from: data.from ?? 1,
        to: data.to ?? roles.length,
      };
    }
  }

  // Remove the automatic page sync to prevent pagination loops
  // The page state should be controlled by user interactions only

  // Only show full page loading on initial load, not on refetch
  if (isLoading && !data) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">Role Management</h1>
        <button
          onClick={() => navigate('/roles/new')}
          className="btn btn-primary flex items-center"
        >
          <Plus className="w-5 h-5 mr-2" />
          Add Role
        </button>
      </div>

      {/* Search */}
      <div className="card mb-6">
        <form onSubmit={handleSearch} className="flex gap-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Search roles by name or description..."
              className="input pl-10"
            />
          </div>
          <button type="submit" className="btn btn-primary">
            Search
          </button>
        </form>
      </div>

      {/* Roles Table */}
      <div className="card relative">
        {/* Loading overlay - only shows during refetch, not initial load */}
        {isFetching && data && (
          <div className="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
            <div className="flex items-center space-x-2">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
              <span className="text-sm text-gray-600">Loading...</span>
            </div>
          </div>
        )}
        <div className="overflow-x-auto">
          <table className="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Permissions</th>
                <th>Users</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {error && !data ? (
                <tr>
                  <td colSpan="7" className="text-center py-8">
                    <div className="text-red-600">
                      <p className="font-medium">Error loading roles</p>
                      <p className="text-sm text-gray-600 mt-1">
                        {error.response?.data?.message || error.message || 'Unknown error'}
                      </p>
                    </div>
                  </td>
                </tr>
              ) : roles.length === 0 ? (
                <tr>
                  <td colSpan="7" className="text-center py-8 text-gray-500">
                    No roles found
                  </td>
                </tr>
              ) : (
                roles.map((role) => (
                  <tr key={role.id}>
                    <td>{role.id}</td>
                    <td className="font-medium">{role.name}</td>
                    <td>{role.description || '-'}</td>
                    <td>
                      <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                        {role.permissions_count || 0} permissions
                      </span>
                    </td>
                    <td>
                      <span className="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">
                        {role.users_count || 0} users
                      </span>
                    </td>
                    <td>{new Date(role.created_at).toLocaleDateString()}</td>
                    <td>
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => navigate(`/roles/${role.id}`)}
                          className="text-primary-600 hover:text-primary-800"
                          title="View"
                        >
                          <Eye className="w-5 h-5" />
                        </button>
                        <button
                          onClick={() => navigate(`/roles/${role.id}/edit`)}
                          className="text-blue-600 hover:text-blue-800"
                          title="Edit"
                        >
                          <Edit className="w-5 h-5" />
                        </button>
                        <button
                          onClick={() => setDeleteRoleId(role.id)}
                          className="text-red-600 hover:text-red-800"
                          title="Delete"
                        >
                          <Trash2 className="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {pagination && pagination.total > 0 && (
          <div className="mt-4 border-t pt-4">
            <div className="flex items-center justify-between mb-2">
              <div className="text-sm text-gray-700">
                Showing {pagination.from || 0} to {pagination.to || 0} of {pagination.total || 0} results
              </div>
              {pagination.last_page > 1 && (
                <div className="flex items-center space-x-2">
                  <button
                    onClick={() => {
                      const newPage = Math.max(1, page - 1);
                      console.log('Previous button clicked, going to page:', newPage);
                      setPage(newPage);
                    }}
                    disabled={page === 1}
                    className="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  <span className="flex items-center px-4 text-sm text-gray-700">
                    Page {page} of {pagination.last_page || 1}
                  </span>
                  <button
                    onClick={() => {
                      const newPage = Math.min(pagination.last_page || 1, page + 1);
                      console.log('Next button clicked, going to page:', newPage);
                      setPage(newPage);
                    }}
                    disabled={page >= (pagination.last_page || 1)}
                    className="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        isOpen={!!deleteRoleId}
        onClose={() => setDeleteRoleId(null)}
        onConfirm={handleDelete}
        title="Delete Role"
        message="Are you sure you want to delete this role? This action cannot be undone."
        confirmText="Delete"
        cancelText="Cancel"
      />
    </div>
  );
}
