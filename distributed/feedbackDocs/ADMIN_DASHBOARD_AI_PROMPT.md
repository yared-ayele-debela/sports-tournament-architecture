# AI Prompt: Create Admin Dashboard for Sports Tournament Management System

## Overview
Create a comprehensive admin dashboard web application to manage a distributed sports tournament management system. The system consists of 5 microservices that need to be integrated into a single admin interface.

## System Architecture

### Services and Ports
- **Auth Service**: `http://localhost:8001/api/v1` (Authentication, Users, Roles, Permissions)
- **Tournament Service**: `http://localhost:8002/api` (Tournaments, Sports, Venues)
- **Team Service**: `http://localhost:8003/api` (Teams, Players)
- **Match Service**: `http://localhost:8004/api` (Matches, Match Events)
- **Results Service**: `http://localhost:8005/api` (Results, Standings, Statistics)

### Authentication
- Uses Laravel Passport OAuth2
- All protected endpoints require Bearer token in Authorization header
- Token format: `Authorization: Bearer {access_token}`
- Login endpoint returns: `{ access_token, token_type: "Bearer", user: {...} }`

## Required Features

### 1. Authentication & Login
- **Login Page**: Email/password login form
- **Token Management**: Store access token in localStorage/sessionStorage
- **Auto-logout**: Handle token expiration (401 responses)
- **Protected Routes**: Redirect to login if not authenticated
- **User Profile**: Display current user info from `/auth/me` endpoint

**Endpoints:**
- `POST /api/v1/auth/login` - Login (returns access_token)
- `POST /api/v1/auth/logout` - Logout
- `GET /api/v1/auth/me` - Get current user with roles/permissions

### 1.5. Profile Management (Logged-in User)
Allow the logged-in user to view and edit their own profile information.

