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
  AlertTriangle,
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

// Minimum recommended values for warnings
const minimums = {
  php_version: { value: "8.0", compare: (v) => v && parseFloat(v) < 8.0, message: "Recommended PHP >= 8.0" },
  memory_limit: { value: 256 * 1024 * 1024, compare: (v) => v && v < 256 * 1024 * 1024, message: "Recommended Memory >= 256MB" },
  disk_free_space: { value: 1 * 1024 * 1024 * 1024, compare: (v) => v && v < 1 * 1024 * 1024 * 1024, message: "Recommended Free Space >= 1GB" },
  max_execution_time: { value: 60, compare: (v) => v && v < 60, message: "Recommended Execution Time >= 60s" },
  ZipArchive: { value: true, compare: (v) => !v, message: "ZipArchive should be enabled" },
  WP_Debug: { value: false, compare: (v) => !!v, message: "WP Debug should be Off" },
  WP_CLI: { value: true, compare: (v) => !v, message: "WP CLI should be enabled" },
  safe_mode: { value: false, compare: (v) => !!v, message: "Safe Mode should be Off" },
  mysql_version: { value: "5.7", compare: (v) => v && parseFloat(v) < 5.7, message: "Recommended MySQL >= 5.7" },
};

const metricsConfig = [
  {
    key: "disk_free_space",
    label: "Disk Free Space",
    format: formatBytes,
    icon: <HardDrive className="tw-w-5 tw-h-5" color="#3B82F6" />,
  },
  {
    key: "disk_total_space",
    label: "Disk Total Space",
    format: formatBytes,
    icon: <Database className="tw-w-5 tw-h-5" color="#6366F1" />,
  },
  {
    key: "memory_limit",
    label: "Memory Limit",
    format: formatBytes,
    icon: <Shield className="tw-w-5 tw-h-5" color="#10B981" />,
  },
  {
    key: "memory_usage",
    label: "Memory Usage",
    format: formatBytes,
    icon: <Clock className="tw-w-5 tw-h-5" color="#EAB308" />,
  },
  {
    key: "max_execution_time",
    label: "Max Execution Time",
    format: formatSeconds,
    icon: <Clock className="tw-w-5 tw-h-5" color="#EC4899" />,
  },
  {
    key: "upload_max_filesize",
    label: "Upload Max Filesize",
    format: formatBytes,
    icon: <Upload className="tw-w-5 tw-h-5" color="#8B5CF6" />,
  },
  {
    key: "post_max_size",
    label: "Post Max Size",
    format: formatBytes,
    icon: <Plus className="tw-w-5 tw-h-5" color="#EF4444" />,
  },
  {
    key: "server_software",
    label: "Server Software",
    format: (v) => v || "-",
    icon: <Server className="tw-w-5 tw-h-5" color="#06B6D4" />,
  },
  {
    key: "php_version",
    label: "PHP Version",
    format: (v) => v || "-",
    icon: <Code className="tw-w-5 tw-h-5" color="#D946EF" />,
  },
  {
    key: "ZipArchive",
    label: "ZipArchive",
    format: (v) => (v ? "On" : "Off"),
    icon: <FileArchive className="tw-w-5 tw-h-5" color="#F97316" />,
  },
  {
    key: "wp_version",
    label: "WP Version",
    format: (v) => v || "-",
    icon: <Code className="tw-w-5 tw-h-5" color="#10B981" />,
  },
  {
    key: "mysql_version",
    label: "MySQL Version",
    format: (v) => v || "-",
    icon: <Database className="tw-w-5 tw-h-5" color="#14B8A6" />,
  },
  {
    key: "WP_Debug",
    label: "WP Debug",
    format: (v) => (v ? "On" : "Off"),
    icon: <Bug className="tw-w-5 tw-h-5" color="#F59E0B" />,
  },
  {
    key: "WP_CLI",
    label: "WP CLI",
    format: (v) => (v ? "On" : "Off"),
    icon: <Terminal className="tw-w-5 tw-h-5" color="#64748B" />,
  },
  {
    key: "WP_Max_Upload_Size",
    label: "WP Max Upload Size",
    format: formatBytes,
    icon: <Upload className="tw-w-5 tw-h-5" color="#8B5CF6" />,
  },
  {
    key: "safe_mode",
    label: "Safe Mode",
    format: (v) => (v ? "On" : "Off"),
    icon: <Lock className="tw-w-5 tw-h-5" color="#F43F5E" />,
  },
];

