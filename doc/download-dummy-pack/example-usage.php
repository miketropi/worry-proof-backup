<?php
/**
 * Example Usage: WORRPB_Dummy_Pack_Downloader
 * 
 * This file demonstrates how to use the dummy pack downloader class.
 * DO NOT include this file in production - it's for reference only.
 * 
 * @package Worry_Proof_Backup
 * @subpackage Dummy_Pack_Downloader
 */

// ============================================================================
// EXAMPLE 1: Basic Download with Ajax Handler
// ============================================================================

/**
 * Ajax handler to start dummy pack download
 */
function worrprba_ajax_start_dummy_pack_download() {
	check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

	// Get payload
	$payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : array();
	$package_id = isset( $payload['ID'] ) ? sanitize_key( $payload['ID'] ) : '';
	$signed_url = isset( $payload['signed_url'] ) ? esc_url_raw( $payload['signed_url'] ) : '';

	if ( empty( $package_id ) || empty( $signed_url ) ) {
		wp_send_json_error( array(
			'error_code'    => 'invalid_params',
			'error_message' => __( 'Package ID and signed URL are required.', 'worry-proof-backup' ),
		) );
	}

	try {
		// Initialize downloader
		$downloader = new WORRPB_Dummy_Pack_Downloader( array(
			'package_id' => $package_id,
			'remote_url' => $signed_url,
			'chunk_size' => 5 * 1024 * 1024, // 5MB chunks
		) );

		// Start download
		$result = $downloader->startDownload();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'error_code'    => $result->get_error_code(),
				'error_message' => $result->get_error_message(),
			) );
		}

		wp_send_json_success( $result );

	} catch ( Exception $e ) {
		wp_send_json_error( array(
			'error_code'    => 'exception',
			'error_message' => $e->getMessage(),
		) );
	}
}
add_action( 'wp_ajax_worrprba_ajax_start_dummy_pack_download', 'worrprba_ajax_start_dummy_pack_download' );

/**
 * Ajax handler to process dummy pack download step
 */
function worrprba_ajax_process_dummy_pack_download() {
	check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

	// Get payload
	$payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : array();
	$package_id = isset( $payload['ID'] ) ? sanitize_key( $payload['ID'] ) : '';
	$signed_url = isset( $payload['signed_url'] ) ? esc_url_raw( $payload['signed_url'] ) : '';

	if ( empty( $package_id ) || empty( $signed_url ) ) {
		wp_send_json_error( array(
			'error_code'    => 'invalid_params',
			'error_message' => __( 'Package ID and signed URL are required.', 'worry-proof-backup' ),
		) );
	}

	try {
		// Initialize downloader with existing progress
		$downloader = new WORRPB_Dummy_Pack_Downloader( array(
			'package_id' => $package_id,
			'remote_url' => $signed_url,
		) );

		// Process one step
		$result = $downloader->processStep();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'error_code'    => $result->get_error_code(),
				'error_message' => $result->get_error_message(),
			) );
		}

		// If download is complete, clean up chunks
		if ( $result['done'] && $result['status'] === 'completed' ) {
			$downloader->cleanup( true ); // Keep final file
		}

		wp_send_json_success( $result );

	} catch ( Exception $e ) {
		wp_send_json_error( array(
			'error_code'    => 'exception',
			'error_message' => $e->getMessage(),
		) );
	}
}
add_action( 'wp_ajax_worrprba_ajax_process_dummy_pack_download', 'worrprba_ajax_process_dummy_pack_download' );

// ============================================================================
// EXAMPLE 2: Get Download Progress
// ============================================================================

/**
 * Ajax handler to get download progress
 */
function worrprba_ajax_get_dummy_pack_download_progress() {
	check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

	$payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : array();
	$package_id = isset( $payload['ID'] ) ? sanitize_key( $payload['ID'] ) : '';
	$signed_url = isset( $payload['signed_url'] ) ? esc_url_raw( $payload['signed_url'] ) : '';

	try {
		$downloader = new WORRPB_Dummy_Pack_Downloader( array(
			'package_id' => $package_id,
			'remote_url' => $signed_url,
		) );

		$progress = $downloader->getProgress();
		wp_send_json_success( $progress );

	} catch ( Exception $e ) {
		wp_send_json_error( array(
			'error_code'    => 'exception',
			'error_message' => $e->getMessage(),
		) );
	}
}
add_action( 'wp_ajax_worrprba_ajax_get_dummy_pack_download_progress', 'worrprba_ajax_get_dummy_pack_download_progress' );

