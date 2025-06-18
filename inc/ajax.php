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

  # get payload
  $payload = isset($_POST['payload']) ? $_POST['payload'] : array();

  $config_file = wp_backup_generate_config_file([
    'backup_name' => isset($payload['name']) ? $payload['name'] : '',
    'backup_types' => isset($payload['types']) ? $payload['types'] : array(),
  ]);

  // check error $config_file 
  if (is_wp_error($config_file)) {
    wp_send_json_error($config_file->get_error_message());
  }

  wp_send_json_success(array_merge(
    $config_file,
    array('next_step' => true)
  ));
}

// wp_backup_ajax_generate_backup_database
add_action('wp_ajax_wp_backup_ajax_generate_backup_database', 'wp_backup_ajax_generate_backup_database');
function wp_backup_ajax_generate_backup_database() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? $_POST['payload'] : array();

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup database
  $backup_ssid = $payload['name_folder'];
  $backup = new WP_Backup_Database(1000, $backup_ssid);

  // check error $backup
  if (is_wp_error($backup)) {
    wp_send_json_error($backup->get_error_message());
  }

  // if payload not backup_ssid, create new backup_ssid
  if (!isset($payload['backup_ssid']) || empty($payload['backup_ssid'])) {
    $result = $backup->startBackup();

    // check error $result
    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
      'backup_ssid' => $backup_ssid,
      'backup_database_status' => 'is_running',
      'next_step' => false,
    ]);
  } else {
    $progress = $backup->processStep();

    // check error $progress
    if (is_wp_error($progress)) {
      wp_send_json_error($progress->get_error_message());
    }
    
    if ($progress['done']) {
      $result = $backup->finishBackup();

      // check error $result
      if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
      }

      wp_send_json_success([
        'backup_ssid' => $backup_ssid,
        'backup_database_status' => 'is_done',
        'next_step' => true,
      ]);
    } else {
      wp_send_json_success([
        'backup_ssid' => $backup_ssid,
        'backup_database_status' => 'is_running',
        'progress' => $progress,
        'next_step' => false,
      ]);
    }
  }
}

// wp_backup_ajax_generate_backup_done
add_action('wp_ajax_wp_backup_ajax_generate_backup_done', 'wp_backup_ajax_generate_backup_done');
function wp_backup_ajax_generate_backup_done() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? $_POST['payload'] : array();

  // get backup size
  $backup_size = wp_backup_calc_folder_size($payload['backup_folder']);

  // update status in config file
  $result = wp_backup_update_config_file($payload['backup_folder'], [
    'backup_status' => 'completed',
    'backup_size' => wp_backup_format_bytes($backup_size),
  ]);

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'backup_process_status' => 'done',
  ]);
}