<?php
/**
 * @author: Mike Tropi
 * @version: 1.0.0
 * @date: 2025-06-19
 * @description: Restore File System Class
 * @support: https://github.com/miketropi/wp-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 */

if ( ! function_exists( 'WP_Filesystem' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
WP_Filesystem();
global $wp_filesystem;

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
            throw new Exception(esc_html__('Zip file path is required', 'wp-backup'));
        }
        if (empty($opts['destination_folder'])) {
            throw new Exception(esc_html__('Destination folder is required', 'wp-backup'));
        }

        $this->zip_file = $opts['zip_file'];
        $this->destination_folder = rtrim($opts['destination_folder'], '/\\');
        $this->overwrite_existing = isset($opts['overwrite_existing']) ? (bool) $opts['overwrite_existing'] : false;
        $this->exclude = isset($opts['exclude']) ? (array) $opts['exclude'] : [];
        $this->include_only = isset($opts['include_only']) ? (array) $opts['include_only'] : [];

        if (!file_exists($this->zip_file)) {
            return new WP_Error('zip_file_not_found', esc_html__('Zip file does not exist: ', 'wp-backup') . $this->zip_file);
        }

        if (!is_dir($this->destination_folder)) {
            if (!wp_mkdir_p($this->destination_folder)) {
                return new Exception(esc_html__('Failed to create destination directory: ', 'wp-backup') . $this->destination_folder);
            }
        }
    }

    public function runRestore() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', esc_html__('PHP ZipArchive extension is not enabled.', 'wp-backup'));
        }

        $zip = new ZipArchive();
        if ($zip->open($this->zip_file) !== true) {
            return new WP_Error('zip_open_failed', esc_html__('Failed to open zip file: ', 'wp-backup') . $this->zip_file);
        }

        $total_files = $zip->numFiles;
        $restored_count = 0;
        $skipped_count = 0;

        for ($i = 0; $i < $total_files; $i++) {
            $file_info = $zip->statIndex($i);
            $file_path = $file_info['name'];

            if ($this->shouldExcludeFile($file_path)) {
                $this->skipped_files[] = $file_path;
                $skipped_count++;
                continue;
            }

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

    private function shouldExcludeFile($file_path) {
        if (empty($this->exclude)) return false;
        $path_parts = explode('/', $file_path);
        return in_array($path_parts[0], $this->exclude, true);
    }

    private function shouldIncludeFile($file_path) {
        if (empty($this->include_only)) return true;
        foreach ($this->include_only as $include_path) {
            if (strpos($file_path, $include_path) === 0) return true;
        }
        return false;
    }

    private function extractFile($zip, $file_info) {
        global $wp_filesystem;

        $file_path = $file_info['name'];
        $file_size = $file_info['size'];
        $is_dir = substr($file_path, -1) === '/';
        $destination_path = trailingslashit($this->destination_folder) . $file_path;

        if ($is_dir) {
            if (!$wp_filesystem->is_dir($destination_path)) {
                if (!$wp_filesystem->mkdir($destination_path, FS_CHMOD_DIR)) {
                    return new WP_Error('mkdir_failed', esc_html__('Failed to create directory: ', 'wp-backup') . $destination_path);
                }
            }
        } else {
            $destination_dir = dirname($destination_path);
            if (!$wp_filesystem->is_dir($destination_dir)) {
                if (!$wp_filesystem->mkdir($destination_dir, FS_CHMOD_DIR)) {
                    return new WP_Error('mkdir_failed', esc_html__('Failed to create directory: ', 'wp-backup') . $destination_dir);
                }
            }

            if ($wp_filesystem->exists($destination_path) && !$this->overwrite_existing) {
                return new WP_Error('file_exists', esc_html__('File already exists and overwrite is disabled: ', 'wp-backup') . $destination_path);
            }

            $stream = $zip->getStream($file_path);
            if (!$stream) {
                return new WP_Error('extract_failed', esc_html__('Failed to extract file: ', 'wp-backup') . $file_path);
            }

            $file_contents = stream_get_contents($stream);

            // No WP_Filesystem alternative for stream from ZipArchive, safe to use fclose here
            fclose($stream); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose	

            $bytes_written = $wp_filesystem->put_contents(
                $destination_path,
                $file_contents,
                FS_CHMOD_FILE
            );

            if ($bytes_written !== strlen($file_contents)) {
                return new WP_Error('copy_failed', esc_html__('Failed to copy complete file: ', 'wp-backup') . $file_path);
            }

            $this->preserveFilePermissions($destination_path, $file_info);
        }

        return true;
    }

    private function preserveFilePermissions($file_path, $file_info) {
        global $wp_filesystem;
        if (isset($file_info['external_attr']) && $file_info['external_attr'] > 0) {
            $permissions = ($file_info['external_attr'] >> 16) & 0x1FF;
            if ($permissions > 0) {
                @$wp_filesystem->chmod($file_path, $permissions);
            }
        }
    }

    public function getZipContents() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', esc_html__('PHP ZipArchive extension is not enabled.', 'wp-backup'));
        }

        $zip = new ZipArchive();
        if ($zip->open($this->zip_file) !== true) {
            return new WP_Error('zip_open_failed', esc_html__('Failed to open zip file: ', 'wp-backup') . $this->zip_file);
        }

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $info = $zip->statIndex($i);
            $files[] = [
                'name' => $info['name'],
                'size' => $info['size'],
                'compressed_size' => $info['comp_size'],
                'is_dir' => substr($info['name'], -1) === '/',
                'modified_time' => $info['mtime']
            ];
        }

        $zip->close();
        return $files;
    }

    public function validateZip() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', esc_html__('PHP ZipArchive extension is not enabled.', 'wp-backup'));
        }

        $zip = new ZipArchive();
        $result = $zip->open($this->zip_file);
        if ($result !== true) {
            return new WP_Error('zip_invalid', esc_html__('Invalid or corrupted zip file: ', 'wp-backup') . $this->zip_file);
        }

        $zip->close();
        return true;
    }

    public function getStats() {
        return [
            'restored_files' => $this->restored_files,
            'skipped_files' => $this->skipped_files,
            'errors' => $this->errors
        ];
    }
}
