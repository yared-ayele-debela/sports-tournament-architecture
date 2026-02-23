import { useState, useEffect, useRef } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { usersService } from '../../api/users';
import { rolesService } from '../../api/roles';
import { useToast } from '../../context/ToastContext';
import { usePermissions } from '../../hooks/usePermissions';
import Unauthorized from '../../components/common/Unauthorized';
import { Search, Plus, Edit, Trash2, Eye } from 'lucide-react';
import ConfirmDialog from '../../components/common/ConfirmDialog';

export default function UsersList() {
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [deleteUserId, setDeleteUserId] = useState(null);
  const searchInputRef = useRef(null);
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const toast = useToast();
  const { hasPermission, isAdmin } = usePermissions();
  
  // Check permissions
  const canManageUsers = hasPermission('manage_users') || isAdmin();
  const canCreateUsers = canManageUsers;
  const canEditUsers = canManageUsers;
  const canDeleteUsers = canManageUsers;

  // Block access if user doesn't have permission
  if (!canManageUsers) {
    return <Unauthorized message="You do not have permission to manage users." />;
  }

  // Debug: Check token on mount
  useEffect(() => {
    const token = localStorage.getItem('access_token');
    console.log('UsersList - Token exists:', !!token);
    console.log('UsersList - Token preview:', token ? token.substring(0, 20) + '...' : 'none');
  }, []);

  // Debounce search term to avoid querying on every keystroke
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
      setPage(1); // Reset to page 1 when search changes
    }, 500); // Wait 500ms after user stops typing

    return () => clearTimeout(timer);
  }, [searchTerm]);

  const { data, isLoading, error, isFetching } = useQuery({
    queryKey: ['users', page, debouncedSearchTerm],
    queryFn: async () => {
      console.log('Fetching users list...');
      const token = localStorage.getItem('access_token');
      console.log('Token before request:', token ? 'exists' : 'missing');
      try {
        const result = await usersService.list({ per_page: 15, page, search: debouncedSearchTerm || undefined });
        console.log('Users list fetched successfully:', result);
        return result;
      } catch (err) {
        console.error('Error in queryFn:', err);
        throw err;
      }
    },
    retry: false, // Don't retry on 401 errors
    placeholderData: (previousData) => previousData, // Keep previous data while fetching
    onError: (error) => {
      // Log error for debugging
      console.error('Users list error:', error);
      console.error('Error response:', error.response);
      console.error('Error status:', error.response?.status);
      // If it's a 401, the interceptor will handle redirect
      // For other errors, we'll display them
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id) => usersService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['users']);
      setDeleteUserId(null);
      toast.success('User deleted successfully');
    },
    onError: (error) => {
      toast.error(error?.response?.data?.message || 'Failed to delete user');
    },
  });

  const handleSearch = (e) => {
    e.preventDefault();
    // Trigger search immediately on form submit
    setDebouncedSearchTerm(searchTerm);
    setPage(1);
  };

  const handleDelete = () => {
    if (deleteUserId) {
      deleteMutation.mutate(deleteUserId);
    }
  };

  // The API returns: { success: true, data: [...], pagination: {...} }
  // extractData returns: response.data?.data || response.data
  // So for paginated: { data: [...], pagination: {...} }
  // For non-paginated: [...]
  
  // Handle both paginated and non-paginated responses
  let users = [];
  let pagination = {};
  
  if (Array.isArray(data)) {
    // Direct array response
    users = data;
    pagination = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: data.length,
      from: 1,
      to: data.length,
    };
  } else if (data && typeof data === 'object') {
    // Paginated response: { data: [...], pagination: {...} }
    users = data.data || [];
    
    // Extract pagination - check nested first, then root level
    if (data.pagination && typeof data.pagination === 'object') {
      pagination = {
        current_page: data.pagination.current_page ?? 1,
        last_page: data.pagination.last_page ?? 1,
        per_page: data.pagination.per_page ?? 15,
        total: data.pagination.total ?? users.length,
        from: data.pagination.from ?? 1,
        to: data.pagination.to ?? users.length,
      };
    } else {
      // Pagination at root level (Laravel pagination format)
      pagination = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 15,
        total: data.total ?? users.length,
        from: data.from ?? 1,
        to: data.to ?? users.length,
      };
    }
  }
  
  // Debug: Log the data structure
  console.log('UsersList - Full data:', JSON.stringify(data, null, 2));
  console.log('UsersList - Users count:', users.length);
  console.log('UsersList - Pagination object:', JSON.stringify(pagination, null, 2));
  console.log('UsersList - Has more pages?', pagination.last_page > 1);
  
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

  // Show error in table area, not full page (if we have previous data)
  const showErrorInTable = error && !data;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold text-gray-900">User Management</h1>
        {canCreateUsers && (
          <button
            onClick={() => navigate('/users/new')}
            className="btn btn-primary flex items-center"
          >
            <Plus className="w-5 h-5 mr-2" />
            Add User
          </button>
        )}
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
              placeholder="Search users by name or email..."
              className="input pl-10"
            />
          </div>
          <button type="submit" className="btn btn-primary">
            Search
          </button>
        </form>
      </div>

      {/* Users Table */}
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
                <th>Email</th>
                <th>Roles</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {error && !data ? (
                <tr>
                  <td colSpan="6" className="text-center py-8">
                    <div className="text-red-600">
                      <p className="font-medium">Error loading users</p>
                      <p className="text-sm text-gray-600 mt-1">
                        {error.response?.data?.message || error.message || 'Unknown error'}
                      </p>
                    </div>
                  </td>
                </tr>
              ) : users.length === 0 ? (
                <tr>
                  <td colSpan="6" className="text-center py-8 text-gray-500">
                    No users found
                  </td>
                </tr>
              ) : (
                users.map((user) => (
                  <tr key={user.id}>
                    <td>{user.id}</td>
                    <td className="font-medium">{user.name}</td>
                    <td>{user.email}</td>
                    <td>
                      <div className="flex flex-wrap gap-1">
                        {user.roles && user.roles.length > 0 ? (
                          user.roles.map((role, idx) => (
                            <span
                              key={idx}
                              className="px-2 py-1 bg-primary-100 text-primary-800 rounded text-xs"
                            >
                              {role.name || role}
                            </span>
                          ))
                        ) : (
                          <span className="text-gray-400 text-sm">No roles</span>
                        )}
                      </div>
                    </td>
                    <td>{new Date(user.created_at).toLocaleDateString()}</td>
                    <td>
                      <div className="flex items-center space-x-2">
                        <button
                          onClick={() => navigate(`/users/${user.id}`)}
                          className="text-primary-600 hover:text-primary-800"
                          title="View"
                        >
                          <Eye className="w-5 h-5" />
                        </button>
                        {canEditUsers && (
                          <button
                            onClick={() => navigate(`/users/${user.id}/edit`)}
                            className="text-blue-600 hover:text-blue-800"
                            title="Edit"
                          >
                            <Edit className="w-5 h-5" />
                          </button>
                        )}
                        {canDeleteUsers && (
                          <button
                            onClick={() => setDeleteUserId(user.id)}
                            className="text-red-600 hover:text-red-800"
                            title="Delete"
                          >
                            <Trash2 className="w-5 h-5" />
                          </button>
                        )}
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
              {(pagination.last_page > 1 || pagination.total > pagination.per_page) && (
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
            {/* Always show pagination info for debugging */}
            <div className="text-xs text-gray-500 text-center">
              Total: {pagination.total} users | Per page: {pagination.per_page} | Current page: {page} | API page: {pagination.current_page} of {pagination.last_page || 1} | 
              {pagination.last_page > 1 ? ' Has multiple pages' : ' Single page'}
            </div>
          </div>
        )}
      </div>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        isOpen={!!deleteUserId}
        onClose={() => setDeleteUserId(null)}
        onConfirm={handleDelete}
        title="Delete User"
        message="Are you sure you want to delete this user? This action cannot be undone."
        confirmText="Delete"
        cancelText="Cancel"
      />
    </div>
  );
}
