# AI Prompt: Create Public View for Sports Tournament Management System

## Overview
Create a comprehensive public-facing web application using React to display sports tournament information, matches, teams, players, standings, and statistics. The application should be accessible to all users without authentication and provide an engaging, modern user experience.

## System Architecture

### Services and Ports
- **Tournament Service**: `http://localhost:8002/api/public` (Tournaments, Sports, Venues)
- **Team Service**: `http://localhost:8003/api/public` (Teams, Players)
- **Match Service**: `http://localhost:8004/api/public` (Matches, Match Events)
- **Results Service**: `http://localhost:8005/api/public` (Standings, Statistics, Top Scorers)

### Authentication
- **No authentication required** - All public endpoints are accessible without tokens
- All endpoints are rate-limited (100 requests/minute per IP)
- CORS enabled for cross-origin access
- Heavy caching implemented on backend (5-10 minutes TTL)

## Required Features

### 1. Home Page / Landing Page
**Purpose**: Main entry point showcasing featured content

**Features**:
- **Hero Section**: 
  - Large banner with featured tournament
  - Call-to-action buttons
  - Current date/time display
- **Featured Tournaments Section**:
  - Display featured/ongoing tournaments
  - Tournament cards with:
    - Tournament name and logo
    - Sport type
    - Start/end dates
    - Status badge (ongoing, upcoming, completed)
    - Number of teams
    - Quick stats (matches played, upcoming matches)
  - "View All Tournaments" link
- **Live Matches Section**:
  - Display currently live matches
  - Match cards showing:
    - Home team vs Away team
    - Current score
    - Match time/status
    - Live indicator
  - "View All Live Matches" link
- **Upcoming Matches Section**:
  - Today's upcoming matches
  - Tomorrow's matches preview
  - Match cards with:
    - Teams
    - Scheduled time
    - Venue
    - "Set Reminder" button (optional)
- **Quick Stats Section**:
  - Total tournaments
  - Total teams
  - Total matches
  - Active tournaments count
- **Search Bar**:
  - Global search across tournaments, teams, players, matches
  - Search suggestions/autocomplete
  - Quick filters (tournaments, teams, matches)

**Endpoints Used**:
- `GET /api/public/tournaments/featured` - Featured tournaments
- `GET /api/public/tournaments/upcoming` - Upcoming tournaments
- `GET /api/public/matches/live` - Live matches
- `GET /api/public/matches/today` - Today's matches
- `GET /api/public/matches/upcoming` - Upcoming matches
- `GET /api/public/search` - Global search

### 2. Tournaments Listing Page
**Purpose**: Browse all available tournaments

**Features**:
- **Filter Section**:
  - Filter by sport (dropdown)
  - Filter by status (all, ongoing, upcoming, completed)
  - Filter by date range (optional)
  - Search by tournament name
- **Sort Options**:
  - Sort by: Name, Start Date, End Date, Status
  - Sort order: Ascending, Descending
- **Tournament Grid/List View**:
  - Toggle between grid and list view
  - Tournament cards showing:
    - Tournament name and logo
    - Sport type with icon
    - Start and end dates
    - Status badge (color-coded)
    - Number of teams
    - Number of matches
    - Tournament description (truncated)
    - "View Details" button
- **Pagination**:
  - Page numbers
  - Items per page selector (12, 24, 48)
  - Previous/Next buttons
  - Showing X-Y of Z results

**Endpoints Used**:
- `GET /api/public/tournaments` - List tournaments (query: `?status=ongoing&sport_id=1&limit=20&page=1`)
- `GET /api/public/sports` - List sports for filter dropdown

### 3. Tournament Details Page
**Purpose**: Comprehensive tournament information

**Features**:
- **Tournament Header**:
  - Tournament name (large)
  - Sport type badge
  - Status badge (ongoing, upcoming, completed)
  - Start and end dates
  - Tournament description
  - Tournament logo/banner image
- **Tab Navigation**:
  - Overview (default)
  - Standings
  - Matches
  - Teams
  - Statistics
