<?php 
/**
 * @author: Mike Tropi
 * @version: 1.0.0
 * @date: 2025-06-19
 * @description: Restore Database Class
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
  private $new_prefix = null; // new prefix is $wpdb->prefix
  private $old_prefix = null; // old prefix is $backup_prefix

  public function __construct($session_id = null, $exclude_tables = [], $backup_prefix = '') {

      // session id is required
      if (!$session_id) {
          return new WP_Error('session_id_required', "Session ID is required.");
      }

      // check if backup prefix is set
      if (!$backup_prefix) {
          return new WP_Error('backup_prefix_required', "Backup prefix is required.");
      }

      global $wpdb;
      $this->wpdb = $wpdb;

      if (!$session_id) {
          $session_id = uniqid('restore_', true);
      }
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
      return $progress;
  }

  public function processStep() {
      $progress = $this->getProgress();
      if (is_wp_error($progress) || $progress['done']) return $progress;

      $handle = fopen($this->restore_file, 'r');
      if (!$handle) {
          return new WP_Error('file_open_failed', "Can't open file: $this->restore_file.");
      }

      $current_line = 0;
      $executed     = 0;
      $buffer       = '';

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

              $result = $this->wpdb->query($query);
              if ($result === false) {
                  fclose($handle);
                  return new WP_Error('query_failed', "Failed at line $current_line: {$this->wpdb->last_error} | Query: $query");
              }

              $buffer = '';
              $executed++;

              if ($executed >= $this->chunk_lines) break;
          }
      }

      if (feof($handle)) {
          $progress['done'] = true;
      }

      $progress['line'] = $current_line;
      fclose($handle);

      file_put_contents($this->progress_file, json_encode($progress, JSON_PRETTY_PRINT));
      return $progress;
  }

  private function processQueryPrefix($query) {
      if (!$this->new_prefix) return $query;

      $pattern = '/\b(' . preg_quote($this->old_prefix, '/') . '[_a-z0-9]+)\b/i';

      return preg_replace_callback($pattern, function ($matches) {
          $old_table = $matches[1];
          $new_table = preg_replace('/^' . preg_quote($this->old_prefix, '/') . '/', $this->new_prefix, $old_table);
          return $new_table;
      }, $query);
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
          return new WP_Error('progress_missing', "Progress file not found.");
      }

      $progress = json_decode(file_get_contents($this->progress_file), true);
      return $progress ?: new WP_Error('progress_corrupt', "Invalid progress file.");
  }

  public function finishRestore() {
      if (file_exists($this->progress_file)) {
          unlink($this->progress_file);
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
