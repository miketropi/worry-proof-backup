<?php
/**
 * Dummy Pack Downloader
 * 
 * Handles downloading large dummy pack files with chunked download support
 * for improved reliability and progress tracking.
 * 
 * @package Worry_Proof_Backup
 * @subpackage Dummy_Pack_Downloader
 * @since 0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WORRPB_Dummy_Pack_Downloader
 * 
 * Downloads remote dummy pack files in chunks to avoid memory issues
 * and provide progress tracking capabilities.
 */
class WORRPB_Dummy_Pack_Downloader {

	/**
	 * Package ID for the dummy pack
	 * 
	 * @var string
	 */
	private $package_id;

	/**
	 * Remote URL to download from
	 * 
	 * @var string
	 */
	private $remote_url;

	/**
	 * Chunk size in bytes (default 5MB)
	 * 
	 * @var int
	 */
	private $chunk_size;

	/**
	 * Download directory path
	 * 
	 * @var string
	 */
	private $download_dir;

	/**
	 * Progress file path
	 * 
	 * @var string
	 */
	private $progress_file;

	/**
	 * Final file path
	 * 
	 * @var string
	 */
	private $final_file_path;

	/**
	 * Current byte offset
	 * 
	 * @var int
	 */
	private $current_offset;

	/**
	 * Total file size
	 * 
	 * @var int
	 */
	private $total_size;

	/**
	 * Downloaded size
	 * 
	 * @var int
	 */
	private $downloaded_size;

	/**
	 * Download status
	 * 
	 * @var string
	 */
	private $status;

	/**
	 * Constructor
	 * 
	 * @param array $args {
	 *     Configuration arguments
	 *     
	 *     @type string $package_id    Package identifier
	 *     @type string $remote_url    Remote file URL
	 *     @type int    $chunk_size    Download chunk size in bytes (default 5MB)
	 * }
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'package_id'  => '',
			'remote_url'  => '',
			'chunk_size'  => 5 * 1024 * 1024, // 5MB
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate required parameters
		if ( empty( $args['package_id'] ) ) {
			throw new Exception( esc_html__( 'Package ID is required.', 'worry-proof-backup' ) );
		}

		if ( empty( $args['remote_url'] ) || ! filter_var( $args['remote_url'], FILTER_VALIDATE_URL ) ) {
			throw new Exception( esc_html__( 'Valid remote URL is required.', 'worry-proof-backup' ) );
		}

		$this->package_id  = sanitize_key( $args['package_id'] );
		$this->remote_url  = esc_url_raw( $args['remote_url'] );
		$this->chunk_size  = absint( $args['chunk_size'] );

		// Set up directories
		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['basedir'] ) ) {
			throw new Exception( esc_html__( 'Upload directory not found.', 'worry-proof-backup' ) );
		}

		$this->download_dir = $upload_dir['basedir'] . "/worry-proof-backup-zip/chunks/{$this->package_id}/";
		$this->progress_file = $this->download_dir . '__download-progress.json';
		$this->final_file_path = $upload_dir['basedir'] . "/worry-proof-backup-zip/{$this->package_id}.zip";

		// Initialize state
		$this->current_offset = 0;
		$this->total_size = 0;
		$this->downloaded_size = 0;
		$this->status = 'pending';
	}

	/**
	 * Start the download process
	 * 
	 * Initializes the download by fetching file size and creating necessary directories.
	 * 
	 * @return array|WP_Error Download initialization data or error
	 */
	public function startDownload() {
		try {
			// Create download directory
			if ( ! file_exists( $this->download_dir ) ) {
				if ( ! wp_mkdir_p( $this->download_dir ) ) {
					return new WP_Error(
						'mkdir_failed',
						sprintf(
							/* translators: %s: Directory path */
							esc_html__( 'Failed to create download directory: %s', 'worry-proof-backup' ),
							$this->download_dir
						)
					);
				}
			}

			// Get remote file size
			$file_size = $this->getRemoteFileSize();
			if ( is_wp_error( $file_size ) ) {
				return $file_size;
			}

			$this->total_size = $file_size;
			$this->status = 'downloading';

			// Save initial progress
			$this->saveProgress();

			return array(
				'success'         => true,
				'status'          => 'downloading',
				'total_size'      => $this->total_size,
				'downloaded_size' => 0,
				'progress'        => 0,
				'package_id'      => $this->package_id,
			);

		} catch ( Exception $e ) {
			return new WP_Error( 'start_failed', $e->getMessage() );
		}
	}

