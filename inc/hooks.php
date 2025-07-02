<?php 
/**
 * Hooks file
 */

add_action( 'admin_menu', 'wp_backup_register_admin_page' );

// wp_backup:after_restore_database_success
add_action('wp_backup:after_restore_database_success', 'wp_backup_after_restore_database_success', 10, 1);
function wp_backup_after_restore_database_success($payload) {
  global $wpdb;

  // delete cache options
  wp_cache_delete( 'alloptions', 'options' );

  // update site url
  $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name IN ('siteurl', 'home')", esc_url_raw( $payload['current_domain'] ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
}

// wp_backup:after_save_backup_schedule_config
add_action('wp_backup:after_save_backup_schedule_config', 'wp_backup_after_save_backup_schedule_config', 10, 1);
function wp_backup_after_save_backup_schedule_config($config) {

  // delete history file
  $cron_manager = new WP_Backup_Cron_Manager('wp_backup_cron_manager', function() {
    // nothing to do
  });

  $cron_manager->delete_history_file();
}