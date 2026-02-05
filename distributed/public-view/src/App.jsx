import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Header from './components/layout/Header';
import Footer from './components/layout/Footer';
import Home from './pages/Home';
import TournamentsList from './pages/Tournaments/TournamentsList';
import TournamentDetails from './pages/Tournaments/TournamentDetails';
import MatchesList from './pages/Matches/MatchesList';
import MatchDetails from './pages/Matches/MatchDetails';
import TeamsList from './pages/Teams/TeamsList';
import TeamDetails from './pages/Teams/TeamDetails';

function App() {
  return (
    <Router>
      <div className="min-h-screen flex flex-col">
        <Header />
        <main className="flex-grow">
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/tournaments" element={<TournamentsList />} />
            <Route path="/tournaments/:id" element={<TournamentDetails />} />
            <Route path="/matches" element={<MatchesList />} />
            <Route path="/matches/:id" element={<MatchDetails />} />
            <Route path="/teams" element={<TeamsList />} />
            <Route path="/teams/:id" element={<TeamDetails />} />
          </Routes>
        </main>
        <Footer />
      </div>
    </Router>
  );
}

export default App;
