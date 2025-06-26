import React from 'react';
import useBackupStore from '../util/store';
import BackupConfigModal from './BackupConfigModal';
import { FileUp, Trash2 } from 'lucide-react';

export const NewBackupButton = () => {
  const { buildBackupProcess } = useBackupStore();
  const [isConfigModalOpen, setIsConfigModalOpen] = React.useState(false);

  const handleCreateBackup = async () => {
    // TODO: Implement create backup functionality
    console.log('Create backup clicked');
    setIsConfigModalOpen(true);
  };

  const handleSaveBackup = async (config) => {
    console.log('Save backup:', config);
    buildBackupProcess(config);
  };

  return <>
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
  </>;
};

const BackupTableTools = ({ onFilterChange, selectedBackups, onDeleteBackups, onUploadBackup }) => {
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
    // console.log('Upload backup clicked');
    onUploadBackup();
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
          <Trash2 className="tw-w-4 tw-h-4 tw-mr-2" />
          Delete Selected
        </button>

        <button
          onClick={handleUploadBackup}
          className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-green-600 hover:tw-bg-green-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-green-500"
        >
          <FileUp className="tw-w-4 tw-h-4 tw-mr-2" />
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
