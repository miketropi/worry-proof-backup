import { useState, useCallback, useEffect } from 'react';
import BackupTypeBadge from './BackupTypeBadge';

export default function RestoreConfigModal({ backup, closeModal, toast }) {

  const [selectedBackupRestoreTypes, setSelectedBackupRestoreTypes] = useState(backup.type);

  return (
    <div>
      <div className="tw-space-y-4">
        <div>
          {/* <h2 className="tw-text-lg tw-font-semibold tw-text-gray-900">Restore Backup</h2> */}
          <p className="tw-text-sm tw-text-gray-600 tw-mt-1">
            Select which components you want to restore from this backup.
          </p>
        </div>
        { JSON.stringify(selectedBackupRestoreTypes) }
        <div className="tw-bg-gray-50 tw-p-4 tw-rounded-lg tw-border tw-border-gray-200">
          <div className="tw-flex tw-items-center tw-justify-between tw-mb-3">
            <span className="tw-text-sm tw-font-medium tw-text-gray-700">Backup Details</span>
            <span className="tw-text-xs tw-text-gray-500">{backup.name}</span>
          </div>
          <div className="tw-space-y-2 tw-text-xs tw-text-gray-600">
            <div className="tw-flex tw-items-center tw-justify-between tw-py-1 tw-border-b tw-border-gray-100">
              <span className="tw-font-medium">Full Size:</span>
              <span>{backup.size}</span>
            </div>
            <div className="tw-flex tw-items-center tw-justify-between tw-py-1 tw-border-b tw-border-gray-100">
              <span className="tw-font-medium">Date:</span>
              <span>{backup.date}</span>
            </div>
            {/* <div className="tw-flex tw-items-center tw-justify-between tw-py-1">
              <span className="tw-font-medium">Status:</span>
              <span className="tw-text-green-600">{backup.status}</span>
            </div> */}
          </div>
        </div>

        <div>
          <label className="tw-text-sm tw-font-medium tw-text-gray-700 tw-block tw-mb-3">
            Select components to restore:
          </label>
          <div className="tw-space-y-2">
            {backup.type.map((type) => (
              <label key={type} className="tw-flex tw-items-center tw-space-x-3 tw-p-3 tw-border tw-border-gray-200 tw-rounded-lg tw-cursor-pointer hover:tw-bg-gray-50">
                <input
                  type="checkbox"
                  defaultChecked={selectedBackupRestoreTypes.includes(type)}
                  onChange={(e) => {
                    if (e.target.checked) {
                      setSelectedBackupRestoreTypes([...selectedBackupRestoreTypes, type]);
                    } else {
                      setSelectedBackupRestoreTypes(selectedBackupRestoreTypes.filter(t => t !== type));
                    }
                  }}
                  className="tw-h-4 tw-w-4 tw-text-blue-600 tw-border-gray-300 tw-rounded focus:tw-ring-blue-500"
                />
                <div className="tw-flex tw-items-center tw-space-x-2">
                  <BackupTypeBadge type={type} />
                  <span className="tw-text-sm tw-text-gray-700">
                    {type === 'database' && 'Database tables and content'}
                    {type === 'plugin' && 'Plugin files and settings'}
                    {type === 'theme' && 'Theme files and customizations'}
                    {type === 'uploads' && 'Media files and uploads'}
                  </span>
                </div>
              </label>
            ))}
          </div>
        </div>

        <div className="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-lg tw-p-3">
          <div className="tw-flex tw-items-start tw-space-x-2">
            <svg className="tw-w-4 tw-h-4 tw-text-yellow-600 tw-mt-0.5 tw-flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <div className="tw-text-sm tw-text-yellow-800">
              <p className="tw-font-medium">Warning</p>
              <p className="tw-mt-1">This will overwrite your current data. Make sure you have a recent backup before proceeding.</p>
            </div>
          </div>
        </div>

        <div className="tw-flex tw-justify-end tw-gap-3 tw-pt-4">
          <button
            onClick={closeModal}
            className="tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white tw-border tw-border-gray-300 tw-rounded-md hover:tw-bg-gray-50 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
          >
            Cancel
          </button>
          <button
            onClick={() => {
              console.log('Restoring backup:', backup.folder_name, selectedBackupRestoreTypes);
              toast({ message: 'Restore functionality is not yet implemented.', type: 'info' });
              closeModal();
            }}
            className="tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-white tw-bg-blue-600 tw-border tw-border-transparent tw-rounded-md hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
          >
            Yes, Restore Backup! 🚀
          </button>
        </div>
      </div>
    </div>
  );
}