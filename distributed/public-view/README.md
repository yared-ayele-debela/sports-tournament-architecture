# Public View - Sports Tournament Management System

A comprehensive public-facing web application built with React to display sports tournament information, matches, teams, players, standings, and statistics.

## Features

- **Home Page**: Featured tournaments, live matches, upcoming matches, quick stats, and global search
- **Tournament Browsing**: View all tournaments with filtering and sorting
- **Match Information**: Live and upcoming matches with detailed information
- **Team Details**: Team information, players, and statistics
- **Standings**: Tournament standings and statistics
- **Responsive Design**: Mobile-first approach with support for all device sizes
- **Dark/Light Theme**: Theme toggle support (optional)

## Technology Stack

- **React 18**: Frontend framework
- **Vite**: Build tool
- **React Router v6**: Routing
- **TanStack Query (React Query)**: Data fetching and caching
- **Axios**: HTTP client
- **Tailwind CSS**: Styling
- **Framer Motion**: Animations
- **Recharts**: Charts and visualizations
- **date-fns**: Date handling
- **Lucide React**: Icons

## Prerequisites

- Node.js (v18 or higher)
- npm or yarn

## Installation

1. Navigate to the project directory:
```bash
cd public-view
```

2. Install dependencies:
```bash
npm install
```

3. Create a `.env` file in the root directory:
```env
VITE_TOURNAMENT_SERVICE_URL=http://localhost:8002/api/public
VITE_TEAM_SERVICE_URL=http://localhost:8003/api/public
VITE_MATCH_SERVICE_URL=http://localhost:8004/api/public
VITE_RESULTS_SERVICE_URL=http://localhost:8005/api/public
```

4. Start the development server:
```bash
npm run dev
```

The application will be available at `http://localhost:3001`

## Project Structure

```
public-view/
├── public/
│   └── index.html
├── src/
│   ├── api/              # API service files
│   │   ├── axios.js
│   │   ├── tournaments.js
│   │   ├── teams.js
│   │   ├── matches.js
│   │   ├── results.js
│   │   └── search.js
│   ├── components/
│   │   ├── common/        # Reusable components
│   │   ├── layout/         # Layout components
│   │   ├── tournament/     # Tournament components
│   │   ├── match/         # Match components
│   │   ├── team/          # Team components
│   │   └── standings/     # Standings components
│   ├── pages/             # Page components
│   ├── hooks/             # Custom React hooks
│   ├── context/           # React Context
│   ├── utils/             # Utility functions
│   ├── styles/            # Global styles
│   ├── App.jsx
│   └── main.jsx
├── .env.example
├── package.json
├── vite.config.js
└── tailwind.config.js
```

## API Services

The application connects to the following microservices:

- **Tournament Service** (Port 8002): Tournaments, Sports, Venues
- **Team Service** (Port 8003): Teams, Players
- **Match Service** (Port 8004): Matches, Match Events
- **Results Service** (Port 8005): Standings, Statistics, Top Scorers

All endpoints are public and do not require authentication.

## Available Scripts

- `npm run dev`: Start development server
- `npm run build`: Build for production
- `npm run preview`: Preview production build
- `npm run lint`: Run ESLint

## Features Implemented

### Phase 1: Foundation ✅
- Project setup with Vite
- API integration with Axios
- React Router setup
- Layout components (Header, Footer, Navigation, Breadcrumbs)
- Common components (Button, Card, Badge, Loading, ErrorMessage, Pagination, SearchBar)
- Global styles and theme context

### Phase 2: Home Page ✅
- Hero section with featured tournament
- Featured tournaments section
- Live matches section with auto-refresh
- Upcoming matches section
- Quick stats section
- Global search bar

## Next Steps

- Tournaments listing and details pages
- Matches listing and details pages
- Teams listing and details pages
- Standings page
- Statistics page
- Search page

## Notes

- All API responses follow a standard format with `success`, `message`, `data`, and `timestamp` fields
- The application handles rate limiting (429 errors) and service unavailable (503 errors) gracefully
- Data is cached using React Query with 5-10 minute TTL
- Live matches auto-refresh every 30 seconds

## License

This project is part of the Sports Tournament Management System.
