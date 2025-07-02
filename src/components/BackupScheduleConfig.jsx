import React, { useState, useEffect } from 'react';
import Notification from './Notification';
import { saveBackupScheduleConfig, getBackupScheduleConfig } from '../util/lib';
import { useToast } from './Toast';

const BACKUP_FREQUENCIES = [
  { id: 'weekly', label: 'Weekly', description: 'Backup every week', icon: '📅' },
  { id: 'monthly', label: 'Monthly', description: 'Backup every month', icon: '🗓️' },
];

const BACKUP_TYPES = [
  { id: 'database', label: 'Database', description: 'WordPress database backup', icon: '🗄️' },
  { id: 'plugin', label: 'Plugins', description: 'All installed plugins', icon: '🔌' },
  { id: 'theme', label: 'Themes', description: 'All installed themes', icon: '🎨' },
  { id: 'uploads', label: 'Uploads Folder', description: 'Media files and uploads', icon: '📁' },
];

const VERSION_LIMITS = [
  { value: 1, label: 'Keep last 1 backup' },
  { value: 2, label: 'Keep last 2 backups' },
  { value: 3, label: 'Keep last 3 backups' },
  { value: 4, label: 'Keep last 4 backups' },
  { value: 5, label: 'Keep last 5 backups' },
];

