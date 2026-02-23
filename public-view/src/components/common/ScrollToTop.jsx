import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';

/**
 * ScrollToTop component
 * 
 * Scrolls to the top of the page when the route changes.
 * This ensures users always start at the top when navigating to a new page.
 */
const ScrollToTop = () => {
  const { pathname } = useLocation();

  useEffect(() => {
    // Scroll to top when route changes
    window.scrollTo({
      top: 0,
      left: 0,
      behavior: 'instant', // Instant scroll for better UX
    });
  }, [pathname]);

  return null;
};

export default ScrollToTop;
