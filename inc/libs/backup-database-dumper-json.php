<?php
/**
 * Creates database backups in the JSON Lines (JSONL) format.
 * Optimized for safe and reliable database backup, especially on hosts like Kinsta, etc. where raw SQL backups can have issues.
 * 
 * @author: Mike Tropi
 * @since: 0.1.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Backup Database Dumper JSON Class
 * 
 * Usage Example:
 * $session_id = uniqid('user123_', true);
 * $exclude_tables = ['wp_users', 'wp_options']; // tables to exclude
 * $backup = new WORRPB_Database_Dumper_JSON(1000, $session_id, $exclude_tables);
 * $backup->start();
 *
 * // In each AJAX request or step, call:
 * $backup = new WORRPB_Database_Dumper_JSON(1000, $_POST['session_id'], $exclude_tables);
 * $progress = $backup->step();
 *
 * // Check progress
 * $progress = $backup->getProgress();
 *
 * // When finished, optionally clean up:
 * $backup->finishBackup();
 */
class WORRPB_Database_Dumper_JSON {

	private $wpdb;
	private $chunk_size;
	private $session_id;
	private $backup_dir;
	private $backup_file;
	private $progress_file;
	private $exclude_tables;

	public function __construct($chunk_size = 1000, $session_id = null, $exclude_tables = array()) {
		global $wpdb;

		$this->wpdb       = $wpdb;
		$this->chunk_size = (int) $chunk_size;
		$this->exclude_tables = $exclude_tables;

		if (!$session_id) {
			$session_id = uniqid('dbjson_', true);
		}

		$this->session_id = sanitize_file_name($session_id);

		$upload_dir = wp_upload_dir();
		if (empty($upload_dir['basedir'])) {
			return new WP_Error('upload_dir_error', __('Upload dir not found', 'worry-proof-backup'));
		}

		$this->backup_dir = $upload_dir['basedir'] . '/worry-proof-backup/' . $this->session_id;

		if (!file_exists($this->backup_dir)) {
			wp_mkdir_p($this->backup_dir);
		}

		$this->backup_file   = $this->backup_dir . '/backup.sql.jsonl';
		$this->progress_file = $this->backup_dir . '/progress.json';
	}

	/* =========================
	 * START BACKUP
	 * ========================= */
	public function start() {

		$tables = $this->wpdb->get_col('SHOW TABLES');

		if (!empty($this->exclude_tables)) {
			$prefix = $this->wpdb->prefix;
			$exclude = array_map(function($t) use ($prefix) {
					return strpos($t, $prefix) === 0 ? $t : $prefix . $t;
			}, $this->exclude_tables);

			$tables = array_diff($tables, $exclude);
		}

		$progress = array(
			'tables'        => array_values($tables),
			'current_table' => 0,
			'offset'        => 0,
			'done'          => false,
		);

		file_put_contents($this->backup_file, '');
		file_put_contents($this->progress_file, wp_json_encode($progress));

		// SQL headers (JSON wrapped).
		$headers = array(
			"SET NAMES utf8mb4",
			"SET time_zone = '+00:00'",
			"SET foreign_key_checks = 0",
			"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'",
		);

		foreach ($headers as $h) {
			$this->appendLine(array(
				'type' => 'header',
				'statements'  => array($h)
			));
		}

		return true;
	}

	/* =========================
	 * PROCESS STEP
	 * ========================= */
	public function step() {

		$progress = $this->getProgress();
		if ($progress['done']) {
			return $progress;
		}

		$tables = $progress['tables'];
		$tIndex = $progress['current_table'];
		$offset = $progress['offset'];

		if (!isset($tables[$tIndex])) {
			$progress['done'] = true;
			$this->saveProgress($progress);
			return $progress;
		}

		$table = $tables[$tIndex];

		if ($offset === 0) {
			$create = $this->wpdb->get_row(
				"SHOW CREATE TABLE `$table`", 
				ARRAY_N
			);

			$this->appendLine(array(
				'type'  => 'table_schema',
				'table' => $table,
				'statements' => array(
					"DROP TABLE IF EXISTS `$table`",
					$create[1]
				)
			));
		}

		$rows = $this->wpdb->get_results(
			"SELECT * FROM `$table` LIMIT $offset, {$this->chunk_size}",
			ARRAY_A
		);

		foreach ($rows as $row) {

			$values = array_map(function($v) {
				if ($v === null) return 'NULL';

				// minimal & predictable escape.
				$v = str_replace(
					array("\\", "\n", "\r", "\t", "'"),
					array("\\\\", "\\n", "\\r", "\\t", "\\'"),
					$v
				);

				return "'" . $v . "'";
			}, array_values($row));

			$sql = "INSERT INTO `$table` VALUES (" . implode(',', $values) . ")";

			$this->appendLine(array(
				'type'  => 'row',
				'table' => $table,
				'statements' => array($sql)
			));
		}

		if (count($rows) < $this->chunk_size) {
			$progress['current_table']++;
			$progress['offset'] = 0;
		} else {
			$progress['offset'] += $this->chunk_size;
		}

		if ($progress['current_table'] >= count($tables)) {
			$progress['done'] = true;
		}

		$this->saveProgress($progress);
		return $progress;
	}

	/* =========================
	 * HELPERS
	 * ========================= */

	private function appendLine(array $data) {
		$line = wp_json_encode(
			$data,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		file_put_contents(
			$this->backup_file,
			$line . PHP_EOL,
			FILE_APPEND | LOCK_EX
		);
	}

	private function getProgress() {
		$json = file_get_contents($this->progress_file);
		if ($json === false || $json === null) {
			throw new Exception(__('Failed to read progress data from progress file.', 'worry-proof-backup'));
		}
		return json_decode($json, true);
	}

	private function saveProgress($progress) {
		$json = wp_json_encode($progress);
		if ($json === false || $json === null) {
			throw new Exception(__('Failed to encode progress data to JSON.', 'worry-proof-backup'));
		}
		if (file_put_contents($this->progress_file, $json) === false) {
			throw new Exception(__('Failed to write progress data to progress file.', 'worry-proof-backup'));
		}
	}

	// finish backup & clean up progress file
	public function finishBackup() {
		if (file_exists($this->progress_file)) {
			if (!wp_delete_file($this->progress_file)) {
				return new WP_Error('unlink_failed', __('Failed to delete progress file.', 'worry-proof-backup'));
			}
		}
	}

	/* =========================
	 * GETTERS
	 * ========================= */
	public function getBackupFile() {
		return $this->backup_file;
	}

	public function getBackupDir() {
		return $this->backup_dir;
	}

	public function getSessionId() {
		return $this->session_id;
	}
}
