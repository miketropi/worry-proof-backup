import React from 'react';

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

export default BackupTypeBadge; 