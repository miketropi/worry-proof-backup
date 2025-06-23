import React from 'react';
import BackupStatusBadge from './BackupStatusBadge';
import { friendlyDateTime } from '../util/lib';

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

export default BackupMobileCard; 