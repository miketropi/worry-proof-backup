<?php 
/**
 * Functions file
 */

/**
 * Load template file
 * 
 * @param string $template_name Template name
 * @param array $args Arguments to pass to template
 * @param bool $echo Whether to echo the template or return it
 * @return string|void
 */
function wp_backup_load_template($template_name, $args = array(), $echo = true) {
  // Get default template path
  $default_template_path = WP_BACKUP_PLUGIN_PATH . 'templates/' . $template_name . '.php';
  
  // Allow template path to be overridden via filter
  $template_path = apply_filters('wp_backup_template_path', $default_template_path, $template_name);

  // Check if template exists
  if (!file_exists($template_path)) {
    return '';
  }

  // Extract args to make them available in template
  if (!empty($args)) {
    extract($args);
  }

  // Start output buffering
  ob_start();

  // Include template
  include $template_path;

  // Get template content
  $template_content = ob_get_clean();

  // Echo or return template content
  if ($echo) {
    echo wp_kses_post($template_content);
  } else {
    return wp_kses_post($template_content);
  }
}

/**
 * Register admin page
 */
function wp_backup_register_admin_page() {
  // check if user is admin role
  if (!current_user_can('manage_options')) {
    return;
  }

  add_management_page(
    __('Worry Proof Backup', 'wp-backup'), // Page title
    __('Backup Tools', 'wp-backup'), // Menu title
    'manage_options', // Capability required
    'wp-backup', // Menu slug
    'wp_backup_admin_page' // Function to display the page
  );
}

function wp_backup_admin_page() {

  # plugin version
  $plugin_version = WP_BACKUP_PLUGIN_VERSION;

  # load template admin page
  wp_backup_load_template('admin-page', array(
    'plugin_version' => $plugin_version,
  ), true);
}

function wp_backup_get_backups() {

  // scan folder: wp-content/uploads/wp-backup/
  $upload_dir = wp_upload_dir();
  $backup_folder = $upload_dir['basedir'] . '/wp-backup/';

  // check if $backup_folder is exists
  if (!file_exists($backup_folder)) {
    return array();
  }

  // get all folders in $backup_folder
  $folders = glob($backup_folder . '/*', GLOB_ONLYDIR);
  
  // check if $folders is not empty
  if (empty($folders)) {
    return array();
  }

  $backups = array();

  // loop through $folders
  foreach ($folders as $folder) {

    // get folder name
    $folder_name = basename($folder);

    // get config file
    $config_file = $folder . '/config.json';

    // check if $config_file is exists
    if (!file_exists($config_file)) {
      continue;
    }

    // get config file content
    $config_file_content = file_get_contents($config_file);

    // check if $config_file_content is not empty
    if (empty($config_file_content)) {
      continue;
    }

    // decode config file content
    $config_file_content = json_decode($config_file_content, true);

    // check if $config_file_content is not empty
    if (empty($config_file_content)) {
      continue;
    }

    $config_file_content['type'] = explode(',', $config_file_content['backup_types']);
    $backups[] = [
      'id' => $config_file_content['backup_id'],
      'name' => $config_file_content['backup_name'],
      'status' => $config_file_content['backup_status'],
      'date' => gmdate('Y-m-d H:i:s', strtotime($config_file_content['backup_date'])),
      'size' => $config_file_content['backup_size'],
      'type' => explode(',', $config_file_content['backup_types']),
      'folder_name' => $folder_name,
    ];
  }

  // sort $backups by date
  usort($backups, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
  });

  // return $backups
  return $backups;
}

/**
 * Check if WP-CLI is available
 * 
 * @return bool
 */
function wp_backup_is_wp_cli_available() {
  if ( defined( 'WP_CLI' ) && WP_CLI ) {
      return true; // WP-CLI is running
  }

  $cli_path = shell_exec( 'which wp' );
  return ! empty( $cli_path );
}

