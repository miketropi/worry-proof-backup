import React from 'react';
import { AlertCircle, CheckCircle, X } from 'lucide-react';

const Notification = ({ type = 'info', title, children, onClose }) => {
  const getIcon = () => {
    switch (type) {
      case 'success':
        return (
          <CheckCircle className="tw-h-5 tw-w-5 tw-text-green-400" />
        );
      case 'warning':
        return (
          <AlertCircle className="tw-h-5 tw-w-5 tw-text-yellow-400" />
        );
      case 'error':
        return (
          <AlertCircle className="tw-h-5 tw-w-5 tw-text-red-400" />
        );
      default:
        return (
          <AlertCircle className="tw-h-5 tw-w-5 tw-text-blue-400" />
        );
    }
  };

  const getStyles = () => {
    switch (type) {
      case 'success':
        return 'tw-bg-green-50 tw-border-green-200 tw-text-green-800';
      case 'warning':
        return 'tw-bg-yellow-50 tw-border-yellow-200 tw-text-yellow-800';
      case 'error':
        return 'tw-bg-red-50 tw-border-red-200 tw-text-red-800';
      default:
        return 'tw-bg-blue-50 tw-border-blue-200 tw-text-blue-800';
    }
  };

  return (
    <div className={`tw-border tw-rounded-lg tw-p-4 tw-mb-6 ${getStyles()}`}>
      <div className="tw-flex tw-items-start tw-space-x-3">
        <div className="tw-flex-shrink-0">
          {getIcon()}
        </div>
        <div className="tw-flex-1 tw-min-w-0">
          {title && (
            <h3 className="tw-text-sm tw-font-bold tw-mb-2">
              {title}
            </h3>
          )}
          <div className="tw-text-sm tw-space-y-2">
            {children}
          </div>
        </div>
        {onClose && (
          <div className="tw-flex-shrink-0">
            <button
              onClick={onClose}
              className="tw-text-gray-400 hover:tw-text-gray-600 tw-transition-colors"
            >
              <svg className="tw-h-5 tw-w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default Notification;
