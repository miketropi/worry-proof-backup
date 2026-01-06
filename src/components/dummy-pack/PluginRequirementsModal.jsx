import React from 'react';
import { X, CheckCircle, AlertTriangle, XCircle } from 'lucide-react';

/**
 * PluginRequirementsModal Component
 * 
 * Displays plugin requirements in a modal dialog.
 * 
 * @param {boolean} isOpen - Whether the modal is open
 * @param {Function} onClose - Callback to close the modal
 * @param {Array} requiredPlugins - Array of required plugin objects
 * @param {Object} pluginsRequirements - Result from checkPluginsRequirements
 * @param {Array} activePlugins - Array of currently active plugins
 */
const PluginRequirementsModal = ({ 
  isOpen, 
  onClose, 
  requiredPlugins = [], 
  pluginsRequirements = null,
  activePlugins = []
}) => {
  if (!isOpen) return null;

  const handleBackdropClick = (e) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  const getStatusIcon = (isCompatible, isIncompatible, isMissing) => {
    if (isCompatible) {
      return <CheckCircle className="tw-w-5 tw-h-5 tw-text-green-600" />;
    } else if (isIncompatible) {
      return <AlertTriangle className="tw-w-5 tw-h-5 tw-text-yellow-600" />;
    } else {
      return <XCircle className="tw-w-5 tw-h-5 tw-text-red-600" />;
    }
  };

  const getStatusBadge = (isCompatible, isIncompatible, isMissing) => {
    if (isCompatible) {
      return (
        <span className="tw-inline-flex tw-items-center tw-gap-1 tw-bg-green-100 tw-text-green-700 tw-px-2.5 tw-py-1 tw-rounded-full tw-text-xs tw-font-semibold">
          <CheckCircle className="tw-w-3.5 tw-h-3.5" />
          Compatible
        </span>
      );
    } else if (isIncompatible) {
      return (
        <span className="tw-inline-flex tw-items-center tw-gap-1 tw-bg-yellow-100 tw-text-yellow-700 tw-px-2.5 tw-py-1 tw-rounded-full tw-text-xs tw-font-semibold">
          <AlertTriangle className="tw-w-3.5 tw-h-3.5" />
          Version Mismatch
        </span>
      );
    } else {
      return (
        <span className="tw-inline-flex tw-items-center tw-gap-1 tw-bg-red-100 tw-text-red-700 tw-px-2.5 tw-py-1 tw-rounded-full tw-text-xs tw-font-semibold">
          <XCircle className="tw-w-3.5 tw-h-3.5" />
          Not Installed
        </span>
      );
    }
  };

  const allCompatible = pluginsRequirements && pluginsRequirements.status === 'ok';

  return (
    <div
      className="tw-fixed tw-inset-0 tw-z-50 tw-overflow-y-auto"
      onClick={handleBackdropClick}
    >
      {/* Backdrop */}
      <div className="tw-fixed tw-inset-0 tw-bg-black tw-bg-opacity-50 tw-transition-opacity" />

      {/* Modal */}
      <div className="tw-flex tw-items-center tw-justify-center tw-min-h-screen tw-px-4 tw-py-8">
        <div className="tw-relative tw-bg-white tw-rounded-lg tw-shadow-xl tw-max-w-2xl tw-w-full tw-max-h-[90vh] tw-overflow-hidden tw-flex tw-flex-col">
          {/* Header */}
          <div className="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-200">
            <div>
              <h2 className="tw-text-lg tw-font-semibold tw-text-gray-900">
                Required Plugins
              </h2>
              <p className="tw-text-sm tw-text-gray-500 tw-mt-1">
                {allCompatible 
                  ? 'All required plugins are installed and compatible' 
                  : 'Some plugins need to be installed or updated'}
              </p>
            </div>
            <button
              onClick={onClose}
              className="tw-text-gray-400 hover:tw-text-gray-500 tw-transition-colors"
            >
              <X className="tw-w-5 tw-h-5" />
            </button>
          </div>

          {/* Content */}
          <div className="tw-flex-1 tw-overflow-y-auto tw-px-6 tw-py-4">
            <div className="tw-space-y-3">
              {requiredPlugins.map((reqPlugin, index) => {
                const isMissing = pluginsRequirements?.missing.includes(reqPlugin.slug) || false;
                const incompatible = pluginsRequirements?.incompatible.find(
                  (inc) => inc.slug === reqPlugin.slug
                );
                const isCompatible = !isMissing && !incompatible;
                
                // Find installed plugin info
                const installedPlugin = activePlugins.find(
                  (p) => p.slug === reqPlugin.slug
                );

                return (
                  <div
                    key={index}
                    className="tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-bg-gray-50 hover:tw-bg-gray-100 tw-transition-colors"
                  >
                    <div className="tw-flex tw-items-start tw-gap-4">
                      {/* Status Icon */}
                      <div className="tw-flex-shrink-0 tw-mt-0.5">
                        {getStatusIcon(isCompatible, !!incompatible, isMissing)}
                      </div>

                      {/* Plugin Info */}
                      <div className="tw-flex-1 tw-min-w-0">
                        <div className="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-mb-2">
                          <h3 className="tw-text-base tw-font-semibold tw-text-gray-900">
                            {reqPlugin.name || reqPlugin.slug}
                          </h3>
                          {getStatusBadge(isCompatible, !!incompatible, isMissing)}
                        </div>

                        {/* Plugin Slug */}
                        <p className="tw-text-xs tw-text-gray-500 tw-mb-3 tw-font-mono">
                          {reqPlugin.slug}
                        </p>

                        {/* Version Info */}
                        <div className="tw-space-y-1.5">
                          {isMissing ? (
                            <div className="tw-text-sm tw-text-red-600">
                              <strong>Status:</strong> Plugin is not installed
                            </div>
                          ) : incompatible ? (
                            <div className="tw-space-y-1">
                              <div className="tw-text-sm tw-text-gray-700">
                                <strong>Installed Version:</strong>{' '}
                                <span className="tw-font-mono tw-text-red-600">
                                  {incompatible.installedVersion || 'N/A'}
                                </span>
                              </div>
                              <div className="tw-text-sm tw-text-gray-700">
                                <strong>Required Version:</strong>{' '}
                                <span className="tw-font-mono tw-font-semibold tw-text-yellow-600">
                                  {incompatible.requiredVersion}+
                                </span>
                              </div>
                              <div className="tw-text-xs tw-text-yellow-700 tw-mt-2 tw-p-2 tw-bg-yellow-50 tw-rounded tw-border tw-border-yellow-200">
                                ⚠️ Please update this plugin to the required version or higher.
                              </div>
                            </div>
                          ) : (
                            <div className="tw-text-sm tw-text-gray-700">
                              <strong>Installed Version:</strong>{' '}
                              <span className="tw-font-mono tw-text-green-700 tw-font-semibold">
                                {installedPlugin?.version || 'N/A'}
                              </span>
                            </div>
                          )}

                          {reqPlugin.version && (
                            <div className="tw-text-xs tw-text-gray-500 tw-mt-1">
                              Minimum required: <span className="tw-font-mono">{reqPlugin.version}+</span>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Footer */}
          <div className="tw-px-6 tw-py-4 tw-border-t tw-border-gray-200 tw-bg-gray-50">
            <div className="tw-flex tw-items-center tw-justify-between">
              <div className="tw-text-sm tw-text-gray-600">
                {allCompatible ? (
                  <span className="tw-text-green-700 tw-font-medium">
                    ✓ All requirements met
                  </span>
                ) : (
                  <span className="tw-text-yellow-700 tw-font-medium">
                    ⚠ Please install or update required plugins
                  </span>
                )}
              </div>
              <button
                onClick={onClose}
                className="tw-px-4 tw-py-2 tw-text-sm tw-font-semibold tw-text-white tw-bg-blue-600 tw-rounded-md hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-transition-colors"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PluginRequirementsModal;

