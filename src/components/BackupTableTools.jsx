import React from 'react';
import useBackupStore from '../util/store';
import BackupConfigModal from './BackupConfigModal';
import { FileUp, Trash2, Clock } from 'lucide-react';

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
      className="tw-inline-flex tw-items-center tw-px-3 sm:tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-xs sm:tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-blue-600 hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-transition-colors tw-duration-200"
    >
      <svg
        className="tw--ml-1 tw-mr-1 sm:tw-mr-2 tw-h-4 tw-w-4 sm:tw-h-5 sm:tw-w-5"
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
      <span className="tw-hidden sm:tw-inline">Create Backup</span>
      <span className="sm:tw-hidden">Create</span>
    </button>

    <BackupConfigModal 
      isOpen={isConfigModalOpen} 
      onClose={() => setIsConfigModalOpen(false)}
      onSave={handleSaveBackup}
    />
  </>;
};

const BackupTableTools = ({ onFilterChange, selectedBackups, onDeleteBackups, onUploadBackup, onBackupSchedule }) => {
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
    <div className="tw-flex tw-flex-col sm:tw-flex-row tw-items-start sm:tw-items-center tw-justify-between tw-gap-3 sm:tw-gap-4 tw-p-3 sm:tw-p-4 tw-bg-white tw-border-b tw-border-gray-200">
      {/* Main action buttons - responsive grid layout */}
      <div className="tw-grid tw-grid-cols-2 sm:tw-flex sm:tw-items-center tw-gap-2 sm:tw-gap-3 tw-w-full sm:tw-w-auto">
        <button
          onClick={handleCreateBackup}
          className="tw-inline-flex tw-items-center tw-justify-center tw-px-3 sm:tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-xs sm:tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-blue-600 hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-transition-colors tw-duration-200 tw-col-span-2 sm:tw-col-span-1"
        >
          <svg
            className="tw-mr-1 sm:tw-mr-2 tw-h-4 tw-w-4 sm:tw-h-5 sm:tw-w-5"
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
          <span className="tw-hidden sm:tw-inline">Create Backup</span>
          <span className="sm:tw-hidden">Create</span>
        </button>

        <button
          onClick={handleDeleteBackups}
          disabled={selectedBackups.length === 0}
          className={`tw-inline-flex tw-items-center tw-justify-center tw-px-3 sm:tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-xs sm:tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-transition-colors tw-duration-200 ${
            selectedBackups.length === 0
              ? 'tw-bg-gray-400 tw-cursor-not-allowed'
              : 'tw-bg-red-600 hover:tw-bg-red-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-red-500'
          }`}
        >
          <Trash2 className="tw-w-4 tw-h-4 tw-mr-1 sm:tw-mr-2" />
          <span className="tw-hidden sm:tw-inline">Delete Selected</span>
          <span className="sm:tw-hidden">Delete</span>
        </button>

        <button
          onClick={handleUploadBackup}
          className="tw-inline-flex tw-items-center tw-justify-center tw-px-3 sm:tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-xs sm:tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-green-600 hover:tw-bg-green-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-green-500 tw-transition-colors tw-duration-200"
        >
          <FileUp className="tw-w-4 tw-h-4 tw-mr-1 sm:tw-mr-2" />
          <span className="tw-hidden sm:tw-inline">Upload Backup</span>
          <span className="sm:tw-hidden">Upload</span>
        </button>

        {/* Backup schedule button */}
        <button
          onClick={e => {
            e.preventDefault();
            onBackupSchedule();
          }}
          className="tw-inline-flex tw-items-center tw-justify-center tw-px-3 sm:tw-px-4 tw-py-2 tw-border tw-border-transparent tw-shadow-sm tw-text-xs sm:tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-yellow-600 hover:tw-bg-yellow-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-yellow-500 tw-transition-colors tw-duration-200"
        >
          <Clock className="tw-w-4 tw-h-4 tw-mr-1 sm:tw-mr-2" />
          <span className="tw-hidden sm:tw-inline">Backup Schedule</span>
          <span className="sm:tw-hidden">Schedule</span>
        </button>
      </div>

      {/* Date filter - full width on mobile, positioned on right on larger screens */}
      <div className="tw-w-full sm:tw-w-auto">
        <div className="tw-relative">
          <label htmlFor="date-filter" className="tw-sr-only">Filter by date</label>
          <input
            id="date-filter"
            type="date"
            value={dateFilter}
            onChange={handleDateFilterChange}
            className="tw-block tw-w-full sm:tw-w-auto tw-rounded-md tw-border-gray-300 tw-shadow-sm focus:tw-border-blue-500 focus:tw-ring-blue-500 tw-text-sm tw-px-3 tw-py-2 tw-min-w-[140px]"
            placeholder="Filter by date"
          />
        </div>
      </div>

      <BackupConfigModal 
        isOpen={isConfigModalOpen} 
        onClose={() => setIsConfigModalOpen(false)}
        onSave={handleSaveBackup}
      />
    </div>
  );
};

export default BackupTableTools;
