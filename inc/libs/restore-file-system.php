<?php
/**
 * @author: Mike Tropi
 * @version: 1.0.0
 * @date: 2025-06-19
 * @description: Restore File System Class
 * @support: https://github.com/miketropi/wp-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 * Restore File System Class
 *
 * Usage:
 * $restore = new WP_Restore_File_System([
 *    'zip_file' => '/path/to/backup.zip', // required
 *    'destination_folder' => '/path/to/restore', // required
 *    'overwrite_existing' => true, // optional, overwrite existing files (default: false)
 *    'exclude' => ['node_modules', '.git', 'vendor'], // optional, folder/file names to skip
 *    'include_only' => ['wp-content/themes', 'wp-content/plugins'], // optional, only restore these paths
 * ]);
 * $result = $restore->runRestore();
 */

class WP_Restore_File_System {
    private $zip_file;
    private $destination_folder;
    private $overwrite_existing;
    private $exclude = [];
    private $include_only = [];
    private $restored_files = [];
    private $skipped_files = [];
    private $errors = [];

    public function __construct($opts = []) {
        if (empty($opts['zip_file'])) {
            throw new Exception('Zip file path is required');
        }
        if (empty($opts['destination_folder'])) {
            throw new Exception('Destination folder is required');
        }

        $this->zip_file = $opts['zip_file'];
        $this->destination_folder = rtrim($opts['destination_folder'], '/\\');
        $this->overwrite_existing = isset($opts['overwrite_existing']) ? (bool) $opts['overwrite_existing'] : false;
        $this->exclude = isset($opts['exclude']) ? (array) $opts['exclude'] : [];
        $this->include_only = isset($opts['include_only']) ? (array) $opts['include_only'] : [];

        // Validate zip file exists
        if (!file_exists($this->zip_file)) {
            throw new Exception('Zip file does not exist: ' . $this->zip_file);
        }

        // Create destination directory if it doesn't exist
        if (!is_dir($this->destination_folder)) {
            if (!wp_mkdir_p($this->destination_folder)) {
                throw new Exception('Failed to create destination directory: ' . $this->destination_folder);
            }
        }
    }

