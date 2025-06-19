<?php 
/**
 * Plugin Name: WordPress Backup
 * Plugin URI: #
 * Description: WordPress backup plugin that allows you to backup your database and files.
 * Version: 1.0.0
 * Author: @Mike
 * Author URI: #
 * Text Domain: wp-backup
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * WordPress Backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * WordPress Backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with WordPress Backup. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

{
  # define plugin path
  define( 'WP_BACKUP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

  # define plugin url
  define( 'WP_BACKUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

  # define plugin version
  define( 'WP_BACKUP_PLUGIN_VERSION', '1.0.0' );
}

{
  /**
   * Include files
   */

  # include libs file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/libs/backup-database.php';
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/libs/backup-file-system.php';

  # include static file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/static.php';

  # include functions file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/functions.php';

  # include ajax file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/ajax.php';

  # include hooks file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/hooks.php';
}