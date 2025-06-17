import React from 'react';
import useBackupStore from '../util/store';
import BackupConfigModal from './BackupConfigModal';

const BackupTableTools = ({ onFilterChange, selectedBackups, onDeleteBackups }) => {
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

  return (
    <div className="flex flex-wrap items-center justify-between gap-4 p-4 bg-white border-b border-gray-200">
      <div className="flex items-center gap-4">
        <button
          onClick={handleCreateBackup}
          className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg
            className="-ml-1 mr-2 h-5 w-5"
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

        <BackupConfigModal isOpen={isConfigModalOpen} onClose={() => setIsConfigModalOpen(false)} />

        <button
          onClick={handleDeleteBackups}
          disabled={selectedBackups.length === 0}
          className={`inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white ${
            selectedBackups.length === 0
              ? 'bg-gray-400 cursor-not-allowed'
              : 'bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500'
          }`}
        >
          <svg
            className="-ml-1 mr-2 h-5 w-5"
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
          className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          <svg
            className="-ml-1 mr-2 h-5 w-5"
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

      <div className="flex items-center gap-4">
        <div className="relative">
          <input
            type="date"
            value={dateFilter}
            onChange={handleDateFilterChange}
            className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          />
        </div>
      </div>
    </div>
  );
};

export default BackupTableTools;