    /**
     * Run restore and return result
     */
    public function runRestore() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', 'PHP ZipArchive extension is not enabled.');
        }

        $zip = new ZipArchive();
        if ($zip->open($this->zip_file) !== true) {
            return new WP_Error('zip_open_failed', 'Failed to open zip file: ' . $this->zip_file);
        }

        $total_files = $zip->numFiles;
        $restored_count = 0;
        $skipped_count = 0;

        for ($i = 0; $i < $total_files; $i++) {
            $file_info = $zip->statIndex($i);
            $file_path = $file_info['name'];

            // Skip if file should be excluded
            if ($this->shouldExcludeFile($file_path)) {
                $this->skipped_files[] = $file_path;
                $skipped_count++;
                continue;
            }

            // Skip if file is not in include_only list
            if (!empty($this->include_only) && !$this->shouldIncludeFile($file_path)) {
                $this->skipped_files[] = $file_path;
                $skipped_count++;
                continue;
            }

            $result = $this->extractFile($zip, $file_info);
            if (is_wp_error($result)) {
                $this->errors[] = $result->get_error_message();
            } else {
                $this->restored_files[] = $file_path;
                $restored_count++;
            }
        }

        $zip->close();

        return [
            'success' => empty($this->errors),
            'restored_files' => $this->restored_files,
            'skipped_files' => $this->skipped_files,
            'errors' => $this->errors,
            'stats' => [
                'total_files' => $total_files,
                'restored_count' => $restored_count,
                'skipped_count' => $skipped_count,
                'error_count' => count($this->errors)
            ]
        ];
    }

    /**
     * Check if file should be excluded
     */
    private function shouldExcludeFile($file_path) {
        if (empty($this->exclude)) {
            return false;
        }

        $path_parts = explode('/', $file_path);
        $first_level = $path_parts[0];

        return in_array($first_level, $this->exclude, true);
    }

    /**
     * Check if file should be included
     */
    private function shouldIncludeFile($file_path) {
        if (empty($this->include_only)) {
            return true;
        }

        foreach ($this->include_only as $include_path) {
            if (strpos($file_path, $include_path) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract a single file from zip
     */
    private function extractFile($zip, $file_info) {
        $file_path = $file_info['name'];
        $file_size = $file_info['size'];
        $is_dir = substr($file_path, -1) === '/';

        // Create full destination path
        $destination_path = $this->destination_folder . '/' . $file_path;

        if ($is_dir) {
            // Handle directory
            if (!is_dir($destination_path)) {
                if (!wp_mkdir_p($destination_path)) {
                    return new WP_Error('mkdir_failed', 'Failed to create directory: ' . $destination_path);
                }
            }
        } else {
            // Handle file
            $destination_dir = dirname($destination_path);
            
            // Create directory if it doesn't exist
            if (!is_dir($destination_dir)) {
                if (!wp_mkdir_p($destination_dir)) {
                    return new WP_Error('mkdir_failed', 'Failed to create directory: ' . $destination_dir);
                }
            }

            // Check if file exists and handle overwrite
            if (file_exists($destination_path) && !$this->overwrite_existing) {
                return new WP_Error('file_exists', 'File already exists and overwrite is disabled: ' . $destination_path);
            }

            // Extract file
            $stream = $zip->getStream($file_path);
            if (!$stream) {
                return new WP_Error('extract_failed', 'Failed to extract file: ' . $file_path);
            }

            $file_handle = fopen($destination_path, 'wb');
            if (!$file_handle) {
                fclose($stream);
                return new WP_Error('file_write_failed', 'Failed to write file: ' . $destination_path);
            }

            // Copy file content
            $copied = stream_copy_to_stream($stream, $file_handle);
            fclose($stream);
            fclose($file_handle);

            if ($copied !== $file_size) {
                return new WP_Error('copy_failed', 'Failed to copy complete file: ' . $file_path);
            }

            // Preserve file permissions if possible
            $this->preserveFilePermissions($destination_path, $file_info);
        }

        return true;
    }

    /**
     * Preserve file permissions from zip
     */
    private function preserveFilePermissions($file_path, $file_info) {
        if (isset($file_info['external_attr']) && $file_info['external_attr'] > 0) {
            $permissions = ($file_info['external_attr'] >> 16) & 0x1FF;
            if ($permissions > 0) {
                @chmod($file_path, $permissions);
            }
        }
    }

    /**
     * Get list of files in zip without extracting
     */
    public function getZipContents() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', 'PHP ZipArchive extension is not enabled.');
        }

        $zip = new ZipArchive();
        if ($zip->open($this->zip_file) !== true) {
            return new WP_Error('zip_open_failed', 'Failed to open zip file: ' . $this->zip_file);
        }

        $files = [];
        $total_files = $zip->numFiles;

        for ($i = 0; $i < $total_files; $i++) {
            $file_info = $zip->statIndex($i);
            $files[] = [
                'name' => $file_info['name'],
                'size' => $file_info['size'],
                'compressed_size' => $file_info['comp_size'],
                'is_dir' => substr($file_info['name'], -1) === '/',
                'modified_time' => $file_info['mtime']
            ];
        }

        $zip->close();
        return $files;
    }

    /**
     * Validate zip file integrity
     */
    public function validateZip() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', 'PHP ZipArchive extension is not enabled.');
        }

        $zip = new ZipArchive();
        $result = $zip->open($this->zip_file);

        if ($result !== true) {
            return new WP_Error('zip_invalid', 'Invalid or corrupted zip file: ' . $this->zip_file);
        }

        $zip->close();
        return true;
    }

    /**
     * Get restore statistics
     */
    public function getStats() {
        return [
            'restored_files' => $this->restored_files,
            'skipped_files' => $this->skipped_files,
            'errors' => $this->errors
        ];
    }
}
