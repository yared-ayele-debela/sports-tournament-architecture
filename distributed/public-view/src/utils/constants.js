// Status constants
export const TOURNAMENT_STATUS = {
  ONGOING: 'ongoing',
  UPCOMING: 'upcoming',
  COMPLETED: 'completed',
};

export const MATCH_STATUS = {
  SCHEDULED: 'scheduled',
  LIVE: 'live',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
  POSTPONED: 'postponed',
};

// Status badge colors
export const STATUS_COLORS = {
  live: 'bg-green-500',
  in_progress: 'bg-green-500',
  scheduled: 'bg-blue-500',
  completed: 'bg-gray-500',
  ongoing: 'bg-green-500',
  upcoming: 'bg-blue-500',
  cancelled: 'bg-red-500',
  postponed: 'bg-yellow-500',
};

// Responsive breakpoints
export const BREAKPOINTS = {
  MOBILE: 768,
  TABLET: 1024,
};

// Pagination defaults
export const PAGINATION = {
  DEFAULT_PAGE: 1,
  DEFAULT_PER_PAGE: 20,
  PER_PAGE_OPTIONS: [12, 24, 48],
};
