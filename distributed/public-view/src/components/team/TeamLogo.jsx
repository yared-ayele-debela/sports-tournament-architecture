import { useState } from 'react';
import { Users } from 'lucide-react';

const TeamLogo = ({ logo, name, size = 'md', className = '' }) => {
  const [imageError, setImageError] = useState(false);

  const sizeClasses = {
    sm: 'h-10 w-10',
    md: 'h-16 w-16',
    lg: 'h-20 w-20',
    xl: 'h-32 w-32',
  };

  const iconSizes = {
    sm: 'h-6 w-6',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
    xl: 'h-16 w-16',
  };

  // Show default if no logo or image failed to load
  if (!logo || imageError) {
    return (
      <div
        className={`${sizeClasses[size]} bg-gray-100 rounded-lg flex items-center justify-center border border-gray-200 ${className}`}
      >
        <Users className={`${iconSizes[size]} text-gray-400`} />
      </div>
    );
  }

  return (
    <img
      src={logo}
      alt={name || 'Team Logo'}
      className={`${sizeClasses[size]} object-contain ${className}`}
      onError={() => setImageError(true)}
    />
  );
};

export default TeamLogo;
