import React, { useRef, useState, forwardRef, useImperativeHandle } from 'react';
import { useToast } from './Toast';
import { FileUp } from 'lucide-react';

/**
 * UploadBackup Usage Example:
 *
 * import React, { useRef } from 'react';
 * import UploadBackup from './UploadBackup';
 *
 * function MyComponent() {
 *   const uploadRef = useRef();
 *
 *   const handleUpload = (files) => {
 *     // Start your upload logic here
 *     // To update progress:
 *     uploadRef.current?.setUploadProgress(30); // 30%
 *     // To reset progress:
 *     uploadRef.current?.resetProgress();
 *   };
 *
 *   return (
 *     <UploadBackup
 *       ref={uploadRef}
 *       onUpload={handleUpload}
 *       accept=".zip,.tar,.gz,.sql"
 *       maxFiles={1}
 *       maxSize={1024 * 1024 * 512} // 512MB
 *     />
 *   );
 * }
 */

/**
 * UploadBackup - Modern, dependency-free file upload component
 * - Drag & drop or click to select
 * - Shows selected files
 * - Calls onUpload(files) prop when files are selected or uploaded
 * - Uses toast for feedback
 * - Clean, modern, accessible UI
 * - Supports upload progress (parent can update via ref)
 */
const UploadBackup = forwardRef(({ onUpload, accept = '.zip,.tar,.gz,.sql', maxFiles = 1, maxSize = 1024 * 1024 * 512 }, ref) => {
  const toast = useToast();
  const inputRef = useRef();
  const [files, setFiles] = useState([]);
  const [dragActive, setDragActive] = useState(false);
  const [error, setError] = useState('');
  const [uploadProgress, setUploadProgress] = useState(0); // 0-100

  // Expose setUploadProgress to parent via ref
  useImperativeHandle(ref, () => ({
    setUploadProgress: (progress) => {
      setUploadProgress(progress);
    },
    resetProgress: () => setUploadProgress(0),
  }), []);

  // Validate file type and size
  const validateFiles = (fileList) => {
    const acceptedTypes = accept.split(',').map(ext => ext.trim().replace(/^\./, ''));
    const validFiles = [];
    for (let file of fileList) {
      const ext = file.name.split('.').pop().toLowerCase();
      if (!acceptedTypes.includes(ext)) {
        setError(`File type .${ext} not accepted.`);
        toast({ message: `File type .${ext} not accepted.`, type: 'error' });
        return [];
      }
      if (file.size > maxSize) {
        setError(`File ${file.name} is too large (max ${Math.round(maxSize / (1024 * 1024))}MB).`);
        toast({ message: `File ${file.name} is too large.`, type: 'error' });
        return [];
      }
      validFiles.push(file);
    }
    setError('');
    return validFiles;
  };

  const handleFiles = (fileList) => {
    let valid = validateFiles(Array.from(fileList));
    if (valid.length > 0) {
      if (maxFiles === 1) valid = [valid[0]];
      setFiles(valid);
      // if (onUpload) onUpload(valid);
      toast({ message: 'File ready to upload!', type: 'success' });
    }
  };

  const handleInputChange = (e) => {
    handleFiles(e.target.files);
  };

  const handleDrop = (e) => {
    e.preventDefault();
    setDragActive(false);
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      handleFiles(e.dataTransfer.files);
    }
  };

  const handleDragOver = (e) => {
    e.preventDefault();
    setDragActive(true);
  };

  const handleDragLeave = (e) => {
    e.preventDefault();
    setDragActive(false);
  };

  const removeFile = (file) => {
    setFiles((prev) => prev.filter((f) => f !== file));
    
    // update inputRef when remove file
    inputRef.current.value = '';
    
    toast({ message: 'File removed', type: 'info' });
  };

  return (
    <div className="tw-w-full tw-max-w-lg tw-mx-auto tw-p-6 tw-bg-white tw-rounded-md tw-border tw-border-gray-200 tw-space-y-4">
      <div
        className={
          'tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-12 tw-px-4 tw-border-2 tw-border-dashed tw-rounded-lg tw-cursor-pointer ' +
          (dragActive ? 'tw-border-blue-500 tw-bg-blue-50' : 'tw-border-gray-300 tw-bg-gray-50')
        }
        tabIndex={0}
        aria-label="File upload dropzone"
        onClick={() => inputRef.current.click()}
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDragEnd={handleDragLeave}
      >
        <input
          ref={inputRef}
          type="file"
          accept={accept}
          multiple={maxFiles > 1}
          className="tw-hidden"
          onChange={handleInputChange}
        />
        <FileUp className="tw-w-10 tw-h-10 tw-text-gray-400" />
        <p className="tw-mt-4 tw-text-base tw-font-medium tw-text-gray-700 tw-text-center">
          {dragActive ? 'Drop the file here...' : 'Drag & drop a backup file here, or click to select'}
        </p>
        <p className="tw-mt-2 tw-text-xs tw-text-gray-500">Accepted: {accept.replace(/\./g, '').replace(/,/g, ', ')}</p>
        <p className="tw-mt-1 tw-text-xs tw-text-gray-400">Max size: {Math.round(maxSize / (1024 * 1024))}MB</p>
      </div>
      {/* Progress Bar */}
      {uploadProgress > 0 && uploadProgress < 100 && (
        <div className="tw-w-full tw-bg-gray-200 tw-rounded tw-h-3 tw-mt-2">
          <div
            className="tw-bg-blue-500 tw-h-3 tw-rounded tw-transition-all tw-duration-300"
            style={{ width: `${uploadProgress}%` }}
          />
          <div className="tw-text-xs tw-text-gray-600 tw-mt-1 tw-text-right">Uploading... {uploadProgress}%</div>
        </div>
      )}
      {error && <div className="tw-text-red-600 tw-text-sm tw-mt-2">{error}</div>}
      {files.length > 0 && (
        <div className="tw-mt-4 tw-space-y-2">
          <div className="tw-text-sm tw-font-semibold tw-text-gray-800">Selected file{files.length > 1 ? 's' : ''}:</div>
          <ul className="tw-space-y-1">
            {files.map((file, idx) => (
              <li key={file.name + idx} className="tw-flex tw-items-center tw-justify-between tw-bg-gray-100 tw-p-2 tw-rounded tw-text-gray-700">
                <span className="tw-flex tw-items-center tw-gap-2">
                  <svg className="tw-w-4 tw-h-4 tw-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16V4a2 2 0 012-2h8a2 2 0 012 2v12" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4-4 4 4m0 0V4m0 12l-4-4-4 4" />
                  </svg>
                  {file.name} <span className="tw-text-xs tw-text-gray-400">({(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </span>
                <button
                  type="button"
                  className="tw-ml-2 tw-p-1 tw-rounded hover:tw-bg-red-100"
                  onClick={() => removeFile(file)}
                  aria-label={`Remove ${file.name}`}
                >
                  <svg className="tw-w-4 tw-h-4 tw-text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </li>
            ))}
          </ul>
        </div>
      )}
      <div className="tw-flex tw-justify-end tw-gap-2">
        <button
          type="button"
          onClick={() => inputRef.current.click()}
          className="tw-px-4 tw-py-2 tw-bg-blue-600 tw-text-white tw-rounded-md tw-font-medium tw-shadow-sm hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-200 tw-font-space-mono"
        >
          Choose File
        </button>
        {files.length > 0 && (
          <button
            type="button"
            onClick={() => {
              if (onUpload) onUpload(files);
              // toast({ message: 'Uploading file...', type: 'info' });
            }}
            className="tw-px-4 tw-py-2 tw-bg-green-600 tw-text-white tw-rounded-md tw-font-medium tw-shadow-sm hover:tw-bg-green-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-green-200 tw-font-space-mono"
          >
            Upload
          </button>
        )}
      </div>
    </div>
  );
});

export default UploadBackup;
