import React, { useEffect, useMemo, useState, useCallback, useRef } from 'react';
import useBackupStore from '../util/store';
import BackupTableTools from './BackupTableTools';
import { deleteBackupFolder, uploadFileWithProgress } from '../util/lib';
import { useConfirm } from './Confirm';
import { useToast } from './Toast';
import { useModal } from './Modal';
import LoadingSkeleton from './LoadingSkeleton';
import EmptyState from './EmptyState';
import BackupTypeBadge from './BackupTypeBadge';
import BackupTableRow from './BackupTableRow';
import BackupMobileCard from './BackupMobileCard';
import RestoreConfigModal from './RestoreConfigModal';
import UploadBackup from './UploadBackup';
import DownloadBackup from './DownloadBackup';
import BackupScheduleConfig from './BackupScheduleConfig';

const BackupTable = () => {
  const { backups, fetchBackups_Fn } = useBackupStore();
  const [isLoading, setIsLoading] = useState(true);
  const [filterDate, setFilterDate] = useState(null);
  const [selectedBackups, setSelectedBackups] = useState([]);
  const uploadRef = useRef();

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

  const handleDownloadBackup = (backup) => {
    // console.log('Download backup:', backupId);
    // toast({ message: 'Download functionality is not yet implemented.', type: 'info' });
    openModal({
      title: 'Download Backup',
      size: 'lg',
      children: <DownloadBackup backup={backup} />,
    });
  };
  
  const handleRestoreBackup = useCallback((backup) => {
    openModal({
      title: 'Restore Backup',
      children: <RestoreConfigModal backup={backup} closeModal={closeModal} toast={toast} />,
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

  const handleUploadBackupFiles = async (files) => {
    const file = files[0];
    try {
      toast({ message: 'Uploading...', type: 'info' });

      const res = await uploadFileWithProgress(file, (percent) => {
        console.log('Upload progress', percent);
        uploadRef.current?.setUploadProgress(percent);
      });

      console.log('Upload res', res);

      toast({ message: 'Upload success!', type: 'success' });
      uploadRef.current?.setUploadProgress(100);
      fetchBackups();
      closeModal();
    } catch (err) {
      toast({ message: `Upload error: ${err}`, type: 'error' });
      uploadRef.current?.resetProgress();
    }
  };

  const handleUploadBackup = () => {
    console.log('Upload backup clicked');
    openModal({
      title: 'Upload Backup',
      size: 'lg',
      children: <UploadBackup 
        ref={uploadRef}
        accept=".zip" 
        maxSize={ wp_backup_php_data.server_metrics.WP_Max_Upload_Size } 
        maxFiles={ 1 }
        onUpload={handleUploadBackupFiles}
        />,
    });
  };

  const handleBackupSchedule = () => {
    openModal({
      title: 'Backup Schedule',
      size: 'lg',
      children: <BackupScheduleConfig />,
    });
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
        onUploadBackup={handleUploadBackup}
        onBackupSchedule={handleBackupSchedule}
      />

      {/* Mobile/Tablet View */}
      <div className="tw-block md:tw-hidden tw-bg-white tw-border-t tw-border-gray-200">
        {filteredBackups.map((backup) => (
          <BackupMobileCard
            key={backup.id}
            backup={backup}
            isSelected={selectedBackups.includes(backup.id)}
            onSelect={handleSelectBackup}
            onDelete={handleDeleteBackup}
            onDownload={handleDownloadBackup}
            onRestore={handleRestoreBackup}
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