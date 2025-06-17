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


 
function wp_backup_register_admin_page() {
  add_menu_page(
    __('WP Backup', 'wp-backup'), // Page title
    __('WP Backup', 'wp-backup'), // Menu title
    'manage_options', // Capability required
    'wp-backup', // Menu slug
    'wp_backup_admin_page', // Function to display the page
    'dashicons-archive', // Icon
    30 // Position
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
  $backups = array(
    array(
      'id' => 'backup_20240315_123456',
      'name' => 'Full Site Backup',
      'status' => 'completed',
      'date' => '2024-03-15 12:34:56',
      'size' => '1.2 MB',
      'type' => array('database', 'plugin', 'theme', 'folder-uploads')
    ),
    array(
      'id' => 'backup_20240315_123457',
      'name' => 'Content Backup',
      'status' => 'completed',
      'date' => '2024-03-15 12:34:57',
      'size' => '0.8 MB',
      'type' => array('database', 'folder-uploads')
    ),
    array(
      'id' => 'backup_20240315_123458',
      'name' => 'Core Backup',
      'status' => 'completed',
      'date' => '2024-03-15 12:34:58',
      'size' => '1.5 MB',
      'type' => array('plugin', 'theme')
    ),
    array(
      'id' => 'backup_20240315_123459',
      'name' => 'Database Only Backup',
      'status' => 'completed',
      'date' => '2024-03-15 12:34:59',
      'size' => '3.7 MB',
      'type' => array('database')
    )
  );

  return $backups;
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

  // Get all tables in the database
  $all_tables = $wpdb->get_col('SHOW TABLES');
  if (!$all_tables) {
    return false;
  }

  $prefix = $wpdb->prefix;
  $exclude_full_tables = array();
  foreach ($exclude_tables as $table) {
    // Support both with and without prefix
    if (strpos($table, $prefix) === 0) {
      $exclude_full_tables[] = $table;
    } else {
      $exclude_full_tables[] = $prefix . $table;
    }
  }

  $tables_to_backup = array_diff($all_tables, $exclude_full_tables);

  $sql_dump = '';
  foreach ($tables_to_backup as $table) {
    // Get CREATE TABLE statement
    $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
    if ($create_table && isset($create_table[1])) {
      $sql_dump .= "\n--\n-- Table structure for table `$table`\n--\n\n";
      $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
      $sql_dump .= $create_table[1] . ";\n";
    }

    // Get table data
    $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
    if ($rows && count($rows) > 0) {
      $sql_dump .= "\n--\n-- Dumping data for table `$table`\n--\n\n";
      foreach ($rows as $row) {
        $values = array();
        foreach ($row as $value) {
          if ($value === null) {
            $values[] = 'NULL';
          } else {
            $values[] = "'" . esc_sql(stripslashes($value)) . "'";
          }
        }
        $sql_dump .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
      }
    }
  }

  return $sql_dump;
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
  $defaults = array(
    'backup_id' => wp_generate_password(12, false),
    'backup_name' => '',
    'backup_types' => '',
    'backup_date' => date('c'),
    'backup_description' => '',
    'backup_author_email' => is_user_logged_in() ? wp_get_current_user()->user_email : '',
    'backup_status' => 'pending',
    'backup_size' => '???',
    'site_url' => home_url(),
  );
  $args = wp_parse_args($args, $defaults);


  $upload_dir = wp_upload_dir();
  $name_folder = 'backup_' . $args['backup_id'] . '_' . date('Y-m-d_H-i-s');
  $backup_folder = $upload_dir['basedir'] . '/wp-backup/' . $name_folder;
  if (!file_exists($backup_folder)) {
    mkdir($backup_folder, 0755, true);
  }

  $config_file = $backup_folder . '/config.json';

  file_put_contents($config_file, json_encode($args));

  return [
    'backup_folder' => $backup_folder,
    'config_file' => $config_file,
    'name_folder' => $name_folder,
  ];
}

function __test() {
  # check GET parameter not test = true return
  if (!isset($_GET['test']) || $_GET['test'] != 'true') {
    return;
  }


  # get all tables
  $sql_dump = wp_backup_database();
  // echo $sql_dump;

  # save to file: uploads/wp-backup/wp-backup-database.sql
  $upload_dir = wp_upload_dir();
  $file_path = $upload_dir['basedir'] . '/wp-backup/wp-backup-database.sql';
  file_put_contents($file_path, $sql_dump);

  # return the file path
  return $file_path;
}

add_action('init', '__test');