	/**
	 * Process download step
	 * 
	 * Downloads one chunk of the file and updates progress.
	 * 
	 * @return array|WP_Error Progress data or error
	 */
	public function processStep() {
		try {
			// Load existing progress
			$this->loadProgress();

			// Check if already completed
			if ( $this->status === 'completed' ) {
				return array(
					'done'            => true,
					'status'          => 'completed',
					'total_size'      => $this->total_size,
					'downloaded_size' => $this->downloaded_size,
					'progress'        => 100,
					'file_path'       => $this->final_file_path,
				);
			}

			// Validate state
			if ( $this->total_size <= 0 ) {
				return new WP_Error(
					'invalid_state',
					esc_html__( 'Download not initialized. Call startDownload() first.', 'worry-proof-backup' )
				);
			}

			// Check if download is complete
			if ( $this->current_offset >= $this->total_size ) {
				return $this->finishDownload();
			}

			// Download next chunk
			$chunk_result = $this->downloadChunk();
			if ( is_wp_error( $chunk_result ) ) {
				$this->status = 'failed';
				$this->saveProgress();
				return $chunk_result;
			}

			// Update progress
			$this->downloaded_size = $this->current_offset;
			$progress_percent = ( $this->total_size > 0 ) ? round( ( $this->downloaded_size / $this->total_size ) * 100, 2 ) : 0;

			// Save progress
			$this->saveProgress();

			// Check if done
			$is_done = ( $this->current_offset >= $this->total_size );

			return array(
				'done'            => $is_done,
				'status'          => $is_done ? 'merging' : 'downloading',
				'total_size'      => $this->total_size,
				'downloaded_size' => $this->downloaded_size,
				'progress'        => $progress_percent,
				'chunk_count'     => $chunk_result['chunk_count'],
			);

		} catch ( Exception $e ) {
			$this->status = 'failed';
			$this->saveProgress();
			return new WP_Error( 'download_failed', $e->getMessage() );
		}
	}

	/**
	 * Download a single chunk
	 * 
	 * @return array|WP_Error Chunk download result or error
	 */
	private function downloadChunk() {
		$end_byte = min( $this->current_offset + $this->chunk_size - 1, $this->total_size - 1 );
		
		// Calculate chunk number
		$chunk_number = (int) floor( $this->current_offset / $this->chunk_size );
		$chunk_file = $this->download_dir . "part-{$chunk_number}";

		// Skip if chunk already exists and is valid
		if ( file_exists( $chunk_file ) ) {
			$expected_size = $end_byte - $this->current_offset + 1;
			$actual_size = filesize( $chunk_file );
			
			if ( $actual_size === $expected_size ) {
				// Chunk already downloaded, skip to next
				$this->current_offset = $end_byte + 1;
				return array(
					'chunk_count' => $chunk_number + 1,
					'cached'      => true,
				);
			}
		}

		// Download chunk using wp_remote_get with Range header
		$response = wp_remote_get(
			$this->remote_url,
			array(
				'timeout' => 300, // 5 minutes
				'headers' => array(
					'Range' => "bytes={$this->current_offset}-{$end_byte}",
				),
				'stream'   => true,
				'filename' => $chunk_file,
			)
		);

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'download_chunk_failed',
				sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Failed to download chunk: %s', 'worry-proof-backup' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( ! in_array( $response_code, array( 200, 206 ), true ) ) {
			return new WP_Error(
				'download_chunk_http_error',
				sprintf(
					/* translators: %d: HTTP response code */
					esc_html__( 'HTTP error %d while downloading chunk.', 'worry-proof-backup' ),
					$response_code
				)
			);
		}

