<?php 
/**
 * Hooks file
 */

add_action( 'admin_menu', 'wp_backup_register_admin_page' );

// wp_backup:after_restore_database_success
add_action('wp_backup:after_restore_database_success', 'wp_backup_after_restore_database_success', 10, 1);
function wp_backup_after_restore_database_success($payload) {
  global $wpdb;

  // delete cache options
  wp_cache_delete( 'alloptions', 'options' );

  // update site url
  $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name IN ('siteurl', 'home')", esc_url_raw( $payload['current_domain'] ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
}

// test backup cron manager
add_action('init', 'wp_backup_cron_handler');

function wp_backup_cron_handler() {
  $init_step = 0;
  $wp_backup_cron_types = ['database', 'plugin', 'theme', 'uploads'];
  $backup_steps = [
    'step__' . $init_step => [
      'callback_fn' => 'wp_backup_cron_step__create_config_file'
    ]
  ];

  foreach ($wp_backup_cron_types as $type) {
    $init_step += 1;
    $backup_steps["step__" . $init_step] = [
      'callback_fn' => 'wp_backup_cron_step__' . $type
    ];
  }

  $backup_steps['step__' . $init_step + 1] = [
    'callback_fn' => 'wp_backup_cron_step__finish'
  ];

  $cron_manager = new WP_Backup_Cron_Manager('wp_backup_cron_manager', function($context) use ($backup_steps) {
    $step = $context['step'] ?? 0;

    return [
      'callback_fn' => $backup_steps['step__' . $step]['callback_fn'],
      'context' => $context,
    ];
  });

  $cron_manager->run_if_due(300);
}