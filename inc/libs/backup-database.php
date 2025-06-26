<?php
/**
 * @author: Mike Tropi
 * @version: 1.0.0
 * @date: 2025-06-18
 * @description: Chunked WordPress Database Backup Class
 * @support: https://github.com/miketropi/wp-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 * 
 * Chunked WordPress Database Backup Class
 * Support multiple sessions at once (AJAX-driven)
 * Each session is saved in a separate folder
 *
 * All methods that interact with the filesystem or database return WP_Error on failure.
 *
 * Usage Example:
 * $session_id = uniqid('user123_', true);
 * $exclude_tables = ['wp_users', 'wp_options']; // tables to exclude
 * $backup = new WP_Backup_Database(1000, $session_id, $exclude_tables);
 * $backup->startBackup();
 *
 * // In each AJAX request or step, call:
 * $backup = new WP_Backup_Database(1000, $_POST['session_id'], $exclude_tables);
 * $progress = $backup->processStep();
 *
 * // Check progress
 * $progress = $backup->getProgress();
 *
 * // When finished, optionally clean up:
 * $backup->finishBackup();
 */

class WP_Backup_Database {
    private $wpdb;
    private $backup_file;
    private $progress_file;
    private $chunk_size;
    private $session_id;
    private $backup_dir;
    private $exclude_tables;

    /**
     * Constructor
     */
    public function __construct($chunk_size = 1000, $session_id = null, $exclude_tables = array()) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->chunk_size = $chunk_size;

        // If no session_id is provided, create a unique id
        if (!$session_id) {
            $session_id = uniqid('backup_', true);
        }

        $this->session_id = sanitize_file_name($session_id);

        // Create a folder for the session
        $upload_dir = wp_upload_dir();
        if (empty($upload_dir['basedir'])) {
            return new WP_Error('upload_dir_error', "Uh-oh! 📂 Couldn't get the upload directory. Please check your WordPress upload settings and try again! 🙏");
        }
        $this->backup_dir = $upload_dir['basedir'] . '/wp-backup/' . $this->session_id;

        if (!file_exists($this->backup_dir)) {
            if (!wp_mkdir_p($this->backup_dir)) {
                return new WP_Error('mkdir_failed', "Yikes! 🛠️ Couldn't create the backup directory: $this->backup_dir. Please check your folder permissions and try again! 🔒");
            }
        }

        // Assign file path
        $this->backup_file   = $this->backup_dir . '/backup.sql';
        $this->progress_file = $this->backup_dir . '/progress.json';

