import React from 'react';

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

export default BackupStatusBadge; 