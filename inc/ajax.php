<?php 
/**
 * Ajax actions
 */

// get backups
add_action('wp_ajax_worrprba_ajax_get_backups', 'worrprba_ajax_get_backups');
function worrprba_ajax_get_backups() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get backups
  $backups = worrprba_get_backups();
  wp_send_json_success($backups);
}

// create backup config file
add_action('wp_ajax_worrprba_ajax_create_backup_config_file', 'worrprba_ajax_create_backup_config_file');
function worrprba_ajax_create_backup_config_file() {

  # check nonce 
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $config_file = worrprba_generate_config_file([
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

// worrprba_ajax_generate_backup_database
add_action('wp_ajax_worrprba_ajax_generate_backup_database', 'worrprba_ajax_generate_backup_database');
function worrprba_ajax_generate_backup_database() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup database using JSON dumper
  $backup_ssid = $payload['name_folder'];
  $exclude_tables = isset($payload['exclude_tables']) ? $payload['exclude_tables'] : array();
  
  // Verify upload directory exists before creating backup instance
  $upload_dir = wp_upload_dir();
  if (empty($upload_dir['basedir'])) {
    wp_send_json_error('Upload directory not found');
  }
  
  try {
    $backup = new WORRPB_Database_Dumper_JSON(1000, $backup_ssid, $exclude_tables);

    // Verify backup directory was created
    $backup_dir = $backup->getBackupDir();
    if (!file_exists($backup_dir)) {
      wp_send_json_error('Failed to create backup directory');
    }

    // if payload not backup_ssid, create new backup_ssid
    if (!isset($payload['backup_database_status']) || empty($payload['backup_database_status'])) {
      $result = $backup->start();

      // check error $result
      if ($result === false) {
        wp_send_json_error('Failed to start backup');
      }

      wp_send_json_success([
        'backup_ssid' => $backup_ssid,
        'backup_database_status' => 'is_running',
        'next_step' => false,
      ]);
    } else {
      $progress = $backup->step();

      // check error $progress - step() returns array, not WP_Error
      if (!is_array($progress)) {
        wp_send_json_error('Failed to process backup step');
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
  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }
}

// backup plugins
add_action('wp_ajax_worrprba_ajax_generate_backup_plugin', 'worrprba_ajax_generate_backup_plugin');
function worrprba_ajax_generate_backup_plugin() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup plugin
  $backup_ssid = $payload['name_folder'];
  
  try {
    $backup = new WORRPB_File_System_V2([
      'source_folder' => WP_PLUGIN_DIR,
      'destination_folder' => $payload['name_folder'],
      'zip_name' => 'plugins.zip',
      'exclude' => ['worry-proof-backup'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
      'chunk_size' => 1000, // Process 1000 files per batch
      'max_zip_size' => 2147483648, // 2GB max per zip file
    ]);

    // if payload not backup_plugin_status, create new backup
    if (!isset($payload['backup_plugin_status']) || empty($payload['backup_plugin_status'])) {
      $result = $backup->startBackup();

      // check error $result
      if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
      }

      wp_send_json_success([
        'backup_ssid' => $backup_ssid,
        'backup_plugin_status' => 'is_running',
        'next_step' => false,
        'progress' => $result,
      ]);
    } else {
      $progress = $backup->processStep();

      // check error $progress
      if (is_wp_error($progress)) {
        wp_send_json_error($progress->get_error_message());
      }
      
      if ($progress['done']) {
        $zip_files = $backup->getZipFiles();

        wp_send_json_success([
          'backup_ssid' => $backup_ssid,
          'backup_plugin_status' => 'done',
          'plugin_zip_files' => $zip_files,
          'next_step' => true,
        ]);
      } else {
        wp_send_json_success([
          'backup_ssid' => $backup_ssid,
          'backup_plugin_status' => 'is_running',
          'progress' => $progress,
          'next_step' => false,
        ]);
      }
    }
  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }
}

// backup themes
add_action('wp_ajax_worrprba_ajax_generate_backup_theme', 'worrprba_ajax_generate_backup_theme');
function worrprba_ajax_generate_backup_theme() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

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
add_action('wp_ajax_worrprba_ajax_generate_backup_uploads', 'worrprba_ajax_generate_backup_uploads');
function worrprba_ajax_generate_backup_uploads() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');
  
  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // check $payload['backup_folder'] is exists
  if (!isset($payload['backup_folder']) || empty($payload['backup_folder'])) {
    wp_send_json_error('Backup folder is empty');
  }

  // create backup uploads
  $backup_ssid = $payload['name_folder'];
  
  try {
    $backup = new WORRPB_File_System_V2([
      'source_folder' => WP_CONTENT_DIR . '/uploads/',
      'destination_folder' => $payload['name_folder'],
      'zip_name' => 'uploads.zip',
      'exclude' => ['worry-proof-backup', 'worry-proof-backup-zip', 'worry-proof-backup-cron-manager'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
      'chunk_size' => 1000, // Process 1000 files per batch
      'max_zip_size' => 2147483648, // 2GB max per zip file
    ]);

    // if payload not backup_ssid, create new backup_ssid
    if (!isset($payload['backup_uploads_status']) || empty($payload['backup_uploads_status'])) {
      $result = $backup->startBackup();

      // check error $result
      if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
      }

      wp_send_json_success([
        'backup_ssid' => $backup_ssid,
        'backup_uploads_status' => 'is_running',
        'next_step' => false,
        'progress' => $result,
      ]);
    } else {
      $progress = $backup->processStep();

      // check error $progress
      if (is_wp_error($progress)) {
        wp_send_json_error($progress->get_error_message());
      }
      
      if ($progress['done']) {
        $zip_files = $backup->getZipFiles();

        wp_send_json_success([
          'backup_ssid' => $backup_ssid,
          'backup_uploads_status' => 'done',
          'uploads_zip_files' => $zip_files,
          'next_step' => true,
        ]);
      } else {
        wp_send_json_success([
          'backup_ssid' => $backup_ssid,
          'backup_uploads_status' => 'is_running',
          'progress' => $progress,
          'next_step' => false,
        ]);
      }
    }
  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }
}

// worrprba_ajax_generate_backup_done
add_action('wp_ajax_worrprba_ajax_generate_backup_done', 'worrprba_ajax_generate_backup_done');
function worrprba_ajax_generate_backup_done() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // get backup size
  $backup_size = worrprba_calc_folder_size($payload['backup_folder']);

  // update status in config file
  $result = worrprba_update_config_file($payload['backup_folder'], [
    'backup_status' => 'completed',
    'backup_size' => worrprba_format_bytes($backup_size),
  ]);

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'backup_process_status' => 'done',
  ]);
}

