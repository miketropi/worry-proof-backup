<?php 
/**
 * Plugin Name: Worry Proof Backup
 * Plugin URI: https://github.com/miketropi/wp-backup
 * Description: 🛡️ Professional WordPress backup solution with comprehensive database and file system protection. Features automated backups, secure storage, and one-click restoration capabilities. Built for reliability and ease of use in production environments. **100% FREE FOREVER** - No hidden costs, no premium tiers, no limitations.
 * Version: 0.1.3
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: @Mike
 * Author URI: https://github.com/miketropi
 * Text Domain: worry-proof-backup
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Worry Proof Backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * Worry Proof Backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Worry Proof Backup. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

{
  # define plugin path
  define( 'WP_BACKUP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

  # define plugin url
  define( 'WP_BACKUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

  # define plugin version
  define( 'WP_BACKUP_PLUGIN_VERSION', '0.1.2' );

  # beta version
  define( 'WP_BACKUP_PLUGIN_BETA', true );
}

{
  /**
   * Include files
   */

  # include libs file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/libs/backup-database.php';
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/libs/backup-file-system.php';
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/libs/restore-database.php';
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/libs/restore-file-system.php';
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/libs/backup-cron-manager.php';
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/libs/backup-cron-handle.php';

  # include static file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/static.php';

  # include functions file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/functions.php';

  # include ajax file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/ajax.php';

  # include hooks file
  require_once WP_BACKUP_PLUGIN_PATH . 'inc/hooks.php';

  
}

// add link go to backup page in plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'worrpb_plugin_action_links');
function worrpb_plugin_action_links($links) {
  $backup_link = '<a href="' . admin_url('admin.php?page=wp-backup') . '">' . __('Backup Now', 'worry-proof-backup') . '</a>';
  array_unshift($links, $backup_link);
  return $links;
}