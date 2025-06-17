<?php 
/**
 * Admin page template
 * @since 1.0.0
 * @package WP-BACKUP
 */
?>

<div id="WP-BACKUP-ADMIN-PAGE" class="wrap font-space-mono">
  <div class="bg-white p-8 mb-8 border border-gray-200">
    <div class="flex items-center space-x-4 mb-4">
      <div class="bg-blue-100 p-3 rounded-lg">
        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
        </svg>
      </div>
      <h1 class="text-3xl font-bold text-gray-900"><?php echo esc_html(get_admin_page_title()); ?> <sup class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">v<?php echo esc_html($plugin_version); ?></sup></h1>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
      <div class="flex items-center gap-4">
        <div class="flex-shrink-0">
          <div class="bg-indigo-50 p-2.5 rounded-lg">
            <svg class="h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>
        <div>
          <p class="text-sm text-gray-600 leading-relaxed"><?php _e('🚀 Supercharge your WordPress site with our awesome backup solution! 💪 Create backups in a snap, manage them like a pro, and restore with confidence. Your site\'s security is our top priority - we\'ve got your back! 🛡️', 'wp-backup'); ?></p>
        </div>
      </div>
    </div>
  </div>
  <div id="WP-BACKUP-ADMIN">
    <!-- We are use react js for this content -->
  </div>
</div>