# Public View - Sports Tournament Management System

A comprehensive public-facing web application built with React 18 to display sports tournament information, matches, teams, players, standings, and statistics. Features real-time updates, responsive design, and modern UI/UX.

## Features Implemented

### 🏠 Home Page
- **Hero Section**: Featured tournament showcase with dynamic content
- **Featured Tournaments**: Highlighted tournaments with quick access
- **Live Matches**: Real-time match updates with auto-refresh (30 seconds)
- **Upcoming Matches**: Scheduled matches with countdown timers
- **Quick Statistics**: Tournament overview with key metrics
- **Global Search**: Site-wide search functionality

### 📊 Tournament Management
- **Tournament Listing**: Browse all tournaments with filtering
- **Tournament Details**: Comprehensive tournament information
- **Tournament Statistics**: Detailed analytics and insights
- **Sport Categories**: Filter by sport type
- **Status Filtering**: Active, completed, upcoming tournaments

### ⚽ Match Information
- **Match Listings**: All matches with comprehensive filtering
- **Live Match Updates**: Real-time score and event updates
- **Match Details**: Teams, players, venues, and events
- **Match Statistics**: Performance analytics
- **Calendar View**: Match scheduling interface

### 👥 Team & Player Information
- **Team Directory**: Complete team listings
- **Team Details**: Roster, statistics, and performance
- **Player Profiles**: Individual player information
- **Team Statistics**: Performance metrics and rankings

### 🏆 Standings & Results
- **Tournament Standings**: Live leaderboard updates
- **Group Standings**: Group-based rankings
- **Top Scorers**: Player performance rankings
- **Historical Results**: Past match results

### 🔍 Search & Discovery
- **Global Search**: Search tournaments, teams, players, matches
- **Advanced Filtering**: Multiple filter criteria
- **Search Results**: Organized result display
- **Quick Access**: Fast navigation to found content

---

## 🚨 Security & Production Improvements

### 🔐 Security Configuration
- **Environment Variables**: All API endpoints and keys in `.env` files
- **No Hardcoded URLs**: All service URLs configurable
- **Input Sanitization**: All user inputs validated and sanitized
- **Secure API Calls**: HTTPS enforcement and certificate validation
- **Rate Limiting**: Client-side request throttling

### 📊 Monitoring & Debugging
- **Performance Monitoring**: Response time tracking for all API calls
- **Error Tracking**: Comprehensive error capture and user feedback
- **Request Tracing**: Correlation IDs for debugging
- **Health Monitoring**: Service availability checks
- **Analytics Integration**: User interaction tracking

### 🛠️ Development Tools
- **Postman Collections**: Complete API testing coverage
- **Setup Scripts**: Automated environment configuration
- **Testing Suites**: Component and integration tests
- **Documentation**: Comprehensive setup and API guides

---

## Technology Stack

### Frontend Framework
- **React 18**: Modern React with hooks and concurrent features
- **Vite**: Ultra-fast build tool and development server
- **React Router v6**: Client-side routing with lazy loading

### Data Management
- **TanStack Query (React Query)**: Server state management with caching
- **Axios**: HTTP client with interceptors and error handling

### UI & Styling
- **Tailwind CSS**: Utility-first CSS framework
- **Framer Motion**: Smooth animations and transitions
- **Recharts**: Interactive charts and data visualizations
- **Lucide React**: Modern, consistent icon library

### Utilities
- **date-fns**: Comprehensive date manipulation
- **ESLint**: Code quality and consistency
- **PostCSS**: CSS processing and optimization

## Prerequisites

- Node.js (v18 or higher)
- npm or yarn
- All microservices running for full functionality:
  - Tournament Service (port 8002)
  - Team Service (port 8003)
  - Match Service (port 8004)
  - Results Service (port 8005)

## Installation & Setup

### 1. Navigate to the project directory
```bash
cd public-view
```

### 2. Install dependencies
```bash
npm install
```

### 3. Create environment configuration
Create a `.env` file in the root directory (or copy from `.env.example`):

```env
VITE_TOURNAMENT_SERVICE_URL=http://localhost:8002/api/public
VITE_TEAM_SERVICE_URL=http://localhost:8003/api/public
VITE_MATCH_SERVICE_URL=http://localhost:8004/api/public
VITE_RESULTS_SERVICE_URL=http://localhost:8005/api/public
```

### 4. Start the development server
```bash
npm run dev
```

The application will be available at `http://localhost:3001`

## Project Structure

