<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!function_exists('WP_Filesystem')) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}
WP_Filesystem();
global $wp_filesystem;

class WORRPB_Restore_File_System {

	private $zip_file;
	private $destination_folder;
	private $overwrite_existing;
	private $exclude = [];
	private $include_only = [];

	private $errors = [];

	private $batch_size = 50; // SAFE DEFAULT
	private $start_index = 0;
	private $progress_file;

	// stats only (DO NOT keep file list)
	private $restored_count = 0;
	private $skipped_count  = 0;

	public function __construct($opts = []) {

		if (empty($opts['zip_file'])) {
			throw new Exception(__('Zip file path is required', 'worry-proof-backup'));
		}
		if (empty($opts['destination_folder'])) {
			throw new Exception(__('Destination folder is required', 'worry-proof-backup'));
		}

		$this->zip_file = $opts['zip_file'];
		$this->destination_folder = rtrim($opts['destination_folder'], '/\\');
		$this->overwrite_existing = !empty($opts['overwrite_existing']);
		$this->exclude = isset($opts['exclude']) ? (array)$opts['exclude'] : [];
		$this->include_only = isset($opts['include_only']) ? (array)$opts['include_only'] : [];
		$this->batch_size = !empty($opts['batch_size']) ? max(10, (int)$opts['batch_size']) : 50;

		$progress_name = $opts['restore_progress_file_name'] ?? 'restore_progress.json';
		$this->progress_file = dirname($this->zip_file) . '/' . $progress_name;

		if (!file_exists($this->zip_file)) {
			throw new Exception(__('Zip file does not exist: ', 'worry-proof-backup') . $this->zip_file);
		}

		if (!is_dir($this->destination_folder)) {
			if (!wp_mkdir_p($this->destination_folder)) {
				throw new Exception(__('Failed to create destination directory: ', 'worry-proof-backup') . $this->destination_folder);
			}
		}
	}

	/* =====================================================
	 * MAIN RESTORE
	 * ===================================================== */
	public function runRestore() {

		@set_time_limit(0);
		@ini_set('memory_limit', '256M');
		@ini_set('output_buffering', 'off');
		@ini_set('zlib.output_compression', 'Off');

		$this->loadProgress();

		if (!class_exists('ZipArchive')) {
			return new WP_Error('missing_ziparchive', __('ZipArchive extension missing', 'worry-proof-backup'));
		}

		$zip = new ZipArchive();
		if ($zip->open($this->zip_file) !== true) {
			return new WP_Error('zip_open_failed', __('Failed to open zip file', 'worry-proof-backup'));
		}

		$total_files = $zip->numFiles;
		$end_index   = min($this->start_index + $this->batch_size, $total_files);

		for ($i = $this->start_index; $i < $end_index; $i++) {

			$file_info = $zip->statIndex($i);
			$file_path = $file_info['name'];

			if ($this->shouldExcludeFile($file_path) || !$this->shouldIncludeFile($file_path)) {
				$this->skipped_count++;
				continue;
			}

			$result = $this->extractFile($zip, $file_info);
			if (is_wp_error($result)) {
				$this->errors[] = $result->get_error_message();
			} else {
				$this->restored_count++;
			}
		}

		$zip->close();

		$next_index = $end_index;
		$done = ($next_index >= $total_files);

		$this->saveProgress($next_index, $done);

		if ($done && file_exists($this->progress_file)) {
			@wp_delete_file($this->progress_file);
		}

		return [
			'success' => empty($this->errors),
			'done' => $done,
			'next_index' => $next_index,
			'stats' => [
				'total_files' => $total_files,
				'restored' => $this->restored_count,
				'skipped' => $this->skipped_count,
				'errors' => count($this->errors),
				'current_index' => $this->start_index,
				'end_index' => $end_index,
			],
			'errors' => $this->errors,
		];
	}

	/* =====================================================
	 * FILTERS
	 * ===================================================== */
	private function shouldExcludeFile($file_path) {
		if (empty($this->exclude)) return false;
		$parts = explode('/', $file_path);
		return in_array($parts[0], $this->exclude, true);
	}

