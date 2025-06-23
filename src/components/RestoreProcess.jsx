import React, { useEffect, useState } from 'react';
import useBackupStore from '../util/store';
import { doRestoreProcess } from '../util/lib';
import { useToast } from './Toast';
import Modal from './Modal';

const RestoreProcess = () => {
  const {
    restoreProcess,
    setRestoreInProgress,
    setRestoreInProgressStep,
    fetchBackups_Fn,
  } = useBackupStore();

  const { process: restoreProcessSteps, inProgress, inProgressStep } = restoreProcess;

  const [responseOldStep, setResponseOldStep] = useState({});
  const [error, setError] = useState(null);
  const toast = useToast();

  const restoreProcessHandler = async (process) => {
    const response = await doRestoreProcess(process);

    if (response.success != true) {
      setError(response.data);
      return;
    }

    if (response.data.restore_process_status == 'done') {
      setRestoreInProgressStep(restoreProcessSteps.length + 1);
      setResponseOldStep({});
      await fetchBackups_Fn();

      toast({
        message: "🎉 Restore complete! Everything is back to its former glory. ✨",
        type: 'success',
      });

      return;
    }

    let response_data = { ...responseOldStep, ...response.data };
    setResponseOldStep(response_data);

    if (response.data.next_step == true) {
      setRestoreInProgressStep(process.step + 1);
      return;
    } else {
      restoreProcessHandler({
        ...process,
        payload: { ...response_data }
      });
    }
  };

  useEffect(() => {
    if (inProgressStep > restoreProcessSteps.length) {
      setTimeout(() => {
        setRestoreInProgress(false);
      }, 3000);
    }
  }, [inProgressStep]);

  useEffect(() => {
    if (inProgress == true) {
      let process = [...restoreProcessSteps].find((step) => step.step === inProgressStep);

      if (!process) {
        return;
      }

      process = {
        ...process,
        payload: { ...process.payload, ...responseOldStep }
      };
      restoreProcessHandler(process);
    }
  }, [inProgress, inProgressStep]);

  return (
    <Modal
      isOpen={inProgress && restoreProcessSteps.length > 0}
      onClose={() => setRestoreInProgress(false)}
      title="Restore Progress"
      size='lg'
    >
      <ol className="tw-relative tw-border-l-2 tw-border-blue-200 tw-ml-4">
        {restoreProcessSteps.map((step, idx) => {
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
                {error && inProgressStep === step.step && (
                  <div className="tw-mt-3 tw-p-3 tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-md">
                    <div className="tw-flex tw-items-start tw-gap-2">
                      <svg className="tw-w-4 tw-h-4 tw-text-red-500 tw-mt-0.5 tw-flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <p className="tw-text-sm tw-text-red-700 tw-leading-relaxed">{error}</p>
                    </div>
                  </div>
                )}
                {isCurrent && !error && (
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
    </Modal>
  );
};

export default RestoreProcess;