// worrprba_ajax_delete_backup_folder
add_action('wp_ajax_worrprba_ajax_delete_backup_folder', 'worrprba_ajax_delete_backup_folder');
function worrprba_ajax_delete_backup_folder() {
  // wp_send_json($_POST);
  // die();
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // name_folder backup folder
  $name_folder = $payload['name_folder'];

  // backup folder  get wp uploads + /worry-proof-backup/name_folder
  $backup_folder_dir = WP_CONTENT_DIR . '/uploads/worry-proof-backup/' . $name_folder;
  
  // delete backup folder
  $result = worrprba_remove_folder($backup_folder_dir);
  
  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success([
    'message' => 'Backup folder deleted successfully',
  ]);
}

// worrprba_ajax_restore_read_backup_config_file
add_action('wp_ajax_worrprba_ajax_restore_read_backup_config_file', 'worrprba_ajax_restore_read_backup_config_file');
function worrprba_ajax_restore_read_backup_config_file() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $folder_name = $payload['folder_name'];
  $types = $payload['types'];

  // get backup config file
  $backup_config = worrprba_get_config_file($folder_name);

  // check error $backup_config
  if (is_wp_error($backup_config)) {
    wp_send_json_error($backup_config->get_error_message());
  }

  $process_restore_id = worrprba_create_process_restore_id($folder_name);
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
 * worrprba_ajax_restore_database
 * 
 * @description: Restore database, this function is called after the user has read the backup config file and has clicked the restore database button.
 * Can't be called independently, and is strictly checked based on the randomly generated process id each time the user requests a restore.
 * During the database restore, your current login session might be lost. This affects WordPress's security check mechanism using nonces. Since we can't verify the usual nonce after a restore, we generate a unique process_restore_id to securely continue the process.
 * 
 */
