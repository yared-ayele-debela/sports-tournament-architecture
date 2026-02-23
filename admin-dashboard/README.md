# Admin Dashboard - Sports Tournament Management

A comprehensive admin dashboard web application for managing distributed sports tournament management system. Built with React 18, Vite, Tailwind CSS, and React Query for efficient data management.

## Features Implemented

### 🎯 Core Features
- **Authentication System**: Login, logout, token management with auto-logout
- **Role-Based Access Control**: Admin, Coach, and Referee dashboards
- **User Profile Management**: View and edit user information
- **Responsive Design**: Mobile-friendly interface with Tailwind CSS

### 📊 Management Modules
- **User Management**: CRUD operations for system users
- **Role & Permission Management**: Role-based access control
- **Tournament Management**: Create and manage tournaments
- **Sports Management**: Manage different sports types
- **Venue Management**: Add and manage tournament venues
- **Team Management**: Team registration and management
- **Player Management**: Player registration with jersey number validation
- **Match Management**: Match scheduling and management
- **Results Management**: Match results and tournament standings

### 🔍 Advanced Features
- **Search Functionality**: Search across tournaments, teams, players, venues, and users
- **Pagination**: Efficient data loading with pagination controls
- **Real-time Updates**: React Query for automatic data synchronization
- **Error Handling**: Comprehensive error handling and user feedback
- **Loading States**: Skeleton loaders and loading indicators
- **Form Validation**: Client-side validation with React Hook Form

## Prerequisites

- Node.js (v18 or higher)
- npm or yarn
- All microservices running (auth-service, tournament-service, team-service, match-service, results-service)

---

## 🚨 Security & Production Improvements

### 🔐 Security Configuration
- **Environment Variables**: All sensitive data moved to `.env` files
- **No Hardcoded Credentials**: All configuration externalized
- **Input Validation**: Client-side and server-side validation implemented
- **Secure Authentication**: JWT tokens with proper expiration
- **CORS Configuration**: Proper cross-origin resource sharing setup

### 📊 Monitoring & Debugging
- **Request Tracing**: Correlation IDs for end-to-end tracking
- **Error Logging**: Comprehensive error capture and reporting
- **Performance Monitoring**: Response time tracking for all API calls
- **Health Checks**: Service availability monitoring
- **Debug Mode**: Development debugging tools and logging

### 🛠️ Development Tools
- **Postman Collections**: 200+ automated tests for all APIs
- **Setup Scripts**: Automated environment setup and testing
- **cURL Scripts**: Manual testing capabilities
- **Documentation**: Comprehensive API and setup guides

---

## Setup Instructions

### 1. Install Dependencies

```bash
cd admin-dashboard
npm install
```

### 2. Configure Environment Variables

Create a `.env` file in the `admin-dashboard` directory (or copy from `.env.example`):

```env
VITE_AUTH_SERVICE_URL=http://localhost:8001/api
VITE_TOURNAMENT_SERVICE_URL=http://localhost:8002/api
VITE_TEAM_SERVICE_URL=http://localhost:8003/api
VITE_MATCH_SERVICE_URL=http://localhost:8004/api
VITE_RESULTS_SERVICE_URL=http://localhost:8005/api
```

### 3. Start Development Server

```bash
npm run dev
```

The application will be available at `http://localhost:3000`

## Testing the Application

### Step 1: Ensure All Services Are Running

Make sure all microservices are running:
- Auth Service: `http://localhost:8001`
- Tournament Service: `http://localhost:8002`
- Team Service: `http://localhost:8003`
- Match Service: `http://localhost:8004`
- Results Service: `http://localhost:8005`

### Step 2: Test Login

1. Open your browser and navigate to `http://localhost:3000`
2. You will be redirected to the login page
3. Use one of the test credentials:

**Test Users (from auth-service seeders):**
- **Admin User:**
  - Email: `admin1@test.com`
  - Password: `password`

- **Coach User:**
  - Email: `coach1@test.com`
  - Password: `password`

- **Referee User:**
  - Email: `referee1@test.com`
  - Password: `password`

### Step 3: Verify Authentication Flow

1. **Login Test:**
   - Enter email and password
   - Click "Sign In"
   - You should be redirected to the dashboard
   - Check browser console for any errors

2. **Token Storage Test:**
   - Open browser DevTools (F12)
   - Go to Application/Storage tab
   - Check Local Storage
   - You should see `access_token` and `user` stored

3. **Protected Route Test:**
   - Try accessing `http://localhost:3000/dashboard` directly
   - If not logged in, you should be redirected to login
   - After login, you should see the dashboard

4. **User Profile Test:**
   - After logging in, check the dashboard
   - You should see your user information:
     - Name
     - Email
     - Roles (if any)
     - Permissions (if any)

5. **Auto-logout Test:**
   - Manually delete the `access_token` from Local Storage
   - Try to navigate to a protected route
   - You should be redirected to login
   - Or make an API call that returns 401 - you should be auto-logged out

### Step 4: Test API Integration

Open browser DevTools Network tab and verify:

1. **Login Request:**
   - POST to `http://localhost:8001/api/auth/login`
   - Should return 200 with token and user data

2. **Get User Profile:**
   - GET to `http://localhost:8001/api/auth/me`
   - Should include `Authorization: Bearer {token}` header
   - Should return user with roles and permissions

3. **Logout Request:**
   - POST to `http://localhost:8001/api/auth/logout`
   - Should clear token from localStorage

