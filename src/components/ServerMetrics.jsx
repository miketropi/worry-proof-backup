import React from "react";
import {
  HardDrive,
  Database,
  Shield,
  Clock,
  Upload,
  Plus,
  Server,
  Code,
  FileArchive,
  Bug,
  Terminal,
  Lock,
  ServerCrash,
} from "lucide-react";

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
    icon: <HardDrive className="tw-w-5 tw-h-5 tw-text-blue-500" />,
  },
  {
    key: "disk_total_space",
    label: "Disk Total Space",
    format: formatBytes,
    icon: <Database className="tw-w-5 tw-h-5 tw-text-indigo-500" />,
  },
  {
    key: "memory_limit",
    label: "Memory Limit",
    format: formatBytes,
    icon: <Shield className="tw-w-5 tw-h-5 tw-text-green-500" />,
  },
  {
    key: "memory_usage",
    label: "Memory Usage",
    format: formatBytes,
    icon: <Clock className="tw-w-5 tw-h-5 tw-text-yellow-500" />,
  },
  {
    key: "max_execution_time",
    label: "Max Execution Time",
    format: formatSeconds,
    icon: <Clock className="tw-w-5 tw-h-5 tw-text-pink-500" />,
  },
  {
    key: "upload_max_filesize",
    label: "Upload Max Filesize",
    format: formatBytes,
    icon: <Upload className="tw-w-5 tw-h-5 tw-text-purple-500" />,
  },
  {
    key: "post_max_size",
    label: "Post Max Size",
    format: formatBytes,
    icon: <Plus className="tw-w-5 tw-h-5 tw-text-red-500" />,
  },
  {
    key: "server_software",
    label: "Server Software",
    format: (v) => v || "-",
    icon: <Server className="tw-w-5 tw-h-5 tw-text-cyan-500" />,
  },
  {
    key: "php_version",
    label: "PHP Version",
    format: (v) => v || "-",
    icon: <Code className="tw-w-5 tw-h-5 tw-text-fuchsia-500" />,
  },
  
  {
    key: "ZipArchive",
    label: "ZipArchive",
    format: (v) => (v ? "On" : "Off"),
    icon: <FileArchive className="tw-w-5 tw-h-5 tw-text-gray-500" />,
  },
  // wp version
  {
    key: "wp_version",
    label: "WP Version",
    format: (v) => v || "-",
    icon: <Code className="tw-w-5 tw-h-5 tw-text-fuchsia-500" />,
  },
  {
    key: "WP_Debug",
    label: "WP Debug",
    format: (v) => (v ? "On" : "Off"),
    icon: <Bug className="tw-w-5 tw-h-5 tw-text-gray-500" />,
  },
  {
    key: "WP_CLI",
    label: "WP CLI",
    format: (v) => (v ? "On" : "Off"),
    icon: <Terminal className="tw-w-5 tw-h-5 tw-text-gray-500" />,
  },
  {
    key: "safe_mode",
    label: "Safe Mode",
    format: (v) => (v ? "On" : "Off"),
    icon: <Lock className="tw-w-5 tw-h-5 tw-text-gray-500" />,
  },
];

const ServerMetrics = ({ metrics }) => {
  return (
    <aside className="tw-bg-white tw-border tw-border-gray-200 tw-p-4">
      <h2 className="tw-text-base tw-font-semibold tw-text-gray-800 tw-mb-3 tw-flex tw-items-center tw-gap-2">
        <Server className="tw-w-5 tw-h-5 tw-text-blue-500" />
        Server Metrics
      </h2>
      <ul className="tw-space-y-2">
        {metricsConfig.map(({ key, label, format, icon }) => (
          <li
            key={key}
            className="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-py-2 tw-rounded hover:tw-bg-gray-50 tw-transition"
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
