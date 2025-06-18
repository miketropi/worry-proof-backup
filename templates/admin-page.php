<?php 
/**
 * Admin page template
 * @since 1.0.0
 * @package WP-BACKUP
 */
?>

<div id="WP-BACKUP-ADMIN-PAGE" class="wrap tw-font-space-mono">
  <div class="tw-bg-white tw-p-8 tw-mb-8 tw-border tw-border-gray-200">
    <div class="tw-flex tw-items-center tw-space-x-4 tw-mb-4">
      <div class="tw-bg-blue-100 tw-p-3 tw-rounded-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="tw-w-6 tw-h-6 tw-text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-history-icon lucide-history"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
      </div>
      <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900"><?php echo esc_html(get_admin_page_title()); ?> <sup class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-0.5 tw-rounded-full tw-text-xs tw-font-medium tw-bg-blue-100 tw-text-blue-800">v<?php echo esc_html($plugin_version); ?></sup></h1>
    </div>
    <div class="tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-p-6 tw-shadow-sm">
      <div class="tw-flex tw-items-center tw-gap-4">
        <div class="tw-flex-shrink-0">
          <div class="tw-bg-indigo-50 tw-p-2.5 tw-rounded-lg">
            🚀
            <!-- <svg class="tw-h-5 tw-w-5 tw-text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg> -->
          </div>
        </div>
        <div>
          <p class="tw-text-sm tw-text-gray-600 tw-leading-relaxed"><?php _e('Supercharge your WordPress site with our awesome backup solution! 💪 Create backups in a snap, manage them like a pro, and restore with confidence. Your site\'s security is our top priority - we\'ve got your back! 🛡️', 'wp-backup'); ?></p>
        </div>
      </div>
    </div>
  </div>
  <div id="WP-BACKUP-ADMIN">
    <!-- We are use react js for this content -->
  </div>
</div>