add_action('wp_ajax_worrprba_ajax_restore_database', 'worrprba_ajax_restore_database');
add_action('wp_ajax_nopriv_worrprba_ajax_restore_database', 'worrprba_ajax_restore_database');
function worrprba_ajax_restore_database() {
  # check nonce
  # During the database restore, your current login session might be lost. This affects WordPress's security check mechanism using nonces. Since we can't verify the usual nonce after a restore, we generate a unique process_restore_id to securely continue the process.
  // check_ajax_referer('worry-proof-backup-restore', 'wp_restore_nonce');

  # get payload
  $raw_payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
  
  # Sanitize payload
  $payload = worrprba_sanitize_payload_array( $raw_payload );

  $process_restore_id = $payload['process_restore_id'];
  $folder_name = $payload['folder_name'];
  $backup_prefix = $payload['table_prefix'];

  // During the database restore, your current login session might be lost. This affects WordPress's security check mechanism using nonces. Since we can't verify the usual nonce after a restore, we generate a unique process_restore_id to securely continue the process.
  $validate_process_restore_id = worrprba_validate_process_restore_id($process_restore_id, $folder_name);
  if (is_wp_error($validate_process_restore_id)) {

    // error delete process restore id
    $delete_process_restore = worrprba_delete_process_restore_id($folder_name);
    wp_send_json_error($validate_process_restore_id->get_error_message());
  }

  $exclude_tables = isset($payload['exclude_tables']) ? $payload['exclude_tables'] : [];
  $exclude_tables = apply_filters('worry-proof-backup:restore_database_exclude_tables', $exclude_tables, $payload);

  // Verify upload directory exists before creating restore instance
  $upload_dir = wp_upload_dir();
  if (empty($upload_dir['basedir'])) {
    wp_send_json_error('Upload directory not found');
  }

  // Verify restore directory and JSON file exist
  $restore_dir = $upload_dir['basedir'] . '/worry-proof-backup/' . $folder_name;
  $restore_file = $restore_dir . '/backup.sql.jsonl';
  
  if (!is_dir($restore_dir)) {
    wp_send_json_error('Restore directory not found');
  }
  
  if (!file_exists($restore_file)) {
    wp_send_json_error('Restore JSON file not found. Expected: backup.sql.jsonl');
  }

  try {
    $restore_database = new WORRPB_Restore_Database_JSON($folder_name, $exclude_tables, $backup_prefix);

    // Note: PHP constructors can't return WP_Error, but the class checks are done above
    // If constructor fails, it would need to throw an exception or we check properties

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
      $percent = isset($progress['percent']) ? $progress['percent'] : 0;
      
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
        do_action('worry-proof-backup:after_restore_database_success', $payload);

        wp_send_json_success([
          'restore_database_ssid' => $folder_name,
          'restore_database_status' => 'done',
          'next_step' => true,
          '__log_process_status' => '👍',
        ]);
      } else {
        
        wp_send_json_success([
          'restore_database_ssid' => $folder_name,
          'restore_database_status' => 'is_running',
          'next_step' => false,
          'progress' => $progress,
          '__log_process_status' => $percent ? $percent . '%' : '0%',
        ]);
      }
    }
  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }
}

// worrprba_ajax_restore_plugin 
add_action('wp_ajax_worrprba_ajax_restore_plugin', 'worrprba_ajax_restore_plugin');
// add_action('wp_ajax_nopriv_worrprba_ajax_restore_plugin', 'worrprba_ajax_restore_plugin');
function worrprba_ajax_restore_plugin() {
  # check nonce
  check_ajax_referer('worry-proof-backup-restore', 'wp_restore_nonce');

  # get payload
  $raw_payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
  
  # Sanitize payload
  $payload = worrprba_sanitize_payload_array( $raw_payload );

  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/worry-proof-backup/' . $folder_name . '/plugins.zip';
  
  // validate $path_zip_file
  if (!file_exists($path_zip_file)) {
    wp_send_json_error('Zip file not found');
  }

  try {
    $restorer = new WORRPB_Restore_File_System([
      'zip_file' => $path_zip_file,
      'destination_folder' => WP_PLUGIN_DIR,
      'overwrite_existing' => true,
      'exclude' => apply_filters('worrprba_restore_plugin_exclude', ['worry-proof-backup']), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
      'restore_progress_file_name' => '__plugin-restore-progress.json',
    ]);

    $result = $restorer->runRestore();

    // stats current index / total files
    $stats = $result['stats'];
    $current_index = $stats['current_index'];
    $total_files = $stats['total_files'];
    $progress = round( $current_index / $total_files * 100, 2 );

    // check error $result
    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    }

    if($result['done'] != true) {
      wp_send_json_success([
        'restore_plugin_status' => 'is_running',
        'next_step' => false,
        '__log_process_status' => $progress ? $progress . '%' : '0%',
        // 'progress' => $result,
      ]);
    } else {
      wp_send_json_success([
        'restore_plugin_status' => 'done',
        'next_step' => true,
        '__log_process_status' => '👍',
      ]);
    }

  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }

  wp_send_json_success([
    'restore_plugin_status' => 'done',
    'next_step' => true,
  ]);
}

