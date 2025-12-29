<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @author: Mike Tropi
 * @version: 2.0.0
 * @date: 2025-01-XX
 * @description: Backup File System Class with Chunked Support
 * @support: https://github.com/miketropi/worry-proof-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 * Backup File System Class with Chunked Support
 *
 * Usage:
 * $backup = new WORRPB_File_System_V2([
 *    'source_folder' => '/path/to/source', // required
 *    'destination_folder' => 'backup_xxx', // required (relative to uploads/worry-proof-backup/)
 *    'zip_name' => 'filesystem.zip', // optional, zip filename base (default: filesystem.zip)
 *    'exclude' => ['node_modules', '.git', 'vendor'], // optional, folder/file names (1st-level)
 *    'chunk_size' => 100, // optional, number of files per batch (default: 100)
 *    'max_zip_size' => 2147483648, // optional, max zip file size in bytes (default: 2GB)
 *    'progress_file_name' => 'backup_progress.json', // optional, progress file name
 * ]);
 * 
 * // Start backup
 * $result = $backup->startBackup();
 * 
 * // Process backup in chunks
 * $result = $backup->processStep();
 * 
 * // Check if done
 * if ($result['done']) {
 *     $zip_files = $backup->getZipFiles();
 * }
 */

class WORRPB_File_System_V2 {
	private $exclude = [];
	private $source_folder;
	private $destination_folder;
	private $backup_dir;
	private $zip_name;
	private $zip_filename_base;
	private $chunk_size = 100;
	private $max_zip_size = 2147483648; // 2GB default
	private $progress_file;
	private $file_list = [];
	private $current_file_index = 0;
	private $current_chunk = 0;
	private $current_zip = null;
	private $current_zip_size = 0;
	private $backed_up_files = [];
	private $skipped_files = [];
	private $errors = [];
	private $zip_files = [];

	public function __construct($opts = []) {
		if (empty($opts['source_folder'])) {
			throw new Exception(esc_html__('Source folder is required', 'worry-proof-backup'));
		}
		if (empty($opts['destination_folder'])) {
			throw new Exception(esc_html__('Destination folder is required', 'worry-proof-backup'));
		}

		$this->zip_name = !empty($opts['zip_name']) ? $opts['zip_name'] : 'filesystem.zip';
		$this->chunk_size = isset($opts['chunk_size']) ? (int) $opts['chunk_size'] : 100;
		$this->max_zip_size = isset($opts['max_zip_size']) ? (int) $opts['max_zip_size'] : 2147483648;

		$this->exclude = isset($opts['exclude']) ? (array) $opts['exclude'] : [];
		$this->source_folder = rtrim($opts['source_folder'], '/\\');
		$this->destination_folder = $opts['destination_folder'];

		$upload_dir = wp_upload_dir();
		$this->backup_dir = $upload_dir['basedir'] . '/worry-proof-backup/' . $this->destination_folder;

		if (!is_dir($this->backup_dir)) {
			wp_mkdir_p($this->backup_dir);
		}

		// Remove .zip extension if present for base name
		$this->zip_filename_base = preg_replace('/\.zip$/', '', $this->zip_name);

		$progress_file_name = $opts['progress_file_name'] ?? 'backup_progress.json';
		$this->progress_file = $this->backup_dir . '/' . $progress_file_name;
	}

	/**
	 * Start a new backup - scan files and initialize
	 */
	public function startBackup() {
		if (!class_exists('ZipArchive')) {
			return new WP_Error('missing_ziparchive', esc_html__('PHP ZipArchive extension is not enabled.', 'worry-proof-backup'));
		}

		// Load existing progress if available
		$this->loadProgress();

		// If we already have a file list, we're resuming
		if (!empty($this->file_list)) {
			return [
				'success' => true,
				'resumed' => true,
				'total_files' => count($this->file_list),
				'current_index' => $this->current_file_index,
				'message' => esc_html__('Resuming backup from previous session', 'worry-proof-backup')
			];
		}

		// Scan all files
		$this->file_list = $this->scanFiles();

		if (empty($this->file_list)) {
			return new WP_Error('no_files', esc_html__('No files found to backup', 'worry-proof-backup'));
		}

		// Save initial progress
		$this->saveProgress();

		return [
			'success' => true,
			'total_files' => count($this->file_list),
			'current_index' => 0,
			'message' => esc_html__('Backup initialized', 'worry-proof-backup')
		];
	}

