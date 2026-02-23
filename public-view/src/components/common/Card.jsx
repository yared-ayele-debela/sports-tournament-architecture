import { motion } from 'framer-motion';

const Card = ({ children, className = '', hover = true, onClick, ...props }) => {
  const baseClasses = 'bg-white rounded-lg shadow-md overflow-hidden';
  const hoverClasses = hover ? 'hover:shadow-lg transition-shadow duration-200 cursor-pointer' : '';

  return (
    <motion.div
      className={`${baseClasses} ${hoverClasses} ${className}`}
      onClick={onClick}
      whileHover={hover && onClick ? { y: -2 } : {}}
      {...props}
    >
      {children}
    </motion.div>
  );
};

export default Card;