// worrprba_ajax_restore_theme
add_action('wp_ajax_worrprba_ajax_restore_theme', 'worrprba_ajax_restore_theme');
// add_action('wp_ajax_nopriv_worrprba_ajax_restore_theme', 'worrprba_ajax_restore_theme');
function worrprba_ajax_restore_theme() {
  # check nonce
  check_ajax_referer('worry-proof-backup-restore', 'wp_restore_nonce');
  
  # get payload
  $raw_payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
  
  # Sanitize payload
  $payload = worrprba_sanitize_payload_array( $raw_payload );


  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/worry-proof-backup/' . $folder_name . '/themes.zip';
  
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

    // stats current index / total files
    $stats = $result['stats'];
    $current_index = $stats['current_index'];
    $total_files = $stats['total_files'];
    $progress = round( $current_index / $total_files * 100, 2 );

    // check error $result
    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    }

    if($result['done'] != true) {
      wp_send_json_success([
        'restore_theme_status' => 'is_running',
        'next_step' => false,
        '__log_process_status' => $progress ? $progress . '%' : '0%',
        // 'progress' => $result,
      ]);
    } else {
      wp_send_json_success([
        'restore_theme_status' => 'done',
        'next_step' => true,
        '__log_process_status' => '👍',
      ]);
    }

  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }

  wp_send_json_success([
    'restore_theme_status' => 'done',
    'next_step' => true,
  ]);
}

// worrprba_ajax_restore_uploads
add_action('wp_ajax_worrprba_ajax_restore_uploads', 'worrprba_ajax_restore_uploads');
// add_action('wp_ajax_nopriv_worrprba_ajax_restore_uploads', 'worrprba_ajax_restore_uploads');
function worrprba_ajax_restore_uploads() {
  # check nonce
  check_ajax_referer('worry-proof-backup-restore', 'wp_restore_nonce');
  
  # get payload
  $raw_payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
  
  # Sanitize payload
  $payload = worrprba_sanitize_payload_array( $raw_payload );

  $folder_name = $payload['folder_name'];
  $path_zip_file = WP_CONTENT_DIR . '/uploads/worry-proof-backup/' . $folder_name . '/uploads.zip';
  
  // validate $path_zip_file
  if (!file_exists($path_zip_file)) {
    wp_send_json_error('Zip file not found');
  }

  try {
    $restorer = new WORRPB_Restore_File_System([
      'zip_file' => $path_zip_file,
      'destination_folder' => WP_CONTENT_DIR . '/uploads/',
      'overwrite_existing' => true,
      'exclude' => ['worry-proof-backup', 'worry-proof-backup-zip'], // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
      'restore_progress_file_name' => '__uploads-restore-progress.json',
    ]);

    $result = $restorer->runRestore();

    // stats current index / total files
    $stats = $result['stats'];
    $current_index = $stats['current_index'];
    $total_files = $stats['total_files'];
    $progress = round( $current_index / $total_files * 100, 2 );

    // check error $result
    if (is_wp_error($result)) {
      wp_send_json_error($result->get_error_message());
    }

    if($result['done'] != true) {
      wp_send_json_success([
        'restore_uploads_status' => 'is_running',
        'next_step' => false,
        '__log_process_status' => $progress ? $progress . '%' : '0%',
        // 'progress' => $result,
      ]);
    } else {
      wp_send_json_success([
        'restore_uploads_status' => 'done',
        'next_step' => true,
        '__log_process_status' => '👍',
      ]);
    }

  } catch (Exception $e) {
    wp_send_json_error($e->getMessage());
  }

  wp_send_json_success([
    'restore_uploads_status' => 'done',
    'next_step' => true,
  ]);
}