- **Overview Tab**:
  - Tournament summary
  - Key dates and milestones
  - Format information (league, knockout, etc.)
  - Venue information
  - Quick stats:
    - Total teams
    - Total matches
    - Matches completed
    - Matches remaining
- **Standings Tab**:
  - Standings table with:
    - Position
    - Team name and logo
    - Played (P)
    - Won (W)
    - Drawn (D)
    - Lost (L)
    - Goals For (GF)
    - Goals Against (GA)
    - Goal Difference (GD)
    - Points
    - Form (last 5 matches: W/L/D indicators)
  - Sortable columns
  - Group stage support (if applicable)
  - "Last Updated" timestamp
- **Matches Tab**:
  - Match list with filters:
    - Filter by round/group
    - Filter by status (all, scheduled, live, completed)
    - Filter by date
  - Match cards showing:
    - Home team vs Away team
    - Date and time
    - Venue
    - Score (if completed)
    - Status badge
    - "View Details" link
  - Group by round/date
  - Pagination
- **Teams Tab**:
  - Team grid/list view
  - Team cards showing:
    - Team name and logo
    - Number of players
    - Match statistics (W/L/D)
    - "View Team" link
  - Search teams
  - Pagination
- **Statistics Tab**:
  - Tournament statistics:
    - Total goals scored
    - Average goals per match
    - Top scoring teams
    - Most goals in a match
    - Clean sheets
    - Red/Yellow cards
  - Charts/visualizations:
    - Goals distribution chart
    - Match results distribution (pie chart)
    - Timeline of matches

**Endpoints Used**:
- `GET /api/public/tournaments/{id}` - Tournament details
- `GET /api/public/tournaments/{tournamentId}/standings` - Tournament standings
- `GET /api/public/tournaments/{tournamentId}/matches` - Tournament matches
- `GET /api/public/tournaments/{tournamentId}/teams` - Tournament teams
- `GET /api/public/tournaments/{tournamentId}/statistics` - Tournament statistics

### 4. Matches Listing Page
**Purpose**: Browse all matches

**Features**:
- **Filter Section**:
  - Filter by tournament
  - Filter by status (all, scheduled, live, completed)
  - Filter by date range
  - Filter by team
  - Search matches
- **View Options**:
  - Calendar view (matches grouped by date)
  - List view (all matches in chronological order)
  - Toggle between views
- **Match Cards**:
  - Home team vs Away team (with logos)
  - Match date and time
  - Venue name
  - Status badge
  - Score (if completed)
  - Live indicator (if live)
  - "View Details" button
- **Grouping**:
  - Group by date (in calendar view)
  - Group by tournament (optional)
  - Group by status (optional)
- **Pagination**:
  - Page numbers
  - Items per page selector

**Endpoints Used**:
- `GET /api/public/matches` - List matches (query: `?tournament_id=1&status=live&date=2026-02-01`)
- `GET /api/public/matches/today` - Today's matches
- `GET /api/public/matches/upcoming` - Upcoming matches
- `GET /api/public/matches/live` - Live matches

### 5. Match Details Page
**Purpose**: Detailed match information

**Features**:
- **Match Header**:
  - Home team vs Away team (large display)
  - Team logos
  - Match date and time
  - Venue name and location
  - Status badge (scheduled, live, completed, cancelled)
  - Round/Group information
- **Score Display**:
  - Large score display (if completed or live)
  - Home score vs Away score
  - Match status (FT, HT, Live, etc.)
- **Match Timeline** (if completed or live):
  - Chronological list of match events:
    - Goals (player name, minute, assist)
    - Yellow cards (player name, minute)
    - Red cards (player name, minute)
    - Substitutions (player in/out, minute)
    - Other events
  - Visual timeline with icons
  - Filter events by type
- **Match Statistics**:
  - Possession percentage
  - Shots (total, on target)
  - Corners
  - Fouls
  - Yellow/Red cards
  - Offsides
  - Pass accuracy (if available)
  - Visual comparison charts (bar charts)
- **Lineups** (if available):
  - Home team starting XI
  - Away team starting XI
  - Substitutes
  - Formation display
