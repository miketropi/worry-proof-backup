/**
 * Dummy Center.
 * 
 * @package Worry_Proof_Backup
 * @subpackage Dummy_Center
 * @since 0.2.0
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import WorrprbaDummyCenter from './WorrprbaDummyCenter';

(function($) {
  'use strict';

  // react dom ready
  const WorrprbaDummyCenter_Init = () => {
    // root id "WORRPRBA-DUMMY-PACK-CENTER-ROOT"
    const el = document.getElementById('WORRPRBA-DUMMY-PACK-CENTER-ROOT');

    // check if el is exists
    if (!el) return;

    const root = createRoot(el); 
    root.render(<WorrprbaDummyCenter />);
  };

  // dom ready
  $(document).ready(function() {
    WorrprbaDummyCenter_Init();
  });
})(jQuery);