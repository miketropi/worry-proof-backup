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
add_action('init', 'wp_backup_cron_handler', 20);

function wp_backup_cron_handler() {
  $init_step = 0;
  $wp_backup_cron_types = ['database', 'plugin', 'theme', 'uploads'];
  $backup_steps = [
    'step__' . $init_step => [
      'name' => 'Create config file',
      'callback_fn' => 'wp_backup_cron_step__create_config_file',
      'context' => [
        'backup_types' => $wp_backup_cron_types,
      ]
    ]
  ];

  foreach ($wp_backup_cron_types as $type) {
    $init_step += 1;
    $backup_steps["step__" . $init_step] = [
      'name' => _x('Backup ' . $type, 'wp-backup'),
      'callback_fn' => 'wp_backup_cron_step__' . $type
    ];
  }

  $backup_steps['step__' . ($init_step + 1)] = [
    'name' => _x('Finish', 'wp-backup'),
    'callback_fn' => 'wp_backup_cron_step__finish'
  ];

  $type = 'weekly'; // 👈 allow: daily, weekly, monthly, ...
  $period_key = wp_backup_get_period_key($type);

  $cron_manager = new WP_Backup_Cron_Manager('wp_backup_cron_manager', function($context) use ($backup_steps, $period_key, $type) {

    $completed = isset($context['completed']) ? $context['completed'] : false;
    $last_key = isset($context['period_key']) ? $context['period_key'] : '';
    $step = isset($context['step']) ? (int) $context['step'] : 0;

    // if new step of period → reset process
    if ($last_key !== $period_key) {
      $step = 0;
      $completed = false;
      error_log("🆕 START BACKUP $type: $period_key");
    }

    // if completed, skip
    if ($completed) {
      error_log("✅ COMPLETED BACKUP $type $period_key → SKIP");
      return $context;
    }

    // 🔧 Process backup each part 
    $callback_fn = $backup_steps['step__' . $step]['callback_fn'];
    $backup_steps_context = isset($backup_steps['step__' . $step]['context']) ? $backup_steps['step__' . $step]['context'] : [];

    $context = array_merge($context, $backup_steps_context);
    $result = call_user_func($callback_fn, $context);

    // merge result with context
    $context = array_merge($context, $result, [
      'period_key' => $period_key,
    ]);

    return $context;
  }); 

  $cron_manager->run_if_due(60);
}