	private function shouldIncludeFile($file_path) {
		if (empty($this->include_only)) return true;
		foreach ($this->include_only as $path) {
			if (strpos($file_path, $path) === 0) return true;
		}
		return false;
	}

	/* =====================================================
	 * STREAM UNZIP (NO MEMORY SPIKE)
	 * ===================================================== */
	private function extractFile($zip, $file_info) {

		global $wp_filesystem;

		$file_path = $file_info['name'];
		$is_dir = substr($file_path, -1) === '/';
		$destination_path = trailingslashit($this->destination_folder) . $file_path;

		if ($is_dir) {
			if (!$wp_filesystem->is_dir($destination_path)) {
				if (!$wp_filesystem->mkdir($destination_path, FS_CHMOD_DIR)) {
					return new WP_Error('mkdir_failed', 'Failed to create directory: ' . $destination_path);
				}
			}
			return true;
		}

		$destination_dir = dirname($destination_path);
		if (!$wp_filesystem->is_dir($destination_dir)) {
			if (!$wp_filesystem->mkdir($destination_dir, FS_CHMOD_DIR)) {
				return new WP_Error('mkdir_failed', 'Failed to create directory: ' . $destination_dir);
			}
		}

		if ($wp_filesystem->exists($destination_path) && !$this->overwrite_existing) {
			return true;
		}

		$stream = $zip->getStream($file_path);
		if (!$stream) {
			return new WP_Error('extract_failed', 'Cannot open zip stream: ' . $file_path);
		}

		$dest = fopen($destination_path, 'w');
		if (!$dest) {
			fclose($stream);
			return new WP_Error('file_open_failed', 'Cannot open destination file');
		}

		$buffer_size = 1024 * 1024; // 1MB unzip buffer

		while (!feof($stream)) {
			$buffer = fread($stream, $buffer_size);
			if ($buffer === false) {
				fclose($stream);
				fclose($dest);
				return new WP_Error('read_failed', 'Failed reading zip stream');
			}
			fwrite($dest, $buffer);
		}

		fclose($stream);
		fclose($dest);

		$this->preserveFilePermissions($destination_path, $file_info);

		return true;
	}

	private function preserveFilePermissions($file_path, $file_info) {
		global $wp_filesystem;
		if (isset($file_info['external_attr']) && $file_info['external_attr'] > 0) {
			$perm = ($file_info['external_attr'] >> 16) & 0x1FF;
			if ($perm > 0) {
				@$wp_filesystem->chmod($file_path, $perm);
			}
		}
	}

	/* =====================================================
	 * PROGRESS
	 * ===================================================== */
	private function loadProgress() {
		if (!file_exists($this->progress_file)) return;

		$data = json_decode(file_get_contents($this->progress_file), true);
		if (!is_array($data)) return;

		$this->start_index    = (int)($data['start_index'] ?? 0);
		$this->restored_count = (int)($data['stats']['restored'] ?? 0);
		$this->skipped_count  = (int)($data['stats']['skipped'] ?? 0);
	}

	private function saveProgress($next_index, $done) {

		$data = [
			'start_index' => $next_index,
			'done' => $done,
			'stats' => [
				'restored' => $this->restored_count,
				'skipped' => $this->skipped_count,
				'errors' => count($this->errors),
			],
			'last_updated' => current_time('mysql'),
		];

		file_put_contents($this->progress_file, wp_json_encode($data));
	}

	/* =====================================================
	 * UTILITIES
	 * ===================================================== */
	public function validateZip() {
		$zip = new ZipArchive();
		$res = $zip->open($this->zip_file);
		if ($res !== true) {
			return new WP_Error('zip_invalid', 'Invalid or corrupted zip');
		}
		$zip->close();
		return true;
	}

	public function getStats() {
		return [
			'restored' => $this->restored_count,
			'skipped' => $this->skipped_count,
			'errors' => $this->errors,
		];
	}
}
