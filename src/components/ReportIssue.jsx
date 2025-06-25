import React, { useState } from 'react';
import { useToast } from './Toast';
import { sendReportEmail } from '../util/lib';

const getSystemInfo = () => {
  if (typeof window !== 'undefined' && window.wp_backup_php_data) {
    const d = window.wp_backup_php_data;
    return {
      wp_version: d.server_metrics?.wp_version || '',
      php_version: d.server_metrics?.php_version || '',
      plugin_version: d.server_metrics?.plugin_version || '',
    };
  }
  return { wp_version: '', php_version: '', plugin_version: '' };
};

const ISSUE_TYPES = [
  { value: 'bug', label: 'Bug' },
  { value: 'feature', label: 'Feature Request' },
  { value: 'question', label: 'Question' },
];

const ReportIssue = () => {
  const toast = useToast();
  const [form, setForm] = useState({
    name: '',
    email: '',
    type: 'bug',
    description: '',
    screenshot: null,
    ...getSystemInfo(),
  });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e) => {
    const { name, value, files } = e.target;
    if (name === 'screenshot') {
      setForm((f) => ({ ...f, screenshot: files[0] }));
    } else {
      setForm((f) => ({ ...f, [name]: value }));
    }
  };

  const handleTypeChange = (value) => {
    setForm((f) => ({ ...f, type: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    if (!form.name || !form.email || !form.description) {
      setError('Please fill in all required fields.');
      return;
    }
    setSubmitting(true);

    const result = await sendReportEmail(form);

    if(result.success == true) {
      setSubmitting(false);
      toast({ message: 'Thank you for your report! We appreciate your feedback.', type: 'success' });
      // reset form
      setForm({
        name: '',
        email: '',
        type: 'bug',
        description: '',
      });
    } else {
      setError('Failed to send report. Please try again.');
    }
  };

  return (
    <form
      className="tw-max-w-3xl tw-mx-auto tw-px-2 sm:tw-px-0 tw-py-10 tw-bg-white"
      onSubmit={handleSubmit}
      style={{ fontFamily: 'inherit' }}
    >
      <h2 className="tw-text-2xl tw-font-semibold tw-text-gray-900 tw-mb-2 tw-text-center tw-col-span-2">Report an Issue</h2>
      <p className="tw-text-gray-600 tw-text-sm tw-text-center tw-mb-6 tw-col-span-2">Please use this template to help us resolve your issue faster. The more details you provide, the better we can help!</p>
      <div className="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-8 tw-items-start">
        {/* Left: Instructions/Checklist */}
        <div className="tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50 tw-rounded-lg tw-p-6 tw-border tw-border-blue-100 tw-mb-6 md:tw-mb-0 tw-shadow-sm">
          <div className="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-3 tw-flex tw-items-center tw-gap-2">
            <span>📝</span>
            Pro Tips for the Perfect Bug Report
          </div>
          <div className="tw-text-sm tw-text-gray-700 tw-space-y-3">
            <div className="tw-flex tw-items-start tw-gap-2">
              <span className="tw-text-blue-500 tw-font-bold">1.</span>
              <span><strong>What's broken?</strong> Keep calm and make a cup of coffee ☕ - describe exactly what went wrong</span>
            </div>
            <div className="tw-flex tw-items-start tw-gap-2">
              <span className="tw-text-blue-500 tw-font-bold">2.</span>
              <span><strong>Steps to recreate:</strong> Walk us through it like we're 5 👶 - what buttons did you click?</span>
            </div>
            <div className="tw-flex tw-items-start tw-gap-2">
              <span className="tw-text-blue-500 tw-font-bold">3.</span>
              <span><strong>Expected vs Reality:</strong> What should've happened vs what actually happened 🤔</span>
            </div>
            <div className="tw-flex tw-items-start tw-gap-2">
              <span className="tw-text-blue-500 tw-font-bold">4.</span>
              <span><strong>Visual proof:</strong> Screenshots are worth 1000 words 📸 - attach <b>image or video url</b> to describe the issue if you can!</span>
            </div>
            <div className="tw-mt-4 tw-p-3 tw-bg-blue-100 tw-rounded-md tw-text-xs tw-text-blue-800">
              <strong>💡 Pro tip:</strong> The more details you give us, the faster we can fix it and get you back to your day! 🚀
            </div>
          </div>
        </div>
        {/* Right: Form Fields */}
        <div className="tw-space-y-5">
          <div>
            <label htmlFor="name" className="tw-block tw-text-sm tw-font-medium tw-text-gray-800 tw-mb-1">Name</label>
            <input
              id="name"
              name="name"
              type="text"
              autoComplete="off"
              className="tw-block tw-w-full tw-py-2 tw-px-3 tw-rounded-md tw-border tw-border-gray-300 tw-bg-white tw-text-gray-900 tw-placeholder-gray-400 focus:tw-border-blue-500 focus:tw-ring-1 focus:tw-ring-blue-200 focus:tw-outline-none tw-transition"
              placeholder="Your name"
              value={form.name}
              onChange={handleChange}
              required
            />
          </div>
          <div>
            <label htmlFor="email" className="tw-block tw-text-sm tw-font-medium tw-text-gray-800 tw-mb-1">Email</label>
            <input
              id="email"
              name="email"
              type="email"
              autoComplete="off"
              className="tw-block tw-w-full tw-py-2 tw-px-3 tw-rounded-md tw-border tw-border-gray-300 tw-bg-white tw-text-gray-900 tw-placeholder-gray-400 focus:tw-border-blue-500 focus:tw-ring-1 focus:tw-ring-blue-200 focus:tw-outline-none tw-transition"
              placeholder="you@email.com"
              value={form.email}
              onChange={handleChange}
              required
            />
          </div>
          <div>
            <label className="tw-block tw-text-sm tw-font-medium tw-text-gray-800 tw-mb-1">Issue Type</label>
            <div className="tw-flex tw-gap-2">
              {ISSUE_TYPES.map((t) => (
                <button
                  key={t.value}
                  type="button"
                  className={`tw-px-4 tw-py-2 tw-rounded-md tw-text-sm tw-font-medium tw-border tw-transition-colors focus:tw-outline-none focus:tw-ring-1 focus:tw-ring-blue-200 ${form.type === t.value ? 'tw-bg-blue-600 tw-text-white tw-border-blue-600' : 'tw-bg-white tw-text-gray-700 tw-border-gray-300 hover:tw-bg-blue-50'}`}
                  onClick={() => handleTypeChange(t.value)}
                  aria-pressed={form.type === t.value}
                >
                  {t.label}
                </button>
              ))}
            </div>
          </div>
          <div>
            <label htmlFor="description" className="tw-block tw-text-sm tw-font-medium tw-text-gray-800 tw-mb-1">Description</label>
            <textarea
              id="description"
              name="description"
              rows={8}
              className="tw-block tw-w-full tw-py-2 tw-px-3 tw-rounded-md tw-border tw-border-gray-300 tw-bg-white tw-text-gray-900 tw-placeholder-gray-400 focus:tw-border-blue-500 focus:tw-ring-1 focus:tw-ring-blue-200 focus:tw-outline-none tw-transition"
              placeholder="Describe the issue, steps to reproduce, expected behavior, etc."
              value={form.description}
              onChange={handleChange}
              required
            />
          </div>
          {/* <div>
            <label htmlFor="screenshot" className="tw-block tw-text-sm tw-font-medium tw-text-gray-800 tw-mb-1">Screenshot (optional)</label>
            <input
              id="screenshot"
              name="screenshot"
              type="file"
              accept="image/*"
              className="tw-block tw-w-full tw-text-sm tw-text-gray-500"
              onChange={handleChange}
            />
            {form.screenshot && (
              <div className="tw-mt-1 tw-text-xs tw-text-gray-600">{form.screenshot.name}</div>
            )}
          </div> */}
          {error && <div className="tw-text-red-600 tw-text-sm tw-mt-2">{error}</div>}
          <button
            type="submit"
            disabled={submitting}
            className="tw-w-full tw-py-2 tw-rounded-md tw-bg-blue-600 tw-text-white tw-font-medium tw-text-base tw-transition-colors hover:tw-bg-blue-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-200 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed"
          >
            {submitting ? 'Submitting...' : 'Submit Report'}
          </button>
        </div>
      </div>
      <div className="tw-mt-10 tw-text-xs tw-text-gray-500 tw-text-center tw-space-x-3">
        <span>WP: {form.wp_version || 'N/A'}</span>
        <span>PHP: {form.php_version || 'N/A'}</span>
        <span>Plugin: {form.plugin_version || 'N/A'}</span>
      </div>
    </form>
  );
};

export default ReportIssue;