function wp_backup_is_current_admin_page( $screen_id_check = '' ) {
  if ( ! is_admin() ) {
      return false;
  }

  $screen = get_current_screen();
  
  if ( ! $screen || ! isset( $screen->id ) ) {
      return false;
  }

  return $screen->id === $screen_id_check;
}

/**
 * Get server metrics relevant for backup configuration.
 *
 * Returns an array with:
 * - disk_free_space: Free disk space in bytes
 * - disk_total_space: Total disk space in bytes
 * - memory_limit: PHP memory limit in bytes
 * - memory_usage: Current PHP memory usage in bytes
 * - max_execution_time: PHP max execution time in seconds
 * - upload_max_filesize: Max upload file size in bytes
 * - post_max_size: Max POST size in bytes
 * - safe_mode: Whether PHP safe mode is enabled (legacy, for old PHP)
 * - server_software: Server software string
 * - php_version: PHP version string
 * - wp_version: WordPress version string
 * - ZipArchive: Whether ZipArchive is available
 * - WP Debug: Whether WP debug is enabled
 *
 * @return array
 */
function wp_backup_get_server_metrics() {
  // Disk space
  $root = ABSPATH;
  $disk_free_space = @disk_free_space($root);
  $disk_total_space = @disk_total_space($root);

  // PHP memory
  $memory_limit = wp_backup_return_bytes(@ini_get('memory_limit'));
  $memory_usage = function_exists('memory_get_usage') ? memory_get_usage() : null;

  // PHP execution time
  $max_execution_time = @ini_get('max_execution_time');

  // Upload limits
  $upload_max_filesize = wp_backup_return_bytes(@ini_get('upload_max_filesize'));
  $post_max_size = wp_backup_return_bytes(@ini_get('post_max_size'));

  // Safe mode (legacy, for old PHP)
  $safe_mode = false;
  if (function_exists('ini_get')) {
    $safe_mode = strtolower(@ini_get('safe_mode')) == 'on' || @ini_get('safe_mode') == 1;
  }

  // Server info
  $server_software = isset($_SERVER['SERVER_SOFTWARE']) ? wp_unslash($_SERVER['SERVER_SOFTWARE']) : '';
  $php_version = phpversion();

  // WordPress version
  $wp_version = get_bloginfo('version');

  // MySQL version
  global $wpdb;
  $mysql_version = $wpdb->db_version();

  return array(
    'disk_free_space'    => $disk_free_space,
    'disk_total_space'   => $disk_total_space,
    'memory_limit'       => $memory_limit,
    'memory_usage'       => $memory_usage,
    'max_execution_time' => $max_execution_time,
    'upload_max_filesize'=> $upload_max_filesize,
    'post_max_size'      => $post_max_size,
    'safe_mode'          => $safe_mode,
    'server_software'    => $server_software,
    'php_version'        => $php_version,
    'wp_version'         => $wp_version,
    'mysql_version'      => $mysql_version,
    'ZipArchive'         => class_exists('ZipArchive'),
    'WP_Debug'           => defined('WP_DEBUG') && WP_DEBUG,
    'WP_CLI'             => wp_backup_is_wp_cli_available(),
    'WP_Max_Upload_Size' => wp_max_upload_size(),
    'plugin_version'     => WP_BACKUP_PLUGIN_VERSION,
  );
}

/**
 * Convert PHP shorthand byte values (e.g. 128M, 2G) to integer bytes.
 *
 * @param string $val
 * @return int
 */
function wp_backup_return_bytes($val) {
  $val = trim($val);
  $last = strtolower($val[strlen($val)-1]);
  $num = (int)$val;
  switch($last) {
    case 'g':
      $num *= 1024;
      // no break
    case 'm':
      $num *= 1024;
      // no break
    case 'k':
      $num *= 1024;
  }
  return $num;
}

/**
 * Backup the WordPress database, with optional exclusion of specific tables.
 *
 * @param array $exclude_tables Array of table names (without prefix) to exclude from backup.
 * @return string|false SQL dump as a string on success, false on failure.
 */
