import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { usePermissions } from '../../hooks/usePermissions';
import { useAuth } from '../../context/AuthContext';
import { 
  LayoutDashboard, 
  Users, 
  Shield, 
  Key, 
  Trophy, 
  Users as TeamIcon, 
  Calendar, 
  BarChart3,
  ChevronLeft,
  ChevronRight,
  Activity,
  UserCircle,
  Award,
  MapPin
} from 'lucide-react';

export default function Sidebar({ onCollapseChange }) {
  const [collapsed, setCollapsed] = useState(false);
  const location = useLocation();
  const { hasPermission, isAdmin, isCoach } = usePermissions();

  const handleToggle = () => {
    const newState = !collapsed;
    setCollapsed(newState);
    if (onCollapseChange) {
      onCollapseChange(newState);
    }
  };

  // Define menu items with their required permissions
  const allMenuItems = [
    { 
      path: '/dashboard', 
      icon: LayoutDashboard, 
      label: 'Dashboard',
      permission: null // Dashboard is accessible to all authenticated users
    },
    { 
      path: '/users', 
      icon: Users, 
      label: 'Users',
      permission: 'manage_users',
      adminOnly: false
    },
    { 
      path: '/roles', 
      icon: Shield, 
      label: 'Roles',
      permission: null, // Roles management is admin-only
      adminOnly: true
    },
    { 
      path: '/tournaments', 
      icon: Trophy, 
      label: 'Tournaments',
      permission: 'manage_tournaments',
      adminOnly: false
    },
    { 
      path: '/sports', 
      icon: Activity, 
      label: 'Sports',
      permission: 'manage_sports',
      adminOnly: false
    },
    { 
      path: '/venues', 
      icon: MapPin, 
      label: 'Venues',
      permission: 'manage_venues',
      adminOnly: false
    },
    { 
      path: '/teams', 
      icon: TeamIcon, 
      label: 'Teams',
      permission: 'manage_teams',
      adminOnly: false
    },
    { 
      path: '/teams/my-teams', 
      icon: TeamIcon, 
      label: 'My Teams',
      permission: null,
      adminOnly: false,
      coachOnly: true
    },
    { 
      path: '/matches/my-matches', 
      icon: Calendar, 
      label: 'My Matches',
      permission: null,
      adminOnly: false,
      coachOnly: true
    },
    { 
      path: '/players', 
      icon: UserCircle, 
      label: 'Players',
      permission: 'manage_players',
      adminOnly: false
    },
    { 
      path: '/matches', 
      icon: Calendar, 
      label: 'Matches',
      permission: 'manage_matches',
      adminOnly: false
    },
    { 
      path: '/standings', 
      icon: Award, 
      label: 'Standings',
      permission: null, // View-only, accessible to all
      adminOnly: false
    },
    { 
      path: '/results', 
      icon: BarChart3, 
      label: 'Results',
      permission: 'record_events', // Can view results if can record events
      adminOnly: false
    },
  ];

  // Filter menu items based on permissions
  const menuItems = allMenuItems.filter((item) => {
    // Dashboard is always accessible
    if (item.path === '/dashboard') {
      return true;
    }
    
    // Coach-only items
    if (item.coachOnly) {
      return isCoach();
    }
    
    // Admin-only items (like Roles)
    if (item.adminOnly) {
      return isAdmin();
    }
    
    // Items with specific permissions
    if (item.permission) {
      return hasPermission(item.permission) || isAdmin();
    }
    
    // Items without specific permission requirement (like Standings - view only)
    return true;
  });

  const isActive = (path) => {
    return location.pathname === path || location.pathname.startsWith(path + '/');
  };

  return (
    <aside
      className={`bg-gray-900 text-white transition-all duration-300 ${
        collapsed ? 'w-16' : 'w-64'
      } min-h-screen fixed left-0 top-0 z-40`}
    >
      <div className="flex flex-col h-full">
        {/* Logo/Header */}
        <div className="h-16 flex items-center justify-between px-4 border-b border-gray-800">
          {!collapsed && (
            <h1 className="text-xl font-bold text-white">Admin Panel</h1>
          )}
          <button
            onClick={handleToggle}
            className="p-2 rounded hover:bg-gray-800 transition-colors"
            title={collapsed ? 'Expand' : 'Collapse'}
          >
            {collapsed ? (
              <ChevronRight className="w-5 h-5" />
            ) : (
              <ChevronLeft className="w-5 h-5" />
            )}
          </button>
        </div>

        {/* Navigation */}
        <nav className="flex-1 overflow-y-auto py-4">
          <ul className="space-y-1 px-2">
            {menuItems.map((item) => {
              const Icon = item.icon;
              const active = isActive(item.path);
              return (
                <li key={item.path}>
                  <Link
                    to={item.path}
                    className={`flex items-center px-4 py-3 rounded-lg transition-colors ${
                      active
                        ? 'bg-primary-600 text-white'
                        : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                    }`}
                    title={collapsed ? item.label : ''}
                  >
                    <Icon className="w-5 h-5 flex-shrink-0" />
                    {!collapsed && <span className="ml-3">{item.label}</span>}
                  </Link>
                </li>
              );
            })}
          </ul>
        </nav>
      </div>
    </aside>
  );
}