- **Match Report** (if available):
  - Written match summary
  - Key moments
  - Player ratings (if available)
- **Related Matches**:
  - Other matches in same tournament
  - Next/Previous matches for teams

**Endpoints Used**:
- `GET /api/public/matches/{id}` - Match details
- `GET /api/public/matches/{id}/events` - Match events

### 6. Teams Listing Page
**Purpose**: Browse all teams

**Features**:
- **Filter Section**:
  - Filter by tournament
  - Filter by sport
  - Search by team name
- **Team Grid/List View**:
  - Toggle between grid and list view
  - Team cards showing:
    - Team name and logo
    - Tournament name
    - Number of players
    - Match statistics (W/L/D)
    - Founded year (if available)
    - "View Team" button
- **Sort Options**:
  - Sort by: Name, Wins, Losses, Tournament
- **Pagination**

**Endpoints Used**:
- `GET /api/public/tournaments/{tournamentId}/teams` - Tournament teams
- `GET /api/public/teams/{id}` - Team details (for preview)

### 7. Team Details Page
**Purpose**: Comprehensive team information

**Features**:
- **Team Header**:
  - Team name (large)
  - Team logo
  - Tournament name
  - Founded year (if available)
  - Team description (if available)
- **Tab Navigation**:
  - Overview (default)
  - Players
  - Matches
  - Statistics
- **Overview Tab**:
  - Team summary
  - Quick statistics:
    - Total matches
    - Wins, Losses, Draws
    - Goals scored/conceded
    - Win percentage
  - Current tournament position
  - Form (last 5 matches)
- **Players Tab**:
  - Player list/grid
  - Player cards showing:
    - Player name
    - Position
    - Jersey number
    - Photo (if available)
  - Filter by position
  - Search players
  - Sort by: Name, Position, Jersey Number
  - Pagination
- **Matches Tab**:
  - Match list with filters:
    - Filter by status (all, completed, upcoming)
    - Filter by date
  - Match cards showing:
    - Opponent team
    - Date and time
    - Venue
    - Score (if completed)
    - Result (Win/Loss/Draw)
    - Home/Away indicator
  - Group by date
  - Pagination
- **Statistics Tab**:
  - Team statistics:
    - Goals scored/conceded
    - Average goals per match
    - Clean sheets
    - Top scorers
    - Most appearances
  - Charts:
    - Goals scored/conceded over time
    - Match results distribution
    - Performance by position

**Endpoints Used**:
- `GET /api/public/teams/{id}` - Team details
- `GET /api/public/teams/{id}/players` - Team players
- `GET /api/public/teams/{id}/matches` - Team matches

### 8. Standings Page
**Purpose**: View tournament standings

**Features**:
- **Tournament Selector**:
  - Dropdown to select tournament
  - Default to most active tournament
- **Standings Table**:
  - Full standings table (same as in Tournament Details)
  - Sortable columns
  - Highlight current team (if viewing from team page)
  - Expandable rows for team details (optional)
- **Group Stage Support**:
  - Tabs for different groups (if applicable)
  - Group standings tables
- **Statistics Summary**:
  - Total matches played
  - Total goals scored
  - Average goals per match
- **Export Options** (optional):
  - Export to CSV
  - Print standings

**Endpoints Used**:
- `GET /api/public/tournaments/{tournamentId}/standings` - Tournament standings

### 9. Statistics Page
**Purpose**: View comprehensive statistics

**Features**:
- **Tournament Selector**:
  - Dropdown to select tournament
- **Statistics Sections**:
  - **Team Statistics**:
    - Top teams by points
    - Top teams by goals scored
    - Best defense (fewest goals conceded)
    - Best attack (most goals scored)
  - **Player Statistics**:
    - Top scorers (goals)
    - Most assists (if available)
    - Most appearances
    - Most yellow/red cards
  - **Match Statistics**:
    - Highest scoring matches
    - Most goals in a match
    - Average goals per match
    - Match results distribution
  - **Charts and Visualizations**:
    - Bar charts for top scorers
    - Pie charts for match results
    - Line charts for goals over time
    - Heat maps (if applicable)
