import React, { useState } from 'react';
import Modal from './Modal';

/**
 * @typedef {Object} BackupConfigModalProps
 * @property {boolean} isOpen - Controls modal visibility
 * @property {() => void} onClose - Function to handle modal closing
 * @property {(config: BackupConfig) => void} onSave - Function to handle saving backup configuration
 */

/**
 * @typedef {Object} BackupConfig
 * @property {string} name - Backup name
 * @property {string[]} types - Selected backup types
 */

const BACKUP_TYPES = [
  { id: 'database', label: 'Database', description: 'WordPress database backup', icon: '🗄️' },
  { id: 'plugin', label: 'Plugins', description: 'All installed plugins', icon: '🔌' },
  { id: 'theme', label: 'Themes', description: 'All installed themes', icon: '🎨' },
  { id: 'uploads', label: 'Uploads Folder', description: 'Media files and uploads', icon: '📁' },
];

const BackupConfigModal = ({ isOpen, onClose, onSave }) => {
  const [config, setConfig] = useState({
    name: '',
    types: ['database'],
  });

  const handleNameChange = (e) => {
    setConfig((prev) => ({
      ...prev,
      name: e.target.value,
    }));
  };

  const handleTypeChange = (typeId) => {
    setConfig((prev) => ({
      ...prev,
      types: prev.types.includes(typeId)
        ? prev.types.filter((id) => id !== typeId)
        : [...prev.types, typeId],
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onSave(config);
    onClose();
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Backup Configuration"
      size="lg"
    >
      <div className="tw-space-y-6">
        {/* Welcome Message */}
        <div className="tw-p-4 tw-bg-gradient-to-r tw-from-blue-50 tw-to-purple-50 tw-border tw-border-blue-200 tw-rounded-lg tw-shadow-sm">
          <div className="tw-flex tw-items-start tw-gap-3">
            <div className="tw-flex-shrink-0 tw-p-2 tw-bg-blue-100 tw-rounded-lg">
              <span className="tw-text-lg">⚡</span>
            </div>
            <div className="tw-flex-1">
              <h3 className="tw-text-sm tw-font-bold tw-text-blue-900 tw-mb-1">
                Let's Set Up Your Backup! 🚀
              </h3>
              <div className="tw-text-sm tw-text-blue-700 tw-leading-relaxed tw-font-space-mono">
                <p>
                  Give your backup a name and choose what to include! 
                  You can select from database, plugins, themes, and uploads folder. 
                  Pick any combination you need! ✨
                </p>
              </div>
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="tw-space-y-8">
          {/* Backup Name Input */}
          <div className="tw-space-y-4">
            <div>
              <h3 className="tw-text-base tw-font-semibold tw-text-gray-900 tw-mb-2">
                Backup Name
              </h3>
              <p className="tw-text-sm tw-text-gray-600">
                Give your backup a descriptive name to easily identify it later
              </p>
            </div>
            
            <div className="tw-max-w-md">
              <input
                type="text"
                id="backup-name"
                value={config.name}
                onChange={handleNameChange}
                className="tw-block tw-w-full tw-p-3 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50 tw-text-gray-900 tw-placeholder-gray-400 tw-transition-colors focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-200 focus:tw-bg-white tw-text-sm"
                placeholder="Enter backup name"
                autoComplete='off'
                required
              />
              {!config.name && (
                <div className="tw-mt-2 tw-text-sm tw-text-red-600 tw-flex tw-items-center tw-gap-1">
                  <svg className="tw-w-4 tw-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Backup name is required
                </div>
              )}
            </div>
          </div>

          {/* Backup Types Selection */}
          <div className="tw-space-y-4">
            <div>
              <h3 className="tw-text-base tw-font-semibold tw-text-gray-900 tw-mb-2">
                Backup Types
              </h3>
              <p className="tw-text-sm tw-text-gray-600">
                Select what you want to include in your backup
              </p>
            </div>
            
            <div className="tw-grid tw-gap-3 sm:tw-grid-cols-1 lg:tw-grid-cols-1">
              {BACKUP_TYPES.map((type) => (
                <label
                  key={type.id}
                  className={`tw-relative tw-flex tw-items-start tw-p-4 tw-rounded-lg tw-border tw-cursor-pointer tw-transition-all hover:tw-shadow-md ${
                    config.types.includes(type.id)
                      ? 'tw-border-green-500 tw-bg-green-50 tw-ring-2 tw-ring-green-200'
                      : 'tw-border-gray-200 tw-bg-gray-50 hover:tw-border-gray-300'
                  }`}
                >
                  <input
                    type="checkbox"
                    checked={config.types.includes(type.id)}
                    onChange={() => handleTypeChange(type.id)}
                    className="tw-sr-only"
                  />
                  <div className="tw-flex tw-items-center tw-gap-3 tw-w-full">
                    <div className="tw-flex-shrink-0 tw-text-xl">
                      {type.icon}
                    </div>
                    <div className="tw-flex-1 tw-min-w-0">
                      <div className="tw-text-sm tw-font-medium tw-text-gray-900">
                        {type.label}
                      </div>
                      <div className="tw-text-xs tw-text-gray-500 tw-leading-tight">
                        {type.description}
                      </div>
                    </div>
                    {config.types.includes(type.id) && (
                      <div className="tw-flex-shrink-0 tw-w-5 tw-h-5 tw-bg-green-500 tw-rounded tw-flex tw-items-center tw-justify-center">
                        <svg className="tw-w-3 tw-h-3 tw-text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      </div>
                    )}
                  </div>
                </label>
              ))}
            </div>
            
            {config.types.length === 0 && (
              <div className="tw-mt-3 tw-text-sm tw-text-red-600 tw-flex tw-items-center tw-gap-2 tw-p-3 tw-bg-red-50 tw-rounded-lg tw-border tw-border-red-200">
                <svg className="tw-w-4 tw-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Please select at least one backup type
              </div>
            )}
          </div>

          {/* Summary */}
          <div className="tw-bg-gray-50 tw-rounded-lg tw-p-4 tw-border tw-border-gray-200">
            <h4 className="tw-text-sm tw-font-semibold tw-text-gray-900 tw-mb-3">Backup Summary</h4>
            <div className="tw-space-y-2 tw-text-sm tw-text-gray-600">
              <div className="tw-flex tw-items-center tw-gap-2">
                <span className="tw-text-gray-400">📝</span>
                <span>Name: <strong className="tw-text-gray-900">{config.name || 'Not specified'}</strong></span>
              </div>
              <div className="tw-flex tw-items-center tw-gap-2">
                <span className="tw-text-gray-400">📦</span>
                <span>Include: <strong className="tw-text-gray-900">{config.types.length} type{config.types.length !== 1 ? 's' : ''}</strong></span>
              </div>
              {config.types.length > 0 && (
                <div className="tw-flex tw-items-center tw-gap-2">
                  <span className="tw-text-gray-400">✅</span>
                  <span>Selected: <strong className="tw-text-gray-900">{config.types.map(id => BACKUP_TYPES.find(t => t.id === id)?.label).join(', ')}</strong></span>
                </div>
              )}
            </div>
          </div>

          {/* Action Buttons */}
          <div className="tw-flex tw-justify-end tw-gap-3 tw-pt-6 tw-border-t tw-border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="tw-px-6 tw-py-2.5 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-shadow-sm hover:tw-bg-gray-50 hover:tw-border-gray-300 tw-transition-colors focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-font-space-mono"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!config.name || config.types.length === 0}
              className="tw-px-6 tw-py-2.5 tw-text-sm tw-font-medium tw-text-white tw-bg-blue-600 tw-rounded-lg tw-shadow-sm hover:tw-bg-blue-700 tw-transition-colors focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed disabled:hover:tw-bg-blue-600 tw-font-space-mono"
            >
              Start Backup
            </button>
          </div>
        </form>
      </div>
    </Modal>
  );
};

export default BackupConfigModal;
