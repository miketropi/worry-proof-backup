import React from "react";

// Helper functions for formatting
const formatBytes = (bytes) => {
  if (bytes === null || bytes === undefined || bytes === "") return "-";
  if (isNaN(bytes)) return bytes;
  const sizes = ["Bytes", "KB", "MB", "GB", "TB"];
  if (bytes === 0) return "0 Byte";
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`;
};

const formatSeconds = (seconds) => {
  if (seconds === null || seconds === undefined || seconds === "") return "-";
  if (isNaN(seconds)) return seconds;
  if (seconds < 60) return `${seconds}s`;
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  if (m < 60) return `${m}m ${s}s`;
  const h = Math.floor(m / 60);
  return `${h}h ${m % 60}m ${s}s`;
};

const metricsConfig = [
  {
    key: "disk_free_space",
    label: "Disk Free Space",
    format: formatBytes,
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7l9 6 9-6" /></svg>
    ),
  },
  {
    key: "disk_total_space",
    label: "Disk Total Space",
    format: formatBytes,
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
    ),
  },
  {
    key: "memory_limit",
    label: "Memory Limit",
    format: formatBytes,
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 9V7a5 5 0 00-10 0v2a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2z" /></svg>
    ),
  },
  {
    key: "memory_usage",
    label: "Memory Usage",
    format: formatBytes,
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3" /></svg>
    ),
  },
  {
    key: "max_execution_time",
    label: "Max Execution Time",
    format: formatSeconds,
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3M12 6a9 9 0 100 18 9 9 0 000-18z" /></svg>
    ),
  },
  {
    key: "upload_max_filesize",
    label: "Upload Max Filesize",
    format: formatBytes,
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M4 12l8-8 8 8" /></svg>
    ),
  },
  {
    key: "post_max_size",
    label: "Post Max Size",
    format: formatBytes,
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" /></svg>
    ),
  },
  {
    key: "safe_mode",
    label: "Safe Mode",
    format: (v) => (v ? "On" : "Off"),
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 11c0-1.104-.896-2-2-2s-2 .896-2 2 .896 2 2 2 2-.896 2-2zm0 0c0-1.104.896-2 2-2s2 .896 2 2-.896 2-2 2-2-.896-2-2z" /></svg>
    ),
  },
  {
    key: "server_software",
    label: "Server Software",
    format: (v) => v || "-",
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9.75 17L9 21m5.25-4l.75 4m-7.5-4h10.5a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0017.25 4.5H6.75A2.25 2.25 0 004.5 6.75v8A2.25 2.25 0 006.75 17z" /></svg>
    ),
  },
  {
    key: "php_version",
    label: "PHP Version",
    format: (v) => v || "-",
    icon: (
      <svg className="tw-w-5 tw-h-5 tw-text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><ellipse cx="12" cy="12" rx="10" ry="6" /><text x="12" y="16" textAnchor="middle" fontSize="8" fill="currentColor">PHP</text></svg>
    ),
  },
];

const ServerMetrics = ({ metrics }) => {
  return (
    <aside className="tw-bg-white tw-border tw-border-gray-200 tw-p-4">
      <h2 className="tw-text-base tw-font-semibold tw-text-gray-800 tw-mb-3 tw-flex tw-items-center tw-gap-2">
        <svg className="tw-w-5 tw-h-5 tw-text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
        </svg>
        Server Metrics
      </h2>
      <ul className="tw-space-y-2">
        {metricsConfig.map(({ key, label, format, icon }) => (
          <li
            key={key}
            className="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-px-2 tw-py-2 tw-rounded hover:tw-bg-gray-50 tw-transition"
          >
            <div className="tw-flex tw-items-center tw-gap-2 tw-mb-1 sm:tw-mb-0">
              <span className="tw-w-4 tw-h-4 tw-flex tw-items-center tw-justify-center">{icon}</span>
              <span className="tw-text-xs tw-text-gray-600">{label}</span>
            </div>
            <span className="tw-text-xs tw-font-mono tw-text-gray-900 sm:tw-text-right">{format(metrics?.[key])}</span>
          </li>
        ))}
      </ul>
    </aside>
  );
};

export default ServerMetrics;