- **Filters**:
  - Filter by date range
  - Filter by team
  - Filter by player

**Endpoints Used**:
- `GET /api/public/tournaments/{tournamentId}/statistics` - Tournament statistics
- `GET /api/public/tournaments/{tournamentId}/top-scorers` - Top scorers

### 10. Search Page
**Purpose**: Global search across all entities

**Features**:
- **Search Bar**:
  - Large, prominent search input
  - Search suggestions/autocomplete
  - Recent searches (localStorage)
  - Popular searches
- **Search Results**:
  - Tabs for result types:
    - All (default)
    - Tournaments
    - Teams
    - Players
    - Matches
  - Result cards for each type:
    - **Tournaments**: Name, sport, dates, status
    - **Teams**: Name, logo, tournament
    - **Players**: Name, team, position
    - **Matches**: Teams, date, score
  - "No results" message
  - "View all results" links
- **Filters**:
  - Filter by type
  - Filter by date (for matches)
  - Filter by tournament
- **Pagination**

**Endpoints Used**:
- `GET /api/public/search` - Global search (query: `?q=search_term&type=tournaments&limit=20`)

### 11. Sports Listing Page (Optional)
**Purpose**: Browse sports and their tournaments

**Features**:
- **Sports Grid**:
  - Sport cards showing:
    - Sport name
    - Sport icon/image
    - Number of tournaments
    - Number of active tournaments
    - "View Tournaments" button
- **Filter by sport** (if on tournaments page)

**Endpoints Used**:
- `GET /api/public/sports` - List sports

## UI/UX Requirements

### Design
- **Modern, clean interface** with professional design
- **Responsive layout** (mobile-first approach):
  - Mobile: Single column, stacked cards
  - Tablet: 2-column grid
  - Desktop: 3-4 column grid
- **Dark/Light theme toggle** (optional but recommended)
- **Consistent color scheme** throughout:
  - Primary color for CTAs
  - Status colors (green for live, blue for scheduled, gray for completed)
  - Team colors (if available)
- **Loading states** for all async operations:
  - Skeleton loaders
  - Spinner for buttons
  - Progress bars for data fetching
- **Error handling** with user-friendly error messages:
  - Network errors
  - 404 errors
  - Rate limit errors
  - Service unavailable errors
- **Success notifications** for user actions (if any)
- **Smooth animations and transitions**:
  - Page transitions
  - Card hover effects
  - Loading animations
  - Scroll animations

### Navigation
- **Top Navigation Bar**:
  - Logo/Brand name (links to home)
  - Main navigation links:
    - Home
    - Tournaments
    - Matches
    - Teams
    - Standings
    - Statistics
  - Search icon/bar
  - Theme toggle (optional)
  - Mobile menu (hamburger icon)
- **Breadcrumbs** for deep navigation:
  - Home > Tournaments > Tournament Name > Match Details
- **Footer**:
  - Links to main pages
  - Social media links (optional)
  - Copyright information
  - API documentation link (optional)
- **Back to Top** button (for long pages)

### Components

#### Data Display Components
- **Tournament Card**:
  - Tournament logo/image
  - Tournament name
  - Sport badge
  - Status badge
  - Dates
  - Quick stats
  - Hover effects
- **Match Card**:
  - Team logos
  - Team names
  - Score (if available)
  - Date/time
  - Venue
  - Status badge
  - Live indicator
- **Team Card**:
  - Team logo
  - Team name
  - Tournament name
  - Statistics
- **Player Card**:
  - Player photo (if available)
  - Player name
  - Position
  - Jersey number
  - Team name
- **Standings Table**:
  - Sortable columns
  - Responsive design (horizontal scroll on mobile)
  - Highlighted rows
  - Form indicators (W/L/D)

#### Form Components
- **Search Bar**:
  - Input with icon
  - Autocomplete dropdown
  - Clear button
  - Search button
- **Filters Panel**:
  - Collapsible filters
  - Checkboxes/Radio buttons
  - Date pickers
  - Dropdown selects
  - Apply/Reset buttons