// ============================================================================
// EXAMPLE 3: Cancel Download
// ============================================================================

/**
 * Ajax handler to cancel download and clean up
 */
function worrprba_ajax_cancel_dummy_pack_download() {
	check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

	$payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : array();
	$package_id = isset( $payload['ID'] ) ? sanitize_key( $payload['ID'] ) : '';
	$signed_url = isset( $payload['signed_url'] ) ? esc_url_raw( $payload['signed_url'] ) : '';

	try {
		$downloader = new WORRPB_Dummy_Pack_Downloader( array(
			'package_id' => $package_id,
			'remote_url' => $signed_url,
		) );

		// Clean up everything including final file
		$downloader->cleanup( false );

		wp_send_json_success( array(
			'message' => __( 'Download cancelled successfully.', 'worry-proof-backup' ),
		) );

	} catch ( Exception $e ) {
		wp_send_json_error( array(
			'error_code'    => 'exception',
			'error_message' => $e->getMessage(),
		) );
	}
}
add_action( 'wp_ajax_worrprba_ajax_cancel_dummy_pack_download', 'worrprba_ajax_cancel_dummy_pack_download' );

// ============================================================================
// EXAMPLE 4: Frontend JavaScript Implementation
// ============================================================================

/**
 * Example JavaScript to handle download on frontend:
 * 
 * const downloadDummyPack = async (packData) => {
 *   const { ID, signed_url } = packData;
 *   
 *   try {
 *     // Step 1: Start download
 *     let response = await fetch(worrprba_dummy_pack_center_data.ajax_url, {
 *       method: 'POST',
 *       headers: {
 *         'Content-Type': 'application/x-www-form-urlencoded',
 *       },
 *       body: new URLSearchParams({
 *         action: 'worrprba_ajax_start_dummy_pack_download',
 *         installNonce: worrprba_dummy_pack_center_data.nonce,
 *         'payload[ID]': ID,
 *         'payload[signed_url]': signed_url,
 *       }),
 *     });
 *     
 *     let data = await response.json();
 *     if (!data.success) {
 *       throw new Error(data.data.error_message);
 *     }
 *     
 *     console.log('Download started:', data.data);
 *     
 *     // Step 2: Process chunks until complete
 *     let isDone = false;
 *     while (!isDone) {
 *       response = await fetch(worrprba_dummy_pack_center_data.ajax_url, {
 *         method: 'POST',
 *         headers: {
 *           'Content-Type': 'application/x-www-form-urlencoded',
 *         },
 *         body: new URLSearchParams({
 *           action: 'worrprba_ajax_process_dummy_pack_download',
 *           installNonce: worrprba_dummy_pack_center_data.nonce,
 *           'payload[ID]': ID,
 *           'payload[signed_url]': signed_url,
 *         }),
 *       });
 *       
 *       data = await response.json();
 *       if (!data.success) {
 *         throw new Error(data.data.error_message);
 *       }
 *       
 *       // Update progress UI
 *       console.log(`Progress: ${data.data.progress}%`);
 *       console.log(`Downloaded: ${formatBytes(data.data.downloaded_size)} / ${formatBytes(data.data.total_size)}`);
 *       
 *       isDone = data.data.done;
 *       
 *       // Small delay to prevent overwhelming server
 *       await new Promise(resolve => setTimeout(resolve, 100));
 *     }
 *     
 *     console.log('Download complete!', data.data.file_path);
 *     return data.data;
 *     
 *   } catch (error) {
 *     console.error('Download failed:', error);
 *     throw error;
 *   }
 * };
 * 
 * const formatBytes = (bytes, decimals = 2) => {
 *   if (bytes === 0) return '0 Bytes';
 *   const k = 1024;
 *   const dm = decimals < 0 ? 0 : decimals;
 *   const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
 *   const i = Math.floor(Math.log(bytes) / Math.log(k));
 *   return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
 * };
 */

// ============================================================================
// EXAMPLE 5: WP-CLI Command
// ============================================================================

