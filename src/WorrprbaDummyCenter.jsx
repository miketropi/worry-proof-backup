import React, { useState, useMemo } from "react";
import useDummyPack from "./hooks/useDummyPack";
import Heading from "./components/dummy-pack/Heading";
import LoadingSkeleton from "./components/LoadingSkeleton";
import PackGrid from "./components/dummy-pack/PackGrid";
import { getDownloadPackUrl } from "./util/dummyPackLib";
import InstallProcess from "./components/dummy-pack/InstallProcess";
import useDummyPackStore from "./util/dummyPackStore";
import { RotateCcw } from "lucide-react";

export default function WorrprbaDummyCenter() {
  const { packs, isLoading, error } = useDummyPack();
  const [searchTerm, setSearchTerm] = useState("");
  const { buildInstallProcess } = useDummyPackStore();

  // Filter packages based on search term
  const filteredPackages = useMemo(() => {
    if (!Array.isArray(packs?.packages)) return [];
    if (!searchTerm.trim()) return packs.packages;
    
    return packs.packages.filter((pack) =>
      pack.name?.toLowerCase().includes(searchTerm.toLowerCase())
    );
  }, [packs?.packages, searchTerm]);

  if (isLoading) {
    return (
      <div className="tw-max-w-7xl tw-mx-auto tw-px-2 sm:tw-px-4 tw-py-10">
        <div className=" tw-p-6 tw-flex tw-flex-col tw-items-center tw-justify-center tw-min-h-[300px]">
          <div className="tw-flex tw-items-center tw-gap-3">
            <svg className="tw-w-6 tw-h-6 tw-text-blue-400 tw-animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle className="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
              <path className="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span className="tw-text-lg tw-text-blue-600 tw-font-semibold">Loading Dummy Packs...</span>
          </div>
          <span className="tw-text-xs tw-text-gray-700 tw-mt-2 tw-font-space-mono">Please wait while we fetch the latest packages for you.</span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="tw-max-w-7xl tw-mx-auto tw-px-2 sm:tw-px-4 tw-py-10"> 
      <div className="tw-bg-red-50 tw-border tw-border-red-200 tw-p-5 tw-rounded-lg tw-shadow-sm tw-flex tw-gap-4 tw-items-start">
        <div className="tw-mt-1 tw-flex-shrink-0">
          <svg className="tw-w-6 tw-h-6 tw-text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle className="tw-opacity-20" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M12 8v4m0 4h.01" />
          </svg>
        </div>
        <div>
          <div className="tw-text-sm tw-font-semibold tw-text-red-700 tw-mb-1 tw-font-space-mono">
            Could not connect to Dummy Pack Center
          </div>
          <p className="tw-text-xs tw-text-red-800 tw-mb-2 tw-font-space-mono">
            {typeof error === "string"
              ? error
              : "Sorry, we couldn't display available dummy packs right now. Please check your internet or try reloading this page in a moment."}
          </p>
          <button
            className="tw-inline-flex tw-items-center tw-gap-2 tw-px-4 tw-py-1.5 tw-bg-red-500 tw-text-white tw-font-semibold tw-rounded hover:tw-bg-red-600 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-red-400"
            onClick={() => window.location.reload()}
          >
            <RotateCcw className="tw-w-4 tw-h-4 tw-text-white" />
            Retry
          </button>
        </div>
      </div>
      </div>
    );
  }

  const onInstall = async (pack) => {
    buildInstallProcess(pack);
  };

  return (
    <div className="tw-max-w-7xl tw-mx-auto tw-px-2 sm:tw-px-4 tw-py-10">
      <Heading
        title={ packs?.name }
        subtitle={ packs?.description }
      />
      
      {/* Search Input */}
      <div className="tw-mt-8 tw-mb-6">
        <div className="tw-relative tw-max-w-md">
          <span className="tw-absolute tw-inset-y-0 tw-left-0 tw-pl-3 tw-flex tw-items-center pointer-events-none">
            <svg
              className="tw-h-5 tw-w-5 tw-text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"
              />
            </svg>
          </span>
          <input
            type="text"
            style={{
              width: "100%",
              padding: "0.6rem 2.5rem 0.6rem 2.5rem",
              borderRadius: "0rem",
              border: "1px solid #d1d5db", // Tailwind gray-300
              fontSize: ".9rem",
              lineHeight: "1.5",
              color: "#111827", // Tailwind gray-900
              backgroundColor: "#fff",
              transition: "border-color 0.2s, box-shadow 0.2s",
              boxSizing: "border-box"
            }}
            placeholder="Search packages by name..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
          {searchTerm && (
            <button
              type="button"
              className="tw-absolute tw-inset-y-0 tw-right-0 tw-pr-3 tw-flex tw-items-center"
              onClick={() => setSearchTerm("")}
            >
              <svg
                className="tw-h-5 tw-w-5 tw-text-gray-400 hover:tw-text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          )}
        </div>
        {searchTerm && (
          <p className="tw-mt-2 tw-text-sm tw-text-gray-600">
            Found {filteredPackages.length} package{filteredPackages.length !== 1 ? 's' : ''}
          </p>
        )}
      </div>

      {/* Content */}
      {filteredPackages.length > 0 ? (
        <>
          <PackGrid packs={filteredPackages} onInstall={onInstall} />
          <InstallProcess />
        </>
      ) : searchTerm ? (
        <div className="tw-text-gray-500 tw-mt-8 tw-text-center tw-py-12">
          <svg
            className="tw-mx-auto tw-h-12 tw-w-12 tw-text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <p className="tw-mt-4 tw-text-lg tw-font-medium">No packages found</p>
          <p className="tw-mt-2 tw-text-sm">Try adjusting your search term</p>
        </div>
      ) : (
        <div className="tw-text-gray-500 tw-mt-8">
          No dummy packs found. Please try again later.
        </div>
      )}
    </div>
  );
}