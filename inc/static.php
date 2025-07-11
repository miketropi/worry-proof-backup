<?php 
/**
 * Static file
 */

function worrpb_wp_enqueue_scripts() {

  // Only enqueue style if on the WP Backup admin page
  if ( worrpb_is_current_admin_page( 'tools_page_wp-backup' ) ) {
    # Google fonts
    // Google Fonts URLs are dynamic and don't have version parameters
    wp_enqueue_style( 'wp-backup-google-fonts', 'https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
    
    # enqueue style
    wp_enqueue_style( 'wp-backup-style', WP_BACKUP_PLUGIN_URL . 'dist/css/wp-backup.bundle.css', array(), WP_BACKUP_PLUGIN_VERSION, 'all' );
  }

  # enqueue script
  wp_enqueue_script( 'wp-backup', WP_BACKUP_PLUGIN_URL . 'dist/wp-backup.bundle.js', array('jquery'), WP_BACKUP_PLUGIN_VERSION, true );

  # current user id
  $current_user_id = get_current_user_id();

  # localize script
  wp_localize_script( 'wp-backup', 'worrpb_php_data', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'language' => array(),
    'server_metrics' => worrpb_get_server_metrics(),
    
    # current datetime of server
    'current_datetime' => gmdate('Y-m-d H:i:s'),

    # current wordpress domain
    'current_domain' => get_home_url(),
    
    'nonce' => array(
      'worrpb_nonce' => wp_create_nonce( 'worrpb_nonce_' . $current_user_id ),
      'wp_restore_nonce' => wp_create_nonce( 'wp-backup-restore' ),
    ),
  ) );
}

add_action( 'admin_enqueue_scripts', 'worrpb_wp_enqueue_scripts' );