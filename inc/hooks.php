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