	/**
	 * Process one step of backup
	 */
	public function processStep() {
		if (!class_exists('ZipArchive')) {
			return new WP_Error('missing_ziparchive', esc_html__('PHP ZipArchive extension is not enabled.', 'worry-proof-backup'));
		}

		// Load progress
		$this->loadProgress();

		if (empty($this->file_list)) {
			return new WP_Error('no_file_list', esc_html__('File list not initialized. Call startBackup() first.', 'worry-proof-backup'));
		}

		$total_files = count($this->file_list);
		$end_index = min($this->current_file_index + $this->chunk_size, $total_files);
		$processed_count = 0;
		$skipped_count = 0;

		// Open or create current zip file
		if ($this->current_zip === null) {
			$zip_result = $this->openCurrentZip();
			if (is_wp_error($zip_result)) {
				return $zip_result;
			}
		}
		
		// Update current zip size from actual file size
		$current_zip_file = $this->getCurrentZipFilename();
		if (file_exists($current_zip_file)) {
			$this->current_zip_size = filesize($current_zip_file);
		}

		// Process files in this batch
		for ($i = $this->current_file_index; $i < $end_index; $i++) {
			if (!isset($this->file_list[$i])) {
				continue;
			}

			$file_data = $this->file_list[$i];
			$file_path = $file_data['path'];
			$relative_path = $file_data['relative'];

			// Add file to zip (handles zip size limits internally)
			$result = $this->addFileToZip($file_path, $relative_path, $file_data['is_dir']);
			
			if (is_wp_error($result)) {
				$this->errors[] = $result->get_error_message();
				$this->skipped_files[] = $relative_path;
				$skipped_count++;
			} else {
				// Only add to backed_up_files if not already there (for resume scenarios)
				if (!in_array($relative_path, $this->backed_up_files, true)) {
					$this->backed_up_files[] = $relative_path;
				}
				$processed_count++;
			}
		}

		$this->current_file_index = $end_index;
		$done = ($this->current_file_index >= $total_files);

		// Close zip if done
		if ($done && $this->current_zip !== null) {
			$this->closeCurrentZip();
		}

		// Save progress
		$this->saveProgress();

		// Clean up progress file if done
		if ($done && file_exists($this->progress_file)) {
			@wp_delete_file($this->progress_file);
		}

		return [
			'success' => empty($this->errors),
			'done' => $done,
			'current_index' => $this->current_file_index,
			'total_files' => $total_files,
			'backed_up_files' => $this->backed_up_files,
			'skipped_files' => $this->skipped_files,
			'errors' => $this->errors,
			'zip_files' => $this->zip_files,
			'stats' => [
				'total_files' => $total_files,
				'processed_count' => $processed_count,
				'skipped_count' => $skipped_count,
				'error_count' => count($this->errors),
				'current_index' => $this->current_file_index,
				'end_index' => $end_index,
				'current_chunk' => $this->current_chunk,
				'zip_files_count' => count($this->zip_files)
			]
		];
	}

	/**
	 * Scan all files in source folder
	 */
	private function scanFiles() {
		$files = [];

		if (!is_dir($this->source_folder)) {
			return $files;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->source_folder, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $file) {
			$file_path = $file->getPathname();

			// Normalize relative path (Windows / Linux safe)
			$relative_path = ltrim(str_replace('\\', '/', str_replace($this->source_folder, '', $file_path)), '/');

			// Split parts to check top-level folder/file
			$relative_parts = explode('/', $relative_path);

			// Exclude if 1st-level folder/file is in $this->exclude
			if (count($relative_parts) >= 1 && in_array($relative_parts[0], $this->exclude, true)) {
				continue;
			}

			$is_dir = $file->isDir();
			$size = $is_dir ? 0 : $file->getSize();

			$files[] = [
				'path' => $file_path,
				'relative' => $relative_path,
				'is_dir' => $is_dir,
				'size' => $size
			];
		}

		return $files;
	}

	/**
	 * Open current zip file
	 */
	private function openCurrentZip() {
		$zip_filename = $this->getCurrentZipFilename();

		$zip = new ZipArchive();
		
		// If file exists, open in append mode (for resume), otherwise create new
		$flags = file_exists($zip_filename) 
			? ZipArchive::CREATE 
			: ZipArchive::CREATE | ZipArchive::OVERWRITE;
		
		$result = $zip->open($zip_filename, $flags);

		if ($result !== true) {
			return new WP_Error('zip_failed', esc_html__('Failed to create zip file: ', 'worry-proof-backup') . $zip_filename);
		}

		$this->current_zip = $zip;
		
		// Get actual zip file size if it exists
		$this->current_zip_size = file_exists($zip_filename) ? filesize($zip_filename) : 0;

		// If this is a new chunk, add to zip_files list
		if (!in_array($zip_filename, $this->zip_files, true)) {
			$this->zip_files[] = $zip_filename;
		}

		return true;
	}