// worrprba_ajax_restore_done
add_action('wp_ajax_worrprba_ajax_restore_done', 'worrprba_ajax_restore_done');
add_action('wp_ajax_nopriv_worrprba_ajax_restore_done', 'worrprba_ajax_restore_done');
function worrprba_ajax_restore_done() {
  # check nonce
  # Skip nonce verification here since the database restore may have logged out the user,
  # making the normal WordPress nonce check fail. We use a separate process_restore_id 
  # system for security during restore operations.
  // check_ajax_referer('worry-proof-backup-restore', 'wp_restore_nonce');

  # get payload
  $raw_payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
  
  # Sanitize payload
  $payload = worrprba_sanitize_payload_array( $raw_payload );

  $folder_name = $payload['folder_name'];

  // delete process restore id
  $delete_process_restore = worrprba_delete_process_restore_id($folder_name);
  if (is_wp_error($delete_process_restore)) {
    wp_send_json_error($delete_process_restore->get_error_message());
  }

  // create hook after restore process successfully
  do_action('worry-proof-backup:after_restore_process_success', $payload);

  wp_send_json_success([
    'restore_process_status' => 'done',
  ]);
}

// worrprba_ajax_send_report_email
add_action('wp_ajax_worrprba_ajax_send_report_email', 'worrprba_ajax_send_report_email');
function worrprba_ajax_send_report_email() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // send report email
  $result = worrprba_send_report_email($payload);

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success(true);
}


// worrprba_ajax_upload_backup_file
add_action('wp_ajax_worrprba_ajax_upload_backup_file', 'worrprba_ajax_upload_backup_file');
function worrprba_ajax_upload_backup_file() {
  // check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

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
  require_once(WORRPRBA_PLUGIN_PATH . 'inc/libs/upload-backup.php');

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


// worrprba_ajax_get_backup_download_zip_path 
add_action('wp_ajax_worrprba_ajax_get_backup_download_zip_path', 'worrprba_ajax_get_backup_download_zip_path');
function worrprba_ajax_get_backup_download_zip_path() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $backup_folder_name = $payload['folder_name'];

  $backup_download_zip_path = worrprba_get_backup_download_zip_path($backup_folder_name);

  wp_send_json_success($backup_download_zip_path);
}

// worrprba_ajax_create_backup_zip
add_action('wp_ajax_worrprba_ajax_create_backup_zip', 'worrprba_ajax_create_backup_zip');
function worrprba_ajax_create_backup_zip() {
  # check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  $backup_folder_name = $payload['folder_name'];

  $backup_create_zip = worrprba_create_backup_zip($backup_folder_name);

  wp_send_json_success($backup_create_zip);
}

// worrprba_ajax_save_backup_schedule_config
add_action('wp_ajax_worrprba_ajax_save_backup_schedule_config', 'worrprba_ajax_save_backup_schedule_config');
function worrprba_ajax_save_backup_schedule_config() {

  // check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce'); 

  // get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

  // validate data type payload
  require_once(WORRPRBA_PLUGIN_PATH . 'inc/libs/type-validator.php');
  $payload_rules = [
    'enabled' => [ 'type' => 'bool', 'default' => false ],
    'frequency' => [ 'type' => 'string', 'default' => 'weekly' ],
    'types' => [ 'type' => 'array', 'default' => ["database"] ],
    'versionLimit' => [ 'type' => 'int', 'default' => 2 ],
  ];
  $payload = WORRPB_Type_Validator::validate( $payload, $payload_rules );

  $result = worrprba_save_backup_schedule_config($payload);

  // check error $result
  if (is_wp_error($result)) {
    wp_send_json_error($result->get_error_message());
  }

  wp_send_json_success($result);
}

// worrprba_ajax_get_backup_schedule_config
add_action('wp_ajax_worrprba_ajax_get_backup_schedule_config', 'worrprba_ajax_get_backup_schedule_config');
function worrprba_ajax_get_backup_schedule_config() {

  // check nonce
  check_ajax_referer('worrprba_nonce_' . get_current_user_id(), 'nonce');

  $result = worrprba_get_backup_schedule_config();
  wp_send_json_success($result);
}