function wp_backup_database($exclude_tables = array()) {
  global $wpdb;

  $all_tables = $wpdb->get_col('SHOW TABLES');
  if ($wpdb->last_error) {
    return new WP_Error('db_error', 'Oops! 😅 Database tables are playing hide and seek: ' . $wpdb->last_error . '. Double-check your database connection and give it another shot. If this keeps happening, reach out to your admin - we\'ve got your back! 🛡️💫');
  }

  if (!$all_tables) {
    return new WP_Error('no_tables', 'Oops! 😱 Database is looking a bit empty today. No tables found - might want to check your database connection or give it a little pep talk! If this keeps happening, reach out to your admin - we\'ve got your back! 🚀💫');
  }

  $prefix = $wpdb->prefix;
  $exclude_full_tables = array();
  foreach ($exclude_tables as $table) {
    $exclude_full_tables[] = (strpos($table, $prefix) === 0) ? $table : $prefix . $table;
  }

  $tables_to_backup = array_diff($all_tables, $exclude_full_tables);
  $sql_dump = '';

  foreach ($tables_to_backup as $table) {
    $table_escaped = esc_sql($table);

    // Dump CREATE TABLE
    $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table_escaped`", ARRAY_N);
    if ($create_table && isset($create_table[1])) {
      $sql_dump .= "\n--\n-- Table structure for table `$table`\n--\n\n";
      $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
      $sql_dump .= $create_table[1] . ";\n";
    }

    // Dump data in chunks
    $limit = 1000;
    $offset = 0;

    do {
      $rows = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM `$table_escaped` LIMIT %d OFFSET %d", $limit, $offset),
        ARRAY_A
      );

      if ($rows && count($rows) > 0) {
        if ($offset === 0) {
          $sql_dump .= "\n--\n-- Dumping data for table `$table`\n--\n\n";
        }

        foreach ($rows as $row) {
          $values = array();
          foreach ($row as $value) {
            if ($value === null) {
              $values[] = 'NULL';
            } else {
              $escaped_value = addslashes($value);
              $values[] = "'$escaped_value'";
            }
          }
          $sql_dump .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
        }

        $offset += $limit;
      }
    } while (count($rows) > 0);
  }

  return $sql_dump;
}

/**
 * Save data to file
 * 
 * @param string $file_path
 * @param string $data
 * @return bool
 */
function wp_backup_save_data_to_file($file_path, $data) {
  // Ensure the directory exists
  $directory = dirname($file_path);
  if (!file_exists($directory)) {
    global $wp_filesystem;
    
    if (empty($wp_filesystem)) {
      require_once(ABSPATH . 'wp-admin/includes/file.php');
      WP_Filesystem();
    }
    
    if (!$wp_filesystem->mkdir($directory, 0755)) {
      return new WP_Error('mkdir_failed', esc_html__('Could not create directory for file: ', 'wp-backup') . $directory);
    }
  }

  // Write data to file
  $result = file_put_contents($file_path, $data);

  if ($result === false) {
    return new WP_Error('write_failed', esc_html__('Could not write data to file: ', 'wp-backup') . $file_path);
  }

  return true;
}

/**
 * Check if file exists and create directory if needed using WP_Filesystem
 * 
 * @param string $file_path Full path to the file
 * @param int $permissions Directory permissions (default: 0755)
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function wp_backup_ensure_file_directory($file_path, $permissions = 0755) {
  global $wp_filesystem;
  
  // Initialize WP_Filesystem if not already done
  if (empty($wp_filesystem)) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    WP_Filesystem();
  }
  
  // Get directory path
  $directory = dirname($file_path);
  
  // Check if directory exists
  if (!$wp_filesystem->exists($directory)) {
    // Create directory recursively
    if (!$wp_filesystem->mkdir($directory, $permissions)) {
      return new WP_Error(
        'mkdir_failed', 
        esc_html__('Could not create directory: ', 'wp-backup') . $directory
      );
    }
  }
  
  return true;
}

/**
 * Create directory using WP_Filesystem (replacement for mkdir)
 * 
 * @param string $path Directory path to create
 * @param int $permissions Directory permissions (default: 0755)
 * @param bool $recursive Whether to create parent directories (default: true)
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function wp_backup_mkdir($path, $permissions = 0755, $recursive = true) {
  global $wp_filesystem;
  
  // Initialize WP_Filesystem if not already done
  if (empty($wp_filesystem)) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    WP_Filesystem();
  }
  
  // Check if directory already exists
  if ($wp_filesystem->exists($path)) {
    return true;
  }
  
  // Create directory
  if (!$wp_filesystem->mkdir($path, $permissions)) {
    return new WP_Error(
      'mkdir_failed', 
      esc_html__('Could not create directory: ', 'wp-backup') . $path
    );
  }
  
  return true;
}


/**
 * generate config file 
 * Create a new folder in uploads/wp-backup/backup_<random_string>
 * Create a new file in the folder: config.json
 * The file should contain the following information:
 * - backup_id
 * - backup_name
 * - backup_types
 * - backup_date
 * - backup_description
 * - backup_author_email
 * - backup_status
 * - backup_size
 */
function wp_backup_generate_config_file($args = array()) {
  global $wpdb;
  
  $defaults = array(
    'backup_id' => wp_generate_uuid4(),
    'backup_name' => '',
    'backup_types' => '',
    'backup_date' => gmdate('c'),
    'backup_description' => '',
    'backup_author_email' => is_user_logged_in() ? wp_get_current_user()->user_email : '',
    'backup_status' => 'pending',
    'backup_size' => '???',
    'site_url' => home_url(),
    'table_prefix' => $wpdb->prefix,
  );
  $args = wp_parse_args($args, $defaults);

  // Sanitize
  $args['backup_name'] = sanitize_text_field($args['backup_name']);
  $args['backup_description'] = sanitize_textarea_field($args['backup_description']);
  $args['site_url'] = esc_url_raw($args['site_url']);
  $args['backup_author_email'] = sanitize_email($args['backup_author_email']);

  $upload_dir = wp_upload_dir();
  $name_folder = 'backup_' . $args['backup_id'] . '_' . gmdate('Y-m-d_H-i-s');
  $backup_folder = $upload_dir['basedir'] . '/wp-backup/' . $name_folder;

  // check if $backup_folder is exists via wp_backup_ensure_file_directory
  if(wp_backup_mkdir($backup_folder) != true) {
    return new WP_Error('mkdir_failed', 'Oops! 🤦‍♂️ Could not create backup folder. Please check folder permissions or try again. If the problem persists, contact your administrator. We\'re rooting for you! 💪✨');
  }
  
  // if (!file_exists($backup_folder)) {
  //   if (!mkdir($backup_folder, 0755, true)) {
  //     return new WP_Error('mkdir_failed', 'Oops! 🤦‍♂️ Could not create backup folder. Please check folder permissions or try again. If the problem persists, contact your administrator. We\'re rooting for you! 💪✨');
  //   }
  // }

  $config_file = $backup_folder . '/config.json';
  $result = file_put_contents($config_file, json_encode($args, JSON_PRETTY_PRINT));

  if ($result === false) {
    return new WP_Error('write_failed', 'Oops! 🤦‍♂️ Could not write config file. Please check folder permissions or try again. If the problem persists, contact your administrator. We\'re rooting for you! 💪✨');
  }

  return [
    'backup_folder' => $backup_folder,
    'config_file' => $config_file,
    'name_folder' => $name_folder,
  ];
}

/**
 * Update any field(s) in the config file.
 * 
 * @param string $backup_folder
 * @param array $fields Associative array of fields to update, e.g. ['backup_status' => 'completed']
 * @return bool|WP_Error
 */
function wp_backup_update_config_file($backup_folder, $fields) {
  $config_file = $backup_folder . '/config.json';

  // Check if config file exists
  if (!file_exists($config_file)) {
    return new WP_Error('config_file_not_found', 'Config file not found');
  }

  // Get config file content
  $config_file_content = file_get_contents($config_file);

  // Check if config file content is not empty
  if (empty($config_file_content)) {
    return new WP_Error('config_file_empty', 'Config file is empty');
  }

  // Decode config file content
  $config_data = json_decode($config_file_content, true);

  if (!is_array($config_data)) {
    return new WP_Error('config_file_invalid', 'Config file is not valid JSON');
  }

  // Update fields
  foreach ($fields as $key => $value) {
    $config_data[$key] = $value;
  }

  // Save config file
  $result = file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT));

  // Check if $result is false
  if ($result === false) {
    return new WP_Error('write_failed', 'Could not write config file: ' . $config_file);
  }

  return true;
}

/**
 * Calculate the size of a folder (optimized, supports >2GB)
 *
 * @param string $folder Absolute path to folder
 * @return float Folder size (bytes)
 */
function wp_backup_calc_folder_size($folder) {
  $size = 0.0;

  if (!is_dir($folder)) {
      return 0;
  }

  try {
      $iterator = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
          RecursiveIteratorIterator::SELF_FIRST
      );

      foreach ($iterator as $file) {
          // Skip symlinks (to avoid loop)
          if ($file->isLink()) {
              continue;
          }

          if ($file->isFile()) {
              $file_size = $file->getSize();
              if ($file_size !== false) {
                  $size += (float) $file_size;
              }
          }
      }
  } catch (Exception $e) {
      // Optionally log error: $e->getMessage()
      return 0;
  }

  return $size;
}

/**
 * Format bytes to MB, GB...
 *
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function wp_backup_format_bytes($bytes, $precision = 2) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  $bytes /= pow(1024, $pow);

  return round($bytes, $precision) . ' ' . $units[$pow];
}


function wp_backup_rmdir( $dir_path ) {
  if ( ! function_exists( 'WP_Filesystem' ) ) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
  }

  global $wp_filesystem;

  if ( ! WP_Filesystem() ) {
      return new WP_Error( 'filesystem_init_failed', 'Unable to initialize WP_Filesystem.' );
  }

  // Normalize path
  $dir_path = trailingslashit( $dir_path );

  if ( ! $wp_filesystem->is_dir( $dir_path ) ) {
      return new WP_Error( 'not_a_directory', 'Path is not a directory.' );
  }

  if ( ! $wp_filesystem->delete( $dir_path, true, 'd' ) ) {
      return new WP_Error( 'delete_failed', 'Failed to delete the directory.' );
  }

  return true;
}

/**
 * Remove a folder and its contents (WordPress safe, with WP_Error)
 *
 * @param string $folder Absolute path to folder
 * @return true|WP_Error True if removed, WP_Error on failure
 */
function wp_backup_remove_folder($folder) {
  if (!is_dir($folder)) {
    // Nothing to do
    return true;
  }

  // get last path of $folder
  $name_folder = basename($folder);

  try {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
      /** @var SplFileInfo $file */
      $path = $file->getPathname();

      if ($file->isDir()) {
        if (!wp_backup_rmdir($path)) {
          return new WP_Error('remove_folder_failed', esc_html__('Failed to remove directory: ', 'wp-backup') . $path);
        }
      } else {
        if (!wp_delete_file($path)) {
          return new WP_Error('remove_file_failed', esc_html__('Failed to remove file: ', 'wp-backup') . $path);
        }
      }
    }

    // check and delete zip file in uploads > wp-backup-zip > $folder .zip
    $upload_dir = wp_upload_dir();
    $backup_zip_path = $upload_dir['basedir'] . '/wp-backup-zip/' . $name_folder . '.zip';
    if (file_exists($backup_zip_path)) {
      wp_delete_file($backup_zip_path);
    }

    // Finally remove the folder itself
    if (!wp_backup_rmdir($folder)) {
      return new WP_Error('remove_folder_failed', esc_html__('Failed to remove directory: ', 'wp-backup') . $folder);
    }

    return true;
  } catch (Exception $e) {
    return new WP_Error('exception', $e->getMessage());
  }
}

function wp_backup_get_config_file($folder_name) {
  $upload_dir = wp_upload_dir();
  $backup_folder = $upload_dir['basedir'] . '/wp-backup/' . $folder_name . '/config.json';

  // check if $backup_folder is exists
  if (!file_exists($backup_folder)) {
    return new WP_Error('config_file_not_found', esc_html__('Config file not found', 'wp-backup'));
  }

  // get config file content
  $config_file_content = file_get_contents($backup_folder);

  // check if $config_file_content is not empty
  if (empty($config_file_content)) {
    return new WP_Error('config_file_empty', esc_html__('Config file is empty', 'wp-backup'));
  }

  // decode config file content
  $config_data = json_decode($config_file_content, true);

  // check if $config_data is not empty
  if (empty($config_data)) {
    return new WP_Error('config_data_empty', esc_html__('Config data is empty', 'wp-backup'));
  }

  return $config_data;
}

// send report email
function wp_backup_send_report_email($args = array()) {
  $defaults = array(
    'name' => '',
    'email' => '',
    'type' => '',
    'description' => '',
  );

  // validate $args
  if (empty($args['name']) || empty($args['email']) || empty($args['type']) || empty($args['description'])) {
    return new WP_Error('invalid_args', esc_html__('Invalid arguments', 'wp-backup'));
  }

  $args = wp_parse_args($args, $defaults);

  // get system info
  $system_info = wp_backup_get_server_metrics();
  
  // wordpress domain
  $wordpress_domain = get_bloginfo('url');

  $to = 'mike.beplus@gmail.com';
  $subject = esc_html__('WP Backup Report', 'wp-backup');
  $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .label { font-weight: bold; color: #495057; }
        .value { margin-left: 10px; }
        .system-info { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h2>🚨 WP Backup Issue Report</h2>
        <p>New issue report submitted via WP Backup plugin</p>
    </div>
    
    <div class="section">
        <div><span class="label">👤 Name:</span><span class="value">' . esc_html($args['name']) . '</span></div>
        <div><span class="label">📧 Email:</span><span class="value">' . esc_html($args['email']) . '</span></div>
        <div><span class="label">🏷️ Type:</span><span class="value">' . esc_html($args['type']) . '</span></div>
    </div>
    
    <div class="section">
        <div class="label">📝 Description:</div>
        <div class="value" style="white-space: pre-wrap;">' . esc_html($args['description']) . '</div>
    </div>
    
    <div class="section">
        <div class="label">🌐 WordPress Domain:</div>
        <div class="value">' . esc_html($wordpress_domain) . '</div>
    </div>
    
    <div class="section">
        <div class="label">💻 System Information:</div>
        <div class="system-info">
            <table style="width: 100%; border-collapse: collapse; font-family: monospace; font-size: 12px;">
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold; width: 40%;">Disk Free Space:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html(wp_backup_format_bytes($system_info['disk_free_space'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Disk Total Space:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html(wp_backup_format_bytes($system_info['disk_total_space'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Memory Limit:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html(wp_backup_format_bytes($system_info['memory_limit'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Memory Usage:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html(wp_backup_format_bytes($system_info['memory_usage'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Max Execution Time:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['max_execution_time']) . ' seconds</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Upload Max Filesize:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html(wp_backup_format_bytes($system_info['upload_max_filesize'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Post Max Size:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html(wp_backup_format_bytes($system_info['post_max_size'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Safe Mode:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['safe_mode'] ? 'Enabled' : 'Disabled') . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">Server Software:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['server_software']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">PHP Version:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['php_version']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">WordPress Version:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['wp_version']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">MySQL Version:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['mysql_version']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">ZipArchive:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['ZipArchive'] ? 'Available' : 'Not Available') . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">WP Debug:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['WP_Debug'] ? 'Enabled' : 'Disabled') . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">WP CLI:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['WP_CLI'] ? 'Available' : 'Not Available') . '</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6; font-weight: bold;">WP Backup Version:</td>
                    <td style="padding: 4px 8px; border-bottom: 1px solid #dee2e6;">' . esc_html($system_info['plugin_version']) . '</td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="footer">
        <p>This report was automatically generated by the WP Backup plugin.</p>
        <p>Report submitted on: ' . gmdate('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';

  // header html
  $headers = array('Content-Type: text/html; charset=UTF-8');

  // send email
  $result = wp_mail($to, $subject, $body, $headers);

  // check if $result is false
  if ($result === false) {
    return new WP_Error('send_email_failed', 'Failed to send email');
  }

  return true;
}

// check backup download available
function wp_backup_get_backup_download_zip_path($backup_folder_name = '') {
  // find backup download zip in uploads > wp-backup-zip > $backup_folder_name .zip
  $upload_dir = wp_upload_dir();
  $backup_zip_path = $upload_dir['basedir'] . '/wp-backup-zip/' . $backup_folder_name . '.zip';

  // check if file exists
  if (file_exists($backup_zip_path)) {
    // return uri of file
    return $upload_dir['baseurl'] . '/wp-backup-zip/' . $backup_folder_name . '.zip';
  }

  return false;
}

// wp_backup_create_backup_zip
function wp_backup_create_backup_zip($backup_folder_name = '') {
  // create backup zip in uploads > wp-backup-zip > $backup_folder_name .zip
  $upload_dir = wp_upload_dir();
  $backup_zip_path = $upload_dir['basedir'] . '/wp-backup-zip/' . $backup_folder_name . '.zip';

  // check if file exists
  if (file_exists($backup_zip_path)) {

    // return uri of file 
    return $upload_dir['baseurl'] . '/wp-backup-zip/' . $backup_folder_name . '.zip';
  }

  $backup_folder_path = $upload_dir['basedir'] . '/wp-backup/' . $backup_folder_name;

  // create folder "wp-backup-zip" if not exists
  if (!file_exists($upload_dir['basedir'] . '/wp-backup-zip/')) {
    $result = wp_mkdir_p($upload_dir['basedir'] . '/wp-backup-zip/');
    if (is_wp_error($result)) {
      return new WP_Error('create_backup_zip_failed', $result->get_error_message());
    }
  }

  // create zip file backup
  $backup_zip_path = $upload_dir['basedir'] . '/wp-backup-zip/' . $backup_folder_name . '.zip';

  // create zip file
  $zip = new ZipArchive();
  $zip->open($backup_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

  $folderPath = realpath($backup_folder_path);

  if (!is_dir($folderPath)) {
    return new WP_Error('create_backup_zip_failed', 'Backup folder not found');
  }

  $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($folderPath),
      RecursiveIteratorIterator::LEAVES_ONLY
  );

  $exclude_files = ['restore.log'];

  foreach ($files as $name => $file) {
    if (!$file->isDir()) {
      $filePath = $file->getRealPath();
      $relativePath = substr($filePath, strlen($folderPath) + 1);

      // check if file is in $exclude_files
      if (in_array($relativePath, $exclude_files)) {
        continue;
      }

      $zip->addFile($filePath, $relativePath);
    }
  }

  $zip->close();

  // check if file exists
  if (!file_exists($backup_zip_path)) {
    return new WP_Error('create_backup_zip_failed', 'Backup zip file failed, please try again!');
  }

  return $upload_dir['baseurl'] . '/wp-backup-zip/' . $backup_folder_name . '.zip';
}