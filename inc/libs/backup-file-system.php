<?php
/**
 * @author: Mike Tropi
 * @version: 1.0.0
 * @date: 2025-06-19
 * @description: Backup File System Class
 * @support: https://github.com/miketropi/wp-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 * Backup File System Class
 *
 * Usage:
 * $backup = new WP_Backup_File_System([
 *    'source_folder' => '/path/to/source', // required
 *    'destination_folder' => 'backup_xxx', // required (relative to uploads/wp-backup/)
 *    'zip_name' => 'filesystem.zip', // optional, zip filename (default: filesystem.zip)
 *    'exclude' => ['node_modules', '.git', 'vendor'], // optional, folder/file names (1st-level)
 * ]);
 * $zip_file = $backup->runBackup();
 */

class WP_Backup_File_System {
    private $exclude = [];
    private $source_folder;
    private $destination_folder;
    private $backup_dir;
    private $zip_filename;
    private $zip_name;

    public function __construct($opts = []) {
        if (empty($opts['source_folder'])) {
            throw new Exception('Source folder is required');
        }
        if (empty($opts['destination_folder'])) {
            throw new Exception('Destination folder is required');
        }

        $this->zip_name = !empty($opts['zip_name']) ? $opts['zip_name'] : 'filesystem.zip';

        $this->exclude = isset($opts['exclude']) ? (array) $opts['exclude'] : [];
        $this->source_folder = rtrim($opts['source_folder'], '/\\');
        $this->destination_folder = $opts['destination_folder'];

        $upload_dir = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'] . '/wp-backup/' . $this->destination_folder;

        if (!is_dir($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
        }

        $this->zip_filename = $this->backup_dir . '/' . $this->zip_name;
    }

    /**
     * Run backup and return zip file path
     */
    public function runBackup() {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('missing_ziparchive', 'PHP ZipArchive extension is not enabled.');
        }

        $zip = new ZipArchive();
        if ($zip->open($this->zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return new WP_Error('zip_failed', 'Failed to create zip file.');
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

            if ($file->isDir()) {
                $zip->addEmptyDir($relative_path);
            } else {
                $zip->addFile($file_path, $relative_path);
            }
        }

        $zip->close();
        return $this->zip_filename;
    }
}
