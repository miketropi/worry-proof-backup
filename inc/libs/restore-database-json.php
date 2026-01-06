<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WORRPB_Restore_Database_JSON {

	private $wpdb;
	private $restore_file;
	private $progress_file;
	private $session_id;
	private $restore_dir;
	private $chunk_lines = 1000;
	private $exclude_tables = [];
	private $new_prefix;
	private $old_prefix;
	private $log_file;
	private $skip_duplicate_entry = true;
	private $use_transaction = true;

	// size of jsonl (bytes)
	private $jsonl_size = 0;

	public function __construct($session_id, $exclude_tables = [], $backup_prefix = '') {

		if (!$session_id) {
			return new WP_Error('session_required', __('Session ID is required.', 'worry-proof-backup'));
		}

		global $wpdb;
		$this->wpdb = $wpdb;
		$this->session_id = sanitize_file_name($session_id);

		$upload_dir = wp_upload_dir();
		if (empty($upload_dir['basedir'])) {
			return new WP_Error('upload_dir_error', __('Upload dir not found', 'worry-proof-backup'));
		}

		$this->restore_dir   = $upload_dir['basedir'] . '/worry-proof-backup/' . $this->session_id;
		$this->restore_file  = $this->restore_dir . '/backup.sql.jsonl';
		$this->progress_file = $this->restore_dir . '/restore-progress.json';
		$this->log_file      = $this->restore_dir . '/restore.log';

		if (!is_dir($this->restore_dir) || !file_exists($this->restore_file)) {
			return new WP_Error('restore_missing', __('Restore data not found.', 'worry-proof-backup'));
		}

		$this->exclude_tables = $exclude_tables;
		$this->new_prefix     = $wpdb->prefix;
		$this->old_prefix     = $backup_prefix;
		$this->jsonl_size     = filesize($this->restore_file);
	}

	/* ================= START ================= */

	public function startRestore() {

		$progress = [
			'line' => 0,
			'offset' => 0,
			'done' => false,
			'processed_bytes' => 0,
			'total_bytes' => $this->jsonl_size,
			'percent' => 0,
		];

		file_put_contents($this->progress_file, wp_json_encode($progress));
		file_put_contents(
			$this->log_file,
			"== Restore started at " . gmdate('Y-m-d H:i:s') . " ==\n",
			FILE_APPEND
		);

		return $progress;
	}

	/* ================= PROCESS ================= */

	public function processStep() {

		$progress = $this->getProgress();
		if (is_wp_error($progress) || !empty($progress['done'])) {
			return $progress;
		}

		$handle = fopen($this->restore_file, 'r');
		if (!$handle) {
			return new WP_Error('file_open_failed', __('Cannot open restore file.', 'worry-proof-backup'));
		}

		// Resume bằng byte offset (không đổi cấu trúc logic)
		if (!empty($progress['offset'])) {
			fseek($handle, (int) $progress['offset']);
		}

		$current  = (int) $progress['line'];
		$executed = 0;

		$this->wpdb->query('SET FOREIGN_KEY_CHECKS=0;');
		if ($this->use_transaction) {
			$this->wpdb->query('START TRANSACTION;');
		}

		while (!feof($handle)) {

			$line = fgets($handle);
			if ($line === false) {
				break;
			}

			$current++;

			if (trim($line) === '') {
				continue;
			}

			$payload = json_decode($line, true);
			if (!$payload || empty($payload['statements']) || !is_array($payload['statements'])) {
				continue;
			}

			foreach ($payload['statements'] as $statement) {

				$sql = $this->processQueryPrefix($statement);
				$sql = $this->normalizeOptionsQuery($sql);

				if ($this->shouldExcludeQuery($sql)) {
					continue;
				}

				$result = $this->executeQuerySafely($sql, $current);
				if (is_wp_error($result)) {
					if ($this->use_transaction) {
						$this->wpdb->query('ROLLBACK;');
					}
					fclose($handle);
					return $result;
				}
			}

			$executed++;
			if ($executed >= $this->chunk_lines) {
				break;
			}
		}

		if ($this->use_transaction) {
			$this->wpdb->query('COMMIT;');
		}

		$this->wpdb->query('SET FOREIGN_KEY_CHECKS=1;');

		$offset = ftell($handle);
		$done   = feof($handle);

		if ($done) {
			file_put_contents(
				$this->log_file,
				"== Restore finished at " . gmdate('Y-m-d H:i:s') . " ==\n",
				FILE_APPEND
			);
		}

		fclose($handle);

		$progress = [
			'line' => $current,
			'offset' => $offset,
			'done' => $done,
			'processed_bytes' => $offset,
			'total_bytes' => $this->jsonl_size,
			'percent' => $this->jsonl_size > 0
				? round(($offset / $this->jsonl_size) * 100, 2)
				: 0,
		];

		file_put_contents($this->progress_file, wp_json_encode($progress));
		return $progress;
	}

	/* ================= SQL GUARD ================= */

	private function executeQuerySafely($sql, $line) {

		if (preg_match('/ADD\s+``\s*\(\s*``\s*\)/', $sql)) {
			$this->log("[Skip] Invalid ADD clause at line {$line}");
			return true;
		}

		$result = $this->wpdb->query($sql); // phpcs:ignore

		if ($result !== false) {
			return true;
		}

		$error = $this->wpdb->last_error;

		$skippable = [
			'Duplicate entry',
			'Duplicate key name',
			'Duplicate column name',
			'Duplicate index',
			'Column already exists',
			'Index already exists',
		];

		foreach ($skippable as $needle) {
			if (stripos($error, $needle) !== false) {
				$this->log("[Skip] {$error} at line {$line}");
				return true;
			}
		}

		$this->log("[Fatal] {$error} at line {$line}");
		return new WP_Error('query_failed', $error);
	}

	private function log($message) {
		file_put_contents($this->log_file, $message . "\n", FILE_APPEND);
	}

	/* ================= HELPERS ================= */

	private function processQueryPrefix($query) {
		if (!$this->old_prefix || $this->old_prefix === $this->new_prefix) {
			return $query;
		}

		$pattern = '/\b' . preg_quote($this->old_prefix, '/') . '([a-z0-9_]+)\b/i';

		return preg_replace_callback($pattern, function ($m) {
			return $this->new_prefix . $m[1];
		}, $query);
	}

	private function normalizeOptionsQuery($sql) {
		if (!preg_match('/^INSERT\s+INTO\s+`?' . preg_quote($this->new_prefix, '/') . 'options`?/i', $sql)) {
			return $sql;
		}
		return preg_replace('/^INSERT\s+INTO/i', 'REPLACE INTO', $sql);
	}

	private function shouldExcludeQuery($query) {
		foreach ($this->exclude_tables as $table) {
			if (stripos($query, $table) !== false) {
				return true;
			}
		}
		return false;
	}

	public function getProgress() {
		if (!file_exists($this->progress_file)) {
			return new WP_Error('progress_missing', __('Progress file missing.', 'worry-proof-backup'));
		}
		return json_decode(file_get_contents($this->progress_file), true);
	}

	public function finishRestore() {
		if (file_exists($this->progress_file)) {
			wp_delete_file($this->progress_file);
		}
		$this->log("== Restore manually finished at " . gmdate('Y-m-d H:i:s') . " ==");
		return true;
	}
}
