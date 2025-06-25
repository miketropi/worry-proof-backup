import React, { useState, useEffect, useRef } from 'react';

/**
 * Modern Tab Component
 * 
 * A responsive, accessible tab component with multiple design variants and mobile optimization.
 * 
 * FEATURES:
 * - Responsive design for mobile, tablet, and desktop
 * - Smooth animations and transitions
 * - Accessible with proper ARIA attributes
 * - Touch-friendly for mobile devices
 * - Clean, modern UI with Tailwind CSS
 * - Multiple design variants (default, pills, underline)
 * - Different sizes (sm, md, lg)
 * - Icon and badge support
 * - Keyboard navigation
 * 
 * USAGE:
 * 
 * Basic Usage:
 * ```jsx
 * const tabs = [
 *   {
 *     label: 'Overview',
 *     content: <div>Overview content here</div>
 *   },
 *   {
 *     label: 'Settings',
 *     content: <div>Settings content here</div>
 *   }
 * ];
 * 
 * <Tab tabs={tabs} />
 * ```
 * 
 * With Icons and Badges:
 * ```jsx
 * const tabs = [
 *   {
 *     label: 'Overview',
 *     icon: <svg>...</svg>,
 *     content: <div>Content</div>
 *   },
 *   {
 *     label: 'Analytics',
 *     icon: <svg>...</svg>,
 *     badge: 'New',
 *     content: <div>Content</div>
 *   }
 * ];
 * ```
 * 
 * Different Variants:
 * ```jsx
 * <Tab tabs={tabs} variant="default" />    // Default rounded style
 * <Tab tabs={tabs} variant="pills" />      // Pill-shaped tabs
 * <Tab tabs={tabs} variant="underline" />  // Underline style
 * ```
 * 
 * Different Sizes:
 * ```jsx
 * <Tab tabs={tabs} size="sm" />  // Small
 * <Tab tabs={tabs} size="md" />  // Medium (default)
 * <Tab tabs={tabs} size="lg" />  // Large
 * ```
 * 
 * Full Width:
 * ```jsx
 * <Tab tabs={tabs} fullWidth />
 * ```
 * 
 * With Callback:
 * ```jsx
 * <Tab 
 *   tabs={tabs} 
 *   onTabChange={(index, tab) => {
 *     console.log('Tab changed to:', index, tab.label);
 *   }}
 * />
 * ```
 * 
 * PROPS:
 * @param {Array} tabs - Array of tab objects
 *   @param {string} tabs[].label - Tab label text
 *   @param {ReactNode} tabs[].content - Tab content (JSX or function)
 *   @param {ReactNode} [tabs[].icon] - Optional icon (SVG recommended)
 *   @param {string} [tabs[].badge] - Optional badge text
 *   @param {boolean} [tabs[].disabled] - Disable tab if true
 * @param {number} [defaultActiveTab=0] - Index of initially active tab
 * @param {string} [className] - Additional CSS classes
 * @param {string} [variant='default'] - Design variant: 'default', 'pills', 'underline'
 * @param {string} [size='md'] - Size: 'sm', 'md', 'lg'
 * @param {boolean} [fullWidth=false] - Expand tabs to full width
 * @param {function} [onTabChange] - Callback when tab changes (index, tab) => void
 * 
 * TAB OBJECT STRUCTURE:
 * {
 *   label: string,           // Required: Tab label
 *   content: ReactNode,      // Required: Tab content (JSX or function)
 *   icon?: ReactNode,        // Optional: Icon element
 *   badge?: string,          // Optional: Badge text
 *   disabled?: boolean       // Optional: Disable tab
 * }
 * 
 * KEYBOARD NAVIGATION:
 * - Tab/Shift+Tab: Navigate between tabs
 * - Enter/Space: Activate tab
 * - Arrow Left/Right: Navigate between tabs
 * 
 * ACCESSIBILITY:
 * - Proper ARIA attributes (role, aria-selected, aria-controls)
 * - Keyboard navigation support
 * - Screen reader friendly
 * - Focus management
 * - High contrast support
 */

