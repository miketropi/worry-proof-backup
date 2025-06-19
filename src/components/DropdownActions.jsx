import React, { useState, useRef, useEffect } from 'react';
import { createPortal } from 'react-dom';

/**
 * @typedef {Object} DropdownActionItem
 * @property {string} label - The label for the action
 * @property {React.ReactNode} [icon] - Optional icon
 * @property {() => void} onClick - Click handler
 * @property {boolean} [danger] - Optional: style as dangerous action
 */

/**
 * DropdownActions Props
 * @param {Object} props
 * @param {DropdownActionItem[]} props.items - Array of action items
 * @param {React.ReactNode} [props.trigger] - Optional custom trigger
 * @param {string} [props.className] - Optional extra classes for dropdown
 */
const DropdownActions = ({ items, trigger, className = '' }) => {
  const [open, setOpen] = useState(false);
  const menuRef = useRef(null);
  const buttonRef = useRef(null);
  const [focusedIndex, setFocusedIndex] = useState(-1);
  const [menuStyle, setMenuStyle] = useState({});

  // Close dropdown on outside click
  useEffect(() => {
    if (!open) return;
    function handleClick(e) {
      if (
        menuRef.current &&
        !menuRef.current.contains(e.target) &&
        buttonRef.current &&
        !buttonRef.current.contains(e.target)
      ) {
        setOpen(false);
      }
    }
    function handleEscape(e) {
      if (e.key === 'Escape') setOpen(false);
    }
    document.addEventListener('mousedown', handleClick);
    document.addEventListener('keydown', handleEscape);
    return () => {
      document.removeEventListener('mousedown', handleClick);
      document.removeEventListener('keydown', handleEscape);
    };
  }, [open]);

  // Keyboard navigation
  useEffect(() => {
    if (!open) return;
    function handleKey(e) {
      if (e.key === 'ArrowDown') {
        setFocusedIndex((i) => (i + 1) % items.length);
        e.preventDefault();
      } else if (e.key === 'ArrowUp') {
        setFocusedIndex((i) => (i - 1 + items.length) % items.length);
        e.preventDefault();
      } else if (e.key === 'Enter' && focusedIndex >= 0) {
        items[focusedIndex].onClick();
        setOpen(false);
      }
    }
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [open, focusedIndex, items]);

  // Focus first item when opened
  useEffect(() => {
    if (open) setFocusedIndex(0);
    else setFocusedIndex(-1);
  }, [open]);

  // Calculate menu position for portal
  useEffect(() => {
    if (open && buttonRef.current) {
      const rect = buttonRef.current.getBoundingClientRect();
      const isMobile = window.innerWidth < 640;
      if (isMobile) {
        setMenuStyle({
          position: 'fixed',
          left: 0,
          right: 0,
          top: rect.bottom + 8,
          zIndex: 9999,
        });
      } else {
        setMenuStyle({
          position: 'fixed',
          left: rect.right - 176, // 44*4 = 176px (w-44)
          top: rect.bottom + 8,
          zIndex: 9999,
        });
      }
    }
  }, [open]);

  // Default trigger: 3 dots
  const defaultTrigger = (
    <button
      ref={buttonRef}
      type="button"
      aria-haspopup="menu"
      aria-expanded={open}
      onClick={() => setOpen((v) => !v)}
      className="tw-inline-flex tw-items-center tw-justify-center tw-rounded-md tw-p-2 tw-text-gray-500 hover:tw-bg-gray-100 hover:tw-text-gray-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500"
    >
      <svg className="tw-w-5 tw-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="6" r="1.5" fill="currentColor" />
        <circle cx="12" cy="12" r="1.5" fill="currentColor" />
        <circle cx="12" cy="18" r="1.5" fill="currentColor" />
      </svg>
      <span className="tw-sr-only">Open actions</span>
    </button>
  );

  const menu = open ? (
    <div
      ref={menuRef}
      className={`tw-bg-white tw-border tw-border-gray-200 tw-rounded-md tw-shadow-lg tw-z-50 tw-py-1 tw-font-space-mono ${className}
        sm:tw-w-44
        tw-w-screen tw-left-0 tw-right-0 tw-mx-2 tw-max-w-xs tw-rounded-b-lg tw-pb-2 tw-pt-2 tw-bottom-auto
        md:tw-w-56 md:tw-mx-0 md:tw-max-w-none md:tw-left-auto md:tw-right-0 md:tw-rounded-md md:tw-py-1
      `}
      style={menuStyle}
      role="menu"
      tabIndex="-1"
    >
      {items.map((item, idx) => (
        <button
          key={item.label}
          type="button"
          className={`tw-flex tw-items-center tw-w-full tw-px-5 tw-py-3 tw-text-base tw-text-left tw-gap-3 tw-transition-colors tw-duration-150 focus:tw-bg-gray-100 hover:tw-bg-gray-50 focus:tw-outline-none
            ${item.danger ? 'tw-text-red-600 hover:tw-bg-red-50 focus:tw-bg-red-50' : 'tw-text-gray-700'}
            ${focusedIndex === idx ? 'tw-bg-gray-100' : ''}
            tw-rounded-none tw-font-medium
            sm:tw-px-4 sm:tw-py-2 sm:tw-text-sm sm:tw-gap-2
          `}
          onClick={() => {
            item.onClick();
            setOpen(false);
          }}
          role="menuitem"
          tabIndex={focusedIndex === idx ? 0 : -1}
          onMouseEnter={() => setFocusedIndex(idx)}
          style={{ touchAction: 'manipulation' }}
        >
          {item.icon && <span className="tw-w-5 tw-h-5 tw-flex tw-items-center">{item.icon}</span>}
          <span>{item.label}</span>
        </button>
      ))}
    </div>
  ) : null;

  return (
    <div className="tw-relative tw-inline-block">
      <span onClick={(e) => e.stopPropagation()}>{
        trigger
          ? React.cloneElement(trigger, {
              ref: buttonRef,
              onClick: (e) => {
                e.stopPropagation();
                setOpen((v) => !v);
                trigger.props.onClick && trigger.props.onClick(e);
              },
              'aria-haspopup': 'menu',
              'aria-expanded': open,
            })
          : defaultTrigger
      }</span>
      {open && createPortal(menu, document.body)}
    </div>
  );
};

export default DropdownActions;