**Endpoints:**
- `GET /api/v1/auth/me` - Get current user profile with roles/permissions
- `PUT /api/v1/admin/users/{id}` - Update user profile (use current user's ID)
- `PATCH /api/v1/admin/users/{id}` - Update user profile (use current user's ID)

**Features:**
- **Profile Page**: 
  - Display current user information (name, email, roles, permissions)
  - Show account creation date
  - Display email verification status
- **Edit Profile Form**:
  - Update name
  - Update email (with email uniqueness validation)
  - Change password (optional, with current password verification)
  - Display current roles (read-only, roles can only be changed by admins)
  - Display permissions (read-only)
- **Password Change Section**:
  - Current password field (required for password change)
  - New password field
  - Confirm new password field
  - Password strength indicator
  - Separate "Change Password" button/section
- **Profile Picture/Avatar** (optional):
  - Upload profile picture
  - Display current avatar
  - Remove avatar option
- **Access from**: 
  - User profile dropdown in top bar
  - Direct route: `/profile` or `/settings/profile`
  - Accessible to all logged-in users

**Request Body (Update Profile):**
```json
{
  "name": "John Doe Updated",
  "email": "john.updated@example.com"
}
```

**Request Body (Change Password):**
```json
{
  "current_password": "oldpassword123",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Request Body (Update Profile with Password):**
```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password": "newpassword123"
}
```

**UI Requirements:**
- Profile page accessible from user dropdown menu
- Two-column layout or tabs:
  - **Profile Information** tab: Name, email, account info
  - **Change Password** tab: Password change form
- Success notification after profile update
- Error handling for:
  - Email already taken
  - Invalid current password
  - Password validation errors
- Form validation before submission
- Loading states during API calls

### 2. User Management
Full CRUD operations for user management.

**Endpoints:**
- `GET /api/v1/admin/users` - List users (query: `?per_page=15&search=term`)
- `GET /api/v1/admin/users/{id}` - Get user details
- `POST /api/v1/admin/users` - Create user
- `PUT /api/v1/admin/users/{id}` - Update user
- `DELETE /api/v1/admin/users/{id}` - Delete user

**Features:**
- User list with pagination and search
- Create/Edit user form (name, email, password, role_ids)
- Assign roles to users
- View user details with roles and permissions
- Delete confirmation dialog

**Request Body (Create/Update):**
```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password": "password123",
  "role_ids": [1, 2]
}
```

### 3. Role Management
Full CRUD operations for role management.

**Endpoints:**
- `GET /api/v1/admin/roles` - List roles (query: `?per_page=15&search=term`)
- `GET /api/v1/admin/roles/{id}` - Get role details
- `POST /api/v1/admin/roles` - Create role
- `PUT /api/v1/admin/roles/{id}` - Update role
- `DELETE /api/v1/admin/roles/{id}` - Delete role

**Features:**
- Role list with pagination and search
- Create/Edit role form (name, description, permission_ids)
- Assign permissions to roles
- View role details with permissions and assigned users
- Delete confirmation dialog

**Request Body (Create/Update):**
```json
{
  "name": "admin",
  "description": "Administrator role with full access",
  "permission_ids": [1, 2, 3]
}
```

### 4. Permission Management
Full CRUD operations for permission management.

**Endpoints:**
- `GET /api/v1/admin/permissions` - List permissions (query: `?per_page=15&search=term`)
- `GET /api/v1/admin/permissions/{id}` - Get permission details
- `POST /api/v1/admin/permissions` - Create permission
- `PUT /api/v1/admin/permissions/{id}` - Update permission
- `DELETE /api/v1/admin/permissions/{id}` - Delete permission

**Features:**
- Permission list with pagination and search
- Create/Edit permission form (name, description)
- View permission details with assigned roles
- Delete confirmation dialog

**Request Body (Create/Update):**
```json
{
  "name": "users.create",
  "description": "Permission to create users"
}
```

### 5. Tournament Management
Full CRUD operations for tournament management.

**Endpoints:**
- `GET /api/tournaments` - List tournaments (query: `?status=ongoing&sport_id=1&per_page=20`)
- `GET /api/tournaments/{id}` - Get tournament details
- `POST /api/tournaments` - Create tournament
- `PUT /api/tournaments/{id}` - Update tournament
- `DELETE /api/tournaments/{id}` - Delete tournament
- `PATCH /api/tournaments/{id}/status` - Update tournament status
- `GET /api/tournaments/{id}/matches` - Get tournament matches
- `GET /api/tournaments/{id}/teams` - Get tournament teams
- `GET /api/tournaments/{id}/standings` - Get tournament standings
- `GET /api/tournaments/{id}/statistics` - Get tournament statistics

**Features:**
- Tournament list with filters (status, sport_id) and pagination
- Create/Edit tournament form
- Tournament details view with:
  - Basic info (name, sport, dates, status)
  - Associated matches
  - Participating teams
  - Standings table
  - Statistics
- Status management (draft, ongoing, completed, cancelled)
- Delete confirmation dialog

**Request Body (Create/Update):**
```json
{
  "name": "World Cup 2026",
  "sport_id": 1,
  "start_date": "2026-06-01",
  "end_date": "2026-07-15",
  "description": "International football tournament"
}
```

### 6. Sports Management
Full CRUD operations for sports management.

**Endpoints:**
- `GET /api/sports` - List sports
- `GET /api/sports/{id}` - Get sport details
- `POST /api/sports` - Create sport
- `PUT /api/sports/{id}` - Update sport
- `DELETE /api/sports/{id}` - Delete sport

**Features:**
- Sports list
- Create/Edit sport form (name, description)
- Delete confirmation dialog

### 7. Team Management
Full CRUD operations for team management.

**Endpoints:**
- `GET /api/tournaments/{tournamentId}/teams` - List teams in tournament
- `GET /api/teams/{id}` - Get team details
- `POST /api/teams` - Create team
- `PUT /api/teams/{id}` - Update team
- `DELETE /api/teams/{id}` - Delete team
- `GET /api/teams/{id}/players` - Get team players

**Features:**
- Team list filtered by tournament
- Create/Edit team form (name, tournament_id, logo, etc.)
- Team details view with:
  - Basic info
  - Players list
  - Match statistics
- Delete confirmation dialog

**Request Body (Create/Update):**
```json
{
  "name": "Team Alpha",
  "tournament_id": 1,
  "logo": "logo.png",
  "founded_year": 2020
}
```

### 8. Player Management
Full CRUD operations for player management.

**Endpoints:**
- `GET /api/players` - List players (query: `?team_id=1&per_page=20`)
- `GET /api/players/{id}` - Get player details
- `POST /api/players` - Create player
- `PUT /api/players/{id}` - Update player
- `DELETE /api/players/{id}` - Delete player

**Features:**
- Player list with filters (team_id) and pagination
- Create/Edit player form (name, team_id, position, jersey_number, etc.)
- Player details view
- Delete confirmation dialog

**Request Body (Create/Update):**
```json
{
  "name": "John Player",
  "team_id": 1,
  "position": "Forward",
  "jersey_number": 10,
  "date_of_birth": "1995-01-15"
}
```

### 9. Match Management
Full CRUD operations for match management.

**Endpoints:**
- `GET /api/matches` - List matches (query: `?tournament_id=1&status=upcoming&per_page=20`)
- `GET /api/matches/{id}` - Get match details
- `POST /api/matches` - Create match
- `PUT /api/matches/{id}` - Update match
- `DELETE /api/matches/{id}` - Delete match
- `PATCH /api/matches/{id}/status` - Update match status
- `POST /api/tournaments/{tournamentId}/generate-schedule` - Generate tournament schedule
- `GET /api/matches/{matchId}/events` - Get match events
- `POST /api/matches/{matchId}/events` - Create match event

**Features:**
- Match list with filters (tournament_id, status, team_id) and pagination
- Create/Edit match form:
  - Tournament selection
  - Home team and away team selection
  - Venue selection
  - Match date and time
  - Round number
- Match details view with:
  - Basic info
  - Teams and scores
  - Match events timeline
  - Match report
- Status management (scheduled, live, completed, cancelled)
- Schedule generator for tournaments
- Delete confirmation dialog

**Request Body (Create/Update):**
```json
{
  "tournament_id": 1,
  "venue_id": 1,
  "home_team_id": 1,
  "away_team_id": 2,
  "referee_id": 1,
  "match_date": "2026-06-15T15:00:00Z",
  "round_number": 1
}
```

### 10. Results & Standings Management
View and manage tournament results and standings.

**Endpoints:**
- `GET /api/tournaments/{tournamentId}/standings` - Get tournament standings
- `POST /api/standings/recalculate/{tournamentId}` - Recalculate standings
- `GET /api/tournaments/{tournamentId}/results` - Get tournament results
- `GET /api/results/{id}` - Get result details
- `POST /api/matches/{matchId}/finalize` - Finalize match result
- `GET /api/teams/{teamId}/statistics` - Get team statistics

**Features:**
- Standings table view:
  - Team position
  - Points, wins, losses, draws
  - Goals for/against, goal difference
  - Sortable columns
- Results list with filters
- Match result finalization form:
  - Home team score
  - Away team score
  - Match events
  - Finalize button
- Recalculate standings button
- Team statistics view

**Request Body (Finalize Match):**
```json
{
  "home_score": 2,
  "away_score": 1,
  "match_events": [
    {
      "type": "goal",
      "player_id": 1,
      "minute": 15
    }
  ]
}
```

## UI/UX Requirements

### Design
- **￼Modern, clean interface** with professional design
- **Responsive layout** (mobile, tablet, desktop)
- **Dark/Light theme toggle** (optional but recommended)
- **Consistent color scheme** throughout the application
- **Loading states** for all async operations
- **Error handling** with user-friendly error messages
- **Success notifications** for completed actions

### Navigation
- **Sidebar navigation** with collapsible menu
- **Breadcrumbs** for deep navigation
- **Top bar** with:
  - User profile dropdown (with "Edit Profile" link)
  - ￼Logout button
  - Notifications (optional)
- **Main content area** with proper spacing
- **Profile page** accessible from user dropdown menu

### Components
- **Data tables** with:
  - Pagination
  - Search/filter functionality
  - Sortable columns
  - Row actions (edit, delete)
  - Bulk actions (optional)
- **Forms** with:
  - Validation
  - Error display
  - Required field indicators
  - Select dropdowns for relationships
  - Date/time pickers
- **Modals** for:
  - Create/Edit forms
  - Delete confirmations
  - Detail views
- **Cards** for dashboard statistics
- **Charts/Graphs** for statistics (optional)

### Dashboard Home Page
- **Statistics cards**:
  - Total users
  - Total tournaments
  - Total teams
  - Total matches
  - Active tournaments
  - Upcoming matches
- **Recent activity** feed
- **Quick actions** buttons
- **Charts** for tournament statistics

## Technical Requirements

### Technology Stack (Recommended)
- **Frontend Framework**: React.js with TypeScript OR Vue.js OR Next.js
- **UI Library**: 
  - Material-UI (MUI) OR
  - Ant Design OR
  - Tailwind CSS + Headless UI OR
  - Shadcn/ui
- **State Management**: 
  - Redux Toolkit OR
  - Zustand OR
  - React Query / TanStack Query
- **HTTP Client**: Axios with interceptors
- **Routing**: React Router OR Vue Router
- **Form Handling**: React Hook Form OR Formik
- **Date Handling**: date-fns OR dayjs
- **Build Tool**: Vite OR Create React App

### API Integration
- **Base URL Configuration**: Environment variables for each service
- **Axios Instance**: Configured with base URLs and interceptors
- **Token Management**: 
  - Store token in localStorage
  - Add to Authorization header automatically
  - Refresh token logic (if implemented)
  - Handle 401 errors (redirect to login)
- **Error Handling**: 
  - Global error handler
  - Display user-friendly error messages
  - Handle network errors
- **Loading States**: Show loading indicators during API calls

### Code Structure
```
src/
├── api/
│   ├── auth.js          # Auth service API calls (login, logout, me)
│   ├── profile.js       # Profile management API calls
│   ├── users.js         # User management API calls
│   ├── roles.js         # Role management API calls
│   ├── permissions.js   # Permission management API calls
│   ├── tournaments.js   # Tournament service API calls
│   ├── teams.js         # Team service API calls
│   ├── players.js       # Player management API calls
│   ├── matches.js       # Match service API calls
│   └── results.js       # Results service API calls
├── components/
│   ├── common/          # Reusable components
│   ├── layout/          # Layout components
│   └── forms/           # Form components
├── pages/
│   ├── Login.js
│   ├── Dashboard.js
│   ├── Profile.js          # User profile page
│   ├── Users/
│   ├── Roles/
│   ├── Permissions/
│   ├── Tournaments/
│   ├── Teams/
│   ├── Players/
│   ├── Matches/
│   └── Results/
├── hooks/               # Custom React hooks
├── store/               # State management
├── utils/               # Utility functions
└── App.js
```

### Environment Variables
```env
VITE_AUTH_SERVICE_URL=http://localhost:8001/api/v1
VITE_TOURNAMENT_SERVICE_URL=http://localhost:8002/api
VITE_TEAM_SERVICE_URL=http://localhost:8003/api
VITE_MATCH_SERVICE_URL=http://localhost:8004/api
VITE_RESULTS_SERVICE_URL=http://localhost:8005/api
```

## Response Format
All API responses follow this format:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "timestamp": "2026-02-01T15:00:00Z"
}
```

