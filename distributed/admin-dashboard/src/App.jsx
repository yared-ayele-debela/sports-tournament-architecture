import { Routes, Route, Navigate } from 'react-router-dom';
import { ProtectedRoute } from './components/ProtectedRoute';
import { AdminRoute } from './components/AdminRoute';
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
            <AdminRoute>
              <Layout>
                <AdminDashboard />
              </Layout>
            </AdminRoute>
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
            <AdminRoute>
              <Layout>
                <UsersList />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/users/new"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <UserForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/users/:id"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <UserDetail />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/users/:id/edit"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <UserForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/roles"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <RolesList />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/roles/new"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <RoleForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/roles/:id"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <RoleDetail />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/roles/:id/edit"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <RoleForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/tournaments"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <TournamentsList />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/tournaments/new"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <TournamentForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/tournaments/:id"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <TournamentDetail />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/tournaments/:id/edit"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <TournamentForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/sports"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <SportsList />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/sports/new"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <SportForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/sports/:id"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <SportDetail />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/sports/:id/edit"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <SportForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <TeamsList />
              </Layout>
            </AdminRoute>
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
            <AdminRoute>
              <Layout>
                <TeamForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams/:id"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <TeamDetail />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/teams/:id/edit"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <TeamForm />
              </Layout>
            </AdminRoute>
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
            <AdminRoute>
              <Layout>
                <PlayersList />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/players/new"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <PlayerForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/players/:id"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <PlayerDetail />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/players/:id/edit"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <PlayerForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <MatchesList />
              </Layout>
            </AdminRoute>
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
            <AdminRoute>
              <Layout>
                <MatchForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches/:id"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <MatchDetail />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/matches/:id/edit"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <MatchForm />
              </Layout>
            </AdminRoute>
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
            <AdminRoute>
              <Layout>
                <VenuesList />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/venues/new"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <VenueForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/venues/:id"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <VenueDetail />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route
        path="/venues/:id/edit"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <Layout>
                <VenueForm />
              </Layout>
            </AdminRoute>
          </ProtectedRoute>
        }
      />
      <Route path="/" element={<Navigate to="/dashboard" replace />} />
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}

export default App;
