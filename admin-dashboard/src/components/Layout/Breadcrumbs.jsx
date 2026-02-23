import { Link, useLocation } from 'react-router-dom';
import { ChevronRight, Home } from 'lucide-react';
import { usePermissions } from '../../hooks/usePermissions';

export default function Breadcrumbs() {
  const location = useLocation();
  const paths = location.pathname.split('/').filter(Boolean);
  const { hasPermission, isAdmin, isCoach } = usePermissions();

  const getBreadcrumbName = (path) => {
    const names = {
      dashboard: 'Dashboard',
      users: 'Users',
      roles: 'Roles',
      permissions: 'Permissions',
      tournaments: 'Tournaments',
      teams: 'Teams',
      matches: 'Matches',
      results: 'Results',
      new: 'Create',
      edit: 'Edit',
    };
    return names[path] || path.charAt(0).toUpperCase() + path.slice(1);
  };

  const buildPath = (index) => {
    return '/' + paths.slice(0, index + 1).join('/');
  };

  if (paths.length === 0) {
    return null;
  }

  return (
    <nav className="flex items-center space-x-2 text-sm">
      <Link
        to="/dashboard"
        className="text-gray-500 hover:text-gray-700 flex items-center"
      >
        <Home className="w-4 h-4" />
      </Link>
      {paths.map((path, index) => {
        const isLast = index === paths.length - 1;
        const pathUrl = buildPath(index);
        const name = getBreadcrumbName(path);

        // Handle numeric IDs
        const isId = /^\d+$/.test(path);
        const displayName = isId ? `#${path}` : name;

        return (
          <div key={index} className="flex items-center space-x-2">
            {isAdmin() && (
  <>
    <ChevronRight className="w-4 h-4 text-gray-400" />
    {isLast ? (
      <span className="text-gray-900 font-medium">
        {displayName}
      </span>
    ) : (
      <Link
        to={pathUrl}
        className="text-gray-500 hover:text-gray-700"
      >
        {displayName}
      </Link>
    )}
  </>
)}

          </div>
        );
      })}
    </nav>
  );
}
