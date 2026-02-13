import { Routes, Route, Navigate } from 'react-router-dom';
import { ProtectedRoute } from './components/ProtectedRoute';
import Layout from './components/Layout/Layout';
import Login from './pages/Login';
import DashboardRouter from './components/DashboardRouter';
import AdminDashboard from './pages/Dashboard';
import CoachDashboard from './pages/CoachDashboard';
import RefereeDashboard from './pages/RefereeDashboard';
import UsersList from './pages/Users/UsersList';
import UserForm from './pages/Users/UserForm';
import UserDetail from './pages/Users/UserDetail';
import RolesList from './pages/Roles/RolesList';
import RoleForm from './pages/Roles/RoleForm';
import RoleDetail from './pages/Roles/RoleDetail';
import TournamentsList from './pages/Tournaments/TournamentsList';
import TournamentForm from './pages/Tournaments/TournamentForm';
import TournamentDetail from './pages/Tournaments/TournamentDetail';
import SportsList from './pages/Sports/SportsList';
import SportForm from './pages/Sports/SportForm';
import SportDetail from './pages/Sports/SportDetail';
import TeamsList from './pages/Teams/TeamsList';
import MyTeams from './pages/Teams/MyTeams';
import TeamForm from './pages/Teams/TeamForm';
import TeamDetail from './pages/Teams/TeamDetail';
import TeamPlayers from './pages/Teams/TeamPlayers';
import PlayersList from './pages/Players/PlayersList';
import PlayerForm from './pages/Players/PlayerForm';
import PlayerDetail from './pages/Players/PlayerDetail';
import MatchesList from './pages/Matches/MatchesList';
import MyMatches from './pages/Matches/MyMatches';
import MatchForm from './pages/Matches/MatchForm';
import MatchDetail from './pages/Matches/MatchDetail';
import MyMatchDetail from './pages/Matches/MyMatchDetail';
import Standings from './pages/Results/Standings';
import ResultsList from './pages/Results/ResultsList';
import MatchFinalizeForm from './pages/Results/MatchFinalizeForm';
import TeamStatistics from './pages/Results/TeamStatistics';
import Profile from './pages/Profile';
import VenuesList from './pages/Venues/VenuesList';
import VenueForm from './pages/Venues/VenueForm';
import VenueDetail from './pages/Venues/VenueDetail';

function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <Layout>
              <DashboardRouter />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/dashboard/admin"
        element={
          <ProtectedRoute>
            <Layout>
              <AdminDashboard />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/dashboard/coach"
        element={
          <ProtectedRoute>
            <Layout>
              <CoachDashboard />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/dashboard/referee"
        element={
          <ProtectedRoute>
            <Layout>
              <RefereeDashboard />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/users"
        element={
          <ProtectedRoute>
            <Layout>
              <UsersList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/users/new"
        element={
          <ProtectedRoute>
            <Layout>
              <UserForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/users/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <UserDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/users/:id/edit"
        element={
          <ProtectedRoute>
            <Layout>
              <UserForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/roles"
        element={
          <ProtectedRoute>
            <Layout>
              <RolesList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/roles/new"
        element={
          <ProtectedRoute>
            <Layout>
              <RoleForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/roles/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <RoleDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/roles/:id/edit"
        element={
          <ProtectedRoute>
            <Layout>
              <RoleForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/tournaments"
        element={
          <ProtectedRoute>
            <Layout>
              <TournamentsList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/tournaments/new"
        element={
          <ProtectedRoute>
            <Layout>
              <TournamentForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/tournaments/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <TournamentDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/tournaments/:id/edit"
        element={
          <ProtectedRoute>
            <Layout>
              <TournamentForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/sports"
        element={
          <ProtectedRoute>
            <Layout>
              <SportsList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/sports/new"
        element={
          <ProtectedRoute>
            <Layout>
              <SportForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/sports/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <SportDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/sports/:id/edit"
        element={
          <ProtectedRoute>
            <Layout>
              <SportForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams"
        element={
          <ProtectedRoute>
            <Layout>
              <TeamsList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams/my-teams"
        element={
          <ProtectedRoute>
            <Layout>
              <MyTeams />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams/new"
        element={
          <ProtectedRoute>
            <Layout>
              <TeamForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <TeamDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams/:id/edit"
        element={
          <ProtectedRoute>
            <Layout>
              <TeamForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams/:id/players"
        element={
          <ProtectedRoute>
            <Layout>
              <TeamPlayers />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/players"
        element={
          <ProtectedRoute>
            <Layout>
              <PlayersList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/players/new"
        element={
          <ProtectedRoute>
            <Layout>
              <PlayerForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/players/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <PlayerDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/players/:id/edit"
        element={
          <ProtectedRoute>
            <Layout>
              <PlayerForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches"
        element={
          <ProtectedRoute>
            <Layout>
              <MatchesList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches/my-matches"
        element={
          <ProtectedRoute>
            <Layout>
              <MyMatches />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches/my-matches/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <MyMatchDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches/new"
        element={
          <ProtectedRoute>
            <Layout>
              <MatchForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <MatchDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches/:id/edit"
        element={
          <ProtectedRoute>
            <Layout>
              <MatchForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches/:matchId/finalize"
        element={
          <ProtectedRoute>
            <Layout>
              <MatchFinalizeForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/standings"
        element={
          <ProtectedRoute>
            <Layout>
              <Standings />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/results"
        element={
          <ProtectedRoute>
            <Layout>
              <ResultsList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams/:teamId/statistics"
        element={
          <ProtectedRoute>
            <Layout>
              <TeamStatistics />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/profile"
        element={
          <ProtectedRoute>
            <Layout>
              <Profile />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/venues"
        element={
          <ProtectedRoute>
            <Layout>
              <VenuesList />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/venues/new"
        element={
          <ProtectedRoute>
            <Layout>
              <VenueForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/venues/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <VenueDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/venues/:id/edit"
        element={
          <ProtectedRoute>
            <Layout>
              <VenueForm />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route path="/" element={<Navigate to="/dashboard" replace />} />
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}

export default App;
