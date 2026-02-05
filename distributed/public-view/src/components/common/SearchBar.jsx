import { useState, useRef, useEffect } from 'react';
import { Search, X } from 'lucide-react';
import { debounce } from '../../utils/formatUtils';

const SearchBar = ({
  onSearch,
  placeholder = 'Search tournaments, teams, players, matches...',
  suggestions = [],
  onSuggestionSelect,
  className = '',
  debounceMs = 300,
}) => {
  const [query, setQuery] = useState('');
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [recentSearches, setRecentSearches] = useState([]);
  const inputRef = useRef(null);
  const suggestionsRef = useRef(null);

  useEffect(() => {
    // Load recent searches from localStorage
    const saved = localStorage.getItem('recentSearches');
    if (saved) {
      setRecentSearches(JSON.parse(saved));
    }
  }, []);

  useEffect(() => {
    // Handle click outside
    const handleClickOutside = (event) => {
      if (
        suggestionsRef.current &&
        !suggestionsRef.current.contains(event.target) &&
        inputRef.current &&
        !inputRef.current.contains(event.target)
      ) {
        setShowSuggestions(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const debouncedSearch = debounce((searchQuery) => {
    if (onSearch && searchQuery.trim()) {
      onSearch(searchQuery);
    }
  }, debounceMs);

  const handleChange = (e) => {
    const value = e.target.value;
    setQuery(value);
    setShowSuggestions(true);
    debouncedSearch(value);
  };

  const handleClear = () => {
    setQuery('');
    setShowSuggestions(false);
    if (onSearch) {
      onSearch('');
    }
  };

  const handleSuggestionClick = (suggestion) => {
    setQuery(suggestion);
    setShowSuggestions(false);
    if (onSuggestionSelect) {
      onSuggestionSelect(suggestion);
    }
    // Save to recent searches
    const updated = [suggestion, ...recentSearches.filter((s) => s !== suggestion)].slice(0, 5);
    setRecentSearches(updated);
    localStorage.setItem('recentSearches', JSON.stringify(updated));
  };

  const displaySuggestions = query.trim() ? suggestions : recentSearches;

  return (
    <div className={`relative ${className}`}>
      <div className="relative">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
        <input
          ref={inputRef}
          type="text"
          value={query}
          onChange={handleChange}
          onFocus={() => setShowSuggestions(true)}
          placeholder={placeholder}
          className="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
        />
        {query && (
          <button
            onClick={handleClear}
            className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <X className="h-5 w-5" />
          </button>
        )}
      </div>

      {showSuggestions && displaySuggestions.length > 0 && (
        <div
          ref={suggestionsRef}
          className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
          {query.trim() && (
            <div className="px-4 py-2 text-xs font-semibold text-gray-500 uppercase border-b">
              Suggestions
            </div>
          )}
          {!query.trim() && recentSearches.length > 0 && (
            <div className="px-4 py-2 text-xs font-semibold text-gray-500 uppercase border-b">
              Recent Searches
            </div>
          )}
          {displaySuggestions.map((suggestion, index) => (
            <button
              key={index}
              onClick={() => handleSuggestionClick(suggestion)}
              className="w-full px-4 py-2 text-left hover:bg-gray-50 transition-colors"
            >
              {suggestion}
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

export default SearchBar;
