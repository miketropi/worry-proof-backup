import React from 'react';

const LoadingSkeleton = () => (
  <div className="tw-animate-pulse">
    <div className="tw-h-8 tw-bg-gray-200 tw-rounded tw-mb-4"></div>
    <div className="tw-space-y-3">
      {[1, 2, 3].map((i) => (
        <div key={i} className="tw-h-16 tw-bg-gray-200 tw-rounded"></div>
      ))}
    </div>
  </div>
);

export default LoadingSkeleton; 