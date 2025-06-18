<?php
/**
 * @author: Mike Tropi
 * @version: 1.0.0
 * @date: 2025-06-18
 * @description: Backup WordPress Plugins Class
 * @support: https://github.com/miketropi/wp-backup
 * @license: GPL-2.0+
 * @copyright: (c) 2025 Mike Tropi
 * 
 * Class Backup_Plugins
 * 
 * Usage:
 * $backup = new Backup_Plugins([
 *    'backup_folder_name' => 'backup_18a60fd6-5972-4f08-b267-1a6bed9dfc82_2025-06-18_16-57-15', // required
 *    'exclude' => ['hello-dolly', 'akismet'], // folder name (not path)
 * ]);
 * $zip_file = $backup->runBackup();
 */

class Backup_Plugins {
    private $exclude_plugins = [];
    private $backup_folder_name = ''; // required
    private $source_dir;
    private $backup_dir;
    private $zip_filename;

    public function __construct($opts = []) {
        // check if backup folder name is set
        if (empty($opts['backup_folder_name'])) {
            throw new Exception('Backup folder name is required');
        }

        $this->exclude_plugins = isset($opts['exclude']) ? (array) $opts['exclude'] : [];
        $this->backup_folder_name = isset($opts['backup_folder_name']) ? $opts['backup_folder_name'] : '';

        $upload_dir = wp_upload_dir();
        $this->source_dir = WP_PLUGIN_DIR;
        $this->backup_dir = $upload_dir['basedir'] . '/wp-backup/' . $this->backup_folder_name;

        // Ensure backup dir exists
        if (!is_dir($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
        }

        // Generate filename "plugins.zip"
        $this->zip_filename = $this->backup_dir . '/plugins.zip';
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
            new RecursiveDirectoryIterator($this->source_dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $file_path = $file->getPathname();

            // Detect plugin folder name
            $relative_path = str_replace($this->source_dir . '/', '', $file_path);
            $plugin_folder = explode('/', $relative_path)[0];

            // Exclude plugin
            if (in_array($plugin_folder, $this->exclude_plugins, true)) {
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
