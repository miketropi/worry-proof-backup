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
