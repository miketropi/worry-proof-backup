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
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

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
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

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

// backup plugins
add_action('wp_ajax_wp_backup_ajax_generate_backup_plugin', 'wp_backup_ajax_generate_backup_plugin');
function wp_backup_ajax_generate_backup_plugin() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }
  
  // create backup plugin
  $backup = new WP_Backup_File_System([
    'source_folder' => WP_PLUGIN_DIR,
    'destination_folder' => $payload['name_folder'],
    'zip_name' => 'plugins.zip',
    'exclude' => ['wp-backup'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
  ]);

  // check error $backup
  if (is_wp_error($backup)) {
    wp_send_json_error($backup->get_error_message());
  }

  // run backup
  $zip_file = $backup->runBackup();

  // check error $zip_file
  if (is_wp_error($zip_file)) {
    wp_send_json_error($zip_file->get_error_message());
  }

  wp_send_json_success([
    'backup_plugin_status' => 'done',
    'plugin_zip_file' => $zip_file,
    'next_step' => true,
  ]);
}

// backup themes
add_action('wp_ajax_wp_backup_ajax_generate_backup_theme', 'wp_backup_ajax_generate_backup_theme');
function wp_backup_ajax_generate_backup_theme() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup theme
  $backup = new WP_Backup_File_System([
    'source_folder' => WP_CONTENT_DIR . '/themes/',
    'destination_folder' => $payload['name_folder'],
    'zip_name' => 'themes.zip',
  ]);

  // check error $backup
  if (is_wp_error($backup)) {
    wp_send_json_error($backup->get_error_message());
  }

  // run backup
  $zip_file = $backup->runBackup();

  // check error $zip_file
  if (is_wp_error($zip_file)) {
    wp_send_json_error($zip_file->get_error_message());
  }

  wp_send_json_success([
    'backup_theme_status' => 'done',
    'theme_zip_file' => $zip_file,
    'next_step' => true,
  ]);
}

// folder uploads
add_action('wp_ajax_wp_backup_ajax_generate_backup_uploads', 'wp_backup_ajax_generate_backup_uploads');
function wp_backup_ajax_generate_backup_uploads() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');
  
  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup uploads
  $backup = new WP_Backup_File_System([
    'source_folder' => WP_CONTENT_DIR . '/uploads/',
    'destination_folder' => $payload['name_folder'],
    'zip_name' => 'uploads.zip',
    'exclude' => ['wp-backup', 'wp-backup-zip'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
  ]);

  // check error $backup
  if (is_wp_error($backup)) {
    wp_send_json_error($backup->get_error_message());
  }

  // run backup
  $zip_file = $backup->runBackup();

  // check error $zip_file
  if (is_wp_error($zip_file)) {
    wp_send_json_error($zip_file->get_error_message());
  }

  wp_send_json_success([
    'backup_uploads_status' => 'done',
    'uploads_zip_file' => $zip_file,
    'next_step' => true,
  ]);
}

// wp_backup_ajax_generate_backup_done
add_action('wp_ajax_wp_backup_ajax_generate_backup_done', 'wp_backup_ajax_generate_backup_done');
function wp_backup_ajax_generate_backup_done() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

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

// wp_backup_ajax_delete_backup_folder
add_action('wp_ajax_wp_backup_ajax_delete_backup_folder', 'wp_backup_ajax_delete_backup_folder');
function wp_backup_ajax_delete_backup_folder() {
  // wp_send_json($_POST);
  // die();
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // name_folder backup folder
  $name_folder = $payload['name_folder'];

  // backup folder  get wp uploads + /wp-backup/name_folder
  $backup_folder_dir = WP_CONTENT_DIR . '/uploads/wp-backup/' . $name_folder;
  
  // delete backup folder
  $result = wp_backup_remove_folder($backup_folder_dir);
  
  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'message' => 'Backup folder deleted successfully',
  ]);
}

// wp_backup_ajax_restore_read_backup_config_file
add_action('wp_ajax_wp_backup_ajax_restore_read_backup_config_file', 'wp_backup_ajax_restore_read_backup_config_file');
function wp_backup_ajax_restore_read_backup_config_file() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $types = $payload['types'];

  // get backup config file
  $backup_config = wp_backup_get_config_file($folder_name);

  // check error $backup_config
  if (is_wp_error($backup_config)) {
    wp_send_json_error($backup_config->get_error_message());
  }

  wp_send_json_success(array_merge([
    'read_backup_config_file_status' => 'done',
    'next_step' => true,
  ], $backup_config));
}

