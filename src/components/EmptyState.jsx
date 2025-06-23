import React from 'react';
import { NewBackupButton } from './BackupTableTools';

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

export default EmptyState; 