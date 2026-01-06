import React from 'react';
import { CheckCircle2, XCircle, AlertCircle, Download, RefreshCw, AlertTriangle } from 'lucide-react';
import Modal from '../Modal';

/**
 * Template configuration mapping
 * Maps __template values from backend to UI configuration
 */
const TEMPLATE_CONFIG = {
  'success': {
    type: 'success',
    icon: CheckCircle2,
    iconColor: 'tw-text-green-500',
    badgeClass: 'tw-bg-green-50 tw-text-green-700 tw-border-green-200 tw-hidden',
    badge: 'Ready',
    message: null,
    messageStyle: null,
  },
  'auto-install': {
    type: 'auto-install',
    icon: Download,
    iconColor: 'tw-text-blue-500',
    badgeClass: 'tw-bg-blue-50 tw-text-blue-700 tw-border-blue-200',
    badge: 'Will Install',
    message: 'We will install the corresponding version for you.',
    messageStyle: {
      bgClass: 'tw-bg-blue-50',
      borderClass: 'tw-border-blue-200',
      textClass: 'tw-text-blue-700',
      iconClass: 'tw-text-blue-500',
    },
  },
  'warning': {
    type: 'warning',
    icon: AlertTriangle,
    iconColor: 'tw-text-yellow-600',
    badgeClass: 'tw-bg-yellow-50 tw-text-yellow-800 tw-border-yellow-300',
    badge: 'Action Required',
    message: 'This plugin is active with an incompatible version. Please update it to meet the requirements or deactivate/remove it before proceeding.',
    messageStyle: {
      bgClass: 'tw-bg-yellow-50',
      borderClass: 'tw-border-yellow-300',
      textClass: 'tw-text-yellow-900',
      iconClass: 'tw-text-yellow-600',
    },
  },
  'auto-update': {
    type: 'auto-update',
    icon: RefreshCw,
    iconColor: 'tw-text-blue-500',
    badgeClass: 'tw-bg-blue-50 tw-text-blue-700 tw-border-blue-200',
    badge: 'Will Update',
    message: null, // Will be set dynamically with version info
    messageStyle: {
      bgClass: 'tw-bg-blue-50',
      borderClass: 'tw-border-blue-200',
      textClass: 'tw-text-blue-700',
      iconClass: 'tw-text-blue-500',
    },
  },
  'auto-activate': {
    type: 'auto-activate',
    icon: CheckCircle2,
    iconColor: 'tw-text-green-500',
    badgeClass: 'tw-bg-green-50 tw-text-green-700 tw-border-green-200',
    badge: 'Will Activate',
    message: 'Plugin meets version requirements and will be activated.',
    messageStyle: {
      bgClass: 'tw-bg-green-50',
      borderClass: 'tw-border-green-200',
      textClass: 'tw-text-green-700',
      iconClass: 'tw-text-green-500',
    },
  },
  'unknown': {
    type: 'unknown',
    icon: AlertCircle,
    iconColor: 'tw-text-gray-500',
    badgeClass: 'tw-bg-gray-50 tw-text-gray-700 tw-border-gray-200',
    badge: 'Unknown',
    message: 'Unable to determine plugin status.',
    messageStyle: {
      bgClass: 'tw-bg-gray-50',
      borderClass: 'tw-border-gray-200',
      textClass: 'tw-text-gray-700',
      iconClass: 'tw-text-gray-500',
    },
  },
};

/**
 * Get plugin status configuration from __template field
 * 
 * @param {Object} plugin - Plugin data from backend
 * @returns {Object} Status configuration for UI rendering
 */
const getPluginStatus = (plugin) => {
  const template = plugin.__template || 'unknown';
  const config = TEMPLATE_CONFIG[template] || TEMPLATE_CONFIG['unknown'];
  
  // For auto-update template, add dynamic version message if available
  if (template === 'auto-update' && plugin.current_version && plugin.required_version) {
    return {
      ...config,
      message: `Current version (v${plugin.current_version}) is lower than required (v${plugin.required_version}). We will update to the corresponding version for you.`,
    };
  }
  
  return config;
};

/**
 * Plugin Requirements Modal
 * 
 * Displays plugin requirements validation results with clean, minimal styling.
 * 
 * @param {Object} props
 * @param {boolean} props.isOpen - Controls modal visibility
 * @param {Function} props.onClose - Function to close the modal
 * @param {Object} props.validatedRequiredPlugins - Validation results object
 */
