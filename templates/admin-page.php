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
      <h2 class="tw-text-xl tw-font-bold tw-text-gray-900 tw-flex tw-items-center tw-gap-2">
        <?php echo esc_html(get_admin_page_title()); ?>
        <div class="tw-flex tw-items-center tw-gap-2">
          <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-px-3 tw-py-1.5 tw-text-xs tw-font-semibold tw-bg-gradient-to-r tw-from-slate-100 tw-to-gray-100 tw-text-slate-700 tw-rounded-md tw-border tw-border-slate-200/60 tw-shadow-sm tw-backdrop-blur-sm">
            <span class="tw-w-1.5 tw-h-1.5 tw-bg-emerald-400 tw-rounded-full tw-animate-pulse" style="background-color: #10b981;"></span>
            v<?php echo esc_html($plugin_version); ?>
          </span>
          <?php if (WP_BACKUP_PLUGIN_BETA) { ?>
            <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-px-3 tw-py-1.5 tw-text-xs tw-font-semibold tw-bg-gradient-to-r tw-from-orange-100 tw-to-amber-100 tw-text-orange-700 tw-rounded-md tw-border tw-border-orange-200/60 tw-shadow-sm tw-backdrop-blur-sm tw-animate-pulse">
              <span class="tw-w-1.5 tw-h-1.5 tw-bg-orange-500 tw-rounded-full" style="background-color: #ff7f00;"></span>
              beta
            </span>
          <?php } ?>
        </div>
      </h2>
    </div>
    <div class="tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-p-6 tw-shadow-sm">
      <div class="tw-flex tw-items-center tw-gap-4">
        <div class="tw-flex-shrink-0">
          <div class="tw-bg-indigo-50 tw-p-2.5 tw-rounded-lg">
            🚀
          </div>
        </div>
        <div>
          <p class="tw-text-sm tw-text-gray-600 tw-leading-relaxed"><?php esc_html_e('Supercharge your WordPress site with our awesome backup solution! 💪 Create backups in a snap, manage them like a pro, and restore with confidence. Your site\'s security is our top priority - we\'ve got your back! 🛡️', 'wp-backup'); ?></p>
        </div>
      </div>
    </div>
  </div>
  <div id="WP-BACKUP-ADMIN">
    <!-- We are use react js for this content -->
  </div>
</div>