        // Set exclude tables
        $this->exclude_tables = $exclude_tables;
    }

    /**
     * Start a new backup
     */
    public function startBackup() {
        $tables = $this->wpdb->get_col('SHOW TABLES');
        if ($this->wpdb->last_error) {
            return new WP_Error('db_error', "Oh no! 🐘 Database error: {$this->wpdb->last_error}. Please check your database connection and try again! 🔌");
        }
        if (!$tables) {
            return new WP_Error('no_tables', "Hmm... 🤔 No tables found in the database. Is your database empty? Please double-check! 🧐");
        }
        // Exclude tables if needed
        if (!empty($this->exclude_tables)) {
            $prefix = $this->wpdb->prefix;
            $exclude_full_tables = array();
            foreach ($this->exclude_tables as $table) {
                $exclude_full_tables[] = (strpos($table, $prefix) === 0) ? $table : $prefix . $table;
            }
            $tables = array_diff($tables, $exclude_full_tables);
        }

        $progress = [
            'tables'         => $tables,
            'current_table'  => 0,
            'offset'         => 0,
            'done'           => false,
        ];

        // Reset file and add SQL header
        $header = "SET NAMES utf8;\n" .
"SET time_zone = '+00:00';\n" .
"SET foreign_key_checks = 0;\n" .
"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n" .
"\n" .
"SET NAMES utf8mb4;\n" .
"\n";

        // Reset file
        if (file_put_contents($this->backup_file, $header . "\n\n") === false) {
            return new WP_Error('write_failed', "Oops! 😅 Couldn't write to the backup file: $this->backup_file. Please check your file permissions and available disk space! 🗃️");
        }
        if (file_put_contents($this->progress_file, json_encode($progress)) === false) {
            return new WP_Error('write_failed', "Oops! 😅 Couldn't write to the progress file: $this->progress_file. Please check your file permissions and available disk space! 🗃️");
        }
    }

    /**
     * Process one step of backup
     */
    public function processStep() {
        $progress = $this->getProgress();

        if (is_wp_error($progress)) {
            return $progress;
        }
        if (!$progress || $progress['done']) {
            return $progress;
        }

        $tables = $progress['tables'];
        $table_index = $progress['current_table'];
        $offset = $progress['offset'];

        if (!isset($tables[$table_index])) {
            // No more tables
            $progress['done'] = true;
            if (file_put_contents($this->progress_file, json_encode($progress)) === false) {
                return new WP_Error('write_failed', "Oops! 😅 Couldn't update the progress file: $this->progress_file. Please check your file permissions and available disk space! 🗃️");
            }
            return $progress;
        }

        $table = $tables[$table_index];

        // Get 1 chunk data
        $rows = $this->wpdb->get_results( "SELECT * FROM `$table` LIMIT $offset, $this->chunk_size", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared 
        if ($this->wpdb->last_error) {
            return new WP_Error('db_error', "Oh no! 🐘 Database error: {$this->wpdb->last_error}. Please check your database connection and try again! 🔌");
        }

        $sql = '';

        if ($offset === 0) {
            // First time of the table → add DROP TABLE IF EXISTS and CREATE TABLE command
            $create = $this->wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            if ($this->wpdb->last_error) {
                return new WP_Error('db_error', "Oh no! 🐘 Database error: {$this->wpdb->last_error}. Please check your database connection and try again! 🔌");
            }
            if (!$create || !isset($create[1])) {
                return new WP_Error('create_table_error', "Whoops! 🏗️ Couldn't get CREATE TABLE statement for: $table. Please check your database structure! 🛠️");
            }
            $sql .= "\n\nDROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create[1] . ";\n\n";
        }

        foreach ($rows as $row) {
            $values = array_map(function($v) {
                return isset($v) ? ("'" . esc_sql($v) . "'") : 'NULL';
            }, array_values($row));

            $sql .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n";
        }

        // Append to file
        if (file_put_contents($this->backup_file, $sql, FILE_APPEND) === false) {
            return new WP_Error('write_failed', "Oops! 😅 Couldn't append to the backup file: $this->backup_file. Please check your file permissions and available disk space! 🗃️");
        }

        // Update progress
        if (count($rows) < $this->chunk_size) {
            // End of the table → go to the next table
            $progress['current_table']++;
            $progress['offset'] = 0;

            if ($progress['current_table'] >= count($tables)) {
                $progress['done'] = true;
            }
        } else {
            $progress['offset'] += $this->chunk_size;
        }

        if (file_put_contents($this->progress_file, json_encode($progress)) === false) {
            return new WP_Error('write_failed', "Oops! 😅 Couldn't update the progress file: $this->progress_file. Please check your file permissions and available disk space! 🗃️");
        }

        return $progress;
    }

    /**
     * Get current progress
     */
    public function getProgress() {
        if (!file_exists($this->progress_file)) {
            return new WP_Error('progress_file_missing', "Uh-oh! 📄 Progress file does not exist: $this->progress_file. Did you start the backup? Try starting a new backup session! 🚀");
        }

        $progress = json_decode(file_get_contents($this->progress_file), true);
        if ($progress === null) {
            return new WP_Error('progress_file_invalid', "Yikes! 🧐 Couldn't decode the progress file: $this->progress_file. The file might be corrupted. Try restarting the backup! 🔄");
        }
        return $progress;
    }

    /**
     * Finish and cleanup
     */
    public function finishBackup() {
        if (file_exists($this->progress_file)) {
            if (!wp_delete_file($this->progress_file)) {
                return new WP_Error('unlink_failed', "Oops! 🧹 Couldn't delete the progress file: $this->progress_file. Please check your file permissions! 🔒");
            }
        }
        // Maybe zip file here if needed
        // Example:
        // $this->zipBackup();
    }

    /**
     * Optional: Zip backup folder
     */
    public function zipBackup() {
        $zip_file = $this->backup_dir . '/backup.zip';

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($this->backup_file, 'backup.sql');
                $zip->close();
            } else {
                return new WP_Error('zip_failed', "Oh snap! 📦 Couldn't create the zip file: $zip_file. Please check your disk space and permissions! 💾");
            }
        } else {
            return new WP_Error('ziparchive_missing', "Heads up! 🧩 The ZipArchive class is not available. Please enable the PHP Zip extension to use this feature! 🛠️");
        }
    }

    /**
     * Get backup file path (public)
     */
    public function getBackupFilePath() {
        return $this->backup_file;
    }

    /**
     * Get backup folder
     */
    public function getBackupFolder() {
        return $this->backup_dir;
    }

    /**
     * Get session ID
     */
    public function getSessionId() {
        return $this->session_id;
    }
}