const PluginRequirementsModal = ({ isOpen, onClose, validatedRequiredPlugins }) => {
  if (!validatedRequiredPlugins) {
    return null;
  }

  const { passed, results = [] } = validatedRequiredPlugins;
  
  // Count different statuses
  const statusCounts = results.reduce((acc, plugin) => {
    const status = getPluginStatus(plugin);
    acc[status.type] = (acc[status.type] || 0) + 1;
    return acc;
  }, {});

  const hasWarnings = (statusCounts['warning'] || 0) > 0;
  const needsAutoAction = (statusCounts['auto-install'] || 0) + (statusCounts['auto-update'] || 0) > 0;

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Plugin Requirements"
      size="lg"
    >
      {/* Overall Status Banner */}
      <div
        className={`tw-border tw-p-4 tw-mb-4 tw-flex tw-items-start tw-gap-3 ${
          passed
            ? 'tw-bg-green-50 tw-border-green-200'
            : hasWarnings
            ? 'tw-bg-yellow-50 tw-border-yellow-300'
            : 'tw-bg-blue-50 tw-border-blue-200'
        }`}
      >
        {passed ? (
          <CheckCircle2 className="tw-w-5 tw-h-5 tw-text-green-500 tw-mt-0.5 tw-flex-shrink-0" />
        ) : hasWarnings ? (
          <AlertTriangle className="tw-w-5 tw-h-5 tw-text-yellow-600 tw-mt-0.5 tw-flex-shrink-0" />
        ) : (
          <AlertCircle className="tw-w-5 tw-h-5 tw-text-blue-500 tw-mt-0.5 tw-flex-shrink-0" />
        )}
        <div className="tw-text-xs tw-leading-relaxed tw-font-space-mono">
          {passed ? (
            <span className="tw-text-green-900">
              <strong className="tw-font-semibold">All requirements met!</strong> All {results.length} required plugins are ready.
            </span>
          ) : (
            <>
              <span className={hasWarnings ? 'tw-text-yellow-900' : 'tw-text-blue-900'}>
                <strong className="tw-font-semibold">
                  {hasWarnings ? 'Manual action required:' : 'Ready to proceed:'}
                </strong>{' '}
                {hasWarnings && (
                  <>
                    {statusCounts['warning']} plugin{statusCounts['warning'] > 1 ? 's' : ''} need{statusCounts['warning'] === 1 ? 's' : ''} manual update or deactivation.
                  </>
                )}
                {needsAutoAction && (
                  <>
                    {hasWarnings && ' '}
                    {statusCounts['auto-install'] || 0} plugin{(statusCounts['auto-install'] || 0) !== 1 ? 's' : ''} will be installed and {statusCounts['auto-update'] || 0} will be updated automatically.
                  </>
                )}
              </span>
            </>
          )}
        </div>
      </div>

      {/* Plugin List */}
      <div className="tw-space-y-2 tw-max-h-[400px] tw-overflow-y-auto">
        {results.map((plugin, index) => {
          const status = getPluginStatus(plugin);
          const StatusIcon = status.icon;
          
          return (
            <div
              key={plugin.slug || index}
              className="tw-bg-white tw-border tw-border-gray-200 tw-p-3 tw-flex tw-items-start tw-gap-3"
            >
              {/* Status Icon */}
              <StatusIcon className={`tw-w-4 tw-h-4 ${status.iconColor} tw-mt-0.5 tw-flex-shrink-0`} />

              {/* Plugin Info */}
              <div className="tw-flex-1 tw-min-w-0">
                <div className="tw-flex tw-items-center tw-justify-between tw-gap-2">
                  <h5 className="tw-text-sm tw-font-semibold tw-text-gray-900 tw-truncate">
                    {plugin.name}
                  </h5>
                  <span className={`tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-border tw-rounded-full ${status.badgeClass} tw-flex-shrink-0`}>
                    {status.badge}
                  </span>
                </div>

                {/* Version Info */}
                <div className="tw-flex tw-items-center tw-gap-3 tw-mt-1 tw-text-xs tw-font-space-mono tw-text-gray-600">
                  <span>
                    Required: <span className="tw-text-gray-900">v{plugin.required_version}</span>
                  </span>
                  {plugin.installed && (
                    <>
                      <span className="tw-text-gray-400">•</span>
                      <span>
                        Current: <span className={plugin.passed ? 'tw-text-green-600' : status.type === 'warning' ? 'tw-text-yellow-700' : 'tw-text-blue-600'}>v{plugin.current_version}</span>
                      </span>
                      <span className="tw-text-gray-400">•</span>
                      <span className={plugin.active ? 'tw-text-green-600' : 'tw-text-gray-500'}>
                        {plugin.active ? 'Active' : 'Inactive'}
                      </span>
                    </>
                  )}
                </div>

                {/* Status Message */}
                {status.message && status.messageStyle && (
                  <div className={`tw-mt-2 tw-p-2 tw-border tw-flex tw-items-start tw-gap-2 ${status.messageStyle.bgClass} ${status.messageStyle.borderClass}`}>
                    <AlertCircle className={`tw-w-3 tw-h-3 tw-mt-0.5 tw-flex-shrink-0 ${status.messageStyle.iconClass}`} />
                    <span className={`tw-text-xs tw-leading-relaxed ${status.messageStyle.textClass} tw-font-space-mono`}>
                      {status.message}
                    </span>
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Action Buttons */}
      <div className="tw-flex tw-justify-between tw-items-center tw-gap-2 tw-mt-4 tw-pt-4 tw-border-t tw-border-gray-200">
        
        
        {/* Buttons */}
        <div className="tw-flex tw-gap-2 tw-ml-auto">
          <button
            type="button"
            onClick={onClose}
            className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-700 tw-rounded-md tw-font-medium tw-shadow-sm tw-transition-all tw-duration-150 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-gray-400 tw-mr-2"
          >
            Close
          </button>
          {!passed && hasWarnings && (
            <button
              type="button"
              onClick={() => {
                window.location.href = '/wp-admin/plugins.php';
              }}
              className="tw-inline-flex tw-items-center tw-px-5 tw-py-2.5 tw-bg-blue-600 hover:tw-bg-blue-700 tw-text-white tw-rounded-md tw-font-semibold tw-shadow-sm tw-transition-all tw-duration-150 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
            >
              Manage Plugins
            </button>
          )}
        </div>
      </div>
    </Modal>
  );
};

export default PluginRequirementsModal;
