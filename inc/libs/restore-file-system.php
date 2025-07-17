<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @author: Mike Tropi
 * @version: 1.0.3
 * @date: 2025-06711
 * @description: Restore File System Class
 * @support: https://github.com/miketropi/worry-proof-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 */

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
    private $restored_files = [];
    private $skipped_files = [];
    private $errors = [];
    private $batch_size = 3000;
    private $start_index = 0;
    private $progress_file;

    public function __construct($opts = []) {
        if (empty($opts['zip_file'])) {
            throw new Exception(esc_html__('Zip file path is required', 'worry-proof-backup'));
        }
        if (empty($opts['destination_folder'])) {
            throw new Exception(esc_html__('Destination folder is required', 'worry-proof-backup'));
        }

        $this->zip_file = $opts['zip_file'];
        $this->destination_folder = rtrim($opts['destination_folder'], '/\\');
        $this->overwrite_existing = !empty($opts['overwrite_existing']);
        $this->exclude = isset($opts['exclude']) ? (array) $opts['exclude'] : [];
        $this->include_only = isset($opts['include_only']) ? (array) $opts['include_only'] : [];
        $this->batch_size = isset($opts['batch_size']) ? (int) $opts['batch_size'] : 3000;

        $restore_progress_file_name = $opts['restore_progress_file_name'] ?? 'restore_progress.json';
        $this->progress_file = dirname($this->zip_file) . '/' . $restore_progress_file_name;

        if (!file_exists($this->zip_file)) {
            throw new Exception(esc_html__('Zip file does not exist: ', 'worry-proof-backup') . $this->zip_file); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        if (!is_dir($this->destination_folder)) {
            if (!wp_mkdir_p($this->destination_folder)) {
                throw new Exception(esc_html__('Failed to create destination directory: ', 'worry-proof-backup') . $this->destination_folder); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
        }
    }

    public function runRestore() {
        $this->loadProgress();

        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', esc_html__('PHP ZipArchive extension is not enabled.', 'worry-proof-backup'));
        }

        $zip = new ZipArchive();
        if ($zip->open($this->zip_file) !== true) {
            return new WP_Error('zip_open_failed', esc_html__('Failed to open zip file: ', 'worry-proof-backup') . $this->zip_file);
        }

        $total_files = $zip->numFiles;
        $end_index = min($this->start_index + $this->batch_size, $total_files);
        $restored_count = 0;
        $skipped_count = 0;

        for ($i = $this->start_index; $i < $end_index; $i++) {
            $file_info = $zip->statIndex($i);
            $file_path = $file_info['name'];

            if ($this->shouldExcludeFile($file_path) || (!$this->shouldIncludeFile($file_path))) {
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
            'restored_files' => $this->restored_files,
            'skipped_files' => $this->skipped_files,
            'errors' => $this->errors,
            'stats' => [
                'total_files' => $total_files,
                'restored_count' => $restored_count,
                'skipped_count' => $skipped_count,
                'error_count' => count($this->errors),
                'current_index' => $this->start_index,
                'end_index' => $end_index
            ]
        ];
    }

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

    private function extractFile($zip, $file_info) {
        global $wp_filesystem;

        $file_path = $file_info['name'];
        $is_dir = substr($file_path, -1) === '/';
        $destination_path = trailingslashit($this->destination_folder) . $file_path;

        if ($is_dir) {
            if (!$wp_filesystem->is_dir($destination_path)) {
                if (!$wp_filesystem->mkdir($destination_path, FS_CHMOD_DIR)) {
                    return new WP_Error('mkdir_failed', esc_html__('Failed to create directory: ', 'worry-proof-backup') . $destination_path);
                }
            }
        } else {
            $destination_dir = dirname($destination_path);
            if (!$wp_filesystem->is_dir($destination_dir)) {
                if (!$wp_filesystem->mkdir($destination_dir, FS_CHMOD_DIR)) {
                    return new WP_Error('mkdir_failed', esc_html__('Failed to create directory: ', 'worry-proof-backup') . $destination_dir);
                }
            }

            if ($wp_filesystem->exists($destination_path) && !$this->overwrite_existing) {
                return new WP_Error('file_exists', esc_html__('File exists and overwrite is disabled: ', 'worry-proof-backup') . $destination_path);
            }

            $stream = $zip->getStream($file_path);
            if (!$stream) {
                return new WP_Error('extract_failed', esc_html__('Failed to extract file: ', 'worry-proof-backup') . $file_path);
            }

            $contents = stream_get_contents($stream);
            fclose($stream); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose	

            $written = $wp_filesystem->put_contents($destination_path, $contents, FS_CHMOD_FILE);

            if ($written === false || $written === 0) {
                return new WP_Error('copy_failed', esc_html__('Failed to write file: ', 'worry-proof-backup') . $file_path);
            }

            // if ($written !== strlen($contents)) {
            //     return new WP_Error('copy_failed', __('Failed to copy complete file: ', 'worry-proof-backup') . $file_path);
            // }

            $this->preserveFilePermissions($destination_path, $file_info);
        }

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

    private function loadProgress() {
        if (file_exists($this->progress_file)) {
            $json = file_get_contents($this->progress_file);
            $data = json_decode($json, true);
            if (is_array($data)) {
                $this->start_index = isset($data['start_index']) ? intval($data['start_index']) : 0;
                $this->restored_files = $data['restored_files'] ?? [];
                $this->skipped_files = $data['skipped_files'] ?? [];
                $this->errors = $data['errors'] ?? [];
            }
        }
    }

    private function saveProgress($next_index, $done) {
        $data = [
            'start_index' => $next_index,
            'restored_files' => $this->restored_files,
            'skipped_files' => $this->skipped_files,
            'errors' => $this->errors,
            'done' => $done,
            'last_updated' => current_time('mysql'),
        ];
        file_put_contents($this->progress_file, wp_json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getZipContents() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', esc_html__('PHP ZipArchive extension is not enabled.', 'worry-proof-backup'));
        }

        $zip = new ZipArchive();
        if ($zip->open($this->zip_file) !== true) {
            return new WP_Error('zip_open_failed', esc_html__('Failed to open zip file: ', 'worry-proof-backup') . $this->zip_file);
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
            return new WP_Error('missing_ziparchive', esc_html__('PHP ZipArchive extension is not enabled.', 'worry-proof-backup'));
        }

        $zip = new ZipArchive();
        $result = $zip->open($this->zip_file);
        if ($result !== true) {
            return new WP_Error('zip_invalid', esc_html__('Invalid or corrupted zip file: ', 'worry-proof-backup') . $this->zip_file);
        }

        $zip->close();
        return true;
    }

    public function getStats() {
        return [
            'restored_files' => $this->restored_files,
            'skipped_files' => $this->skipped_files,
            'errors' => $this->errors,
        ];
    }
}