Error responses:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Error message"]
  }
}
```

## Pagination Format
Paginated responses:
```json
{
  "success": true,
  "data": {
    "data": [...],
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100,
    "from": 1,
    "to": 20
  }
}
```

## Implementation Checklist

### Phase 1: Setup & Authentication
- [ ] Project setup with chosen framework
- [ ] Install dependencies
- [ ] Configure routing
- [ ] Create API service layer
- [ ] Implement login page
- [ ] Implement token storage and management
- [ ] Create protected route wrapper
- [ ] Implement logout functionality
- [ ] Profile page with view and edit functionality
- [ ] Password change functionality
- [ ] Profile update API integration

### Phase 2: User Management
- [ ] Users list page with pagination
- [ ] User create/edit form
- [ ] User details view
- [ ] User delete functionality
- [ ] Role assignment in user form

### Phase 3: Role & Permission Management
- [ ] Roles list page
- [ ] Role create/edit form
- [ ] Role details view
- [ ] Permissions list page
- [ ] Permission create/edit form
- [ ] Permission assignment in role form

### Phase 4: Tournament Management
- [ ] Tournaments list page with filters
- [ ] Tournament create/edit form
- [ ] Tournament details view
- [ ] Tournament status management
- [ ] Sports management pages

### Phase 5: Team & Player Management
- [ ] Teams list page
- [ ] Team create/edit form
- [ ] Team details view
- [ ] Players list page
- [ ] Player create/edit form
- [ ] Player details view

### Phase 6: Match Management
- [ ] Matches list page with filters
- [ ] Match create/edit form
- [ ] Match details view
- [ ] Match status management
- [ ] Schedule generator
- [ ] Match events management

### Phase 7: Results & Standings
- [ ] Standings table view
- [ ] Results list page
- [ ] Match result finalization form
- [ ] Recalculate standings functionality
- [ ] Team statistics view

### Phase 8: Dashboard & Polish
- [ ] Dashboard home page with statistics
- [ ] Navigation sidebar
- [ ] Error handling improvements
- [ ] Loading states
- [ ] Responsive design
- [ ] Final testing

## Additional Notes

1. **CORS**: Ensure all services have CORS enabled for the admin dashboard origin
2. **Error Handling**: Handle network errors, timeout errors, and API errors gracefully
3. **Validation**: Implement client-side validation before API calls
4. **Optimistic Updates**: Consider optimistic UI updates for better UX
5. **Caching**: Consider caching frequently accessed data (tournaments, sports, etc.)
6. **Real-time Updates**: Optional - implement WebSocket for real-time match updates
7. **Export**: Optional - add export functionality (CSV, PDF) for data tables
8. **Search**: Implement global search across all entities (optional)

## Deliverables
1. Complete admin dashboard application
2. All CRUD operations for all entities
3. Authentication and authorization
4. Responsive design
5. Error handling and loading states
6. Clean, maintainable code
7. README with setup instructions
8. Environment configuration file

---

**Note**: This prompt should be used with an AI code generation tool (like ChatGPT, Claude, GitHub Copilot, etc.) to generate the complete admin dashboard application. Adjust the technology stack recommendations based on your preferences and requirements.
