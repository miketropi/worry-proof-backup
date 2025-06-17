<?php 
/**
 * Ajax actions
 */

// get backups
add_action('wp_ajax_wp_backup_ajax_get_backups', 'wp_backup_ajax_get_backups');
function wp_backup_ajax_get_backups() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get backups
  $backups = wp_backup_get_backups();
  wp_send_json_success($backups);
}