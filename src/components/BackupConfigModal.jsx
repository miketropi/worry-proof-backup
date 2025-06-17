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
      <form onSubmit={handleSubmit} className="space-y-8">
        {/* Backup Name Input */}
        <div className="space-y-2">
          <label
            htmlFor="backup-name"
            className="block text-sm font-semibold text-gray-800"
          >
            Backup Name
          </label>
          <input
            type="text"
            id="backup-name"
            value={config.name}
            onChange={handleNameChange}
            className="block w-full p-4 rounded-lg border border-gray-200 bg-gray-50 text-gray-900 placeholder-gray-400 transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:bg-white"
            placeholder="Enter backup name"
            required
          />
        </div>

        {/* Backup Types Selection */}
        <div className="space-y-3">
          <label className="block text-sm font-semibold text-gray-800">
            Backup Types
          </label>
          <div className="grid gap-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
            {BACKUP_TYPES.map((type) => (
              <label
                key={type.id}
                className="flex items-center p-3 rounded-md hover:bg-white transition-colors cursor-pointer"
              >
                <input
                  type="checkbox"
                  checked={config.types.includes(type.id)}
                  onChange={() => handleTypeChange(type.id)}
                  className="h-5 w-5 rounded-md border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-200"
                />
                <span className="ml-3 text-sm font-medium text-gray-700">{type.label}</span>
              </label>
            ))}
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex justify-end gap-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            onClick={onClose}
            className="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={!config.name || config.types.length === 0}
            className="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600"
          >
            Start Backup
          </button>
        </div>
      </form>
    </Modal>
  );
};

export default BackupConfigModal;
