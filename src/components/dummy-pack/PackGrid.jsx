import React, { useState } from 'react';
import { ExternalLink, Download, Code, Server, FileArchive, Package, Info, LockKeyhole } from 'lucide-react';
import { validateVersionPackageRequirements } from '../../util/dummyPackLib';
import PluginRequirementsModal from './PluginRequirementsModal';

/**
 * PackGrid Component
 * 
 * Displays dummy packs in a responsive grid layout with clean, minimal styling.
 * 
 * @param {Array} packs - Array of pack objects
 * @param {Function} onInstall - Callback function when install button is clicked (pack) => void
 * @param {Function} onPreview - Callback function when preview button is clicked (pack) => void
 */
const PackGrid = ({ packs = [], onInstall, onPreview }) => {
  if (!packs || packs.length === 0) {
    return (
      <div className="tw-text-center tw-py-12 tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg">
        <FileArchive className="tw-mx-auto tw-h-12 tw-w-12 tw-text-gray-400 tw-mb-4" />
        <h3 className="tw-text-sm tw-font-medium tw-text-gray-900 tw-mb-1">No packs available</h3>
        <p className="tw-text-sm tw-text-gray-500">
          Check back soon for available dummy packs.
        </p>
      </div>
    );
  }

  return (
    <div className="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
      {packs.map((pack) => (
        <PackCard
          key={pack.ID}
          pack={pack}
          onInstall={onInstall}
          onPreview={onPreview}
        />
      ))}
    </div>
  );
};

/**
 * PackCard Component
 * 
 * Individual card with clean, minimal design.
 */
