import React from 'react';
import useBackupStore from '../util/store';
import BackupConfigModal from './BackupConfigModal';

const BackupTableTools = ({ onFilterChange, selectedBackups, onDeleteBackups }) => {
  const { buildBackupProcess } = useBackupStore();
  const [dateFilter, setDateFilter] = React.useState('');
  const [isConfigModalOpen, setIsConfigModalOpen] = React.useState(false);

  const handleDateFilterChange = (e) => {
    const value = e.target.value;
    setDateFilter(value);
    onFilterChange(value);
  };

  const handleCreateBackup = async () => {
    // TODO: Implement create backup functionality
    console.log('Create backup clicked');
    setIsConfigModalOpen(true);
  };

  const handleDeleteBackups = async () => {
    if (selectedBackups.length === 0) {
      alert('Please select backups to delete');
      return;
    }
    // TODO: Implement delete backup functionality
    console.log('Delete backups:', selectedBackups);
    onDeleteBackups(selectedBackups);
  };

  const handleUploadBackup = async () => {
    // TODO: Implement upload backup functionality
    console.log('Upload backup clicked');
  };

  const handleSaveBackup = async (config) => {
    console.log('Save backup:', config);
    buildBackupProcess(config);
  };

  return (
    <div className="tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-4 tw-p-4 tw-bg-white tw-border-b tw-border-gray-200">
      <div className="tw-flex tw-items-center tw-gap-4">
        <button
          onClick={handleCreateBackup}
          className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-blue-600 hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
        >
          <svg
            className="tw--ml-1 tw-mr-2 tw-h-5 tw-w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 6v6m0 0v6m0-6h6m-6 0H6"
            />
          </svg>
          Create Backup
        </button>

        <BackupConfigModal 
          isOpen={isConfigModalOpen} 
          onClose={() => setIsConfigModalOpen(false)}
          onSave={handleSaveBackup}
        />

        <button
          onClick={handleDeleteBackups}
          disabled={selectedBackups.length === 0}
          className={`tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-sm tw-font-medium tw-rounded-md tw-text-white ${
            selectedBackups.length === 0
              ? 'tw-bg-gray-400 tw-cursor-not-allowed'
              : 'tw-bg-red-600 hover:tw-bg-red-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-red-500'
          }`}
        >
          <svg
            className="tw--ml-1 tw-mr-2 tw-h-5 tw-w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            />
          </svg>
          Delete Selected
        </button>

        <button
          onClick={handleUploadBackup}
          className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-green-600 hover:tw-bg-green-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-green-500"
        >
          <svg
            className="tw--ml-1 tw-mr-2 tw-h-5 tw-w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v6m0 0l-3-3m3 3l3-3M12 4v8"
            />
          </svg>
          Upload Backup
        </button>
      </div>

      <div className="tw-flex tw-items-center tw-gap-4">
        <div className="tw-relative">
          <input
            type="date"
            value={dateFilter}
            onChange={handleDateFilterChange}
            className="tw-block tw-w-full tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-blue-500 focus:tw-ring-blue-500 sm:tw-text-sm"
          />
        </div>
      </div>
    </div>
  );
};

export default BackupTableTools;
