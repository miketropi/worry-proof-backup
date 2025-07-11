<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @author: Mike Tropi
 * @version: 1.1.1
 * @date: 2025-06-24
 * @description: Restore Database Class (Safe Enhanced)
 * @support: https://github.com/miketropi/wp-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 */

class WP_Restore_Database {
    private $wpdb;
    private $restore_file;
    private $progress_file;
    private $session_id;
    private $restore_dir;
    private $chunk_lines = 1000;
    private $exclude_tables = [];
    private $new_prefix = null;
    private $old_prefix = null;
    private $log_file;
    private $skip_duplicate_entry = true;
    private $use_transaction = true;

    public function __construct($session_id = null, $exclude_tables = [], $backup_prefix = '') {
        if (!$session_id) return new WP_Error('session_id_required', "Session ID is required.");
        if (!$backup_prefix) return new WP_Error('backup_prefix_required', "Backup prefix is required.");

        global $wpdb;
        $this->wpdb = $wpdb;

        $this->session_id = sanitize_file_name($session_id);

        $upload_dir = wp_upload_dir();
        if (empty($upload_dir['basedir'])) {
            return new WP_Error('upload_dir_error', "Upload directory error.");
        }

        $this->restore_dir = $upload_dir['basedir'] . '/wp-backup/' . $this->session_id;
        if (!file_exists($this->restore_dir)) {
            return new WP_Error('restore_dir_not_found', "Restore directory not found: $this->restore_dir");
        }

        $this->restore_file  = $this->restore_dir . '/backup.sql';
        $this->progress_file = $this->restore_dir . '/progress.json';
        $this->log_file      = $this->restore_dir . '/restore.log';

        $this->exclude_tables = $exclude_tables;
        $this->new_prefix     = $wpdb->prefix;
        $this->old_prefix     = $backup_prefix;
    }

    public function startRestore() {
        if (!file_exists($this->restore_file)) {
            return new WP_Error('file_missing', "Restore file not found: $this->restore_file.");
        }

        $progress = [
            'line' => 0,
            'done' => false,
        ];

        file_put_contents($this->progress_file, json_encode($progress, JSON_PRETTY_PRINT));
        file_put_contents($this->log_file, "== Restore started at " . gmdate('Y-m-d H:i:s') . " ==\n", FILE_APPEND);

        return $progress;
    }

    public function processStep() {
        @set_time_limit(300); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged

        $progress = $this->getProgress();
        if (is_wp_error($progress) || $progress['done']) return $progress;

        // fopen() is used here because WP_Filesystem does not support reading line-by-line from large SQL files.
        $handle = fopen($this->restore_file, 'r'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        if (!$handle) {
            return new WP_Error('file_open_failed', "Can't open file: $this->restore_file.");
        }

        $current_line = 0;
        $executed     = 0;
        $buffer       = '';

        $this->wpdb->query('SET FOREIGN_KEY_CHECKS = 0;');
        if ($this->use_transaction) {
            $this->wpdb->query('START TRANSACTION;');
        }

        while (!feof($handle)) {
            $line = fgets($handle);
            $current_line++;

            if ($current_line <= $progress['line']) continue;

            $line = trim($line);
            if ($line === '' || str_starts_with($line, '--') || str_starts_with($line, '/*')) continue;

            $buffer .= $line . ' ';

            if (substr($line, -1) === ';') {
                $query = $this->processQueryPrefix($buffer);

                if ($this->shouldExcludeQuery($query)) {
                    $buffer = '';
                    continue;
                }

                // No need to prepare() because there are no volatile parameters
                $result = $this->wpdb->query($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

                if ($result === false) {
                    $error = $this->wpdb->last_error;

                    if ($this->skip_duplicate_entry && stripos($error, 'Duplicate entry') !== false) {
                        file_put_contents($this->log_file, "[Warning] Duplicate entry skipped at line $current_line: $error | Query: $query\n", FILE_APPEND);
                        $buffer = '';
                        $executed++;
                        continue;
                    }

                    if ($this->use_transaction) {
                        $this->wpdb->query('ROLLBACK;');
                    }

                    // fclose() is necessary for proper stream cleanup; no WP_Filesystem alternative exists for open file handles.
                    fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

                    return new WP_Error('query_failed', "Failed at line $current_line: $error | Query: $query");
                }

                $buffer = '';
                $executed++;

                if ($executed >= $this->chunk_lines) break;
            }
        }

        if ($this->use_transaction) {
            $this->wpdb->query('COMMIT;');
        }

        $this->wpdb->query('SET FOREIGN_KEY_CHECKS = 1;');

        if (feof($handle)) {
            $progress['done'] = true;
            file_put_contents($this->log_file, esc_html__("== Restore finished at ", 'worry-proof-backup') . gmdate('Y-m-d H:i:s') . " ==\n", FILE_APPEND);
        }

        $progress['line'] = $current_line;

        // fclose() is necessary for proper stream cleanup; no WP_Filesystem alternative exists for open file handles.
        fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        file_put_contents($this->progress_file, json_encode($progress, JSON_PRETTY_PRINT));
        return $progress;
    }

    private function processQueryPrefix($query) {
        if (!$this->new_prefix) return $query;

        $pattern = '/\b(' . preg_quote($this->old_prefix, '/') . '[_a-z0-9]+)\b/i';

        $query = preg_replace_callback($pattern, function ($matches) {
            $old_table = $matches[1];
            $new_table = preg_replace('/^' . preg_quote($this->old_prefix, '/') . '/', $this->new_prefix, $old_table);
            return $new_table;
        }, $query);

        // Auto-rewrite INSERT INTO wp_options → INSERT IGNORE
        if (preg_match('/^\s*INSERT\s+INTO\s+`?' . preg_quote($this->new_prefix . 'options', '/') . '`?/i', $query)) {
            $query = preg_replace('/^\s*INSERT\s+INTO/i', 'INSERT IGNORE INTO', $query, 1);
        }

        return $query;
    }

    private function shouldExcludeQuery($query) {
        if (empty($this->exclude_tables)) return false;

        foreach ($this->exclude_tables as $table) {
            if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $query)) {
                return true;
            }
        }

        return false;
    }

    public function getProgress() {
        if (!file_exists($this->progress_file)) {
            return new WP_Error('progress_missing', esc_html__("Progress file not found", 'worry-proof-backup') . ": " . $this->progress_file);
        }

        $progress = json_decode(file_get_contents($this->progress_file), true);
        return $progress ?: new WP_Error('progress_corrupt', esc_html__("Invalid progress file", 'worry-proof-backup'));
    }

    public function finishRestore() {
        if (file_exists($this->progress_file)) {
            wp_delete_file($this->progress_file);
        }
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, esc_html__("== Restore manually finished at ", 'worry-proof-backup') . gmdate('Y-m-d H:i:s') . " ==\n", FILE_APPEND);
        }
    }

    public function getRestoreFilePath() {
        return $this->restore_file;
    }

    public function getRestoreFolder() {
        return $this->restore_dir;
    }

    public function getSessionId() {
        return $this->session_id;
    }
}
