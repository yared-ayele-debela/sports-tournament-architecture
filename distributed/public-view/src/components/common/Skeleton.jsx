const Skeleton = ({ className = '', variant = 'default' }) => {
  const variants = {
    default: 'h-4 bg-gray-200 rounded',
    card: 'h-48 bg-gray-200 rounded-lg',
    text: 'h-4 bg-gray-200 rounded',
    title: 'h-6 bg-gray-200 rounded',
    avatar: 'h-12 w-12 bg-gray-200 rounded-full',
  };

  return (
    <div
      className={`animate-pulse ${variants[variant]} ${className}`}
      aria-label="Loading..."
    />
  );
};

export const SkeletonCard = () => {
  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden p-6">
      <Skeleton variant="avatar" className="mb-4" />
      <Skeleton variant="title" className="mb-2 w-3/4" />
      <Skeleton variant="text" className="mb-4 w-full" />
      <Skeleton variant="text" className="mb-2 w-2/3" />
      <Skeleton variant="text" className="w-1/2" />
    </div>
  );
};

export const SkeletonMatchCard = () => {
  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden p-6">
      <Skeleton variant="text" className="mb-4 w-1/4 mx-auto" />
      <div className="space-y-4 mb-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3 flex-1">
            <Skeleton variant="avatar" />
            <Skeleton variant="text" className="w-32" />
          </div>
          <Skeleton variant="text" className="w-8 h-8" />
        </div>
        <Skeleton variant="text" className="w-8 mx-auto" />
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3 flex-1">
            <Skeleton variant="avatar" />
            <Skeleton variant="text" className="w-32" />
          </div>
          <Skeleton variant="text" className="w-8 h-8" />
        </div>
      </div>
      <Skeleton variant="text" className="w-full mb-2" />
      <Skeleton variant="text" className="w-2/3" />
    </div>
  );
};

export default Skeleton;
