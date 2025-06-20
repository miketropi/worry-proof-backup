import React, { useEffect } from 'react';
import useBackupStore from '../util/store';
import BackupTableTools, { NewBackupButton } from './BackupTableTools';
import { friendlyDateTime, deleteBackupFolder } from '../util/lib';
import { useConfirm } from './Confirm';
import { useToast } from './Toast';
import DropdownActions from './DropdownActions';
import { FileDown, Trash2, RotateCcw } from 'lucide-react';
import { useModal } from './Modal';

const BackupTable = () => {
  const { backups, setBackups, fetchBackups_Fn } = useBackupStore();
  const [isLoading, setIsLoading] = React.useState(true);
  const [filteredBackups, setFilteredBackups] = React.useState([]);
  const confirm = useConfirm();
  const toast = useToast();
  const { openModal, closeModal } = useModal();

  // selected backups
  const [selectedBackups, setSelectedBackups] = React.useState([]);

  const fetchBackups = async () => {
    await fetchBackups_Fn();
    setIsLoading(false);
  }

  const handleDeleteBackup = async (backupId) => {
    // confirm
    try {
      await confirm({
        title: 'Delete item?',
        description: '⚠️ This action cannot be undone, so make sure you really want to do this! 🗑️',
        confirmText: 'Yes, delete',
        danger: true,
      });

      // get folder name
      const folderName = backups.find(backup => backup.id === backupId).folder_name;

      // delete backup folder
      const response = await deleteBackupFolder(folderName);
      
      // if response is success, fetch backups
      if (response.success != true) {
        // error message
        toast({
          message: 'Failed to delete backup',
          type: 'error',
        });
        return;
      }

      // success message
      toast({
        message: 'Backup yeeted into oblivion 🗑️✨',
        type: 'success',
      });

      // fetch backups
      fetchBackups();
    } catch {
      // user cancelled
      return;
    }
  }

  React.useEffect(() => {
    fetchBackups();
  }, [fetchBackups_Fn]);

  React.useEffect(() => {
    setFilteredBackups(backups);
  }, [backups]);

  const handleFilterChange = (date) => {
    if (!date) {
      setFilteredBackups(backups);
      return;
    }

    const filtered = backups.filter(backup => {
      const backupDate = new Date(backup.date);
      const filterDate = new Date(date);
      return backupDate.toDateString() === filterDate.toDateString();
    });

    setFilteredBackups(filtered);
  };

  if (isLoading) {
    return (
      <div className="tw-animate-pulse">
        <div className="tw-h-8 tw-bg-gray-200 tw-rounded tw-mb-4"></div>
        <div className="tw-space-y-3">
          {[1, 2, 3].map((i) => (
            <div key={i} className="tw-h-16 tw-bg-gray-200 tw-rounded"></div>
          ))}
        </div>
      </div>
    );
  }

  if (!backups || backups.length === 0) {
    return (
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
  }

  const handleSelectBackup = (e, backupId) => {
    if (e.target.checked) {
      setSelectedBackups([...selectedBackups, backupId]);
    } else {
      setSelectedBackups(selectedBackups.filter(id => id !== backupId));
    }
  };

  const handleDeleteBackups = (backupsSelected) => {
    let __filteredBackups = [...filteredBackups];
    backupsSelected.forEach(backupId => {
      __filteredBackups = __filteredBackups.filter(backup => backup.id !== backupId);
    });
    setFilteredBackups(__filteredBackups);
    setSelectedBackups([]);
  };

  const handleSelectAllBackups = (e) => {
    if (e.target.checked) {
      setSelectedBackups(filteredBackups.map(backup => backup.id));
    } else {
      setSelectedBackups([]);
    }
  };

  const handleRestoreBackup = (backupId) => {
    console.log('restore backup', backupId);
  }

  return (
    <div className="tw-bg-white tw-border tw-border-gray-200">
      <BackupTableTools 
        onFilterChange={handleFilterChange} 
        onDeleteBackups={handleDeleteBackups}
        selectedBackups={selectedBackups} />

      {/* Mobile/Tablet View */}
      <div className="tw-block md:tw-hidden">
        {filteredBackups.map((backup) => (
          <div key={backup.id} className="tw-p-4 tw-border-b tw-border-gray-200">
            <div className="tw-flex tw-items-center tw-justify-between tw-mb-2">
              <input 
                type="checkbox" 
                className="tw-form-checkbox tw-h-4 tw-w-4 tw-text-blue-600" 
                checked={selectedBackups.includes(backup.id)}
                onChange={(e) => handleSelectBackup(e, backup.id)}
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
                <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-green-100 tw-text-green-800">
                  {backup.status}
                </span>
                <div className="tw-space-x-2">
                  <button className="tw-text-blue-600 hover:tw-text-blue-900">Download</button>
                  <button className="tw-text-red-600 hover:tw-text-red-900">Delete</button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Desktop View */}
      <div className="tw-hidden md:tw-block tw-overflow-x-auto">
        <table className="tw-min-w-full tw-divide-y tw-divide-gray-200">
          <thead className="tw-bg-gray-50">
            <tr>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500" width="3%">
                <input type="checkbox" className="tw-form-checkbox tw-h-4 tw-w-4 tw-text-blue-600" checked={selectedBackups.length === filteredBackups.length} onChange={handleSelectAllBackups} />
              </th>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                Name
              </th>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">
                Date
              </th>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">
                Size
              </th>
              <th scope="col" className="tw-px-6 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase tw-tracking-wider">
                Status
              </th>
              <th scope="col" className="tw-relative tw-px-6 tw-py-3">
                <span className="tw-sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody className="tw-bg-white tw-divide-y tw-divide-gray-200">
            {filteredBackups.map((backup) => (
              <tr key={backup.id}>
                <td className="tw-px-6 tw-py-4" width="3%">
                  <input 
                    type="checkbox" 
                    className="tw-form-checkbox tw-h-4 tw-w-4 tw-text-blue-600" 
                    checked={selectedBackups.includes(backup.id)}
                    onChange={(e) => handleSelectBackup(e, backup.id)}
                  />
                </td>
                <td className="tw-px-6 tw-py-4">
                  <div className="tw-text-sm tw-font-medium tw-text-gray-900">
                    {backup.name} 
                  </div>
                  <div className="tw-flex tw-flex-wrap tw-gap-1 tw-mt-2">
                    {backup.type.map((type, index) => {
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
                          key={index}
                          className={`tw-inline-flex tw-items-center tw-gap-1 tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-rounded-full ${style} tw-transition-colors tw-duration-200`}
                          style={{ letterSpacing: "0.01em" }}
                        >
                          {label}
                        </span>
                      );
                    })}
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
                  {backup.status === "pending" && (
                    <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-yellow-100 tw-text-yellow-800">
                      Pending
                    </span>
                  )}
                  {backup.status === "completed" && (
                    <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-green-100 tw-text-green-800">
                      Completed
                    </span>
                  )}
                  {backup.status === "fail" && (
                    <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-red-100 tw-text-red-800">
                      Failed
                    </span>
                  )}
                  {/* fallback for unknown status */}
                  {["pending", "completed", "fail"].indexOf(backup.status) === -1 && (
                    <span className="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-gray-100 tw-text-gray-800">
                      {backup.status}
                    </span>
                  )}
                </td>
                <td className="tw-px-6 tw-py-4 tw-whitespace-nowrap tw-text-right tw-text-sm tw-font-medium">
                  
                  <DropdownActions 
                    items={[
                      // restore backup
                      {
                        label: 'Restore',
                        icon: <RotateCcw />,
                        onClick: () => {
                          // setSelectedBackupRestore(backup);
                          // setIsRestoreModalOpen(true);
                          openModal({
                            title: 'Restore Backup',
                            children: <div>
                              <h1>Restore Backup</h1>
                              <p>Restore backup { JSON.stringify(backup) }</p>
                              <button onClick={closeModal}>Close</button>
                            </div>,
                          });
                        },
                      },

                      {
                        label: 'Download',
                        icon: <FileDown />,
                        action: () => handleDownloadBackup(backup.id),
                      },
                      {
                        label: 'Delete',
                        icon: <Trash2 />,
                        danger: true,
                        onClick: () => handleDeleteBackup(backup.id),
                      }
                  ]}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default BackupTable;
