<?php
/**
 * Unit Tests for WORRPB_Dummy_Pack_Downloader
 * 
 * Run with: wp eval-file test-download-dummy-pack.php
 * 
 * @package Worry_Proof_Backup
 */

// Load WordPress
if (!defined('ABSPATH')) {
    die('This file must be run within WordPress context. Use: wp eval-file test-download-dummy-pack.php');
}

// Load the class
require_once WORRPRBA_PLUGIN_PATH . 'inc/libs/dummy-pack/download-dummy-pack.php';

/**
 * Test Helper Class
 */
class WORRPB_Downloader_Test {
    private $tests_passed = 0;
    private $tests_failed = 0;
    private $test_package_id;

    public function __construct() {
        $this->test_package_id = 'test-' . uniqid();
    }

    /**
     * Run all tests
     */
    public function runTests() {
        echo "\n";
        echo "========================================\n";
        echo "WORRPB_Dummy_Pack_Downloader Test Suite\n";
        echo "========================================\n\n";

        $this->test_constructor_validation();
        $this->test_directory_creation();
        $this->test_progress_tracking();
        $this->test_cleanup();

        echo "\n";
        echo "========================================\n";
        echo sprintf("Tests: %d passed, %d failed\n", $this->tests_passed, $this->tests_failed);
        echo "========================================\n\n";

        return $this->tests_failed === 0;
    }

    /**
     * Test constructor validation
     */
    private function test_constructor_validation() {
        echo "Test: Constructor Validation\n";

        // Test missing package_id
        try {
            $downloader = new WORRPB_Dummy_Pack_Downloader([
                'remote_url' => 'https://example.com/test.zip',
            ]);
            $this->fail('Should throw exception for missing package_id');
        } catch (Exception $e) {
            $this->pass('Correctly validates missing package_id');
        }

        // Test invalid URL
        try {
            $downloader = new WORRPB_Dummy_Pack_Downloader([
                'package_id' => 'test-123',
                'remote_url' => 'invalid-url',
            ]);
            $this->fail('Should throw exception for invalid URL');
        } catch (Exception $e) {
            $this->pass('Correctly validates invalid URL');
        }

        // Test valid construction
        try {
            $downloader = new WORRPB_Dummy_Pack_Downloader([
                'package_id' => 'test-123',
                'remote_url' => 'https://example.com/test.zip',
            ]);
            $this->pass('Accepts valid parameters');
        } catch (Exception $e) {
            $this->fail('Should accept valid parameters: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Test directory creation
     */
    private function test_directory_creation() {
        echo "Test: Directory Creation\n";

        $upload_dir = wp_upload_dir();
        $test_dir = $upload_dir['basedir'] . "/worry-proof-backup-zip/chunks/{$this->test_package_id}/";

        // Clean up if exists
        if (file_exists($test_dir)) {
            $this->removeDirectory($test_dir);
        }

        try {
            $downloader = new WORRPB_Dummy_Pack_Downloader([
                'package_id' => $this->test_package_id,
                'remote_url' => 'https://httpbin.org/bytes/100', // Small test file
            ]);

            // Start download (should create directory)
            $result = $downloader->startDownload();

            if (is_wp_error($result)) {
                // This might fail if httpbin is not accessible, which is okay for this test
                echo "  ⚠️  Warning: Could not start download (network may be unavailable)\n";
                echo "     Error: " . $result->get_error_message() . "\n";
            } else {
                if (file_exists($test_dir)) {
                    $this->pass('Successfully creates download directory');
                } else {
                    $this->fail('Failed to create download directory');
                }
            }

            // Cleanup
            $downloader->cleanup(false);

        } catch (Exception $e) {
            $this->fail('Exception during directory creation: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Test progress tracking
     */
    private function test_progress_tracking() {
        echo "Test: Progress Tracking\n";

        try {
            $downloader = new WORRPB_Dummy_Pack_Downloader([
                'package_id' => $this->test_package_id . '-progress',
                'remote_url' => 'https://httpbin.org/bytes/100',
            ]);

            // Initial progress should be pending
            $progress = $downloader->getProgress();
            
            if ($progress['status'] === 'pending') {
                $this->pass('Initial status is pending');
            } else {
                $this->fail('Initial status should be pending, got: ' . $progress['status']);
            }

            // Start download
            $result = $downloader->startDownload();
            
            if (is_wp_error($result)) {
                echo "  ⚠️  Warning: Could not test progress (network may be unavailable)\n";
            } else {
                // Check progress after start
                $progress = $downloader->getProgress();
                
                if ($progress['status'] === 'downloading') {
                    $this->pass('Status changes to downloading after start');
                } else {
                    $this->fail('Status should be downloading, got: ' . $progress['status']);
                }

                if ($progress['total_size'] > 0) {
                    $this->pass('Total size is set correctly');
                } else {
                    $this->fail('Total size should be > 0');
                }
            }

            // Cleanup
            $downloader->cleanup(false);

        } catch (Exception $e) {
            echo "  ⚠️  Exception during progress tracking: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test cleanup functionality
     */
    private function test_cleanup() {
        echo "Test: Cleanup\n";

        $upload_dir = wp_upload_dir();
        $test_dir = $upload_dir['basedir'] . "/worry-proof-backup-zip/chunks/{$this->test_package_id}-cleanup/";
        $final_file = $upload_dir['basedir'] . "/worry-proof-backup-zip/{$this->test_package_id}-cleanup.zip";

        try {
            $downloader = new WORRPB_Dummy_Pack_Downloader([
                'package_id' => $this->test_package_id . '-cleanup',
                'remote_url' => 'https://httpbin.org/bytes/100',
            ]);

            // Start download to create directories
            $result = $downloader->startDownload();

            if (!is_wp_error($result)) {
                // Cleanup with keep_final = false
                $downloader->cleanup(false);

                if (!file_exists($test_dir)) {
                    $this->pass('Successfully removes chunks directory');
                } else {
                    $this->fail('Chunks directory should be removed');
                }

                if (!file_exists($final_file)) {
                    $this->pass('Successfully removes final file when keep_final=false');
                } else {
                    $this->fail('Final file should be removed when keep_final=false');
                }
            } else {
                echo "  ⚠️  Warning: Could not test cleanup (network may be unavailable)\n";
            }

        } catch (Exception $e) {
            echo "  ⚠️  Exception during cleanup test: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Mark test as passed
     */
    private function pass($message) {
        echo "  ✅ PASS: $message\n";
        $this->tests_passed++;
    }

    /**
     * Mark test as failed
     */
    private function fail($message) {
        echo "  ❌ FAIL: $message\n";
        $this->tests_failed++;
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory($dir) {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                wp_delete_file($path);
            }
        }

        rmdir($dir);
    }
}

// Run tests if executed directly
if (defined('WP_CLI') && WP_CLI) {
    $tester = new WORRPB_Downloader_Test();
    $success = $tester->runTests();
    
    if ($success) {
        WP_CLI::success('All tests passed!');
    } else {
        WP_CLI::error('Some tests failed!');
    }
} else {
    echo "This test file should be run with WP-CLI:\n";
    echo "wp eval-file test-download-dummy-pack.php\n";
}

