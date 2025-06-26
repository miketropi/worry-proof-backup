import React from 'react';
import BackupTypeBadge from './BackupTypeBadge';
import BackupStatusBadge from './BackupStatusBadge';
import { friendlyDateTime } from '../util/lib';
import DropdownActions from './DropdownActions';
import { FileDown, Trash2, RotateCcw } from 'lucide-react';

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
                icon: <RotateCcw size={16} />,
                onClick: () => onRestore(backup),
              },
              {
                label: 'Download',
                icon: <FileDown size={16} />,
                onClick: () => onDownload(backup),
              },
              {
                label: 'Delete',
                icon: <Trash2 size={16} />,
                danger: true,
                onClick: () => onDelete(backup.id),
              }
          ]}
          />
        </td>
      </tr>
);

export default BackupTableRow; 