/**
 * Example WP-CLI command to download dummy pack:
 * 
 * wp eval-file download-dummy-pack-cli.php pack_id signed_url
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Download a dummy pack via CLI
	 * 
	 * ## OPTIONS
	 * 
	 * <package_id>
	 * : The package ID
	 * 
	 * <signed_url>
	 * : The signed URL for download
	 * 
	 * ## EXAMPLES
	 * 
	 *     wp eval-file download-dummy-pack-cli.php my-pack-123 "https://example.com/pack.zip?signature=abc"
	 */
	function worrprba_cli_download_dummy_pack( $args ) {
		list( $package_id, $signed_url ) = $args;

		try {
			$downloader = new WORRPB_Dummy_Pack_Downloader( array(
				'package_id' => $package_id,
				'remote_url' => $signed_url,
				'chunk_size' => 10 * 1024 * 1024, // 10MB for CLI
			) );

			// Start download
			WP_CLI::log( 'Starting download...' );
			$result = $downloader->startDownload();

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}

			WP_CLI::log( sprintf( 'Total size: %s', size_format( $result['total_size'], 2 ) ) );

			// Process until complete
			$progress_bar = \WP_CLI\Utils\make_progress_bar( 'Downloading', 100 );
			$last_progress = 0;

			while ( true ) {
				$result = $downloader->processStep();

				if ( is_wp_error( $result ) ) {
					$progress_bar->finish();
					WP_CLI::error( $result->get_error_message() );
				}

				// Update progress bar
				$current_progress = (int) $result['progress'];
				if ( $current_progress > $last_progress ) {
					$progress_bar->tick( $current_progress - $last_progress );
					$last_progress = $current_progress;
				}

				if ( $result['done'] ) {
					$progress_bar->finish();
					break;
				}
			}

			// Clean up chunks
			$downloader->cleanup( true );

			WP_CLI::success( sprintf( 'Download complete: %s', $result['file_path'] ) );

		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	// Only run if called with arguments
	if ( isset( $args ) && count( $args ) >= 2 ) {
		worrprba_cli_download_dummy_pack( $args );
	}
}

// ============================================================================
// EXAMPLE 6: Integration with Existing Dummy Pack Center
// ============================================================================

/**
 * Modified version of worrprba_ajax_download_dummy_pack from inc/dummy-center.php
 * This integrates the new downloader class
 */
function worrprba_ajax_download_dummy_pack_v2() {
	check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

	$payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : array();
	$package_id = isset( $payload['ID'] ) ? sanitize_key( $payload['ID'] ) : '';
	$download_step = isset( $payload['download_step'] ) ? sanitize_text_field( $payload['download_step'] ) : 'init';

	// Step 1: Get signed URL
	if ( 'init' === $download_step ) {
		$response = worrprba_dummy_pack_get_signed_url( $package_id );
		
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'error_code'    => 'failed_to_fetch_signed_url',
				'error_message' => $response->get_error_message(),
			) );
		}

		$signed_url = isset( $response['signed_url'] ) ? $response['signed_url'] : '';
		
		if ( empty( $signed_url ) ) {
			wp_send_json_error( array(
				'error_code'    => 'invalid_signed_url',
				'error_message' => __( 'Invalid signed URL received.', 'worry-proof-backup' ),
			) );
		}

		// Return signed URL to frontend
		wp_send_json_success( array(
			'download_step' => 'start',
			'signed_url'    => $signed_url,
		) );
	}

	// Step 2: Start download
	if ( 'start' === $download_step ) {
		$signed_url = isset( $payload['signed_url'] ) ? esc_url_raw( $payload['signed_url'] ) : '';

		try {
			$downloader = new WORRPB_Dummy_Pack_Downloader( array(
				'package_id' => $package_id,
				'remote_url' => $signed_url,
			) );

			$result = $downloader->startDownload();

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array(
					'error_code'    => $result->get_error_code(),
					'error_message' => $result->get_error_message(),
				) );
			}

			$result['download_step'] = 'downloading';
			wp_send_json_success( $result );

		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'error_code'    => 'exception',
				'error_message' => $e->getMessage(),
			) );
		}
	}

	// Step 3: Process download
	if ( 'downloading' === $download_step ) {
		$signed_url = isset( $payload['signed_url'] ) ? esc_url_raw( $payload['signed_url'] ) : '';

		try {
			$downloader = new WORRPB_Dummy_Pack_Downloader( array(
				'package_id' => $package_id,
				'remote_url' => $signed_url,
			) );

			$result = $downloader->processStep();

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array(
					'error_code'    => $result->get_error_code(),
					'error_message' => $result->get_error_message(),
				) );
			}

			if ( $result['done'] ) {
				$downloader->cleanup( true );
				$result['download_step'] = 'completed';
			} else {
				$result['download_step'] = 'downloading';
			}

			wp_send_json_success( $result );

		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'error_code'    => 'exception',
				'error_message' => $e->getMessage(),
			) );
		}
	}
}

