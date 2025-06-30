<?php 
/**
 * @author: Mike Tropi
 * @version: 1.0.0
 * @date: 2025-06-30
 * @description: Backup Cron Manager Class
 * @support: https://github.com/miketropi/wp-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 */

class WP_Backup_Cron_Manager {
    protected $lock_file;
    protected $history_file;
    protected $task_callback;
    protected $lock_timeout = 300; // 5 minutes
    protected $base_path;
    protected $fs;

    public function __construct($task_id, callable $callback) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();

        global $wp_filesystem;
        $this->fs = $wp_filesystem;

        // folder uploads
        $upload_dir = wp_upload_dir();
        if (empty($upload_dir['basedir'])) {
            return new WP_Error('upload_dir_error', "Uh-oh! 📂 Couldn't get the upload directory. Please check your WordPress upload settings and try again! 🙏");
        }

        $this->base_path = $upload_dir['basedir'] . '/wp-backup-cron-manager/';

        // check if folder exists
        if (!$this->fs->exists($this->base_path)) {
            $check_folder = $this->fs->mkdir($this->base_path, 0755);
            if (is_wp_error($check_folder)) {
                return new WP_Error('mkdir_failed', $check_folder->get_error_message());
            }
        }

        $this->lock_file = $this->base_path . $task_id . '.lock';
        $this->history_file = $this->base_path . $task_id . '.json';
        $this->task_callback = $callback;
    }

    public function run_if_due($interval_in_seconds = 300) {
        if ($this->is_locked()) return false;

        $last_run = $this->get_history('last_run');
        $now = time();

        if (!$last_run || ($now - $last_run) >= $interval_in_seconds) {
            $this->lock();

            $context = $this->get_history('context') ?: [];

            // Call the task defined by the user
            $new_context = call_user_func($this->task_callback, $context);

            $this->update_history([
                'last_run' => $now,
                'context'  => $new_context,
            ]);

            $this->unlock();
            return true;
        }

        return false;
    }

    protected function is_locked() {
        if (!$this->fs->exists($this->lock_file)) return false;

        $mtime = $this->fs->mtime($this->lock_file);
        if ((time() - $mtime) > $this->lock_timeout) {
            $this->fs->delete($this->lock_file);
            return false;
        }

        return true;
    }

    protected function lock() {
        $this->fs->put_contents($this->lock_file, time());
    }

    protected function unlock() {
        $this->fs->delete($this->lock_file);
    }

    protected function get_history($key = null) {
        if (!$this->fs->exists($this->history_file)) return null;

        $raw = $this->fs->get_contents($this->history_file);
        $data = json_decode($raw, true);
        if (!$data) return null;

        return $key ? ($data[$key] ?? null) : $data;
    }

    protected function update_history(array $new_data) {
        $data = $this->get_history() ?: [];
        $data = array_merge($data, $new_data);

        $this->fs->put_contents($this->history_file, json_encode($data, JSON_PRETTY_PRINT));
    }
}
