import { Link } from 'react-router-dom';

const Footer = () => {
  const currentYear = new Date().getFullYear();

  const footerLinks = {
    main: [
      { name: 'Home', path: '/' },
      { name: 'Tournaments', path: '/tournaments' },
      { name: 'Matches', path: '/matches' },
      { name: 'Teams', path: '/teams' },
      { name: 'Standings', path: '/standings' },
      { name: 'Statistics', path: '/statistics' },
    ],
    resources: [
      { name: 'API Documentation', path: '/api-docs', external: true },
      { name: 'About', path: '/about' },
      { name: 'Contact', path: '/contact' },
    ],
  };

  return (
    <footer className="bg-gray-900 text-gray-300">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* Brand */}
          <div>
            <h3 className="text-2xl font-bold text-white mb-4">SportsTour</h3>
            <p className="text-sm">
              Your comprehensive source for sports tournament information, matches, teams, and statistics.
            </p>
          </div>

          {/* Main Links */}
          <div>
            <h4 className="text-white font-semibold mb-4">Navigation</h4>
            <ul className="space-y-2">
              {footerLinks.main.map((link) => (
                <li key={link.name}>
                  <Link
                    to={link.path}
                    className="text-sm hover:text-white transition-colors"
                  >
                    {link.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Resources */}
          <div>
            <h4 className="text-white font-semibold mb-4">Resources</h4>
            <ul className="space-y-2">
              {footerLinks.resources.map((link) => (
                <li key={link.name}>
                  {link.external ? (
                    <a
                      href={link.path}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-sm hover:text-white transition-colors"
                    >
                      {link.name}
                    </a>
                  ) : (
                    <Link
                      to={link.path}
                      className="text-sm hover:text-white transition-colors"
                    >
                      {link.name}
                    </Link>
                  )}
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="mt-8 pt-8 border-t border-gray-800">
          <p className="text-sm text-center">
            © {currentYear} SportsTour. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