## Troubleshooting

### Issue: Cannot connect to services

**Solution:** 
- Verify all microservices are running
- Check CORS settings in each service
- Verify the service URLs in `.env` are correct

### Issue: Login fails with 401

**Solution:**
- Verify the user exists in the database
- Check if the password is correct
- Ensure auth-service is running on port 8001

### Issue: Token not being sent in requests

**Solution:**
- Check browser Local Storage for `access_token`
- Verify the axios interceptor is working (check Network tab headers)
- Clear browser cache and try again

### Issue: CORS errors

**Solution:**
- Ensure all services have CORS enabled for `http://localhost:3000`
- Check service configuration files

## Development Commands

```bash
# Start development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Run linter
npm run lint
```

## Project Structure

```
admin-dashboard/
├── src/
│   ├── api/                    # API service files
│   │   ├── auth.js            # Authentication API
│   │   ├── tournaments.js     # Tournament API
│   │   ├── teams.js           # Team API
│   │   ├── players.js         # Player API
│   │   ├── matches.js         # Match API
│   │   ├── results.js         # Results API
│   │   ├── users.js           # User management API
│   │   ├── roles.js           # Role management API
│   │   ├── sports.js          # Sports API
│   │   └── venues.js          # Venue API
│   ├── components/            # Reusable components
│   │   ├── Layout/            # Layout components
│   │   ├── Forms/             # Form components
│   │   ├── Tables/            # Table components
│   │   └── UI/                # UI components
│   ├── context/               # React context
│   │   ├── AuthContext.jsx    # Authentication context
│   │   └── ThemeContext.jsx   # Theme context
│   ├── hooks/                 # Custom hooks
│   │   └── useAuth.js         # Authentication hook
│   ├── lib/                   # Utilities
│   │   ├── api.js             # API client configuration
│   │   └── queryClient.js     # React Query configuration
│   ├── pages/                 # Page components
│   │   ├── Dashboard.jsx      # Admin dashboard
│   │   ├── CoachDashboard.jsx # Coach dashboard
│   │   ├── RefereeDashboard.jsx # Referee dashboard
│   │   ├── Login.jsx          # Login page
│   │   ├── Profile.jsx        # User profile
│   │   ├── Users/             # User management pages
│   │   ├── Roles/             # Role management pages
│   │   ├── Tournaments/       # Tournament pages
│   │   ├── Sports/            # Sports management pages
│   │   ├── Venues/            # Venue management pages
│   │   ├── Teams/             # Team management pages
│   │   ├── Players/           # Player management pages
│   │   ├── Matches/           # Match management pages
│   │   └── Results/           # Results pages
│   ├── App.jsx                # Main app component
│   ├── main.jsx               # Entry point
│   └── index.css              # Global styles
├── .env                       # Environment variables
├── .env.example              # Environment variables template
├── package.json              # Dependencies and scripts
├── vite.config.js            # Vite configuration
├── tailwind.config.js        # Tailwind CSS configuration
├── postcss.config.js         # PostCSS configuration
└── README.md                 # This file
```

## Technology Stack

### Frontend
- **React 18**: Modern React with hooks and concurrent features
- **Vite**: Fast build tool and development server
- **React Router DOM**: Client-side routing
- **Tailwind CSS**: Utility-first CSS framework
- **Lucide React**: Modern icon library
- **React Hook Form**: Form handling with validation
- **Date-fns**: Date manipulation utilities

### State Management & Data Fetching
- **React Query (@tanstack/react-query)**: Server state management
- **React Context**: Authentication state management
- **Axios**: HTTP client with interceptors

### Development Tools
- **ESLint**: Code linting and formatting
- **PostCSS**: CSS processing
- **Vite Plugin React**: React support for Vite

## User Roles & Access Control

### Admin User
- Full access to all management modules
- User and role management
- System configuration
- Tournament oversight

### Coach User
- Manage assigned teams only
- Add/edit players for assigned teams
- View team statistics and match schedules
- Limited to team-specific data

### Referee User
- View assigned matches
- Update match results
- Manage match events
- Limited to match-specific functions

## API Integration

The dashboard integrates with the following microservices:
- **Auth Service** (port 8001): Authentication and user management
- **Tournament Service** (port 8002): Tournaments, sports, and venues
- **Team Service** (port 8003): Teams and players
- **Match Service** (port 8004): Match scheduling and events
- **Results Service** (port 8005): Match results and standings

## Development Features

### Search & Filtering
- Real-time search with debouncing (500ms)
- Multi-field search across all entities
- Filter by status, sport, tournament, etc.
- Clear filters functionality

### Pagination
- Server-side pagination for large datasets
- Smart pagination controls with validation
- Page state management with URL sync

### Performance Optimizations
- React Query caching and background updates
- Optimistic updates for better UX
- Skeleton loaders for perceived performance
- Code splitting with lazy loading

### Error Handling
- Global error boundaries
- API error handling with user-friendly messages
- Network error detection and retry logic
- Form validation with error display

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Android Chrome)

## Contributing

1. Follow the existing code style
2. Use Tailwind CSS for styling
3. Implement proper error handling
4. Add loading states for async operations
5. Test with different user roles

## Support

For issues or questions:
1. Check the browser console for errors
2. Verify all microservices are running
3. Ensure proper environment configuration
4. Refer to individual service README files
5. Check the main project documentation