```
public-view/
├── public/
│   └── index.html              # HTML template
├── src/
│   ├── api/                    # API service layer
│   │   ├── axios.js           # Axios configuration
│   │   ├── tournaments.js     # Tournament API calls
│   │   ├── teams.js           # Team API calls
│   │   ├── matches.js         # Match API calls
│   │   ├── results.js         # Results API calls
│   │   └── search.js          # Search API calls
│   ├── components/             # Reusable components
│   │   ├── common/            # Shared UI components
│   │   │   ├── Button.jsx
│   │   │   ├── Card.jsx
│   │   │   ├── Badge.jsx
│   │   │   ├── Loading.jsx
│   │   │   ├── ErrorMessage.jsx
│   │   │   ├── Pagination.jsx
│   │   │   └── SearchBar.jsx
│   │   ├── layout/            # Layout components
│   │   │   ├── Header.jsx
│   │   │   ├── Footer.jsx
│   │   │   ├── Navigation.jsx
│   │   │   └── Breadcrumbs.jsx
│   │   ├── tournament/        # Tournament-specific components
│   │   ├── match/             # Match-specific components
│   │   ├── team/              # Team-specific components
│   │   └── standings/         # Standings components
│   ├── pages/                 # Page components
│   │   ├── Home.jsx           # Home page
│   │   ├── Search.jsx         # Search results page
│   │   ├── Standings.jsx      # Tournament standings
│   │   ├── Tournaments/       # Tournament pages
│   │   ├── Matches/           # Match pages
│   │   └── Teams/             # Team pages
│   ├── hooks/                 # Custom React hooks
│   ├── context/               # React Context providers
│   ├── utils/                 # Utility functions
│   ├── styles/                # Global styles
│   ├── App.jsx                # Main app component
│   └── main.jsx               # Application entry point
├── .env.example               # Environment variables template
├── .env                       # Environment variables (create this)
├── package.json               # Dependencies and scripts
├── vite.config.js            # Vite configuration
├── tailwind.config.js        # Tailwind CSS configuration
└── README.md                 # This file
```

## API Integration

The application connects to the following microservices via their public APIs:

### Tournament Service (Port 8002)
- **Endpoints**: Tournaments, Sports, Venues
- **Features**: Tournament listings, details, sport categories, venue information
- **Data**: Tournament metadata, schedules, participant information

### Team Service (Port 8003)
- **Endpoints**: Teams, Players
- **Features**: Team directories, player rosters, team statistics
- **Data**: Team information, player profiles, performance metrics

### Match Service (Port 8004)
- **Endpoints**: Matches, Match Events
- **Features**: Live matches, upcoming matches, match events, real-time updates
- **Data**: Match schedules, live scores, events, team lineups

### Results Service (Port 8005)
- **Endpoints**: Standings, Statistics, Top Scorers
- **Features**: Tournament rankings, player statistics, historical data
- **Data**: League tables, performance analytics, leaderboards

### API Characteristics
- **Public Access**: All endpoints are publicly accessible (no authentication required)
- **Standard Format**: Consistent response structure with success, message, data, timestamp
- **Error Handling**: Graceful handling of rate limiting (429) and service unavailable (503)
- **Caching**: React Query caching with 5-10 minute TTL for optimal performance

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

## Performance Features

### Data Management
- **React Query Caching**: Intelligent caching with 5-10 minute TTL
- **Background Updates**: Automatic data refetching and synchronization
- **Optimistic Updates**: Enhanced user experience with instant UI feedback
- **Error Recovery**: Automatic retry mechanisms with exponential backoff

### Real-time Features
- **Live Match Updates**: Auto-refresh every 30 seconds for live matches
- **Real-time Standings**: Dynamic leaderboard updates
- **Push Notifications**: Browser notifications for important events (optional)

### Performance Optimizations
- **Code Splitting**: Lazy loading of routes and components
- **Image Optimization**: Responsive images with lazy loading
- **Bundle Optimization**: Tree shaking and minification
- **Caching Strategy**: Service worker for offline support (planned)

## User Experience

### Responsive Design
- **Mobile-First**: Optimized for all device sizes
- **Touch-Friendly**: Intuitive touch interactions
- **Progressive Enhancement**: Works on all modern browsers
- **Accessibility**: WCAG 2.1 compliance (in progress)

### Search & Discovery
- **Global Search**: Search across tournaments, teams, players, and matches
- **Advanced Filtering**: Multiple filter criteria with real-time updates
- **Smart Suggestions**: Auto-complete and search recommendations
- **Quick Navigation**: Fast access to frequently accessed content

### Visual Design
- **Modern UI**: Clean, contemporary design with Tailwind CSS
- **Smooth Animations**: Framer Motion for fluid transitions
- **Data Visualization**: Interactive charts with Recharts
- **Consistent Theming**: Unified design language throughout

## Browser Support

- **Chrome/Edge**: 90+
- **Firefox**: 88+
- **Safari**: 14+
- **Mobile Browsers**: iOS Safari, Android Chrome

## Development Guidelines

### Code Standards
- Use ESLint for code consistency
- Follow React best practices and hooks patterns
- Implement proper error boundaries
- Add loading states for all async operations

### Performance Guidelines
- Optimize images and assets
- Use React.memo for expensive components
- Implement proper key props in lists
- Minimize re-renders with useMemo and useCallback

### Testing
- Component testing with React Testing Library (planned)
- E2E testing with Playwright (planned)
- API integration testing (planned)

## Deployment

### Production Build
```bash
npm run build
```

### Environment Variables
Ensure all environment variables are properly configured in production:
- API service URLs
- Any feature flags or configuration

### Static Hosting
The application can be deployed to any static hosting service:
- Vercel
- Netlify
- GitHub Pages
- AWS S3 + CloudFront

## Troubleshooting

### Common Issues

**API Connection Errors**
- Verify all microservices are running
- Check service URLs in `.env` file
- Ensure CORS is properly configured

**Build Errors**
- Clear node_modules and reinstall: `rm -rf node_modules && npm install`
- Check for dependency conflicts
- Verify environment variables

**Performance Issues**
- Check browser console for errors
- Monitor network requests in DevTools
- Verify React Query caching configuration

## Contributing

1. Follow the existing code style and patterns
2. Use Tailwind CSS for all styling
3. Implement proper error handling and loading states
4. Test responsive design on multiple devices
5. Add appropriate comments for complex logic
6. Update documentation for new features

## License

This project is part of the Sports Tournament Management System.
