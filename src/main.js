/**
 * Main file
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';

(function($) {
  'use strict';

  // react dom ready
  const WpBackup_Init = () => {
    // root id "WP-BACKUP-ADMIN"
    const el = document.getElementById('WP-BACKUP-ADMIN');
    const root = createRoot(el);
    root.render(<App />);
  };

  // dom ready
  $(document).ready(function() {
    WpBackup_Init();
  });
})(jQuery);