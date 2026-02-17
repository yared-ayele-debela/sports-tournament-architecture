import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Menu, X, Radio } from 'lucide-react';

const Header = () => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const navigation = [
    { name: 'Home', path: '/' },
    { name: 'Tournaments', path: '/tournaments' },
    { name: 'Matches', path: '/matches' },
    { name: 'Live Matches', path: '/matches/live', highlight: true },
    { name: 'Teams', path: '/teams' },
  ];

  return (
    <header className="bg-white shadow-md sticky top-0 z-40">
      <nav className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <div className="flex-shrink-0">
            <Link to="/" className="flex items-center">
              <img src="src/assets/logo.png" alt="Sports Tournament" className="h-10 w-10" />
              <span className="text-2xl font-bold text-primary-600">
                Sports Tournament</span>
            </Link>
          </div>

          {/* Desktop Navigation */}
          <div className="hidden md:flex md:items-center md:space-x-4">
            {navigation.map((item) => (
              <Link
                key={item.name}
                to={item.path}
                className={`px-3 py-2 text-sm font-medium transition-colors flex items-center gap-2 ${
                  item.highlight
                    ? 'text-red-600 hover:text-red-700 font-semibold'
                    : 'text-gray-700 hover:text-primary-600'
                }`}
              >
                {item.highlight && <Radio className="h-4 w-4 animate-pulse" />}
                {item.name}
              </Link>
            ))}
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden">
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="p-2 text-gray-700"
            >
              {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
            </button>
          </div>
        </div>

        {/* Mobile menu */}
        {mobileMenuOpen && (
          <div className="md:hidden pb-4">
            <div className="space-y-1">
              {navigation.map((item) => (
                <Link
                  key={item.name}
                  to={item.path}
                  onClick={() => setMobileMenuOpen(false)}
                  className={`px-3 py-2 text-base font-medium rounded-md transition-colors flex items-center gap-2 ${
                    item.highlight
                      ? 'text-red-600 hover:text-red-700 hover:bg-red-50 font-semibold'
                      : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50'
                  }`}
                >
                  {item.highlight && <Radio className="h-4 w-4 animate-pulse" />}
                  {item.name}
                </Link>
              ))}
            </div>
          </div>
        )}
      </nav>
    </header>
  );
};

export default Header;
