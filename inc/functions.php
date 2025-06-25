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
    echo $template_content;
  } else {
    return $template_content;
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
    __('WP Backup', 'wp-backup'), // Page title
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
      'date' => date('Y-m-d H:i:s', strtotime($config_file_content['backup_date'])),
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
  $server_software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
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
    if (!mkdir($directory, 0755, true)) {
      return new WP_Error('mkdir_failed', 'Could not create directory for file: ' . $directory);
    }
  }

  // Write data to file
  $result = file_put_contents($file_path, $data);

  if ($result === false) {
    return new WP_Error('write_failed', 'Could not write data to file: ' . $file_path);
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
    'backup_date' => date('c'),
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
  $name_folder = 'backup_' . $args['backup_id'] . '_' . date('Y-m-d_H-i-s');
  $backup_folder = $upload_dir['basedir'] . '/wp-backup/' . $name_folder;

  if (!file_exists($backup_folder)) {
    if (!mkdir($backup_folder, 0755, true)) {
      return new WP_Error('mkdir_failed', 'Oops! 🤦‍♂️ Could not create backup folder. Please check folder permissions or try again. If the problem persists, contact your administrator. We\'re rooting for you! 💪✨');
    }
  }

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

  try {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
      /** @var SplFileInfo $file */
      $path = $file->getPathname();

      if ($file->isDir()) {
        if (!rmdir($path)) {
          return new WP_Error('remove_folder_failed', "Failed to remove directory: {$path}");
        }
      } else {
        if (!unlink($path)) {
          return new WP_Error('remove_file_failed', "Failed to remove file: {$path}");
        }
      }
    }

    // Finally remove the folder itself
    if (!rmdir($folder)) {
      return new WP_Error('remove_folder_failed', "Failed to remove directory: {$folder}");
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
    return new WP_Error('config_file_not_found', 'Config file not found');
  }

  // get config file content
  $config_file_content = file_get_contents($backup_folder);

  // check if $config_file_content is not empty
  if (empty($config_file_content)) {
    return new WP_Error('config_file_empty', 'Config file is empty');
  }

  // decode config file content
  $config_data = json_decode($config_file_content, true);

  // check if $config_data is not empty
  if (empty($config_data)) {
    return new WP_Error('config_data_empty', 'Config data is empty');
  }

  return $config_data;
}