- **Pagination**:
  - Page numbers
  - Previous/Next buttons
  - Items per page selector
  - Jump to page (optional)

#### Interactive Components
- **Tabs**:
  - Tab navigation
  - Active tab indicator
  - Smooth transitions
- **Modals**:
  - Image lightbox
  - Confirmation dialogs
  - Detail views
- **Tooltips**:
  - Hover information
  - Help text
- **Badges**:
  - Status badges
  - Count badges
  - Category badges

#### Charts/Visualizations
- **Bar Charts**: Top scorers, team stats
- **Pie Charts**: Match results distribution
- **Line Charts**: Goals over time, performance trends
- **Tables**: Standings, statistics

### Responsive Breakpoints
- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

### Performance
- **Lazy loading** for images
- **Code splitting** for routes
- **Memoization** for expensive computations
- **Debouncing** for search inputs
- **Virtual scrolling** for long lists (optional)
- **Image optimization** (WebP format, responsive images)

## Technical Requirements

### Technology Stack (Recommended)
- **Frontend Framework**: React.js (latest version)
- **Build Tool**: Vite (recommended) OR Create React App
- **UI Library**: 
  - Tailwind CSS (recommended) OR
  - Material-UI (MUI) OR
  - Ant Design OR
  - Chakra UI
- **State Management**: 
  - React Context API (for simple state) OR
  - Zustand (lightweight) OR
  - Redux Toolkit (for complex state)
- **Data Fetching**: 
  - React Query / TanStack Query (recommended) OR
  - SWR OR
  - Axios with custom hooks
- **HTTP Client**: Axios
- **Routing**: React Router v6
- **Form Handling**: React Hook Form (if needed)
- **Date Handling**: date-fns OR dayjs
- **Charts**: 
  - Recharts (recommended) OR
  - Chart.js with react-chartjs-2 OR
  - Victory
- **Icons**: 
  - React Icons OR
  - Heroicons OR
  - Font Awesome
- **Animations**: 
  - Framer Motion (recommended) OR
  - React Spring

### API Integration
- **Base URL Configuration**: Environment variables for each service
- **Axios Instance**: Configured with base URLs
- **Error Handling**: 
  - Global error handler
  - Display user-friendly error messages
  - Handle network errors
  - Handle rate limit errors (429)
  - Handle service unavailable (503)
- **Loading States**: Show loading indicators during API calls
- **Caching**: 
  - React Query caching (automatic)
  - LocalStorage for user preferences
  - SessionStorage for temporary data
- **Retry Logic**: Automatic retry for failed requests (with exponential backoff)