	/**
	 * Close current zip file
	 */
	private function closeCurrentZip() {
		if ($this->current_zip !== null) {
			$this->current_zip->close();
			$this->current_zip = null;
			$this->current_zip_size = 0;
			$this->current_chunk++;
		}
	}

	/**
	 * Get current zip filename
	 */
	private function getCurrentZipFilename() {
		if ($this->current_chunk === 0) {
			return $this->backup_dir . '/' . $this->zip_name;
		}
		return $this->backup_dir . '/' . $this->zip_filename_base . '_part' . ($this->current_chunk + 1) . '.zip';
	}

	/**
	 * Add file to current zip
	 */
	private function addFileToZip($file_path, $relative_path, $is_dir) {
		if ($this->current_zip === null) {
			return new WP_Error('no_zip', esc_html__('No zip file open', 'worry-proof-backup'));
		}

		// Check if file already exists in zip (for resume scenarios)
		if ($this->current_zip->locateName($relative_path) !== false) {
			// File already in zip, skip it
			return true;
		}

		if ($is_dir) {
			$this->current_zip->addEmptyDir($relative_path);
		} else {
			if (!file_exists($file_path)) {
				return new WP_Error('file_not_found', esc_html__('File not found: ', 'worry-proof-backup') . $file_path);
			}

			$file_size = filesize($file_path);
			
			// If single file is larger than max size, we still add it (but warn)
			// Otherwise check if adding would exceed limit
			if ($file_size < $this->max_zip_size && $this->current_zip_size + $file_size > $this->max_zip_size && $this->current_zip_size > 0) {
				// Close current zip and open new one
				$this->closeCurrentZip();
				$zip_result = $this->openCurrentZip();
				if (is_wp_error($zip_result)) {
					return $zip_result;
				}
			}

			if (!$this->current_zip->addFile($file_path, $relative_path)) {
				return new WP_Error('add_file_failed', esc_html__('Failed to add file to zip: ', 'worry-proof-backup') . $relative_path);
			}
			
			// Update zip size estimate (compressed size will be different, but we track uncompressed)
			$this->current_zip_size += $file_size;
		}

		return true;
	}

	/**
	 * Load progress from file
	 */
	private function loadProgress() {
		if (file_exists($this->progress_file)) {
			$json = file_get_contents($this->progress_file);
			$data = json_decode($json, true);
			if (is_array($data)) {
				$this->file_list = $data['file_list'] ?? [];
				$this->current_file_index = isset($data['current_file_index']) ? (int) $data['current_file_index'] : 0;
				$this->current_chunk = isset($data['current_chunk']) ? (int) $data['current_chunk'] : 0;
				$this->backed_up_files = $data['backed_up_files'] ?? [];
				$this->skipped_files = $data['skipped_files'] ?? [];
				$this->errors = $data['errors'] ?? [];
				$this->zip_files = $data['zip_files'] ?? [];
			}
		}
	}

	/**
	 * Save progress to file
	 */
	private function saveProgress() {
		$data = [
			'file_list' => $this->file_list,
			'current_file_index' => $this->current_file_index,
			'current_chunk' => $this->current_chunk,
			'backed_up_files' => $this->backed_up_files,
			'skipped_files' => $this->skipped_files,
			'errors' => $this->errors,
			'zip_files' => $this->zip_files,
			'last_updated' => current_time('mysql'),
		];
		file_put_contents($this->progress_file, wp_json_encode($data, JSON_PRETTY_PRINT));
	}

	/**
	 * Get list of created zip files
	 */
	public function getZipFiles() {
		return $this->zip_files;
	}

	/**
	 * Get backup statistics
	 */
	public function getStats() {
		return [
			'total_files' => count($this->file_list),
			'backed_up_files' => $this->backed_up_files,
			'skipped_files' => $this->skipped_files,
			'errors' => $this->errors,
			'zip_files' => $this->zip_files,
			'current_index' => $this->current_file_index,
			'current_chunk' => $this->current_chunk,
		];
	}

	/**
	 * Clean up progress file
	 */
	public function cleanup() {
		if (file_exists($this->progress_file)) {
			@wp_delete_file($this->progress_file);
		}
		if ($this->current_zip !== null) {
			$this->closeCurrentZip();
		}
	}
}

