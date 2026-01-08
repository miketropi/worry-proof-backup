import { useState, useEffect } from 'react';
import useDummyPackStore from '../../util/dummyPackStore';
import Modal from '../Modal';
import { doInstallProcess } from '../../util/dummyPackLib';
import useDummyPackPreinstall from '../../hooks/useDummyPackPreinstall';

export default function InstallProcess() {
  const { setInstallProcessInProgress, setInstallProcessInProgressStep, setInstallProcessModalOpen, resetInstallProcess, ...rest } = useDummyPackStore();
  const { process, inProgress, inProgressStep, isModalOpen, packData } = rest.installProcess;
  const { resultPreinstall, errorPreinstall, loadingPreinstall } = useDummyPackPreinstall(packData?.ID);
  const [error, setError] = useState(null);
  const [payload, setPayload] = useState({});
  const [allInstallProcessDone, setAllInstallProcessDone] = useState(false);
  const [responsePerStep, setResponsePerStep] = useState({});

  const onStartInstallProcess = () => {
    setInstallProcessInProgress(true);
  };

  const installProcessHandler = async (process, retry = 0) => {
    let __payload = { ...payload, ...process.payload };
    const response = await doInstallProcess({
      ...process,
      payload: __payload,
    });
    if(response.success != true) {
      // alert('error: ' + response.data.error_message);

      let error_message = response.data.error_message ? response.data.error_message : response.data;
      if (retry > 4) {
        setError(error_message);
        return;
      }

      // recall
      setTimeout(() => {
        installProcessHandler(process, retry + 1);
      }, 4000);

      return;
    }

    let response_data = { ...payload, ...response.data };
    setPayload(response_data);
    setResponsePerStep({ ...responsePerStep, [process.step]: response.data });

    if(response.data.next_step == true) {
      setInstallProcessInProgressStep(inProgressStep + 1);
      return;
    } else {
      installProcessHandler({
        ...process,
        payload: {
          ...process.payload,
          ...response.data,
        },
      });
    }
  };

  useEffect(() => {
    // If inProgressStep is greater than or equal to process.length, the installation is done
    if (inProgress && inProgressStep >= process.length) {
      // Mark install process as complete
      setInstallProcessInProgress(false);
      setAllInstallProcessDone(true);
      return;
    }

    if(inProgress == true) {
      installProcessHandler(process[inProgressStep]);
    }
  }, [inProgressStep, inProgress])

  if(!packData) return <></>

  const processStepContent = (
    <>
      <div className="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-p-4 tw-rounded-md tw-mb-6 tw-flex tw-items-start tw-gap-3">
        <svg className="tw-w-5 tw-h-5 tw-text-yellow-400 tw-mt-0.5 tw-flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span className="tw-text-xs tw-text-yellow-900 tw-leading-relaxed tw-font-space-mono">
          <strong className="tw-font-semibold">Warning:</strong> Your data (database, plugins, uploads) will be lost during this process; for safety, please back it up before proceeding.<br />
          <hr className="tw-my-2 tw-border-t-1 tw-border-b-0 tw-border-yellow-200" />
          <strong className="tw-font-semibold">Important:</strong> To ensure the installation process works properly, please do not close your browser, reload the page, or close this popup while the installation is in progress.
        </span>
      </div>
      <ol className="tw-relative tw-border-l-2 tw-border-blue-200 tw-ml-4">
        {process.map((step, idx) => {
          const isCompleted = inProgressStep > idx; // step.step;
          const isCurrent = inProgressStep === idx; // step.step;
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
                {isCurrent && inProgress && !error && (
                  <div className="tw-mt-2 tw-flex tw-items-center tw-gap-2">
                    <svg className="tw-w-4 tw-h-4 tw-text-blue-400 tw-animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <circle className="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span className="tw-text-xs tw-text-blue-500 tw-font-medium">
                      In progress {responsePerStep[step.step]?.__log_process_status 
                      ? `(${responsePerStep[step.step]?.__log_process_status})` 
                      : ''}...
                    </span>
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

      {/* Start Button */}
      {(!inProgress && !error && !allInstallProcessDone) && (
        <div className="tw-mt-8 tw-flex tw-justify-end">
          <button
            type="button"
            className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-700 tw-rounded-md tw-font-medium tw-shadow-sm tw-transition-all tw-duration-150 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-gray-400 tw-mr-2"
            aria-label="Close"
            onClick={ () => {
              // setInstallProcessModalOpen(false);
              resetInstallProcess();
              setAllInstallProcessDone(false);
              setError(null);
            } }
          >
            Close
          </button>
          <button
            type="button"
            className="tw-inline-flex tw-items-center tw-px-5 tw-py-2.5 tw-bg-blue-600 hover:tw-bg-blue-700 tw-text-white tw-rounded-md tw-font-semibold tw-shadow-sm tw-transition-all tw-duration-150 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
            onClick={ onStartInstallProcess }
          >
            Yes, Install Now
          </button>
        </div>
      )}

      {allInstallProcessDone && (
        <div className="tw-mt-8 tw-flex tw-justify-end">
          <button
            type="button"
            className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-700 tw-rounded-md tw-font-medium tw-shadow-sm tw-transition-all tw-duration-150 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-gray-400 tw-mr-2"
            aria-label="Close"
            onClick={ () => {
              // setInstallProcessModalOpen(false);
              resetInstallProcess();
              setAllInstallProcessDone(false);
              setError(null);
            } }
          >
            Close
          </button>
          {/* Button to visit homepage if payload.current_domain exists */}
          {payload?.current_domain && (
            <a
              href={payload.current_domain}
              target="_blank"
              rel="noopener noreferrer"
              className="tw-inline-flex tw-items-center tw-px-5 tw-py-2.5 tw-bg-blue-600 hover:tw-bg-blue-700 tw-text-white hover:tw-text-white tw-rounded-md tw-font-semibold tw-shadow-sm tw-transition-all tw-duration-150 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500"
              style={{ marginLeft: 8 }}
            >
              Visit Homepage
            </a>
          )}
        </div>
      )}
    </>
  )

  return (
    <Modal
      isOpen={ isModalOpen }
      onClose={ () => {
        // setInstallProcessModalOpen(false);
        resetInstallProcess();
        setAllInstallProcessDone(false);
        setError(null);
      } }
      title={ `Install "${packData.name}"` }
      size='xl'
    >
      {
        (() => {
          if(loadingPreinstall) {
            return (
              <div className="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-12">
                <svg className="tw-w-4 tw-h-4 tw-text-blue-400 tw-animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle className="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span className="tw-font-semibold tw-mt-2">Validating package, please wait...</span>
              </div>
            );
          }

          if(errorPreinstall) {
            return (
              <>
                <div className="tw-bg-yellow-50 tw-border tw-border-yellow-200 tw-rounded-md tw-p-4 tw-flex tw-items-start tw-gap-3 tw-mx-auto tw-max-w-md">
                  <svg className="tw-w-5 tw-h-5 tw-text-yellow-400 tw-mt-0.5 tw-flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <span className="tw-block tw-font-semibold tw-text-yellow-900">Warning: Failed to Validate Package</span>
                    <div 
                      className="tw-text-xs tw-text-yellow-800 tw-font-space-mono" 
                      dangerouslySetInnerHTML={{ __html: errorPreinstall }}></div>
                  </div>
                </div>

                <div className="tw-mt-8 tw-flex tw-justify-end">
                  <button
                    type="button"
                    className="tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-bg-gray-100 hover:tw-bg-gray-200 tw-text-gray-700 tw-rounded-md tw-font-medium tw-shadow-sm tw-transition-all tw-duration-150 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-gray-400 tw-mr-2"
                    aria-label="Close"
                    onClick={ () => {
                      // setInstallProcessModalOpen(false);
                      resetInstallProcess();
                      setAllInstallProcessDone(false);
                      setError(null);
                    } }
                  >
                    Close
                  </button>
                </div>
              </>
            );
          }

          return processStepContent
        })()
      }
    </Modal>
  );
}