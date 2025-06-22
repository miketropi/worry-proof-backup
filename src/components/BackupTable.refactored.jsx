import React, { useEffect, useMemo, useState, useCallback } from 'react';
import useBackupStore from '../util/store';
import BackupTableTools, { NewBackupButton } from './BackupTableTools';
import { friendlyDateTime, deleteBackupFolder } from '../util/lib';
import { useConfirm } from './Confirm';
import { useToast } from './Toast';
import DropdownActions from './DropdownActions';
import { FileDown, Trash2, RotateCcw } from 'lucide-react';
import { useModal } from './Modal';

const LoadingSkeleton = () => (
  <div className="tw-animate-pulse">
    <div className="tw-h-8 tw-bg-gray-200 tw-rounded tw-mb-4"></div>
    <div className="tw-space-y-3">
      {[1, 2, 3].map((i) => (
        <div key={i} className="tw-h-16 tw-bg-gray-200 tw-rounded"></div>
      ))}
    </div>
  </div>
);

const EmptyState = () => (
  <div className="tw-text-center">
    <div className="tw-bg-gray-50 tw-p-12 tw-border tw-border-gray-200">
      <svg
        className="tw-mx-auto tw-h-12 tw-w-12 tw-text-gray-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
        />
      </svg>
      <h3 className="tw-mt-2 tw-text-sm tw-font-medium tw-text-gray-900">No backups</h3>
      <p className="tw-mt-1 tw-text-sm tw-text-gray-500">
        Get started by creating a new backup.
      </p>
      <div className="tw-mt-6">
        <NewBackupButton />
      </div>
    </div>
  </div>
);

const BackupTypeBadge = ({ type }) => {
  let style = "";
  let label = "";
  switch (type) {
    case "database":
      style = "tw-bg-blue-100 tw-text-blue-800 tw-border tw-border-blue-200";
      label = "Database";
      break;
    case "plugin":
      style = "tw-bg-purple-100 tw-text-purple-800 tw-border tw-border-purple-200";
      label = "Plugins";
      break;
    case "theme":
      style = "tw-bg-yellow-100 tw-text-yellow-800 tw-border tw-border-yellow-200";
      label = "Themes";
      break;
    case "uploads":
      style = "tw-bg-green-100 tw-text-green-800 tw-border tw-border-green-200";
      label = "Uploads";
      break;
    default:
      style = "tw-bg-gray-100 tw-text-gray-800 tw-border tw-border-gray-200";
      label = type;
  }
  return (
    <span
      className={`tw-inline-flex tw-items-center tw-gap-1 tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-rounded-full ${style} tw-transition-colors tw-duration-200`}
      style={{ letterSpacing: "0.01em" }}
    >
      {label}
    </span>
  );
};

const BackupStatusBadge = ({ status }) => {
  switch (status) {
    case "pending":
      return (
        <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-yellow-100 tw-text-yellow-800">
          Pending
        </span>
      );
    case "completed":
      return (
        <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-green-100 tw-text-green-800">
          Completed
        </span>
      );
    case "fail":
      return (
        <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-red-100 tw-text-red-800">
          Failed
        </span>
      );
    default:
      return (
        <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-gray-100 tw-text-gray-800">
          {status}
        </span>
      );
  }
};

const BackupTableRow = ({ backup, isSelected, onSelect, onDelete, onDownload, onRestore }) => (
    <tr key={backup.id}>
        <td className="tw-px-6 tw-py-4" width="3%">
          <input 
            type="checkbox" 
            className="tw-form-checkbox tw-h-4 tw-w-4 tw-text-blue-600" 
            checked={isSelected}
            onChange={(e) => onSelect(e, backup.id)}
          />
        </td>
        <td className="tw-px-6 tw-py-4">
          <div className="tw-text-sm tw-font-medium tw-text-gray-900">
            {backup.name} 
          </div>
          <div className="tw-flex tw-flex-wrap tw-gap-1 tw-mt-2">
            {backup.type.map((type) => <BackupTypeBadge key={type} type={type} />)}
          </div>
        </td>
        <td className="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">
          <div className="tw-text-sm tw-text-gray-500">
            <span className="tw-inline-flex tw-items-center tw-gap-1 tw-px-2.5 tw-py-1 tw-text-xs tw-font-medium tw-rounded-md tw-bg-slate-100 tw-text-slate-700 tw-border tw-border-slate-200/60 tw-shadow-sm" title={backup.date}>
              <svg className="tw-w-3 tw-h-3 tw-text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              { friendlyDateTime(backup.date, wp_backup_php_data.current_datetime) }  
            </span>
          </div>
        </td>
        <td className="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">
          {backup.size}
        </td>
        <td className="tw-px-6 tw-py-4 tw-whitespace-nowrap">
          <BackupStatusBadge status={backup.status} />
        </td>
        <td className="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-right tw-text-sm tw-font-medium">
          <DropdownActions 
            items={[
              {
                label: 'Restore',
                icon: <RotateCcw />,
                onClick: () => onRestore(backup),
              },
              {
                label: 'Download',
                icon: <FileDown />,
                onClick: () => onDownload(backup.id),
              },
              {
                label: 'Delete',
                icon: <Trash2 />,
                danger: true,
                onClick: () => onDelete(backup.id),
              }
          ]}
          />
        </td>
      </tr>
);

