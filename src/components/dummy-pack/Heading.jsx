import React from 'react';
import { Package } from 'lucide-react';

/**
 * Heading Component for Dummy Pack Page
 * 
 * Displays the main heading for the Dummy Pack Center page with title, subtitle, and optional icon.
 * 
 * @param {string} title - Main heading text (default: "Dummy Pack Center")
 * @param {string} subtitle - Optional subtitle/description text
 * @param {ReactNode} icon - Optional icon element (default: Package icon)
 * @param {string} className - Additional CSS classes
 */
const Heading = ({ 
  title = "Dummy Pack Center", 
  subtitle,
  icon,
  className = ""
}) => {
  const IconComponent = icon || <Package className="tw-w-5 tw-h-5" />;

  return (
    <div className={`tw-bg-white tw-p-8 tw-mb-8 tw-border tw-border-gray-200 ${className}`}>
      <h2 className="tw-text-2xl tw-font-semibold tw-text-gray-900 tw-mb-3 tw-flex tw-items-center tw-gap-2">
        {IconComponent && (
          <span className="tw-bg-gray-100 tw-p-2 tw-rounded-xl tw-shadow-sm tw-flex tw-items-center tw-justify-center tw-border tw-border-gray-200 tw-mr-1">
            <span className="tw-text-blue-500 tw-drop-shadow-sm">
              {IconComponent}
            </span>
          </span>
        )}
        {title}
      </h2>
      {subtitle && (
        <div className="tw-text-sm tw-text-gray-600 tw-leading-relaxed __html-content-styled" dangerouslySetInnerHTML={{ __html: subtitle }}>
        </div>
      )}
    </div>
  );
};

export default Heading;