// wp_backup_ajax_restore_database
add_action('wp_ajax_wp_backup_ajax_restore_database', 'wp_backup_ajax_restore_database');
add_action('wp_ajax_nopriv_wp_backup_ajax_restore_database', 'wp_backup_ajax_restore_database');
function wp_backup_ajax_restore_database() {
  # check nonce
  // check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $backup_prefix = $payload['table_prefix'];

  $exclude_tables = isset($payload['exclude_tables']) ? $payload['exclude_tables'] : [];
  $exclude_tables = apply_filters('wp_backup:restore_database_exclude_tables', $exclude_tables, $payload);

  $restore_database = new WP_Restore_Database($folder_name, $exclude_tables, $backup_prefix);

  // check error $restore_database
  if (is_wp_error($restore_database)) {
    wp_send_json_error($restore_database->get_error_message());
  }

  if(!isset($payload['restore_database_ssid']) || empty($payload['restore_database_ssid'])) {
    $progress = $restore_database->startRestore();

    // check error $progress
    if (is_wp_error($progress)) {
      wp_send_json_error($progress->get_error_message());
    }

    wp_send_json_success([
      'restore_database_ssid' => $folder_name,
      'next_step' => false,
    ]);
  } else {
    $progress = $restore_database->processStep();
    
    // check error $progress
    if (is_wp_error($progress)) {
      wp_send_json_error($progress->get_error_message());
    }

    if($progress['done']) {
      $result = $restore_database->finishRestore();

      // check error $result
      if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
      }

      // create hook after restore database successfully
      do_action('wp_backup:after_restore_database_success', $payload);

      wp_send_json_success([
        'restore_database_ssid' => $folder_name,
        'restore_database_status' => 'done',
        'next_step' => true,
      ]);
    } else {
      
      wp_send_json_success([
        'restore_database_ssid' => $folder_name,
        'restore_database_status' => 'is_running',
        'next_step' => false,
        'progress' => $progress,
      ]);
    }
  }
}

// wp_backup_ajax_restore_plugin 
add_action('wp_ajax_wp_backup_ajax_restore_plugin', 'wp_backup_ajax_restore_plugin');
add_action('wp_ajax_nopriv_wp_backup_ajax_restore_plugin', 'wp_backup_ajax_restore_plugin');
function wp_backup_ajax_restore_plugin() {
  # check nonce
  // check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/wp-backup/' . $folder_name . '/plugins.zip';
  
  // validate $path_zip_file
  if (!file_exists($path_zip_file)) {
    wp_send_json_error('Zip file not found');
  }

  $restore_plugin = new WP_Restore_File_System([
    'zip_file' => $path_zip_file,
    'destination_folder' => WP_PLUGIN_DIR,
    'overwrite_existing' => true,
    'exclude' => ['wp-backup'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
  ]);

  // check error $restore_plugin
  if (is_wp_error($restore_plugin)) {
    wp_send_json_error($restore_plugin->get_error_message());
  }

  $result = $restore_plugin->runRestore();

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'restore_plugin_status' => 'done',
    'next_step' => true,
  ]);
}

// wp_backup_ajax_restore_theme
add_action('wp_ajax_wp_backup_ajax_restore_theme', 'wp_backup_ajax_restore_theme');
add_action('wp_ajax_nopriv_wp_backup_ajax_restore_theme', 'wp_backup_ajax_restore_theme');
function wp_backup_ajax_restore_theme() {
  # check nonce
  // check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');
  
  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/wp-backup/' . $folder_name . '/themes.zip';
  
  // validate $path_zip_file
  if (!file_exists($path_zip_file)) {
    wp_send_json_error('Zip file not found');
  }

  $restore_theme = new WP_Restore_File_System([
    'zip_file' => $path_zip_file,
    'destination_folder' => WP_CONTENT_DIR . '/themes/',
    'overwrite_existing' => true,
  ]);

  // check error $restore_theme
  if (is_wp_error($restore_theme)) {
    wp_send_json_error($restore_theme->get_error_message());
  }

  $result = $restore_theme->runRestore();

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'restore_theme_status' => 'done',
    'next_step' => true,
  ]);
}