const BackupMobileCard = ({ backup, isSelected, onSelect, onDelete, onDownload }) => (
  <div key={backup.id} className="tw-p-4 tw-border-b tw-border-gray-200">
    <div className="tw-flex tw-items-center tw-justify-between tw-mb-2">
      <input 
        type="checkbox" 
        className="tw-form-checkbox tw-h-4 tw-w-4 tw-text-blue-600" 
        checked={isSelected}
        onChange={(e) => onSelect(e, backup.id)}
      />
      <div className="tw-text-sm tw-font-medium tw-text-gray-900">{backup.name}</div>
    </div>
    <div className="tw-space-y-2">
      <div className="tw-flex tw-flex-wrap tw-gap-1">
        {backup.type.map((type, index) => (
          <span 
            key={index}
            className="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-rounded-md tw-bg-gray-100 tw-text-gray-800"
          >
            {type}
          </span>
        ))}
      </div>
      <div className="tw-flex tw-justify-between tw-text-sm tw-text-gray-500">
        <span>{ friendlyDateTime(backup.date, wp_backup_php_data.current_datetime) }</span>
        <span>{backup.size}</span>
      </div>
      <div className="tw-flex tw-items-center tw-justify-between">
        <BackupStatusBadge status={backup.status} />
        <div className="tw-space-x-2">
          <button onClick={() => onDownload(backup.id)} className="tw-text-blue-600 hover:tw-text-blue-900">Download</button>
          <button onClick={() => onDelete(backup.id)} className="tw-text-red-600 hover:tw-text-red-900">Delete</button>
        </div>
      </div>
    </div>
  </div>
);


