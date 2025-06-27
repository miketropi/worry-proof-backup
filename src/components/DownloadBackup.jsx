import React, { useState, useEffect, useCallback } from 'react';
import { Package, DownloadCloud, CheckCircle2, FolderArchive, Loader2, Download, Copy } from 'lucide-react';
import { getBackupDownloadZipPath, createBackupZip } from '../util/lib';
import { useToast } from './Toast';

/**
 * DownloadBackup component
 * @param {Object} props
 * @param {Object} props.backup - The backup item
 * @param {Function} [props.onDownload] - Called when download is requested
 * @param {Function} [props.onCreateZip] - Called when create zip is requested
 */
const DownloadBackup = ({ backup }) => {
  const toast = useToast();
  const [isLoading, setIsLoading] = useState(false);
  const [isCreatingZip, setIsCreatingZip] = useState(false);
  const [backupDownloadZipPath, setBackupDownloadZipPath] = useState(null);

  const handleDownload = async () => {
    setIsLoading(true);
    const backup_download_zip_path = await getBackupDownloadZipPath(backup.folder_name);

    // validate
    if(backup_download_zip_path.success != true) {
      toast({ message: backup_download_zip_path.data, type: 'error' });
      return;
    }

    setBackupDownloadZipPath(backup_download_zip_path.data);
    setIsLoading(false);
  };

  const copyToClipboard = (text) => {
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      navigator.clipboard.writeText(text).then(() => {
        console.log('Copied to clipboard!');
      }).catch(err => {
        console.error('Failed to copy: ', err);
      });
    } else {
      // Fallback cho trình duyệt cũ
      const textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';  // tránh cuộn trang
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();
      try {
        document.execCommand('copy');
        console.log('Copied with fallback!');
      } catch (err) {
        console.error('Fallback failed: ', err);
      }
      document.body.removeChild(textarea);
    }
  }

  const onHandleCopyLink = useCallback(() => {
    copyToClipboard(backupDownloadZipPath);
    toast({ message: 'Link copied to clipboard', type: 'success' });
  }, [backupDownloadZipPath]);
  

  useEffect(() => {
    handleDownload();
  }, []);

  const handleCreateZip = async () => {
    setIsCreatingZip(true);
    const backup_create_zip = await createBackupZip(backup.folder_name);
    console.log(backup_create_zip);
    // validate
    if(backup_create_zip.success != true) {
      toast({ message: backup_create_zip.data, type: 'error' });
      return;
    }

    setBackupDownloadZipPath(backup_create_zip.data);

    setIsCreatingZip(false);
  };

  if(isLoading) {
    return (
      <div className="tw-flex tw-items-center tw-justify-center tw-h-full">
        <Loader2 className="tw-w-4 tw-h-4 tw-animate-spin" />
      </div>
    );
  }

  if(backupDownloadZipPath == false) {
    return (
      <>
        <div className="tw-flex tw-flex-col tw-items-center tw-justify-center tw-h-full">
          <div className="tw-bg-blue-50 tw-border tw-border-blue-200 tw-rounded-md tw-p-4">
            <div className="tw-flex tw-items-start tw-space-x-3">
              <div className="tw-flex-shrink-0">
                <FolderArchive className="tw-w-5 tw-h-5" color="#007bff" />
              </div>
              <div className="tw-flex-1">
                <p className="tw-text-sm tw-text-blue-800 tw-leading-relaxed">
                Backups are created on-demand to save storage 💾. Click below to generate one when needed, and don’t forget to delete it once you’re finished — keep things clean and efficient. 🧹✨
                </p>
              </div>
            </div>
          </div>
        </div>
        <div className="tw-mt-4 tw-flex tw-justify-end">
          <button
            onClick={handleCreateZip}
            disabled={isCreatingZip}
            className={ `tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-bg-blue-600 tw-text-white tw-text-sm tw-font-medium tw-rounded-md tw-shadow-sm hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed tw-transition-colors ${ isCreatingZip ? 'tw-opacity-50 tw-cursor-not-allowed' : '' }` }
          >
            {isCreatingZip ? (
              <>
                <Loader2 className="tw-w-4 tw-h-4 tw-mr-2 tw-animate-spin" />
                Creating Backup...
              </>
            ) : (
              <>
                <Package className="tw-w-4 tw-h-4 tw-mr-2" />
                Create Backup Zip
              </>
            )}
          </button>
        </div>
      </>
    );
  }

  return (
    <>
      <div className="tw-flex tw-flex-col tw-items-center tw-justify-center tw-h-full">
        <div className="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-md tw-p-4">
          <div className="tw-flex tw-items-start tw-space-x-3">
            <div className="tw-flex-shrink-0">
              <Package className="tw-w-5 tw-h-5" color="#16a34a" />
            </div>
            <div className="tw-flex-1">
              <h3 className="tw-text-sm tw-font-medium tw-text-green-800 tw-mb-2">
                Backup Ready for Download
              </h3>
              <p className="tw-text-sm tw-text-green-700 tw-leading-relaxed">
                Your backup zip file has been created successfully. Click the download button below to save it to your computer.
              </p>
            </div>
          </div>
        </div>
      </div>
      <div className="tw-mt-4 tw-flex tw-justify-end tw-space-x-3">
        <button
          onClick={onHandleCopyLink}
          className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-bg-gray-600 tw-text-white tw-text-sm tw-font-medium tw-rounded-md tw-shadow-sm hover:tw-bg-gray-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-gray-500 focus:tw-ring-offset-2 tw-transition-colors"
        >
          <Copy className="tw-w-4 tw-h-4 tw-mr-2" />
          Copy Link
        </button>
        <button
          onClick={() => window.open(backupDownloadZipPath, '_blank')}
          className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-bg-green-600 tw-text-white tw-text-sm tw-font-medium tw-rounded-md tw-shadow-sm hover:tw-bg-green-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-green-500 focus:tw-ring-offset-2 tw-transition-colors"
        >
          <Download className="tw-w-4 tw-h-4 tw-mr-2" />
          Download Backup
        </button>
      </div>
    </>
  );
};

export default DownloadBackup;
