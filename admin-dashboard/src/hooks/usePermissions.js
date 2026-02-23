import { useAuth } from '../context/AuthContext';

/**
 * Hook to check user permissions and roles
 * @returns {Object} Permission checking functions
 */
export const usePermissions = () => {
  const { permissions, roles } = useAuth();

  /**
   * Check if user has a specific permission
   * @param {string} permissionName - Name of the permission (e.g., 'manage_sports')
   * @returns {boolean}
   */
  const hasPermission = (permissionName) => {
    if (!permissions || !Array.isArray(permissions)) {
      return false;
    }
    return permissions.some(
      (permission) => permission.name === permissionName
    );
  };

  /**
   * Check if user has any of the specified permissions
   * @param {string[]} permissionNames - Array of permission names
   * @returns {boolean}
   */
  const hasAnyPermission = (permissionNames) => {
    if (!Array.isArray(permissionNames)) {
      return false;
    }
    return permissionNames.some((permissionName) => hasPermission(permissionName));
  };

  /**
   * Check if user has all of the specified permissions
   * @param {string[]} permissionNames - Array of permission names
   * @returns {boolean}
   */
  const hasAllPermissions = (permissionNames) => {
    if (!Array.isArray(permissionNames)) {
      return false;
    }
    return permissionNames.every((permissionName) => hasPermission(permissionName));
  };

  /**
   * Check if user has a specific role
   * @param {string} roleName - Name of the role (e.g., 'Administrator', 'Coach', 'Referee')
   * @returns {boolean}
   */
  const hasRole = (roleName) => {
    if (!roles || !Array.isArray(roles)) {
      return false;
    }
    return roles.some((role) => role.name === roleName);
  };

  /**
   * Check if user has any of the specified roles
   * @param {string[]} roleNames - Array of role names
   * @returns {boolean}
   */
  const hasAnyRole = (roleNames) => {
    if (!Array.isArray(roleNames)) {
      return false;
    }
    return roleNames.some((roleName) => hasRole(roleName));
  };

  /**
   * Check if user is an administrator
   * @returns {boolean}
   */
  const isAdmin = () => {
    return hasRole('Administrator');
  };

  /**
   * Check if user is a coach
   * @returns {boolean}
   */
  const isCoach = () => {
    return hasRole('Coach');
  };

  /**
   * Check if user is a referee
   * @returns {boolean}
   */
  const isReferee = () => {
    return hasRole('Referee');
  };

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    isAdmin,
    isCoach,
    isReferee,
    permissions,
    roles,
  };
};