		// Verify chunk was saved
		if ( ! file_exists( $chunk_file ) ) {
			return new WP_Error(
				'chunk_save_failed',
				esc_html__( 'Failed to save downloaded chunk.', 'worry-proof-backup' )
			);
		}

		// Update offset
		$this->current_offset = $end_byte + 1;

		return array(
			'chunk_count' => $chunk_number + 1,
			'cached'      => false,
		);
	}

	/**
	 * Finish download by merging all chunks
	 * 
	 * @return array|WP_Error Completion data or error
	 */
	private function finishDownload() {
		try {
			// Merge chunks into final file
			$merge_result = $this->mergeChunks();
			if ( is_wp_error( $merge_result ) ) {
				return $merge_result;
			}

			$this->status = 'completed';
			$this->saveProgress();

			return array(
				'done'            => true,
				'status'          => 'completed',
				'total_size'      => $this->total_size,
				'downloaded_size' => $this->downloaded_size,
				'progress'        => 100,
				'file_path'       => $this->final_file_path,
			);

		} catch ( Exception $e ) {
			return new WP_Error( 'finish_failed', $e->getMessage() );
		}
	}

	/**
	 * Merge downloaded chunks into final file
	 * 
	 * @return true|WP_Error True on success or error
	 */
	private function mergeChunks() {
		// Calculate total chunks
		$total_chunks = (int) ceil( $this->total_size / $this->chunk_size );

		// Create final directory if needed
		$final_dir = dirname( $this->final_file_path );
		if ( ! file_exists( $final_dir ) ) {
			if ( ! wp_mkdir_p( $final_dir ) ) {
				return new WP_Error(
					'mkdir_failed',
					sprintf(
						/* translators: %s: Directory path */
						esc_html__( 'Failed to create final directory: %s', 'worry-proof-backup' ),
						$final_dir
					)
				);
			}
		}

		// Open final file for writing
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$out = fopen( $this->final_file_path, 'wb' );
		if ( false === $out ) {
			return new WP_Error(
				'file_open_failed',
				sprintf(
					/* translators: %s: File path */
					esc_html__( 'Failed to open final file for writing: %s', 'worry-proof-backup' ),
					$this->final_file_path
				)
			);
		}

		// Merge chunks
		for ( $i = 0; $i < $total_chunks; $i++ ) {
			$chunk_file = $this->download_dir . "part-{$i}";
			
			if ( ! file_exists( $chunk_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				fclose( $out );
				return new WP_Error(
					'chunk_missing',
					sprintf(
						/* translators: %d: Chunk number */
						esc_html__( 'Chunk %d is missing.', 'worry-proof-backup' ),
						$i
					)
				);
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$in = fopen( $chunk_file, 'rb' );
			if ( false === $in ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				fclose( $out );
				return new WP_Error(
					'chunk_read_failed',
					sprintf(
						/* translators: %d: Chunk number */
						esc_html__( 'Failed to read chunk %d.', 'worry-proof-backup' ),
						$i
					)
				);
			}

			// Copy chunk to final file
			stream_copy_to_stream( $in, $out );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $in );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $out );

		// Verify final file size
		$final_size = filesize( $this->final_file_path );
		if ( $final_size !== $this->total_size ) {
			return new WP_Error(
				'size_mismatch',
				sprintf(
					/* translators: 1: Expected size, 2: Actual size */
					esc_html__( 'File size mismatch. Expected: %1$s, Got: %2$s', 'worry-proof-backup' ),
					size_format( $this->total_size, 2 ),
					size_format( $final_size, 2 )
				)
			);
		}

		return true;
	}

	/**
	 * Get remote file size
	 * 
	 * @return int|WP_Error File size in bytes or error
	 */
	private function getRemoteFileSize() {
    $response = wp_remote_get(
        $this->remote_url,
        [
            'headers' => [
                'Range' => 'bytes=0-0',
            ],
            'timeout'     => 30,
            'redirection' => 0,
        ]
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( ! in_array( $code, [200, 206], true ) ) {
        return new WP_Error(
            'remote_http_error',
            'HTTP error ' . $code
        );
    }

    /**
     * Content-Range: bytes 0-0/123456789
     */
    $content_range = wp_remote_retrieve_header( $response, 'content-range' );

    if ( $content_range && preg_match( '#/(\d+)$#', $content_range, $m ) ) {
        return (int) $m[1];
    }

    /**
     * Fallback (rare case)
     */
    $content_length = wp_remote_retrieve_header( $response, 'content-length' );
    if ( $content_length ) {
        return (int) $content_length;
    }

    return new WP_Error(
        'cannot_detect_size',
        'Unable to determine remote file size'
    );
  }


	/**
	 * Load progress from file
	 */
	private function loadProgress() {
		if ( file_exists( $this->progress_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$json = file_get_contents( $this->progress_file );
			$data = json_decode( $json, true );
			
			if ( is_array( $data ) ) {
				$this->current_offset = isset( $data['current_offset'] ) ? absint( $data['current_offset'] ) : 0;
				$this->total_size = isset( $data['total_size'] ) ? absint( $data['total_size'] ) : 0;
				$this->downloaded_size = isset( $data['downloaded_size'] ) ? absint( $data['downloaded_size'] ) : 0;
				$this->status = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'pending';
			}
		}
	}

	/**
	 * Save progress to file
	 */
	private function saveProgress() {
		$data = array(
			'package_id'       => $this->package_id,
			'remote_url'       => $this->remote_url,
			'current_offset'   => $this->current_offset,
			'total_size'       => $this->total_size,
			'downloaded_size'  => $this->downloaded_size,
			'status'           => $this->status,
			'last_updated'     => current_time( 'mysql' ),
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->progress_file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Clean up download files
	 * 
	 * Removes all chunks and progress file.
	 * 
	 * @param bool $keep_final Whether to keep the final merged file
	 */
	public function cleanup( $keep_final = true ) {
		// Remove chunks directory
		if ( file_exists( $this->download_dir ) ) {
			$this->removeDirectory( $this->download_dir );
		}

		// Remove final file if requested
		if ( ! $keep_final && file_exists( $this->final_file_path ) ) {
			wp_delete_file( $this->final_file_path );
		}
	}

	/**
	 * Recursively remove directory
	 * 
	 * @param string $dir Directory path
	 */
	private function removeDirectory( $dir ) {
		if ( ! file_exists( $dir ) ) {
			return;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		
		foreach ( $files as $file ) {
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			
			if ( is_dir( $path ) ) {
				$this->removeDirectory( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		rmdir( $dir );
	}

	/**
	 * Get current progress
	 * 
	 * @return array Progress data
	 */
	public function getProgress() {
		$this->loadProgress();

		$progress_percent = 0;
		if ( $this->total_size > 0 ) {
			$progress_percent = round( ( $this->downloaded_size / $this->total_size ) * 100, 2 );
		}

		return array(
			'package_id'       => $this->package_id,
			'status'           => $this->status,
			'total_size'       => $this->total_size,
			'downloaded_size'  => $this->downloaded_size,
			'progress'         => $progress_percent,
			'current_offset'   => $this->current_offset,
			'file_path'        => $this->final_file_path,
		);
	}

	/**
	 * Check if download is complete
	 * 
	 * @return bool True if download is complete
	 */
	public function isComplete() {
		$this->loadProgress();
		return ( 'completed' === $this->status && file_exists( $this->final_file_path ) );
	}

	/**
	 * Get final file path
	 * 
	 * @return string|null File path if complete, null otherwise
	 */
	public function getFinalFilePath() {
		if ( $this->isComplete() ) {
			return $this->final_file_path;
		}
		return null;
	}
}

