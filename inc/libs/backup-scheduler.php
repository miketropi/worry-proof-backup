<?php 
/**
 * @author: Mike Tropi
 * @version: 1.0.0
 * @date: 2025-06-30
 * @description: Backup Scheduler Class
 * @support: https://github.com/miketropi/wp-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 */

class WP_Backup_Scheduler {
    protected $hook = 'wp_backup_run_scheduled_backup';

    public function __construct() {
        add_action('init', [ $this, 'maybe_schedule_event' ]);
        add_action($this->hook, [ $this, 'handle_backup_task' ]);
        add_filter('cron_schedules', [ $this, 'custom_intervals' ]);
    }

    public function maybe_schedule_event() {
        $option = get_option('my_backup_schedule'); // example: ['interval' => 'fifteen_minutes']

        if (empty($option['interval'])) return;

        if (!wp_next_scheduled($this->hook)) {
            wp_schedule_event(time(), $option['interval'], $this->hook);
        }
    }

    public function handle_backup_task() {
        // Call the actual backup function here
        // if (function_exists('my_custom_backup_function')) {
        //     my_custom_backup_function();
        // }
    }

    public function custom_intervals($schedules) {
        $schedules['twelve_hours'] = [
            'interval' => 43200,
            'display'  => __('Every 12 Hours')
        ];
        $schedules['daily'] = [
            'interval' => 86400,
            'display'  => __('Daily')
        ];
        $schedules['weekly'] = [
            'interval' => 604800,
            'display'  => __('Weekly')
        ];
        $schedules['monthly'] = [
            'interval' => 2592000,
            'display'  => __('Monthly')
        ];
        // 3 months
        $schedules['three_months'] = [
            'interval' => 7776000,
            'display'  => __('Every 3 Months')
        ];
        
        return $schedules;
    }

    public function clear_schedule() {
        $timestamp = wp_next_scheduled($this->hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $this->hook);
        }
    }
}