const Tab = ({ 
  tabs = [], 
  defaultActiveTab = 0, 
  className = '',
  variant = 'default', // 'default', 'pills', 'underline'
  size = 'md', // 'sm', 'md', 'lg'
  fullWidth = false,
  onTabChange = null
}) => {
  const [activeTab, setActiveTab] = useState(defaultActiveTab);
  const [isMobile, setIsMobile] = useState(false);
  const tabRefs = useRef([]);
  const indicatorRef = useRef(null);

  // Check if device is mobile
  useEffect(() => {
    const checkMobile = () => {
      setIsMobile(window.innerWidth < 768);
    };
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
    
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  // Update active tab indicator position
  useEffect(() => {
    if (indicatorRef.current && tabRefs.current[activeTab]) {
      const activeTabElement = tabRefs.current[activeTab];
      const indicator = indicatorRef.current;
      
      indicator.style.left = `${activeTabElement.offsetLeft}px`;
      indicator.style.width = `${activeTabElement.offsetWidth}px`;
    }
  }, [activeTab, isMobile]);

  const handleTabClick = (index) => {
    setActiveTab(index);
    if (onTabChange) {
      onTabChange(index, tabs[index]);
    }
  };

  const handleKeyDown = (event, index) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      handleTabClick(index);
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      const nextIndex = (index + 1) % tabs.length;
      handleTabClick(nextIndex);
      tabRefs.current[nextIndex]?.focus();
    } else if (event.key === 'ArrowLeft') {
      event.preventDefault();
      const prevIndex = index === 0 ? tabs.length - 1 : index - 1;
      handleTabClick(prevIndex);
      tabRefs.current[prevIndex]?.focus();
    }
  };

  // Size classes
  const sizeClasses = {
    sm: {
      tab: 'tw-px-3 tw-py-2 tw-text-sm',
      content: 'tw-p-4',
      icon: 'tw-w-4 tw-h-4',
      badge: 'tw-text-xs tw-px-1.5 tw-py-0.5'
    },
    md: {
      tab: 'tw-px-4 tw-py-2 tw-text-sm',
      content: 'tw-p-4',
      icon: 'tw-w-5 tw-h-5',
      badge: 'tw-text-xs tw-px-2 tw-py-0.5'
    },
    lg: {
      tab: 'tw-px-6 tw-py-3 tw-text-base',
      content: 'tw-p-6',
      icon: 'tw-w-6 tw-h-6',
      badge: 'tw-text-sm tw-px-2.5 tw-py-1'
    }
  };

  // Variant classes
  const getVariantClasses = (isActive) => {
    const baseClasses = 'tw-transition-all tw-duration-200 tw-ease-in-out tw-font-medium tw-relative';
    
    switch (variant) {
      case 'pills':
        return `${baseClasses} tw-rounded-md ${
          isActive 
            ? 'tw-bg-blue-600 tw-text-white' 
            : 'tw-bg-gray-100 tw-text-gray-700 hover:tw-bg-gray-200'
        }`;
      
      case 'underline':
        return `${baseClasses} tw-border-b-2 ${
          isActive 
            ? 'tw-border-blue-600 tw-text-blue-600' 
            : 'tw-border-transparent tw-text-gray-600 hover:tw-text-gray-800 hover:tw-border-gray-300'
        }`;
      
      default:
        return `${baseClasses} tw-border tw-border-b-0 ${
          isActive 
            ? 'tw-bg-white tw-text-gray-900 tw-border-gray-200 tw-z-10 tw-relative' 
            : 'tw-bg-gray-100 tw-text-gray-600 hover:tw-bg-gray-50 hover:tw-text-gray-800 tw-border-gray-200 tw-border-b-gray-200'
        }`;
    }
  };

  if (!tabs || tabs.length === 0) {
    return (
      <div className="tw-text-center tw-text-gray-500 tw-py-8">
        <div className="tw-text-sm tw-font-medium">No tabs available</div>
      </div>
    );
  }

  return (
    <div className={`tw-w-full ${className}`}>
      {/* Tab Navigation */}
      <div className="tw-relative">
        <div 
          className={`
            tw-flex tw-gap-0 tw-bg-gray-100 tw-border tw-border-gray-200 tw-border-b-0 tw-p-1 tw-pb-0
            ${fullWidth ? 'tw-w-full' : 'tw-w-fit'}
            ${variant === 'underline' ? 'tw-bg-transparent tw-p-0 tw-border-b tw-border-gray-200' : ''}
            ${variant === 'pills' ? 'tw-bg-gray-100 tw-p-1 tw-rounded-lg tw-border' : ''}
            tw-overflow-x-auto tw-scrollbar-hide tw-whitespace-nowrap md:tw-flex-wrap md:tw-overflow-visible
          `}
          role="tablist"
          aria-label="Tab navigation"
          // Mobile: horizontal scroll, hide scrollbar
          style={{ WebkitOverflowScrolling: 'touch' }}
        >
          {tabs.map((tab, index) => (
            <button
              key={index}
              ref={(el) => (tabRefs.current[index] = el)}
              className={`
                ${sizeClasses[size].tab}
                ${getVariantClasses(activeTab === index)}
                ${fullWidth ? 'tw-flex-1' : ''}
                tw-flex tw-items-center tw-justify-center tw-gap-2
                focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2
                disabled:tw-opacity-50 disabled:tw-cursor-not-allowed
                ${variant === 'default' ? 'tw-mr-1 last:tw-mr-0' : ''}
                tw-min-w-[3.5rem] sm:tw-min-w-[5rem] tw-py-3 sm:tw-py-2 tw-px-4
              `}
              role="tab"
              aria-selected={activeTab === index}
              aria-controls={`tabpanel-${index}`}
              id={`tab-${index}`}
              onClick={() => handleTabClick(index)}
              onKeyDown={(e) => handleKeyDown(e, index)}
              disabled={tab.disabled}
              tabIndex={activeTab === index ? 0 : -1}
              // Mobile: increase touch target
              style={{ touchAction: 'manipulation' }}
            >
              {tab.icon && (
                <span className={`${sizeClasses[size].icon} tw-flex-shrink-0 tw-flex tw-items-center tw-justify-center`}>
                  {tab.icon}
                </span>
              )}
              <span className="tw-truncate">{tab.label}</span>
              {tab.badge && (
                <span className={`
                  tw-bg-red-100 tw-text-red-800 tw-font-medium tw-rounded-full
                  ${sizeClasses[size].badge} tw-ml-1
                `}>
                  {tab.badge}
                </span>
              )}
            </button>
          ))}
          
          {/* Animated indicator for underline variant */}
          {variant === 'underline' && (
            <div
              ref={indicatorRef}
              className="tw-absolute tw-bottom-0 tw-h-1 md:tw-h-0.5 tw-bg-blue-600 tw-transition-all tw-duration-200 tw-ease-in-out"
              // Mobile: thicker indicator
            />
          )}
        </div>
      </div>

      {/* Tab Content */}
      <div className="tw-mt-0">
        {tabs.map((tab, index) => (
          <div
            key={index}
            role="tabpanel"
            id={`tabpanel-${index}`}
            aria-labelledby={`tab-${index}`}
            className={`
              ${activeTab === index ? 'tw-block' : 'tw-hidden'}
              ${sizeClasses[size].content}
              tw-bg-white tw-border tw-border-gray-200
              ${variant === 'default' ? 'tw-border-t-0' : 'tw-rounded-lg'}
            `}
          >
            {typeof tab.content === 'function' ? tab.content() : tab.content}
          </div>
        ))}
      </div>

      {/* Mobile swipe indicator */}
      {isMobile && tabs.length > 1 && (
        <div className="tw-flex tw-justify-center tw-mt-4 tw-gap-1">
          {tabs.map((_, index) => (
            <div
              key={index}
              className={`tw-w-2 tw-h-2 tw-rounded-full tw-transition-all tw-duration-200 ${
                activeTab === index ? 'tw-bg-blue-600' : 'tw-bg-gray-300'
              }`}
            />
          ))}
        </div>
      )}
    </div>
  );
};

export default Tab;
