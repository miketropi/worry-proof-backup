<?php 
/**
 * Ajax actions
 */

// get backups
add_action('wp_ajax_worrpb_ajax_get_backups', 'worrpb_ajax_get_backups');
function worrpb_ajax_get_backups() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get backups
  $backups = worrpb_get_backups();
  wp_send_json_success($backups);
}

// create backup config file
add_action('wp_ajax_worrpb_ajax_create_backup_config_file', 'worrpb_ajax_create_backup_config_file');
function worrpb_ajax_create_backup_config_file() {

  # check nonce 
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $config_file = worrpb_generate_config_file([
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

// worrpb_ajax_generate_backup_database
add_action('wp_ajax_worrpb_ajax_generate_backup_database', 'worrpb_ajax_generate_backup_database');
function worrpb_ajax_generate_backup_database() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup database
  $backup_ssid = $payload['name_folder'];
  $backup = new WORRPB_Database(1000, $backup_ssid);

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
add_action('wp_ajax_worrpb_ajax_generate_backup_plugin', 'worrpb_ajax_generate_backup_plugin');
function worrpb_ajax_generate_backup_plugin() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }
  
  // create backup plugin
  $backup = new WORRPB_File_System([
    'source_folder' => WP_PLUGIN_DIR,
    'destination_folder' => $payload['name_folder'],
    'zip_name' => 'plugins.zip',
    'exclude' => ['worry-proof-backup'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
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
add_action('wp_ajax_worrpb_ajax_generate_backup_theme', 'worrpb_ajax_generate_backup_theme');
function worrpb_ajax_generate_backup_theme() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup theme
  $backup = new WORRPB_File_System([
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
add_action('wp_ajax_worrpb_ajax_generate_backup_uploads', 'worrpb_ajax_generate_backup_uploads');
function worrpb_ajax_generate_backup_uploads() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');
  
  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup uploads
  $backup = new WORRPB_File_System([
    'source_folder' => WP_CONTENT_DIR . '/uploads/',
    'destination_folder' => $payload['name_folder'],
    'zip_name' => 'uploads.zip',
    'exclude' => ['wp-backup', 'wp-backup-zip', 'wp-backup-cron-manager'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
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

// worrpb_ajax_generate_backup_done
add_action('wp_ajax_worrpb_ajax_generate_backup_done', 'worrpb_ajax_generate_backup_done');
function worrpb_ajax_generate_backup_done() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // get backup size
  $backup_size = worrpb_calc_folder_size($payload['backup_folder']);

  // update status in config file
  $result = worrpb_update_config_file($payload['backup_folder'], [
    'backup_status' => 'completed',
    'backup_size' => worrpb_format_bytes($backup_size),
  ]);

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'backup_process_status' => 'done',
  ]);
}

// worrpb_ajax_delete_backup_folder
add_action('wp_ajax_worrpb_ajax_delete_backup_folder', 'worrpb_ajax_delete_backup_folder');
function worrpb_ajax_delete_backup_folder() {
  // wp_send_json($_POST);
  // die();
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // name_folder backup folder
  $name_folder = $payload['name_folder'];

  // backup folder  get wp uploads + /wp-backup/name_folder
  $backup_folder_dir = WP_CONTENT_DIR . '/uploads/wp-backup/' . $name_folder;
  
  // delete backup folder
  $result = worrpb_remove_folder($backup_folder_dir);
  
  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'message' => 'Backup folder deleted successfully',
  ]);
}

// worrpb_ajax_restore_read_backup_config_file
add_action('wp_ajax_worrpb_ajax_restore_read_backup_config_file', 'worrpb_ajax_restore_read_backup_config_file');
function worrpb_ajax_restore_read_backup_config_file() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $types = $payload['types'];

  // get backup config file
  $backup_config = worrpb_get_config_file($folder_name);

  // check error $backup_config
  if (is_wp_error($backup_config)) {
    wp_send_json_error($backup_config->get_error_message());
  }

  $process_restore_id = worrpb_create_process_restore_id($folder_name);
  if (is_wp_error($process_restore_id)) {
    wp_send_json_error($process_restore_id->get_error_message());
  }

  wp_send_json_success(array_merge([
    'read_backup_config_file_status' => 'done',
    'next_step' => true,
    'current_domain' => get_home_url(),
    'process_restore_id' => $process_restore_id,
  ], $backup_config));
}

/**
 * worrpb_ajax_restore_database
 * 
 * @description: Restore database, this function is called after the user has read the backup config file and has clicked the restore database button.
 * Can't be called independently, and is strictly checked based on the randomly generated process id each time the user requests a restore.
 * During the database restore, your current login session might be lost. This affects WordPress's security check mechanism using nonces. Since we can't verify the usual nonce after a restore, we generate a unique process_restore_id to securely continue the process.
 * 
 */
add_action('wp_ajax_worrpb_ajax_restore_database', 'worrpb_ajax_restore_database');
add_action('wp_ajax_nopriv_worrpb_ajax_restore_database', 'worrpb_ajax_restore_database');
function worrpb_ajax_restore_database() {
  # check nonce
  // check_ajax_referer('wp-backup-restore', 'wp_restore_nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing

  $process_restore_id = $payload['process_restore_id'];
  $folder_name = $payload['folder_name'];
  $backup_prefix = $payload['table_prefix'];

  // During the database restore, your current login session might be lost. This affects WordPress's security check mechanism using nonces. Since we can't verify the usual nonce after a restore, we generate a unique process_restore_id to securely continue the process.
  $validate_process_restore_id = worrpb_validate_process_restore_id($process_restore_id, $folder_name);
  if (is_wp_error($validate_process_restore_id)) {

    // error delete process restore id
    $delete_process_restore = worrpb_delete_process_restore_id($folder_name);
    wp_send_json_error($validate_process_restore_id->get_error_message());
  }

  $exclude_tables = isset($payload['exclude_tables']) ? $payload['exclude_tables'] : [];
  $exclude_tables = apply_filters('wp_backup:restore_database_exclude_tables', $exclude_tables, $payload);

  $restore_database = new WORRPB_Restore_Database($folder_name, $exclude_tables, $backup_prefix);

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

// worrpb_ajax_restore_plugin 
add_action('wp_ajax_worrpb_ajax_restore_plugin', 'worrpb_ajax_restore_plugin');
// add_action('wp_ajax_nopriv_worrpb_ajax_restore_plugin', 'worrpb_ajax_restore_plugin');
function worrpb_ajax_restore_plugin() {
  # check nonce
  check_ajax_referer('wp-backup-restore', 'wp_restore_nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/wp-backup/' . $folder_name . '/plugins.zip';
  
  // validate $path_zip_file
  if (!file_exists($path_zip_file)) {
    wp_send_json_error('Zip file not found');
  }

  try {
    $restorer = new WORRPB_Restore_File_System([
      'zip_file' => $path_zip_file,
      'destination_folder' => WP_PLUGIN_DIR,
      'overwrite_existing' => true,
      'exclude' => ['worry-proof-backup'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
      'restore_progress_file_name' => '__plugin-restore-progress.json',
    ]);

    $result = $restorer->runRestore();

    // check error $result
    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    }

    if($result['done'] != true) {
      wp_send_json_success([
        'restore_plugin_status' => 'is_running',
        'next_step' => false,
        // 'progress' => $result,
      ]);
    } else {
      wp_send_json_success([
        'restore_plugin_status' => 'done',
        'next_step' => true,
      ]);
    }

  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }

  // $restore_plugin = new WORRPB_Restore_File_System([
  //   'zip_file' => $path_zip_file,
  //   'destination_folder' => WP_PLUGIN_DIR,
  //   'overwrite_existing' => true,
  //   'exclude' => ['worry-proof-backup'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
  // ]);

  // // check error $restore_plugin
  // if (is_wp_error($restore_plugin)) {
  //   wp_send_json_error($restore_plugin->get_error_message());
  // }

  // $result = $restore_plugin->runRestore();

  // // check error $result
  // if (is_wp_error($result)) {
  //   wp_send_json_error($result->get_error_message());
  // }

  wp_send_json_success([
    'restore_plugin_status' => 'done',
    'next_step' => true,
  ]);
}

// worrpb_ajax_restore_theme
add_action('wp_ajax_worrpb_ajax_restore_theme', 'worrpb_ajax_restore_theme');
// add_action('wp_ajax_nopriv_worrpb_ajax_restore_theme', 'worrpb_ajax_restore_theme');
function worrpb_ajax_restore_theme() {
  # check nonce
  check_ajax_referer('wp-backup-restore', 'wp_restore_nonce');
  
  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/wp-backup/' . $folder_name . '/themes.zip';
  
  // validate $path_zip_file
  if (!file_exists($path_zip_file)) {
    wp_send_json_error('Zip file not found');
  }

  try {
    $restorer = new WORRPB_Restore_File_System([
      'zip_file' => $path_zip_file,
      'destination_folder' => WP_CONTENT_DIR . '/themes/',
      'overwrite_existing' => true,
      'restore_progress_file_name' => '__theme-restore-progress.json',
    ]);

    $result = $restorer->runRestore();

    // check error $result
    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    }

    if($result['done'] != true) {
      wp_send_json_success([
        'restore_theme_status' => 'is_running',
        'next_step' => false,
        // 'progress' => $result,
      ]);
    } else {
      wp_send_json_success([
        'restore_theme_status' => 'done',
        'next_step' => true,
      ]);
    }

  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }

  // $restore_theme = new WORRPB_Restore_File_System([
  //   'zip_file' => $path_zip_file,
  //   'destination_folder' => WP_CONTENT_DIR . '/themes/',
  //   'overwrite_existing' => true,
  // ]);

  // // check error $restore_theme
  // if (is_wp_error($restore_theme)) {
  //   wp_send_json_error($restore_theme->get_error_message());
  // }

  // $result = $restore_theme->runRestore();

  // // check error $result
  // if (is_wp_error($result)) {
  //   wp_send_json_error($result->get_error_message());
  // }

  wp_send_json_success([
    'restore_theme_status' => 'done',
    'next_step' => true,
  ]);
}

// worrpb_ajax_restore_uploads
add_action('wp_ajax_worrpb_ajax_restore_uploads', 'worrpb_ajax_restore_uploads');
// add_action('wp_ajax_nopriv_worrpb_ajax_restore_uploads', 'worrpb_ajax_restore_uploads');
function worrpb_ajax_restore_uploads() {
  # check nonce
  check_ajax_referer('wp-backup-restore', 'wp_restore_nonce');
  
  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/wp-backup/' . $folder_name . '/uploads.zip';
  
  // validate $path_zip_file
  if (!file_exists($path_zip_file)) {
    wp_send_json_error('Zip file not found');
  }

  try {
    $restorer = new WORRPB_Restore_File_System([
      'zip_file' => $path_zip_file,
      'destination_folder' => WP_CONTENT_DIR . '/uploads/',
      'overwrite_existing' => true,
      'exclude' => ['wp-backup', 'wp-backup-zip'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
      'restore_progress_file_name' => '__uploads-restore-progress.json',
    ]);

    $result = $restorer->runRestore();

    // check error $result
    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    }

    if($result['done'] != true) {
      wp_send_json_success([
        'restore_uploads_status' => 'is_running',
        'next_step' => false,
        // 'progress' => $result,
      ]);
    } else {
      wp_send_json_success([
        'restore_uploads_status' => 'done',
        'next_step' => true,
      ]);
    }

  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }

  // $restore_uploads = new WORRPB_Restore_File_System([
  //   'zip_file' => $path_zip_file,
  //   'destination_folder' => WP_CONTENT_DIR . '/uploads/',
  //   'overwrite_existing' => true,
  //   'exclude' => ['wp-backup', 'wp-backup-zip'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
  // ]);

  // // check error $restore_uploads
  // if (is_wp_error($restore_uploads)) {
  //   wp_send_json_error($restore_uploads->get_error_message());
  // }

  // $result = $restore_uploads->runRestore();

  // // check error $result
  // if (is_wp_error($result)) {
  //   wp_send_json_error($result->get_error_message());
  // }

  wp_send_json_success([
    'restore_uploads_status' => 'done',
    'next_step' => true,
  ]);
}

// worrpb_ajax_restore_done
add_action('wp_ajax_worrpb_ajax_restore_done', 'worrpb_ajax_restore_done');
add_action('wp_ajax_nopriv_worrpb_ajax_restore_done', 'worrpb_ajax_restore_done');
function worrpb_ajax_restore_done() {
  # check nonce
  // check_ajax_referer('wp-backup-restore', 'wp_restore_nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing

  $folder_name = $payload['folder_name'];

  // delete process restore id
  $delete_process_restore = worrpb_delete_process_restore_id($folder_name);
  if (is_wp_error($delete_process_restore)) {
    wp_send_json_error($delete_process_restore->get_error_message());
  }

  // create hook after restore process successfully
  do_action('wp_backup:after_restore_process_success', $payload);

  wp_send_json_success([
    'restore_process_status' => 'done',
  ]);
}

// worrpb_ajax_send_report_email
add_action('wp_ajax_worrpb_ajax_send_report_email', 'worrpb_ajax_send_report_email');
function worrpb_ajax_send_report_email() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // send report email
  $result = worrpb_send_report_email($payload);

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success(true);
}


// worrpb_ajax_upload_backup_file
add_action('wp_ajax_worrpb_ajax_upload_backup_file', 'worrpb_ajax_upload_backup_file');
function worrpb_ajax_upload_backup_file() {
  // check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

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
  $uploader = new WORRPB_Upload_Backup_File([
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


// worrpb_ajax_get_backup_download_zip_path 
add_action('wp_ajax_worrpb_ajax_get_backup_download_zip_path', 'worrpb_ajax_get_backup_download_zip_path');
function worrpb_ajax_get_backup_download_zip_path() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $backup_folder_name = $payload['folder_name'];

  $backup_download_zip_path = worrpb_get_backup_download_zip_path($backup_folder_name);

  wp_send_json_success($backup_download_zip_path);
}

// worrpb_ajax_create_backup_zip
add_action('wp_ajax_worrpb_ajax_create_backup_zip', 'worrpb_ajax_create_backup_zip');
function worrpb_ajax_create_backup_zip() {
  # check nonce
  check_ajax_referer('worrpb_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $backup_folder_name = $payload['folder_name'];

  $backup_create_zip = worrpb_create_backup_zip($backup_folder_name);

  wp_send_json_success($backup_create_zip);
}

// worrpb_ajax_save_backup_schedule_config
add_action('wp_ajax_worrpb_ajax_save_backup_schedule_config', 'worrpb_ajax_save_backup_schedule_config');
function worrpb_ajax_save_backup_schedule_config() {
  $json = file_get_contents("php://input");
  $data = json_decode($json, true);
  $nonce = $data['nonce'];
  $payload = $data['payload'];

  # check ajax nonce
  if (!wp_verify_nonce($nonce, 'worrpb_nonce_' . get_current_user_id())) {
    wp_send_json_error(['message' => 'Nonce is invalid'], 403);
  }

  $result = worrpb_save_backup_schedule_config($payload);

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success($result);
}

// worrpb_ajax_get_backup_schedule_config
add_action('wp_ajax_worrpb_ajax_get_backup_schedule_config', 'worrpb_ajax_get_backup_schedule_config');
function worrpb_ajax_get_backup_schedule_config() {
  $json = file_get_contents("php://input");
  $data = json_decode($json, true);
  $nonce = $data['nonce'];

  # check ajax nonce
  if (!wp_verify_nonce($nonce, 'worrpb_nonce_' . get_current_user_id())) {
    wp_send_json_error(['message' => 'Nonce is invalid'], 403);
  }

  $result = worrpb_get_backup_schedule_config();

  wp_send_json_success($result);
}