export default function BackupScheduleConfig({ onCancel }) {
  const toast = useToast();
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [schedule, setSchedule] = useState({
    enabled: false,
    frequency: 'weekly',
    types: ['database'],
    versionLimit: 2,
  });

  useEffect(() => {
    const fetchSchedule = async () => {
      setIsLoading(true);
      const response = await getBackupScheduleConfig();

      if (response.data === false) {
        setIsLoading(false);
        return;
      }

      setSchedule(response.data);
      setIsLoading(false);
    };
    fetchSchedule();
  }, []);

  const handleFrequencyChange = (frequency) => {
    setSchedule(prev => ({ ...prev, frequency }));
  };

  const handleTypeChange = (typeId) => {
    setSchedule(prev => ({
      ...prev,
      types: prev.types.includes(typeId)
        ? prev.types.filter(id => id !== typeId)
        : [...prev.types, typeId],
    }));
  };

  const handleVersionLimitChange = (e) => {
    setSchedule(prev => ({ ...prev, versionLimit: parseInt(e.target.value) }));
  };

  const handleEnableToggle = () => {
    setSchedule(prev => ({ ...prev, enabled: !prev.enabled }));
  };

  const handleSaveSchedule = async () => {
    // TODO: Implement save functionality
    // console.log('Saving schedule:', schedule);
    setIsSaving(true);
    const response = await saveBackupScheduleConfig(schedule);
    // console.log(response);
    if (response.success) {
      toast({
        message: 'Backup schedule saved',
        type: 'success',
      });
    } else {
      toast({
        message: 'Backup schedule failed',
        type: 'error',
      });
    }
    setIsSaving(false);
  };

  if (isLoading) {
    return <div className="tw-flex tw-justify-center tw-items-center tw-h-screen">
      <div className="tw-animate-spin tw-rounded-full tw-h-10 tw-w-10 tw-border-t-2 tw-border-b-2 tw-border-blue-500"></div>
    </div>;
  }

  return (
    <div className="tw-space-y-6">
      {/* Warning Notification */}
      <Notification type="warning" title="Backup Scheduling">
        <p className="tw-text-xs">
          <strong>Heads up! 🚨 </strong> This feature only works effectively when your website has stable traffic.
        </p>
      </Notification>

      {/* Schedule Configuration Form */}
      <div className="tw-bg-white">

        <div className="tw-space-y-8">
          {/* Enable Backup Schedule */}
          <div className="tw-space-y-4">
            <div className="tw-flex tw-items-center tw-justify-between tw-p-4 tw-bg-gray-50 tw-rounded-lg tw-border tw-border-gray-200">
              <div className="tw-flex tw-items-center tw-gap-3">
                <div className="tw-flex-shrink-0 tw-p-2 tw-bg-blue-100 tw-rounded-lg">
                  <span className="tw-text-lg">⚡</span>
                </div>
                <div>
                  <h3 className="tw-text-base tw-font-semibold tw-text-gray-900">
                    Enable Backup Schedule
                  </h3>
                  <p className="tw-text-sm tw-text-gray-600">
                    Turn on automatic backup scheduling
                  </p>
                </div>
              </div>
              <button
                type="button"
                onClick={handleEnableToggle}
                className={`tw-relative tw-inline-flex tw-h-6 tw-w-11 tw-flex-shrink-0 tw-cursor-pointer tw-rounded-full tw-border-2 tw-border-transparent tw-transition-colors tw-duration-200 tw-ease-in-out focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2 ${
                  schedule.enabled ? 'tw-bg-blue-600' : 'tw-bg-gray-200'
                }`}
                role="switch"
                aria-checked={schedule.enabled}
              >
                <span
                  className={`tw-pointer-events-none tw-inline-block tw-h-5 tw-w-5 tw-transform tw-rounded-full tw-bg-white tw-shadow tw-ring-0 tw-transition tw-duration-200 tw-ease-in-out ${
                    schedule.enabled ? 'tw-translate-x-5' : 'tw-translate-x-0'
                  }`}
                />
              </button>
            </div>
          </div>

          {/* Backup Frequency */}
          <div className={`tw-space-y-4 ${!schedule.enabled ? 'tw-opacity-50 tw-pointer-events-none' : ''}`}>
            <div>
              <h3 className="tw-text-base tw-font-semibold tw-text-gray-900 tw-mb-2">
                Backup Frequency
              </h3>
              <p className="tw-text-sm tw-text-gray-600">
                Choose how often you want automatic backups to run
              </p>
            </div>
            
            <div className="tw-grid tw-gap-3 sm:tw-grid-cols-2">
              {BACKUP_FREQUENCIES.map((frequency) => (
                <label
                  key={frequency.id}
                  className={`tw-relative tw-flex tw-items-start tw-p-4 tw-rounded-lg tw-border tw-cursor-pointer tw-transition-all hover:tw-shadow-md ${
                    schedule.frequency === frequency.id
                      ? 'tw-border-blue-500 tw-bg-blue-50 tw-ring-2 tw-ring-blue-200'
                      : 'tw-border-gray-200 tw-bg-gray-50 hover:tw-border-gray-300'
                  }`}
                >
                  <input
                    type="radio"
                    name="frequency"
                    value={frequency.id}
                    checked={schedule.frequency === frequency.id}
                    onChange={() => handleFrequencyChange(frequency.id)}
                    className="tw-sr-only"
                  />
                  <div className="tw-flex tw-items-center tw-gap-3 tw-w-full">
                    <div className="tw-flex-shrink-0 tw-text-2xl">
                      {frequency.icon}
                    </div>
                    <div className="tw-flex-1 tw-min-w-0">
                      <div className="tw-text-sm tw-font-medium tw-text-gray-900">
                        {frequency.label}
                      </div>
                      <div className="tw-text-xs tw-text-gray-500">
                        {frequency.description}
                      </div>
                    </div>
                    {schedule.frequency === frequency.id && (
                      <div className="tw-flex-shrink-0 tw-w-5 tw-h-5 tw-bg-blue-500 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                        <svg className="tw-w-3 tw-h-3 tw-text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      </div>
                    )}
                  </div>
                </label>
              ))}
            </div>
          </div>

          {/* Backup Types */}
          <div className={`tw-space-y-4 ${!schedule.enabled ? 'tw-opacity-50 tw-pointer-events-none' : ''}`}>
            <div>
              <h3 className="tw-text-base tw-font-semibold tw-text-gray-900 tw-mb-2">
                Backup Types
              </h3>
              <p className="tw-text-sm tw-text-gray-600">
                Select what you want to include in your scheduled backups
              </p>
            </div>
            
            <div className="tw-grid tw-gap-3 sm:tw-grid-cols-1 lg:tw-grid-cols-1">
              {BACKUP_TYPES.map((type) => (
                <label
                  key={type.id}
                  className={`tw-relative tw-flex tw-items-start tw-p-4 tw-rounded-lg tw-border tw-cursor-pointer tw-transition-all hover:tw-shadow-md ${
                    schedule.types.includes(type.id)
                      ? 'tw-border-green-500 tw-bg-green-50 tw-ring-2 tw-ring-green-200'
                      : 'tw-border-gray-200 tw-bg-gray-50 hover:tw-border-gray-300'
                  }`}
                >
                  <input
                    type="checkbox"
                    checked={schedule.types.includes(type.id)}
                    onChange={() => handleTypeChange(type.id)}
                    className="tw-sr-only"
                  />
                  <div className="tw-flex tw-items-center tw-gap-3 tw-w-full">
                    <div className="tw-flex-shrink-0 tw-text-xl">
                      {type.icon}
                    </div>
                    <div className="tw-flex-1 tw-min-w-0">
                      <div className="tw-text-sm tw-font-medium tw-text-gray-900">
                        {type.label}
                      </div>
                      <div className="tw-text-xs tw-text-gray-500 tw-leading-tight">
                        {type.description}
                      </div>
                    </div>
                    {schedule.types.includes(type.id) && (
                      <div className="tw-flex-shrink-0 tw-w-5 tw-h-5 tw-bg-green-500 tw-rounded tw-flex tw-items-center tw-justify-center">
                        <svg className="tw-w-3 tw-h-3 tw-text-white" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                        </svg>
                      </div>
                    )}
                  </div>
                </label>
              ))}
            </div>
            
            {schedule.types.length === 0 && (
              <div className="tw-mt-3 tw-text-sm tw-text-red-600 tw-flex tw-items-center tw-gap-2 tw-p-3 tw-bg-red-50 tw-rounded-lg tw-border tw-border-red-200">
                <svg className="tw-w-4 tw-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Please select at least one backup type
              </div>
            )}
          </div>

          {/* Version Limit */}
          <div className={`tw-space-y-4 ${!schedule.enabled ? 'tw-opacity-50 tw-pointer-events-none' : ''}`}>
            <div>
              <h3 className="tw-text-base tw-font-semibold tw-text-gray-900 tw-mb-2">
                Backup Retention
              </h3>
              <p className="tw-text-sm tw-text-gray-600">
                Choose how many backup versions to keep (older backups will be automatically deleted)
              </p>
            </div>
            
            <div className="tw-max-w-full">
              <select
                value={schedule.versionLimit}
                onChange={handleVersionLimitChange}
                className="tw-block tw-w-full tw-p-3 tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50 tw-text-gray-900 tw-text-sm focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-200 focus:tw-bg-white tw-transition-colors"
              >
                {VERSION_LIMITS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Summary */}
          <div className={`tw-bg-gray-50 tw-rounded-lg tw-p-4 tw-border tw-border-gray-200 ${!schedule.enabled ? 'tw-opacity-50' : ''}`}>
            <h4 className="tw-text-sm tw-font-semibold tw-text-gray-900 tw-mb-3">Schedule Summary</h4>
                          <div className="tw-space-y-2 tw-text-sm tw-text-gray-600">
                <div className="tw-flex tw-items-center tw-gap-2">
                  <span className="tw-text-gray-400">⚡</span>
                  <span>Schedule: <strong className="tw-text-gray-900">{schedule.enabled ? 'Enabled' : 'Disabled'}</strong></span>
                </div>
                {schedule.enabled && (
                  <>
                    <div className="tw-flex tw-items-center tw-gap-2">
                      <span className="tw-text-gray-400">📅</span>
                      <span>Backup every <strong className="tw-text-gray-900">{schedule.frequency === 'weekly' ? 'week' : 'month'}</strong></span>
                    </div>
                    <div className="tw-flex tw-items-center tw-gap-2">
                      <span className="tw-text-gray-400">📦</span>
                      <span>Include: <strong className="tw-text-gray-900">{schedule.types.length} type{schedule.types.length !== 1 ? 's' : ''}</strong></span>
                    </div>
                    <div className="tw-flex tw-items-center tw-gap-2">
                      <span className="tw-text-gray-400">🗑️</span>
                      <span>Keep <strong className="tw-text-gray-900">last {schedule.versionLimit} backup{schedule.versionLimit !== 1 ? 's' : ''}</strong></span>
                    </div>
                  </>
                )}
              </div>
          </div>

          {/* Action Buttons */}
          <div className="tw-flex tw-justify-end tw-gap-3 tw-pt-6 tw-border-t tw-border-gray-200">
            <button
              type="button"
              className="tw-px-6 tw-py-2.5 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-shadow-sm hover:tw-bg-gray-50 hover:tw-border-gray-300 tw-transition-colors focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
              onClick={onCancel}
            >
              Cancel
            </button>
            <button
              type="button"
              onClick={handleSaveSchedule}
              disabled={schedule.enabled && schedule.types.length === 0 || isSaving}
              className={ `tw-px-6 tw-py-2.5 tw-text-sm tw-font-medium tw-text-white tw-bg-blue-600 tw-rounded-lg tw-shadow-sm hover:tw-bg-blue-700 tw-transition-colors focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed disabled:hover:tw-bg-blue-600 ${isSaving ? 'tw-opacity-50 tw-cursor-not-allowed' : ''}` }
            >
              {isSaving ? 'Saving...' : 'Save Schedule'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}