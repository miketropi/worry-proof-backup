<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * WORRPB_Upload_Backup_File Class
 *
 * Upload and extract a backup zip file to wp-content/uploads/wp-backup
 *
 * @author: @Mike
 * @version: 1.0.0
 * @date: 2025-06-25
 * @license: GPL-2.0+
 */

class WORRPB_Upload_Backup_File {
    private $file;
    private $session_id;
    private $upload_dir;
    private $backup_dir;
    private $zip_path;
    private $overwrite;
    private $errors = [];
    private $extracted_files = [];
    private $wp_filesystem;

    public function __construct($opts = []) {
        if (empty($opts['file'])) {
            throw new Exception('No file provided for upload.');
        }
        $this->file = $opts['file'];
        $this->session_id = !empty($opts['session_id']) ? sanitize_file_name($opts['session_id']) : uniqid('upload_', true);
        $this->overwrite = !empty($opts['overwrite']);

        $upload_dir = wp_upload_dir();
        if (empty($upload_dir['basedir'])) {
            throw new Exception('Could not get WordPress upload directory.');
        }
        $this->upload_dir = $upload_dir['basedir'];
        $this->backup_dir = $this->upload_dir . '/wp-backup/' . $this->session_id;

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;
        $this->wp_filesystem = $wp_filesystem;
    }

    public function handle() {
        if (!file_exists($this->backup_dir)) {
            if (!wp_mkdir_p($this->backup_dir)) {
                return $this->error_result('Failed to create backup directory: ' . $this->backup_dir);
            }
        }

        $overrides = [
            'test_form' => false,
            'mimes' => ['zip' => 'application/zip'],
        ];
        $upload = wp_handle_upload($this->file, $overrides);
        if (isset($upload['error'])) {
            return $this->error_result('Upload error: ' . $upload['error']);
        }
        $this->zip_path = $upload['file'];

        $dest_path = $this->backup_dir . '/' . basename($this->zip_path);
        if (!$this->wp_filesystem->move($this->zip_path, $dest_path, true)) {
            return $this->error_result('Failed to move uploaded file to backup directory.');
        }
        $this->zip_path = $dest_path;

        $extract_result = $this->extract_zip();
        if (is_wp_error($extract_result)) {
            wp_delete_file($this->zip_path);
            return $this->error_result($extract_result->get_error_message());
        }

        wp_delete_file($this->zip_path);

        return [
            'success' => true,
            'session_id' => $this->session_id,
            'upload_path' => $this->zip_path,
            'extracted_files' => $this->extracted_files,
            'errors' => $this->errors,
        ];
    }

    private function extract_zip() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', 'PHP ZipArchive extension is not enabled.');
        }
        $zip = new ZipArchive();
        if ($zip->open($this->zip_path) !== true) {
            return new WP_Error('zip_open_failed', 'Failed to open zip file: ' . $this->zip_path);
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_info = $zip->statIndex($i);
            $file_path = $file_info['name'];
            $dest_path = $this->backup_dir . '/' . $file_path;

            if (substr($file_path, -1) === '/') {
                if (!is_dir($dest_path) && !wp_mkdir_p($dest_path)) {
                    $this->errors[] = 'Failed to create directory: ' . $dest_path;
                    continue;
                }
            } else {
                $dest_dir = dirname($dest_path);
                if (!is_dir($dest_dir) && !wp_mkdir_p($dest_dir)) {
                    $this->errors[] = 'Failed to create directory: ' . $dest_dir;
                    continue;
                }
                if (file_exists($dest_path) && !$this->overwrite) {
                    $this->errors[] = 'File exists and overwrite is disabled: ' . $dest_path;
                    continue;
                }

                $stream = $zip->getStream($file_path);
                if (!$stream) {
                    $this->errors[] = 'Failed to extract file: ' . $file_path;
                    continue;
                }
                // Use WP_Filesystem to write file content
                ob_start();
                stream_copy_to_stream($stream, fopen('php://output', 'wb'));
                $contents = ob_get_clean();

                // fclose() is necessary for proper stream cleanup; no WP_Filesystem alternative exists for open file handles.
                fclose($stream); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

                if (!$this->wp_filesystem->put_contents($dest_path, $contents, FS_CHMOD_FILE)) {
                    $this->errors[] = 'Failed to write file: ' . $dest_path;
                    continue;
                }
                $this->extracted_files[] = $file_path;
            }
        }
        $zip->close();
        return true;
    }

    private function error_result($msg) {
        $this->errors[] = $msg;
        return [
            'success' => false,
            'session_id' => $this->session_id,
            'upload_path' => $this->zip_path,
            'extracted_files' => $this->extracted_files,
            'errors' => $this->errors,
        ];
    }
}