### Code Structure
```
public-view/
├── public/
│   ├── index.html
│   └── favicon.ico
├── src/
│   ├── api/
│   │   ├── tournaments.js      # Tournament service API calls
│   │   ├── teams.js            # Team service API calls
│   │   ├── matches.js          # Match service API calls
│   │   ├── results.js          # Results service API calls
│   │   ├── search.js           # Search API calls
│   │   └── axios.js            # Axios instance configuration
│   ├── components/
│   │   ├── common/             # Reusable components
│   │   │   ├── Button.jsx
│   │   │   ├── Card.jsx
│   │   │   ├── Badge.jsx
│   │   │   ├── Loading.jsx
│   │   │   ├── ErrorMessage.jsx
│   │   │   ├── Pagination.jsx
│   │   │   └── SearchBar.jsx
│   │   ├── layout/             # Layout components
│   │   │   ├── Header.jsx
│   │   │   ├── Footer.jsx
│   │   │   ├── Navigation.jsx
│   │   │   └── Breadcrumbs.jsx
│   │   ├── tournament/         # Tournament-specific components
│   │   │   ├── TournamentCard.jsx
│   │   │   ├── TournamentHeader.jsx
│   │   │   └── TournamentTabs.jsx
│   │   ├── match/             # Match-specific components
│   │   │   ├── MatchCard.jsx
│   │   │   ├── MatchHeader.jsx
│   │   │   ├── MatchTimeline.jsx
│   │   │   └── MatchStatistics.jsx
│   │   ├── team/              # Team-specific components
│   │   │   ├── TeamCard.jsx
│   │   │   ├── TeamHeader.jsx
│   │   │   └── TeamTabs.jsx
│   │   ├── standings/         # Standings components
│   │   │   ├── StandingsTable.jsx
│   │   │   └── StandingsRow.jsx
│   │   └── charts/            # Chart components
│   │       ├── BarChart.jsx
│   │       ├── PieChart.jsx
│   │       └── LineChart.jsx
│   ├── pages/
│   │   ├── Home.jsx
│   │   ├── Tournaments/
│   │   │   ├── TournamentsList.jsx
│   │   │   └── TournamentDetails.jsx
│   │   ├── Matches/
│   │   │   ├── MatchesList.jsx
│   │   │   └── MatchDetails.jsx
│   │   ├── Teams/
│   │   │   ├── TeamsList.jsx
│   │   │   └── TeamDetails.jsx
│   │   ├── Standings.jsx
│   │   ├── Statistics.jsx
│   │   └── Search.jsx
│   ├── hooks/                 # Custom React hooks
│   │   ├── useTournaments.js
│   │   ├── useMatches.js
│   │   ├── useTeams.js
│   │   ├── useStandings.js
│   │   └── useSearch.js
│   ├── context/               # React Context (if using)
│   │   ├── ThemeContext.jsx
│   │   └── AppContext.jsx
│   ├── utils/                 # Utility functions
│   │   ├── dateUtils.js
│   │   ├── formatUtils.js
│   │   └── constants.js
│   ├── styles/
│   │   ├── index.css
│   │   └── components.css
│   ├── App.jsx
│   └── main.jsx
├── .env.example
├── .gitignore
├── package.json
├── vite.config.js (or similar)
└── README.md
```

### Environment Variables
```env
VITE_TOURNAMENT_SERVICE_URL=http://localhost:8002/api/public
VITE_TEAM_SERVICE_URL=http://localhost:8003/api/public
VITE_MATCH_SERVICE_URL=http://localhost:8004/api/public
VITE_RESULTS_SERVICE_URL=http://localhost:8005/api/public
```

