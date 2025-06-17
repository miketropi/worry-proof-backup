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
  { id: 'database', label: 'Database' },
  { id: 'plugin', label: 'Plugins' },
  { id: 'theme', label: 'Themes' },
  { id: 'folder-uploads', label: 'Uploads Folder' },
];

const BackupConfigModal = ({ isOpen, onClose, onSave }) => {
  const [config, setConfig] = useState({
    name: '',
    types: [],
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
      size="md"
    >
      <form onSubmit={handleSubmit} className="tw-space-y-8">
        {/* Backup Name Input */}
        <div className="tw-space-y-2">
          <label
            htmlFor="backup-name"
            className="tw-block tw-text-sm tw-font-semibold tw-text-gray-800"
          >
            Backup Name
          </label>
          <input
            type="text"
            id="backup-name"
            value={config.name}
            onChange={handleNameChange}
            className="tw-block tw-w-full tw-p-4 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50 tw-text-gray-900 tw-placeholder-gray-400 tw-transition-colors focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-200 focus:tw-bg-white"
            placeholder="Enter backup name"
            required
          />
        </div>

        {/* Backup Types Selection */}
        <div className="tw-space-y-3">
          <label className="tw-block tw-text-sm tw-font-semibold tw-text-gray-800">
            Backup Types
          </label>
          <div className="tw-grid tw-gap-3 tw-p-4 tw-bg-gray-50 tw-rounded-lg tw-border tw-border-gray-200">
            {BACKUP_TYPES.map((type) => (
              <label
                key={type.id}
                className="tw-flex tw-items-center tw-p-3 tw-rounded-md hover:tw-bg-white tw-transition-colors tw-cursor-pointer"
              >
                <input
                  type="checkbox"
                  checked={config.types.includes(type.id)}
                  onChange={() => handleTypeChange(type.id)}
                  className="tw-h-5 tw-w-5 tw-rounded-md tw-border-gray-300 tw-text-blue-600 focus:tw-ring-2 focus:tw-ring-blue-200"
                />
                <span className="tw-ml-3 tw-text-sm tw-font-medium tw-text-gray-700">{type.label}</span>
              </label>
            ))}
          </div>
        </div>

        {/* Action Buttons */}
        <div className="tw-flex tw-justify-end tw-gap-3 tw-pt-6 tw-border-t tw-border-gray-200">
          <button
            type="button"
            onClick={onClose}
            className="tw-px-5 tw-py-2.5 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-shadow-sm hover:tw-bg-gray-50 hover:tw-border-gray-300 tw-transition-colors focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={!config.name || config.types.length === 0}
            className="tw-px-5 tw-py-2.5 tw-text-sm tw-font-medium tw-text-white tw-bg-blue-600 tw-rounded-lg tw-shadow-sm hover:tw-bg-blue-700 tw-transition-colors focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed disabled:hover:tw-bg-blue-600"
          >
            Start Backup
          </button>
        </div>
      </form>
    </Modal>
  );
};

export default BackupConfigModal;
