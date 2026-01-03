import React, { createContext, useContext, useState, useCallback, useRef } from 'react';
import Modal from './Modal';

const Confirm = ({
  isOpen,
  onClose,
  onConfirm,
  title = 'Are you sure?',
  description = '',
  confirmText = 'Confirm',
  cancelText = 'Cancel',
  danger = false,
  loading = false,
}) => {
  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={title}
      size="sm"
      showCloseButton={!loading}
    >
      <div className="tw-space-y-6">
        {description && (
          <div className="tw-text-gray-700 tw-text-base tw-leading-relaxed">
            {description}
          </div>
        )}
        <div className="tw-flex tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
          <button
            type="button"
            onClick={onClose}
            disabled={loading}
            className="tw-px-5 tw-py-2.5 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-shadow-sm hover:tw-bg-gray-50 hover:tw-border-gray-300 tw-transition-colors focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed tw-font-space-mono"
          >
            {cancelText}
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={loading}
            className={`tw-px-5 tw-py-2.5 tw-text-sm tw-font-medium tw-rounded-lg tw-shadow-sm tw-transition-colors focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 tw-font-space-mono 
              ${danger
                ? 'tw-text-white tw-bg-red-600 hover:tw-bg-red-700 focus:tw-ring-red-500'
                : 'tw-text-white tw-bg-blue-600 hover:tw-bg-blue-700 focus:tw-ring-blue-500'}
              disabled:tw-opacity-50 disabled:tw-cursor-not-allowed disabled:hover:tw-bg-blue-600`}
          >
            {loading ? (
              <span className="tw-flex tw-items-center tw-gap-2">
                <svg className="tw-animate-spin tw-h-5 tw-w-5 tw-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                {confirmText}
              </span>
            ) : (
              confirmText
            )}
          </button>
        </div>
      </div>
    </Modal>
  );
};

// --- Context & Provider ---
const ConfirmContext = createContext();

export const ConfirmProvider = ({ children }) => {
  const [state, setState] = useState({
    isOpen: false,
    options: {},
    loading: false,
  });

  const resolveRef = useRef(null);
  const rejectRef = useRef(null);

  const openConfirm = useCallback((options = {}) => {
    return new Promise((resolve, reject) => {
      resolveRef.current = resolve;
      rejectRef.current = reject;
      setState({
        isOpen: true,
        options,
        loading: false,
      });
    });
  }, []);

  const handleClose = useCallback(() => {
    if (rejectRef.current) {
      rejectRef.current(new Error('cancelled'));
    }
    resolveRef.current = null;
    rejectRef.current = null;
    setState((s) => ({ ...s, isOpen: false, loading: false }));
  }, []);

  const handleConfirm = useCallback(async () => {
    setState((s) => ({ ...s, loading: true }));
    try {
      if (resolveRef.current) {
        resolveRef.current(true);
      }
    } finally {
      resolveRef.current = null;
      rejectRef.current = null;
      setState((s) => ({ ...s, isOpen: false, loading: false }));
    }
  }, []);

  return (
    <ConfirmContext.Provider value={openConfirm}>
      {children}
      <Confirm
        isOpen={state.isOpen}
        onClose={handleClose}
        onConfirm={handleConfirm}
        loading={state.loading || state.options.loading}
        {...state.options}
      />
    </ConfirmContext.Provider>
  );
};

// --- useConfirm Hook ---
export const useConfirm = () => {
  const openConfirm = useContext(ConfirmContext);
  if (!openConfirm) throw new Error('useConfirm must be used within a ConfirmProvider');
  return openConfirm;
};

export default Confirm;
