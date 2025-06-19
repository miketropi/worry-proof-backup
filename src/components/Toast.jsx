/**
 * Toast Notification System (Context + Hook)
 * 
 * Features:
 * - Multiple toast types: success, error, warning, info
 * - Auto-dismiss with customizable duration
 * - Stacked notifications
 * - Accessible and responsive
 * 
 * Usage:
 * 1. Wrap your app with ToastProvider:
 * 
 *    import { ToastProvider } from './Toast';
 *    
 *    <ToastProvider>
 *      <App />
 *    </ToastProvider>
 * 
 * 2. Use the useToast hook anywhere:
 * 
 *    import { useToast } from './Toast';
 * 
 *    const toast = useToast();
 * 
 *    // Show success toast
 *    toast({
 *      message: 'Backup created successfully!',
 *      type: 'success',  // 'success' | 'error' | 'warning' | 'info'
 *      duration: 5000,   // milliseconds, default: 5000, use 0 for no auto-dismiss
 *    });
 * 
 *    // Show error toast
 *    toast({
 *      message: 'Failed to create backup',
 *      type: 'error',
 *    });
 */

import React, { createContext, useContext, useCallback, useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { 
  CircleCheck, 
  CircleX, 
  TriangleAlert, 
  Info, 
  X, 
} from 'lucide-react';

const ToastContext = createContext(null);

// Toast types with their respective styles and icons
const TOAST_TYPES = {
  success: {
    icon: <CircleCheck color="white" className="tw-w-5 tw-h-5" />,
    style: 'tw-text-green-500 tw-bg-green-50 tw-border-green-200',
  },
  error: {
    icon: <CircleX color="white" className="tw-w-5 tw-h-5" />,
    style: 'tw-text-red-500 tw-bg-red-50 tw-border-red-200',
  },
  warning: {
    icon: <TriangleAlert color="white" className="tw-w-5 tw-h-5" />,
    style: 'tw-text-yellow-500 tw-bg-yellow-50 tw-border-yellow-200',
  },
  info: {
    icon: <Info color="white" className="tw-w-5 tw-h-5" />,
    style: 'tw-text-blue-500 tw-bg-blue-50 tw-border-blue-200',
  },
};

// Individual toast notification component
const Toast = ({ message, type = 'info', onDismiss, onPause, onResume }) => {
  const { icon, style } = TOAST_TYPES[type] || TOAST_TYPES.info;

  return (
    <div
      className={`tw-flex tw-items-center tw-p-6 tw-mb-4 tw-border tw-rounded-md tw-backdrop-blur-sm tw-bg-white/95 tw-transform tw-transition-all tw-duration-500 tw-ease-out ${style}`}
      role="alert"
      onMouseEnter={onPause}
      onMouseLeave={onResume}
    >
      <div className="tw-flex-shrink-0 tw-p-2 tw-rounded-full tw-bg-opacity-10 tw-bg-current">{icon}</div>
      <div className="tw-ml-4 tw-mr-6 tw-flex-1 tw-text-sm tw-font-medium tw-leading-relaxed">{message}</div>
      <button
        onClick={onDismiss}
        className="tw-flex-shrink-0 tw-rounded-full tw-p-2 tw-inline-flex tw-items-center tw-justify-center tw-text-gray-400 hover:tw-text-gray-600 hover:tw-bg-gray-100 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-transition-all tw-duration-200"
      >
        <span className="tw-sr-only">Close</span>
        <X className="tw-h-5 tw-w-5" />
      </button>
    </div>
  );
};

// Toast container that renders all active toasts
const ToastContainer = ({ toasts, removeToast, pauseToast, resumeToast }) => {
  return createPortal(
    <div className="tw-fixed tw-top-10 tw-right-4 tw-z-50 tw-w-full tw-max-w-sm tw-space-y-2 tw-font-space-mono">
      {toasts.map((toast) => (
        <div
          key={toast.id}
          className="tw-transform tw-transition-all tw-duration-300 tw-ease-in-out"
        >
          <Toast
            message={toast.message}
            type={toast.type}
            onDismiss={() => removeToast(toast.id)}
            onPause={() => pauseToast(toast.id)}
            onResume={() => resumeToast(toast.id)}
          />
        </div>
      ))}
    </div>,
    document.body
  );
};

// Provider component that manages toast state
export const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);
  // Store timer and remaining time for each toast
  const timers = React.useRef({});

  const removeToast = useCallback((id) => {
    setToasts((prevToasts) => prevToasts.filter((toast) => toast.id !== id));
    if (timers.current[id]) {
      clearTimeout(timers.current[id].timeoutId);
      delete timers.current[id];
    }
  }, []);

  const pauseToast = useCallback((id) => {
    const timer = timers.current[id];
    if (timer && !timer.paused) {
      clearTimeout(timer.timeoutId);
      timer.paused = true;
      timer.remaining = timer.end - Date.now();
    }
  }, []);

  const resumeToast = useCallback((id) => {
    const timer = timers.current[id];
    if (timer && timer.paused) {
      timer.paused = false;
      timer.end = Date.now() + timer.remaining;
      timer.timeoutId = setTimeout(() => removeToast(id), timer.remaining);
    }
  }, [removeToast]);

  const addToast = useCallback(({ message, type = 'info', duration = 5000 }) => {
    const id = Date.now().toString() + Math.random().toString(36).substr(2, 5);
    setToasts((prevToasts) => [
      ...prevToasts,
      {
        id,
        message,
        type,
      },
    ]);

    if (duration > 0) {
      const end = Date.now() + duration;
      const timeoutId = setTimeout(() => removeToast(id), duration);
      timers.current[id] = {
        timeoutId,
        end,
        remaining: duration,
        paused: false,
      };
    }

    return id;
  }, [removeToast]);

  useEffect(() => {
    // Clean up timers on unmount
    return () => {
      Object.values(timers.current).forEach((timer) => {
        clearTimeout(timer.timeoutId);
      });
      timers.current = {};
    };
  }, []);

  return (
    <ToastContext.Provider value={addToast}>
      {children}
      <ToastContainer
        toasts={toasts}
        removeToast={removeToast}
        pauseToast={pauseToast}
        resumeToast={resumeToast}
      />
    </ToastContext.Provider>
  );
};

// Hook to use toast
export const useToast = () => {
  const addToast = useContext(ToastContext);
  if (!addToast) {
    throw new Error('useToast must be used within a ToastProvider');
  }

  return useCallback(
    ({ message, type, duration }) => addToast({ message, type, duration }),
    [addToast]
  );
};

export default Toast;
