import React, { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';

/**
 * @typedef {Object} ModalProps
 * @property {boolean} isOpen - Controls modal visibility
 * @property {() => void} onClose - Function to handle modal closing
 * @property {React.ReactNode} children - Modal content
 * @property {string} [title] - Modal title
 * @property {string} [className] - Additional CSS classes
 * @property {boolean} [closeOnBackdropClick=true] - Whether clicking backdrop closes modal
 * @property {boolean} [showCloseButton=true] - Whether to show close button
 * @property {'sm'|'md'|'lg'|'xl'|'full'} [size='md'] - Modal size
 */

/**
 * A modern, flexible modal component built with Tailwind CSS
 * @param {ModalProps} props
 */
const Modal = ({
  isOpen,
  onClose,
  children,
  title,
  className = '',
  closeOnBackdropClick = true,
  showCloseButton = true,
  size = 'md',
}) => {
  const modalRef = useRef(null);
  const previousActiveElement = useRef(null);

  // Size classes mapping
  const sizeClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    full: 'max-w-full mx-4',
  };

  useEffect(() => {
    if (isOpen) {
      // Store the previously focused element
      previousActiveElement.current = document.activeElement;
      
      // Focus the modal when it opens
      modalRef.current?.focus();
      
      // Add event listener for ESC key
      const handleEscKey = (event) => {
        if (event.key === 'Escape') {
          onClose();
        }
      };
      
      document.addEventListener('keydown', handleEscKey);
      
      // Prevent body scrolling when modal is open
      document.body.style.overflow = 'hidden';
      
      return () => {
        document.removeEventListener('keydown', handleEscKey);
        document.body.style.overflow = '';
        // Restore focus to the previous element
        previousActiveElement.current?.focus();
      };
    }
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const handleBackdropClick = (e) => {
    if (closeOnBackdropClick && e.target === e.currentTarget) {
      onClose();
    }
  };

  const modalContent = (
    <div
      className="fixed inset-0 z-50 overflow-y-auto font-space-mono"
      onClick={handleBackdropClick}
      role="presentation"
    >
      {/* Backdrop */}
      <div className="fixed inset-0 bg-black bg-opacity-50 transition-opacity" />
      
      {/* Modal */}
      <div className="flex min-h-full items-center justify-center p-4 text-center">
        <div
          ref={modalRef}
          className={`relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 w-full ${sizeClasses[size]} ${className}`}
          role="dialog"
          aria-modal="true"
          aria-labelledby="modal-title"
          tabIndex="-1"
        >
          {/* Header */}
          <div className="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            {title && (
              <h3 id="modal-title" className="text-lg font-semibold text-gray-900">
                {title}
              </h3>
            )}
            {showCloseButton && (
              <button
                onClick={onClose}
                className="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                aria-label="Close modal"
              >
                <svg
                  className="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  strokeWidth="1.5"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            )}
          </div>

          {/* Content */}
          <div className="px-6 py-4">
            {children}
          </div>
        </div>
      </div>
    </div>
  );

  return createPortal(modalContent, document.body);
};

export default Modal;
