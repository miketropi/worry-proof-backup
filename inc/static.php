<?php 
/**
 * Static file
 */

function wp_backup_wp_enqueue_scripts() {

  // Only enqueue style if on the WP Backup admin page
  if ( isset($_GET['page']) && $_GET['page'] === 'wp-backup' ) {
    # Google fonts
    wp_enqueue_style( 'wp-backup-google-fonts', 'https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap', array(), null );
    
    # enqueue style
    wp_enqueue_style( 'wp-backup-style', WP_BACKUP_PLUGIN_URL . 'dist/css/wp-backup.bundle.css', array(), WP_BACKUP_PLUGIN_VERSION, 'all' );
  }

  # enqueue script
  wp_enqueue_script( 'wp-backup', WP_BACKUP_PLUGIN_URL . 'dist/wp-backup.bundle.js', array('jquery'), WP_BACKUP_PLUGIN_VERSION, true );

  # current user id
  $current_user_id = get_current_user_id();

  # localize script
  wp_localize_script( 'wp-backup', 'wp_backup_php_data', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'language' => array(),
    'server_metrics' => wp_backup_get_server_metrics(),
    
    # current datetime of server
    'current_datetime' => date('Y-m-d H:i:s'),
    
    'nonce' => array(
      'wp_backup_nonce' => wp_create_nonce( 'wp_backup_nonce_' . $current_user_id ),
    ),
  ) );
}

add_action( 'admin_enqueue_scripts', 'wp_backup_wp_enqueue_scripts' );