// wp_backup_ajax_restore_uploads
add_action('wp_ajax_wp_backup_ajax_restore_uploads', 'wp_backup_ajax_restore_uploads');
add_action('wp_ajax_nopriv_wp_backup_ajax_restore_uploads', 'wp_backup_ajax_restore_uploads');
function wp_backup_ajax_restore_uploads() {
  # check nonce
  // check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');
  
  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/wp-backup/' . $folder_name . '/uploads.zip';
  
  // validate $path_zip_file
  if (!file_exists($path_zip_file)) {
    wp_send_json_error('Zip file not found');
  }

  $restore_uploads = new WP_Restore_File_System([
    'zip_file' => $path_zip_file,
    'destination_folder' => WP_CONTENT_DIR . '/uploads/',
    'overwrite_existing' => true,
    'exclude' => ['wp-backup', 'wp-backup-zip'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
  ]);

  // check error $restore_uploads
  if (is_wp_error($restore_uploads)) {
    wp_send_json_error($restore_uploads->get_error_message());
  }

  $result = $restore_uploads->runRestore();

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'restore_uploads_status' => 'done',
    'next_step' => true,
  ]);
}

// wp_backup_ajax_restore_done
add_action('wp_ajax_wp_backup_ajax_restore_done', 'wp_backup_ajax_restore_done');
add_action('wp_ajax_nopriv_wp_backup_ajax_restore_done', 'wp_backup_ajax_restore_done');
function wp_backup_ajax_restore_done() {
  # check nonce
  // check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // create hook after restore process successfully
  do_action('wp_backup:after_restore_process_success', $payload);

  wp_send_json_success([
    'restore_process_status' => 'done',
  ]);
}

// wp_backup_ajax_send_report_email
add_action('wp_ajax_wp_backup_ajax_send_report_email', 'wp_backup_ajax_send_report_email');
function wp_backup_ajax_send_report_email() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // send report email
  $result = wp_backup_send_report_email($payload);

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success(true);
}


// wp_backup_ajax_upload_backup_file
add_action('wp_ajax_wp_backup_ajax_upload_backup_file', 'wp_backup_ajax_upload_backup_file');
function wp_backup_ajax_upload_backup_file() {
  // check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  if (!isset($_FILES['file'])) {
    wp_send_json_error('No file uploaded');
  }

  $file = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // Check file size
  $max_size = wp_max_upload_size();
  if ($file['size'] > $max_size) {
      wp_send_json_error('File too large');
  }

  // Allow only specific extensions
  $allowed = ['zip'];
  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  if (!in_array(strtolower($ext), $allowed)) {
      wp_send_json_error('Invalid file type');
  }

  // require libs/upload-backup.php
  require_once(WP_BACKUP_PLUGIN_PATH . 'inc/libs/upload-backup.php');

  // wp_send_json_success($file);
  // die();

  // upload backup file
  $uploader = new WP_Upload_Backup_File([
    'file' => $file,
    'session_id' => 'backup_upload_' . uniqid(),
    'overwrite' => true,
  ]);

  // handle upload
  $result = $uploader->handle();

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success($result);
}


// wp_backup_ajax_get_backup_download_zip_path 
add_action('wp_ajax_wp_backup_ajax_get_backup_download_zip_path', 'wp_backup_ajax_get_backup_download_zip_path');
function wp_backup_ajax_get_backup_download_zip_path() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $backup_folder_name = $payload['folder_name'];

  $backup_download_zip_path = wp_backup_get_backup_download_zip_path($backup_folder_name);

  wp_send_json_success($backup_download_zip_path);
}

// wp_backup_ajax_create_backup_zip
add_action('wp_ajax_wp_backup_ajax_create_backup_zip', 'wp_backup_ajax_create_backup_zip');
function wp_backup_ajax_create_backup_zip() {
  # check nonce
  check_ajax_referer('wp_backup_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $backup_folder_name = $payload['folder_name'];

  $backup_create_zip = wp_backup_create_backup_zip($backup_folder_name);

  wp_send_json_success($backup_create_zip);
}