## Response Format
All API responses follow this format:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "cached": true,
  "cache_expires_at": "2026-02-01T16:00:00Z",
  "timestamp": "2026-02-01T15:00:00Z"
}
```

Error responses:
```json
{
  "success": false,
  "message": "Error message",
  "error_code": "ERROR_CODE",
  "errors": {
    "field": ["Error message"]
  },
  "timestamp": "2026-02-01T15:00:00Z"
}
```

## Pagination Format
Paginated responses:
```json
{
  "success": true,
  "data": {
    "data": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 20,
      "total": 100,
      "from": 1,
      "to": 20
    }
  }
}
```

## Implementation Checklist

### Phase 1: Project Setup & Foundation
- [ ] Initialize React project with Vite
- [ ] Install dependencies (React Router, Axios, React Query, Tailwind CSS, etc.)
- [ ] Set up project structure
- [ ] Configure environment variables
- [ ] Set up Axios instance with base URLs
- [ ] Create API service files for each service
- [ ] Set up routing with React Router
- [ ] Create basic layout components (Header, Footer, Navigation)
- [ ] Set up global styles and theme
- [ ] Create common components (Button, Card, Badge, Loading, ErrorMessage)

### Phase 2: Home Page
- [ ] Create Home page component
- [ ] Implement hero section
- [ ] Fetch and display featured tournaments
- [ ] Fetch and display live matches
- [ ] Fetch and display upcoming matches
- [ ] Implement search bar with autocomplete
- [ ] Create tournament cards
- [ ] Create match cards
- [ ] Add loading states
- [ ] Add error handling

### Phase 3: Tournaments
- [ ] Create TournamentsList page
- [ ] Implement filters (sport, status, date)
- [ ] Implement search functionality
- [ ] Implement sorting
- [ ] Implement pagination
- [ ] Create TournamentDetails page
- [ ] Implement tab navigation (Overview, Standings, Matches, Teams, Statistics)
- [ ] Fetch and display tournament details
- [ ] Fetch and display standings
- [ ] Fetch and display tournament matches
- [ ] Fetch and display tournament teams
- [ ] Fetch and display tournament statistics
- [ ] Create standings table component

### Phase 4: Matches
- [ ] Create MatchesList page
- [ ] Implement filters (tournament, status, date, team)
- [ ] Implement calendar view
- [ ] Implement list view
- [ ] Create MatchDetails page
- [ ] Display match header with teams and score
- [ ] Fetch and display match events timeline
- [ ] Display match statistics
- [ ] Display lineups (if available)
- [ ] Display match report (if available)
- [ ] Show related matches

### Phase 5: Teams
- [ ] Create TeamsList page
- [ ] Implement filters (tournament, sport)
- [ ] Implement search
- [ ] Create TeamDetails page
- [ ] Implement tab navigation (Overview, Players, Matches, Statistics)
- [ ] Fetch and display team details
- [ ] Fetch and display team players
- [ ] Fetch and display team matches
- [ ] Display team statistics
- [ ] Create player cards

### Phase 6: Standings & Statistics
- [ ] Create Standings page
- [ ] Implement tournament selector
- [ ] Display standings table
- [ ] Implement group stage support (if applicable)
- [ ] Create Statistics page
- [ ] Fetch and display tournament statistics
- [ ] Fetch and display top scorers
- [ ] Create charts (bar, pie, line)
- [ ] Implement filters

### Phase 7: Search
- [ ] Create Search page
- [ ] Implement global search API integration
- [ ] Create search results tabs (All, Tournaments, Teams, Players, Matches)
- [ ] Display search results
- [ ] Implement search suggestions/autocomplete
- [ ] Add recent searches (localStorage)
- [ ] Handle "no results" state

### Phase 8: Polish & Optimization
- [ ] Add loading skeletons
- [ ] Improve error handling
- [ ] Add animations and transitions
- [ ] Optimize images (lazy loading, WebP)
- [ ] Implement code splitting
- [ ] Add SEO meta tags
- [ ] Implement responsive design for all pages
- [ ] Add dark/light theme toggle (optional)
- [ ] Test on different devices
- [ ] Performance optimization
- [ ] Accessibility improvements (ARIA labels, keyboard navigation)

## Additional Features (Optional)

### Real-time Updates
- WebSocket integration for live match updates
- Auto-refresh for live matches
- Push notifications for match events

### User Preferences
- Save favorite teams/tournaments
- Customize dashboard
- Notification preferences

### Social Features
- Share tournament/match/team pages
- Social media integration
- Embed codes for matches

### Advanced Features
- Match predictions (if available)
- Fantasy league integration (if available)
- Match highlights/videos (if available)
- Player profiles with detailed stats

## Additional Notes

1. **CORS**: All services have CORS enabled for public access
2. **Rate Limiting**: Handle 429 (Too Many Requests) errors gracefully
3. **Caching**: Backend implements heavy caching (5-10 minutes), so data may be slightly stale
4. **Error Handling**: Display user-friendly error messages for all error scenarios
5. **Loading States**: Always show loading indicators during API calls
6. **Empty States**: Show helpful messages when no data is available
7. **SEO**: Add proper meta tags, Open Graph tags, and structured data
8. **Accessibility**: Follow WCAG guidelines for accessibility
9. **Performance**: Optimize bundle size, use code splitting, lazy load routes
10. **Testing**: Write unit tests for components and integration tests for API calls (optional)

## Deliverables
1. Complete public view React application
2. All pages and features implemented
3. Responsive design (mobile, tablet, desktop)
4. Error handling and loading states
5. Clean, maintainable code
6. README with setup instructions
7. Environment configuration file (.env.example)
8. Package.json with all dependencies

---

**Note**: This prompt should be used with an AI code generation tool (like ChatGPT, Claude, GitHub Copilot, etc.) to generate the complete public view application. Start with Phase 1 and proceed step by step. Adjust the technology stack recommendations based on your preferences and requirements.