const Warning = ({ message }) => (
  <span className="tw-text-xs tw-text-gray-400" title={message}>
    {message}
  </span>
);

const ServerMetrics = ({ metrics, showWarnings = true }) => {
  return (
    <div className="tw-max-w-3xl tw-mx-auto tw-px-2 sm:tw-px-0 tw-py-10 tw-bg-white">
      <aside className="tw-py-4 tw-mx-auto tw-mb-6 tw-w-full sm:tw-max-w-full">
        <h2 className="tw-text-base tw-font-semibold tw-text-gray-800 tw-mb-4 tw-flex tw-items-center tw-gap-2">
          <Server className="tw-w-5 tw-h-5 tw-text-gray-400" />
          Server Metrics
        </h2>

        <div className="tw-bg-gradient-to-r tw-from-indigo-50 tw-to-purple-50 tw-border tw-border-indigo-200 tw-rounded-md tw-p-6 tw-mb-6 tw-shadow-sm">
          <div className="tw-flex tw-items-start tw-gap-4">
            <div className="tw-flex-shrink-0 tw-mt-1">
              <div className="tw-w-8 tw-h-8 tw-bg-gradient-to-r tw-from-indigo-500 tw-to-purple-600 tw-rounded-lg tw-flex tw-items-center tw-justify-center">
                <span className="tw-text-white tw-text-sm">⚡</span>
              </div>
            </div>
            <div>
              <h3 className="tw-text-base tw-font-semibold tw-text-indigo-900 tw-mb-2 tw-flex tw-items-center tw-gap-2">
                Pro Tips for Your Server Setup 💡
              </h3>
              <p className="tw-text-sm tw-leading-relaxed tw-text-indigo-700">
                Hey there! 👋 Your server setup is like choosing the right gear for a mission - 
                it totally depends on your site's vibe! Small sites? You're good with the basics! 
                But if you're running a massive site with tons of plugins, themes, or getting that 
                sweet traffic flow, maybe consider leveling up your server resources. 
                It's like upgrading from a bicycle to a rocket ship! 🚀✨
              </p>
            </div>
          </div>
        </div>

        <div className="tw-overflow-x-auto">
          <table className="tw-w-full tw-bg-white tw-text-sm tw-border-collapse tw-shadow-sm tw-border tw-border-gray-200">
            <thead className="tw-bg-gray-50">
              <tr>
                <th className="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 "> </th>
                <th className="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 ">METRIC</th>
                <th className="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 ">VALUE</th>
                <th className="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 "> </th>
              </tr>
            </thead>
            <tbody>
              {metricsConfig.map(({ key, label, format, icon }) => {
                const value = metrics?.[key];
                const warning =
                  showWarnings && minimums[key] && minimums[key].compare(value)
                    ? minimums[key].message
                    : null;
                return (
                  <tr key={key} className="tw-border-t tw-border-gray-100 hover:tw-bg-gray-50 tw-transition-colors">
                    <td className="tw-px-4 tw-py-3 tw-align-middle">
                      <span className="tw-w-5 tw-h-5 tw-flex tw-items-center tw-justify-center tw-text-gray-400">
                        {React.cloneElement(icon, { className: "tw-w-4 tw-h-4 tw-text-gray-400" })}
                      </span>
                    </td>
                    <td className="tw-px-4 tw-py-3 tw-align-middle tw-text-gray-700 tw-whitespace-nowrap">{label}</td>
                    <td className="tw-px-4 tw-py-3 tw-align-middle tw-font-mono tw-text-gray-900 tw-break-all">{format(value)}</td>
                    <td className="tw-px-4 tw-py-3 tw-align-middle">
                      {warning && <Warning message={warning} />}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </aside>
    </div>
  );
};

export default ServerMetrics;
