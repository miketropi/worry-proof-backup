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

// create backup config file
add_action('wp_ajax_wp_backup_ajax_create_backup_config_file', 'wp_backup_ajax_create_backup_config_file');
function wp_backup_ajax_create_backup_config_file() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  // wp_send_json_success($_POST);

  # get payload
  $payload = isset($_POST['payload']) ? $_POST['payload'] : array();

  $config_file = wp_backup_generate_config_file([
    'backup_name' => isset($payload['name']) ? $payload['name'] : '',
    'backup_types' => isset($payload['types']) ? $payload['types'] : array(),
  ]);

  wp_send_json_success($config_file);
}