const BackupTable = () => {
  const { backups, fetchBackups_Fn } = useBackupStore();
  const [isLoading, setIsLoading] = useState(true);
  const [filterDate, setFilterDate] = useState(null);
  const [selectedBackups, setSelectedBackups] = useState([]);

  const confirm = useConfirm();
  const toast = useToast();
  const { openModal, closeModal } = useModal();

  const fetchBackups = useCallback(async () => {
    await fetchBackups_Fn();
    setIsLoading(false);
  }, [fetchBackups_Fn]);

  useEffect(() => {
    fetchBackups();
  }, [fetchBackups]);

  const filteredBackups = useMemo(() => {
    if (!filterDate) {
      return backups;
    }
    const filterDateStr = filterDate.toDateString();
    return backups.filter(backup => {
      const backupDate = new Date(backup.date);
      return backupDate.toDateString() === filterDateStr;
    });
  }, [backups, filterDate]);

  const handleFilterChange = (date) => {
    setFilterDate(date ? new Date(date) : null);
  };

  const handleDeleteBackup = useCallback(async (backupId) => {
    try {
      await confirm({
        title: 'Delete item?',
        description: '⚠️ This action cannot be undone, so make sure you really want to do this! 🗑️',
        confirmText: 'Yes, delete',
        danger: true,
      });

      const backupToDelete = backups.find(backup => backup.id === backupId);
      if (!backupToDelete) {
        toast({ message: 'Backup not found', type: 'error' });
        return;
      }
      
      const response = await deleteBackupFolder(backupToDelete.folder_name);
      
      if (!response.success) {
        toast({ message: response.data.message || 'Failed to delete backup', type: 'error' });
        return;
      }

      toast({ message: 'Backup yeeted into oblivion 🗑️✨', type: 'success' });
      fetchBackups();
    } catch {
      // User cancelled confirmation
    }
  }, [backups, confirm, toast, fetchBackups]);

  const handleDeleteSelectedBackups = useCallback(async () => {
    if (selectedBackups.length === 0) return;

    try {
      await confirm({
        title: `Delete ${selectedBackups.length} items?`,
        description: '⚠️ This action cannot be undone. Are you sure?',
        confirmText: `Yes, delete ${selectedBackups.length} items`,
        danger: true,
      });

      const results = await Promise.all(selectedBackups.map(id => {
        const backup = backups.find(b => b.id === id);
        if (backup?.folder_name) {
          return deleteBackupFolder(backup.folder_name);
        }
        return Promise.resolve({ success: false, data: { message: `Backup with ID ${id} not found.` } });
      }));

      const successfulDeletes = results.filter(r => r.success).length;
      const failedDeletes = results.length - successfulDeletes;

      if (successfulDeletes > 0) {
        toast({ message: `${successfulDeletes} backup(s) deleted successfully.`, type: 'success' });
      }
      if (failedDeletes > 0) {
        toast({ message: `Failed to delete ${failedDeletes} backup(s).`, type: 'error' });
      }

      setSelectedBackups([]);
      fetchBackups();
    } catch {
      // User cancelled confirmation
    }
  }, [selectedBackups, backups, confirm, toast, fetchBackups]);

  const handleDownloadBackup = (backupId) => {
    console.log('Download backup:', backupId);
    toast({ message: 'Download functionality is not yet implemented.', type: 'info' });
  };
  
  const handleRestoreBackup = useCallback((backup) => {
    openModal({
      title: 'Restore Backup',
      children: <div>
        <div className="tw-space-y-4">
          <div>
            {/* <h2 className="tw-text-lg tw-font-semibold tw-text-gray-900">Restore Backup</h2> */}
            <p className="tw-text-sm tw-text-gray-600 tw-mt-1">
              Select which components you want to restore from this backup.
            </p>
          </div>

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
                    defaultChecked={true}
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
                console.log('Restoring backup:', backup.id);
                toast({ message: 'Restore functionality is not yet implemented.', type: 'info' });
                closeModal();
              }}
              className="tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-white tw-bg-blue-600 tw-border tw-border-transparent tw-rounded-md hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
            >
              Yes, Restore Backup! 🚀
            </button>
          </div>
        </div>
      </div>,
    });
  }, [openModal, closeModal, toast]);

  const handleSelectBackup = (e, backupId) => {
    if (e.target.checked) {
      setSelectedBackups(prev => [...prev, backupId]);
    } else {
      setSelectedBackups(prev => prev.filter(id => id !== backupId));
    }
  };

  const handleSelectAllBackups = (e) => {
    if (e.target.checked) {
      setSelectedBackups(filteredBackups.map(backup => backup.id));
    } else {
      setSelectedBackups([]);
    }
  };

  if (isLoading) {
    return <LoadingSkeleton />;
  }

  if (!backups || backups.length === 0) {
    return <EmptyState />;
  }

  return (
    <div className="tw-bg-white tw-border tw-border-gray-200">
      <BackupTableTools 
        onFilterChange={handleFilterChange} 
        onDeleteBackups={handleDeleteSelectedBackups}
        selectedBackups={selectedBackups}
      />

      {/* Mobile/Tablet View */}
      <div className="tw-block md:tw-hidden">
        {filteredBackups.map((backup) => (
          <BackupMobileCard
            key={backup.id}
            backup={backup}
            isSelected={selectedBackups.includes(backup.id)}
            onSelect={handleSelectBackup}
            onDelete={handleDeleteBackup}
            onDownload={handleDownloadBackup}
          />
        ))}
      </div>

      {/* Desktop View */}
      <div className="tw-hidden md:tw-block tw-overflow-x-auto">
        <table className="tw-min-w-full tw-divide-y tw-divide-gray-200">
          <thead className="tw-bg-gray-50">
            <tr>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500" width="3%">
                <input 
                    type="checkbox" 
                    className="tw-form-checkbox tw-h-4 tw-w-4 tw-text-blue-600" 
                    checked={filteredBackups.length > 0 && selectedBackups.length === filteredBackups.length} 
                    onChange={handleSelectAllBackups} 
                    disabled={filteredBackups.length === 0}
                />
              </th>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">Name</th>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Date</th>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Size</th>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">Status</th>
              <th scope="col" className="tw-relative tw-px-6 tw-py-3"><span className="tw-sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody className="tw-bg-white tw-divide-y tw-divide-gray-200">
            {filteredBackups.map((backup) => (
              <BackupTableRow
                key={backup.id}
                backup={backup}
                isSelected={selectedBackups.includes(backup.id)}
                onSelect={handleSelectBackup}
                onDelete={handleDeleteBackup}
                onDownload={handleDownloadBackup}
                onRestore={handleRestoreBackup}
              />
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default BackupTable; 