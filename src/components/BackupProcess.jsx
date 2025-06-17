import React from 'react';
import useBackupStore from '../util/store';

/**
 * BackupProcess - Stepper UI for backup process
 * Shows each step, highlights current, marks completed, and disables future steps
 * Uses Tailwind CSS with 'tw-' prefix for all classes
 */
const BackupProcess = () => {
  const { backupProcess, inProgress, inProgressStep } = useBackupStore();

  if (!inProgress || !backupProcess.length) return null;

  return (
    <div className="tw-bg-white tw-border tw-border-gray-200 tw-p-6 tw-mb-6">
      <h2 className="tw-text-lg tw-font-semibold tw-text-gray-800 tw-mb-6 tw-flex tw-items-center tw-gap-2">
        <svg className="tw-w-6 tw-h-6 tw-text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3M12 6a9 9 0 100 18 9 9 0 000-18z" />
        </svg>
        Backup Progress
      </h2>
      <ol className="tw-relative tw-border-l-2 tw-border-blue-200 tw-ml-4">
        {backupProcess.map((step, idx) => {
          const isCompleted = inProgressStep > step.step;
          const isCurrent = inProgressStep === step.step;
          return (
            <li key={step.step} className="tw-mb-8 tw-ml-6 tw-last:mb-0">
              <span className={`tw-absolute tw--left-4 tw-flex tw-items-center tw-justify-center tw-w-8 tw-h-8 tw-rounded-full tw-border-2 ${
                isCompleted
                  ? 'tw-bg-blue-500 tw-border-blue-500'
                  : isCurrent
                  ? 'tw-bg-white tw-border-blue-500 tw-animate-pulse'
                  : 'tw-bg-gray-100 tw-border-gray-300'
              }`}>
                {isCompleted ? (
                  <svg className="tw-w-5 tw-h-5 tw-text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                ) : (
                  <span className={`tw-text-base ${isCurrent ? 'tw-text-blue-600' : 'tw-text-gray-400'}`}>{step.step}</span>
                )}
              </span>
              <div className="tw-pl-4">
                <h3 className={`tw-text-base tw-font-semibold ${
                  isCompleted
                    ? 'tw-text-blue-600'
                    : isCurrent
                    ? 'tw-text-blue-700'
                    : 'tw-text-gray-500'
                }`}>
                  {step.name}
                </h3>
                <p className="tw-mt-1 tw-text-sm tw-text-gray-500">{step.description}</p>
                {isCurrent && (
                  <div className="tw-mt-2 tw-flex tw-items-center tw-gap-2">
                    <svg className="tw-w-4 tw-h-4 tw-text-blue-400 tw-animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <circle className="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span className="tw-text-xs tw-text-blue-500 tw-font-medium">In progress...</span>
                  </div>
                )}
                {isCompleted && (
                  <span className="tw-mt-2 tw-inline-block tw-text-xs tw-text-green-600 tw-font-medium">Completed</span>
                )}
              </div>
            </li>
          );
        })}
      </ol>
    </div>
  );
};

export default BackupProcess;
