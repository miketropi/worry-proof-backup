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
 * A modern, flexible modal component built with Tailwind CSS (tw- prefix)
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

  // Size classes mapping with tw- prefix
  const sizeClasses = {
    sm: 'tw-max-w-sm',
    md: 'tw-max-w-md',
    lg: 'tw-max-w-lg',
    xl: 'tw-max-w-xl',
    full: 'tw-max-w-full tw-mx-4',
  };

  useEffect(() => {
    if (isOpen) {
      previousActiveElement.current = document.activeElement;
      modalRef.current?.focus();

      const handleEscKey = (event) => {
        if (event.key === 'Escape') {
          onClose();
        }
      };

      document.addEventListener('keydown', handleEscKey);
      document.body.style.overflow = 'hidden';

      return () => {
        document.removeEventListener('keydown', handleEscKey);
        document.body.style.overflow = '';
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
      className="tw-fixed tw-inset-0 tw-z-50 tw-overflow-y-auto tw-font-space-mono"
      onClick={handleBackdropClick}
      role="presentation"
    >
      {/* Backdrop */}
      <div className="tw-fixed tw-inset-0 tw-bg-black tw-bg-opacity-50 tw-transition-opacity" />

      {/* Modal */}
      <div className="tw-flex tw-min-h-full tw-items-center tw-justify-center tw-p-4 tw-text-center">
        <div
          ref={modalRef}
          className={`tw-relative tw-transform tw-overflow-hidden tw-rounded-lg tw-bg-white tw-text-left tw-shadow-xl tw-transition-all sm:tw-my-8 tw-w-full ${sizeClasses[size]} ${className}`}
          role="dialog"
          aria-modal="true"
          aria-labelledby="modal-title"
          tabIndex="-1"
        >
          {/* Header */}
          <div className="tw-flex tw-items-center tw-justify-between tw-border-b tw-border-gray-200 tw-px-6 tw-py-4">
            {title && (
              <h3 id="modal-title" className="tw-text-lg tw-font-semibold tw-text-gray-900">
                {title}
              </h3>
            )}
            {showCloseButton && (
              <button
                onClick={onClose}
                className="tw-rounded-md tw-p-2 tw-text-gray-400 hover:tw-bg-gray-100 hover:tw-text-gray-500 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2"
                aria-label="Close modal"
              >
                <svg
                  className="tw-h-6 tw-w-6"
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
          <div className="tw-px-6 tw-py-4">
            {children}
          </div>
        </div>
      </div>
    </div>
  );

  return createPortal(modalContent, document.body);
};

export default Modal;
