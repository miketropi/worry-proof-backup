import React, { useState, useMemo } from "react";
import useDummyPack from "./hooks/useDummyPack";
import Heading from "./components/dummy-pack/Heading";
import LoadingSkeleton from "./components/LoadingSkeleton";
import PackGrid from "./components/dummy-pack/PackGrid";
import { getDownloadPackUrl } from "./util/dummyPackLib";
import InstallProcess from "./components/dummy-pack/InstallProcess";
import useDummyPackStore from "./util/dummyPackStore";

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
      <div>Loading...</div>
    );
  }

  if (error) {
    return (
      <div className="tw-max-w-7xl tw-mx-auto tw-px-2 sm:tw-px-4 tw-py-10 tw-bg-white">
        <div className="tw-bg-red-50 tw-border tw-border-red-200 tw-rounded-md tw-p-4">
          <div className="tw-flex tw-items-center">
            <div className="tw-flex-shrink-0">
              <svg
                className="tw-h-5 tw-w-5 tw-text-red-400"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="tw-ml-3">
              <h3 className="tw-text-sm tw-font-medium tw-text-red-800">
                Error loading dummy packs
              </h3>
              <div className="tw-mt-2 tw-text-sm tw-text-red-700">
                <p>{error}</p>
              </div>
            </div>
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
      <h1></h1>
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