const PackCard = ({ pack, onInstall, onPreview }) => {
  const [isPluginModalOpen, setIsPluginModalOpen] = useState(false);

  const handlePreview = (e) => {
    e.preventDefault();
    if (pack.preview_url && pack.preview_url !== '#') {
      if (onPreview) {
        onPreview(pack);
      } else {
        window.open(pack.preview_url, '_blank', 'noopener,noreferrer');
      }
    }
  };

  const handleInstall = (e) => {
    e.preventDefault();
    if (onInstall) {
      onInstall(pack);
    }
  };

  const handleOpenPluginModal = (e) => {
    e.preventDefault();
    setIsPluginModalOpen(true);
  };

  const buttonInstallActiveByPluginRequirements = pack.validated_required_plugins ? pack.validated_required_plugins.passed : true;
  console.log(buttonInstallActiveByPluginRequirements)
  return (
    <div className="tw-bg-white tw-border tw-border-gray-200 tw-overflow-hidden hover:tw-shadow-md tw-transition-shadow tw-duration-200">
      {/* Image */}
      <div className="tw-relative tw-w-full tw-h-48 tw-bg-gray-50 tw-overflow-hidden tw-group">
        {pack.image ? (
          <img
            src={pack.image}
            alt={pack.name}
            className="tw-w-full tw-h-full tw-object-cover tw-border-none"
            loading="lazy"
          />
        ) : (
          <div className="tw-w-full tw-h-full tw-flex tw-items-center tw-justify-center">
            <Package className="tw-w-16 tw-h-16 tw-text-gray-300" />
          </div>
        )}

        {pack?.free && (
        <span className="tw-absolute tw-top-2 tw-left-2 tw-bg-green-500 tw-text-white tw-text-xs tw-font-semibold tw-px-2 tw-py-0.5 tw-rounded-full tw-z-10 tw-font-space-mono tw-border-2 tw-border-white">
          Free
        </span>
        )}
      </div>

      {/* Content */}
      <div className="tw-p-4">
        {/* Title */}
        <h3 className="tw-text-base tw-font-semibold tw-text-gray-900 tw-mb-2 tw-line-clamp-1">
          {pack.name}
        </h3>

        {/* Description */}
        {pack.description && (
          <p className="tw-text-xs tw-text-gray-600 tw-leading-relaxed tw-mb-3 tw-line-clamp-2 tw-font-space-mono" title={ pack.description }>
            {pack.description
              ? pack.description.split(" ").slice(0, 10).join(" ") +
                (pack.description.split(" ").length > 10 ? "..." : "")
              : ""}
          </p>
        )}

        {/* Tags */}
        {pack.tags && pack.tags.length > 0 && (
          <div className="tw-flex tw-flex-wrap tw-gap-2 tw-mb-3">
            {pack.tags.map((tag, index) => (
              <span
                key={index}
                className="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-rounded-md tw-bg-blue-50 tw-text-blue-700 tw-border tw-border-blue-100"
              >
                {tag}
              </span>
            ))}
          </div>
        )}

        {/* Meta Info */}
        <div className="tw-space-y-2 tw-mb-4 tw-font-space-mono">
          {/* Size */}
          {/* {pack.size && (
            <div className="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-600">
              <FileArchive className="tw-w-3.5 tw-h-3.5 tw-text-gray-400" />
              <span>{pack.size}</span>
            </div>
          )} */}

          {/* Requirements */}
          {pack.required && pack.required.length > 0 && (
            <div className="tw-mb-2 tw-border-gray-100 tw-pt-2">
              <ul className="tw-space-y-1 tw-pl-0 tw-pt-1">
                {pack.required.map((req, index) => (
                  <li
                    key={index}
                    className="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-700 tw-pl-1 tw-mb-0"
                  >
                    <>
                      <span>
                        <strong className="tw-font-semibold">
                          {req.type.replace(/_/g, " ").replace(/\b\w/g, l => l.toUpperCase())} {req.value}
                          {req.type === 'php_version' ? '+' : '+'}
                        </strong>
                      </span>
                      {validateVersionPackageRequirements(req.type, req.value) ? (
                        <span className="tw-inline-flex tw-items-center tw-gap-1 tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-semibold" title="Compatible">
                          <svg className="tw-w-3 tw-h-3" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                          </svg>
                        </span>
                      ) : (
                        <span className="tw-inline-flex tw-items-center tw-gap-1 tw-bg-red-100 tw-text-red-700 tw-px-2 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-semibold" title="Not compatible">
                          <svg className="tw-w-3 tw-h-3" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </span>
                      )}
                    </>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Plugin Requirements Summary */}
          {
            pack.validated_required_plugins && (
              <div
                className="tw-flex tw-items-center tw-gap-2 tw-cursor-pointer tw-rounded-md"
                role="button"
                tabIndex={0}
                title="View required plugins"
                onClick={() => setIsPluginModalOpen(true)}
                onKeyPress={e => {
                  if (e.key === 'Enter' || e.key === ' ') setIsPluginModalOpen(true);
                }}
                style={{ minHeight: '28px' }}
              >
                {/* Show an icon and summary indicating if all plugin requirements are met */}
                {pack.validated_required_plugins.passed ? (
                  <>
                    <span className="tw-inline-flex tw-items-center tw-bg-green-100 tw-text-green-700 tw-px-2 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-semibold">
                      <svg className="tw-w-4 tw-h-4 tw-mr-1" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                      All plugin requirements met
                    </span>
                  </>
                ) : (
                  <>
                    <span className="tw-inline-flex tw-items-center tw-bg-yellow-50 tw-text-yellow-700 tw-px-2 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-semibold">
                      <svg className="tw-w-4 tw-h-4 tw-mr-1" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2" fill="none" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01" />
                      </svg>
                      Plugin requirements
                    </span>
                  </>
                )}
              </div>
            )
          }
        </div>

        {/* Actions */}
        <div className="tw-flex tw-gap-2 tw-pt-3 tw-border-t tw-border-gray-200">
          {pack.preview_url && pack.preview_url !== '#' && (
            <button
              onClick={handlePreview}
              className="tw-flex-1 tw-inline-flex tw-items-center tw-justify-center tw-gap-1.5 tw-px-3 tw-py-2 tw-text-xs tw-font-semibold tw-text-gray-700 tw-bg-white tw-border tw-border-gray-300 tw-rounded-md hover:tw-bg-gray-50 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-transition-colors __tw-font-space-mono"
            >
              <ExternalLink className="tw-w-4 tw-h-4" />
              <span>Preview</span>
            </button>
          )}
          {
            (() => {
              if(pack.locked) {
                return <button
                  className={
                    "tw-flex-1 tw-inline-flex tw-items-center tw-justify-center tw-gap-1.5 tw-px-3 tw-py-2 tw-text-xs tw-font-semibold tw-text-white tw-bg-blue-600 tw-border tw-border-transparent tw-rounded-md hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-transition-colors __tw-font-space-mono tw-opacity-60 tw-cursor-not-allowed hover:tw-bg-blue-600"
                  }
                  disabled={true}
                >
                  <LockKeyhole className="tw-w-4 tw-h-4" />
                  <span>Locked</span>
                </button>
              }

              return <button
                onClick={handleInstall}
                className={
                  "tw-flex-1 tw-inline-flex tw-items-center tw-justify-center tw-gap-1.5 tw-px-3 tw-py-2 tw-text-xs tw-font-semibold tw-text-white tw-bg-blue-600 tw-border tw-border-transparent tw-rounded-md hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-blue-500 tw-transition-colors __tw-font-space-mono" +
                  (
                    (pack.required && 
                    pack.required.length > 0 && 
                    !pack.required.every(req => validateVersionPackageRequirements(req.type, req.value)) ||
                    !buttonInstallActiveByPluginRequirements)
                      ? " tw-opacity-60 tw-cursor-not-allowed hover:tw-bg-blue-600"
                      : ""
                  )
                }
                disabled={
                  (pack.required &&
                  pack.required.length > 0 &&
                  !pack.required.every(req => validateVersionPackageRequirements(req.type, req.value)) ||
                  !buttonInstallActiveByPluginRequirements)
                }
              >
                <Download className="tw-w-4 tw-h-4" />
                <span>Install</span>
              </button>
            })()
          }
          
        </div>
      </div>

      {/* Plugin Requirements Modal */}
      {pack.validated_required_plugins && (
        <PluginRequirementsModal 
          isOpen={ isPluginModalOpen } 
          onClose={ () => setIsPluginModalOpen(false) } 
          validatedRequiredPlugins={pack.validated_required_plugins} />
      )}
    </div>
  );
};

export default PackGrid;

