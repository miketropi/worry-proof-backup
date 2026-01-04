<?php
/**
 * Dummy Pack Center.
 * 
 * @package Worry_Proof_Backup
 * @subpackage Dummy_Pack_Center
 * @since 0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Load downloader class
 */
require_once WORRPRBA_PLUGIN_PATH . 'inc/libs/dummy-pack/download-dummy-pack.php';

/**
 * Initialize dummy pack center.
 */
add_action('init', 'worrprba_dummy_pack_center_init');
function worrprba_dummy_pack_center_init() {
  if ( ! defined( 'WORRPRBA_DUMMY_PACK_CENTER_SUPPORTED' ) || ! WORRPRBA_DUMMY_PACK_CENTER_SUPPORTED ) {
    return;
  }

  // Validate the required constants and display an admin notice if invalid.
  if (
    ! defined('WORRPRBA_DUMMY_PACK_CENTER_ENDPOINT') || empty(WORRPRBA_DUMMY_PACK_CENTER_ENDPOINT) ||
    ! defined('WORRPRBA_DUMMY_PACK_CENTER_THEME_SLUG') || empty(WORRPRBA_DUMMY_PACK_CENTER_THEME_SLUG)
  ) {
    add_action('admin_notices', function() {
      ?>
      <div class="notice notice-error">
        <p>
          <?php 
            esc_html_e('Dummy Pack Center requires that both WORRPRBA_DUMMY_PACK_CENTER_ENDPOINT and WORRPRBA_DUMMY_PACK_CENTER_THEME_SLUG are defined and not empty. Please check your theme or plugin configuration.', 'worry-proof-backup'); 
          ?>
          <br>
          <?php 
            printf(
              /* translators: 1: Documentation URL. */
              wp_kses(
                __('For setup instructions, please read the <a href="#" target="_blank">documentation</a>.', 'worry-proof-backup'),
                array('a' => array('href' => array(), 'target' => array()))
              )
            );
          ?>
        </p>
      </div>
      <?php
    });
    // Prevent further execution
    return;
  }

  add_action('admin_menu', 'worrprba_dummy_pack_center_register_submenu');
  add_action('admin_enqueue_scripts', 'worrprba_dummy_pack_center_enqueue_script');
}

/**
 * Get license key for dummy pack center.
 * 
 * @return string
 */
function worrprba_dummy_pack_center_get_license_key() {
  return apply_filters( 'worrprba_dummy_pack_center_license_key', 'xxx' );
}

/**
 * Enqueue script for dummy pack center.
 */
function worrprba_dummy_pack_center_enqueue_script() {
  // worry-proof-backup.bundle.css
  wp_enqueue_style( 'worry-proof-backup-dummy-center', WORRPRBA_PLUGIN_URL . 'dist/css/worry-proof-backup.bundle.css', array(), WORRPRBA_PLUGIN_VERSION, 'all' );

  wp_enqueue_script( 'worry-proof-backup-dummy-center', WORRPRBA_PLUGIN_URL . 'dist/dummy-center.bundle.js', array('jquery'), WORRPRBA_PLUGIN_VERSION, true );

  $theme = wp_get_theme();

  $parent = $theme->parent();
  $parent_version = $parent ? $parent->get( 'Version' ) : $theme->get( 'Version' );
  $license_key = worrprba_dummy_pack_center_get_license_key();

  wp_localize_script( 'worry-proof-backup-dummy-center', 'worrprba_dummy_pack_center_data', array(
    'endpoint' => WORRPRBA_DUMMY_PACK_CENTER_ENDPOINT,
    'theme_slug' => WORRPRBA_DUMMY_PACK_CENTER_THEME_SLUG,
    'nonce' => wp_create_nonce( 'worrprba_dummy_pack_center_nonce' ),
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    // Add parent theme (if any) version and current PHP version
    'php_version' => phpversion(),
    'parent_theme_version' => $parent_version,
    'wordpress_version' => wp_get_wp_version(),
    'license_key' => $license_key,
  ) );
}

/**
 * Register submenu for dummy pack center.
 */
function worrprba_dummy_pack_center_register_submenu() {

  // add dummy pack center as a submenu under "Appearance"
  $submenu_args = array(
    'parent_slug' => 'themes.php',
    'page_title'  => __('Dummy Pack Center', 'worry-proof-backup'),
    'menu_title'  => __('Dummy Pack Center', 'worry-proof-backup'),
    'capability'  => 'manage_options',
    'menu_slug'   => 'dummy-pack-center',
    'callback'    => 'worrprba_dummy_pack_center_page'
  );

  // Allow filtering the Dummy Pack Center submenu arguments before registration.
  $submenu_args = apply_filters('worrprba_dummy_pack_center_submenu_args', $submenu_args);

  add_submenu_page(
    $submenu_args['parent_slug'],
    $submenu_args['page_title'],
    $submenu_args['menu_title'],
    $submenu_args['capability'],
    $submenu_args['menu_slug'],
    $submenu_args['callback']
  );
}

/**
 * Dummy Pack Center Page.
 */
function worrprba_dummy_pack_center_page() {
  ?>
  <div id="WORRPRBA-DUMMY-PACK-CENTER-ROOT">
    <!-- We are use react js for this content -->
  </div> <!-- #WORRPRBA-DUMMY-PACK-CENTER-ROOT -->
  <?php
}

/**
 * Get signed url for dummy pack.
 * 
 * @param string $package_id
 * @return string|WP_Error
 */
function worrprba_dummy_pack_get_signed_url($package_id = '') {
  $headers = array(
    'Content-Type' => 'application/json',
    'license_key' => worrprba_dummy_pack_center_get_license_key(),
  );
  $url = WORRPRBA_DUMMY_PACK_CENTER_ENDPOINT . 'packages/' . WORRPRBA_DUMMY_PACK_CENTER_THEME_SLUG . '/' . $package_id;
  $response = wp_remote_get( $url, array( 'headers' => $headers ) );

  if($response['response']['code'] !== 200) {
    return new WP_Error( 'error', 'Failed to fetch dummy pack signed url. Status code: ' . $response['response']['code'] . ' - ' . $response['body'] );
  }

  if( is_wp_error( $response ) ) {
    return new WP_Error( 'error', $response->get_error_message() );
  } else {
    $body = json_decode( $response['body'], true );
    return $body;
  }
}

/**
 * Hooks ajax install
 */
add_action( 'wp_ajax_worrprba_ajax_download_dummy_pack', 'worrprba_ajax_download_dummy_pack' );

/**
 * Download dummy pack with chunked download support.
 * 
 * Supports three download steps:
 * - 'init': Get signed URL from remote server
 * - 'start': Initialize chunked download
 * - 'downloading': Process download chunks
 * 
 * @return void Sends JSON response
 */
function worrprba_ajax_download_dummy_pack() {
  // check nonce
  check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array();
  $package_id = isset( $payload['ID'] ) ? sanitize_key( $payload['ID'] ) : 'xxx';
  $download_step = isset( $payload['download_step'] ) ? sanitize_text_field( $payload['download_step'] ) : 'init';

  if ( empty( $package_id ) ) {
    wp_send_json_error( array(
      'error_code' => 'invalid_package_id',
      'error_message' => __( 'Package ID is required.', 'worry-proof-backup' ),
    ) );
  }

  // Step 1: Get signed URL from remote server
  if ( $download_step === 'init' ) {
    $response = worrprba_dummy_pack_get_signed_url( $package_id );
    
    if ( is_wp_error( $response ) ) {
      wp_send_json_error( array(
        'error_code' => 'failed_to_fetch_signed_url',
        'error_message' => $response->get_error_message(),
      ) );
    }

    // Extract signed URL from response
    $signed_url = isset( $response['signedUrl'] ) ? $response['signedUrl'] : '';
    
    if ( empty( $signed_url ) ) {
      wp_send_json_error( array(
        'error_code' => 'invalid_signed_url',
        'error_message' => __( 'Invalid signed URL received from server.', 'worry-proof-backup' ),
      ) );
    }

    // Return signed URL to frontend
    wp_send_json_success( array(
      'download_step' => 'start',
      'signed_url' => $signed_url,
      'next_step' => false,
    ) );
  }

  // Step 2: Start chunked download
  if ( $download_step === 'start' ) {
    $signed_url = isset( $payload['signed_url'] ) ? esc_url_raw( $payload['signed_url'] ) : '';

    if ( empty( $signed_url ) ) {
      wp_send_json_error( array(
        'error_code' => 'missing_signed_url',
        'error_message' => __( 'Signed URL is required to start download.', 'worry-proof-backup' ),
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
          'error_code' => $result->get_error_code(),
          'error_message' => $result->get_error_message(),
        ) );
      }

      // Add download_step and next_step to response
      $result['download_step'] = 'downloading';
      $result['next_step'] = false;

      wp_send_json_success( $result );

    } catch ( Exception $e ) {
      wp_send_json_error( array(
        'error_code' => 'exception',
        'error_message' => $e->getMessage(),
      ) );
    }
  }

  // Step 3: Continue downloading chunks
  if ( $download_step === 'downloading' ) {
    $signed_url = isset( $payload['signed_url'] ) ? esc_url_raw( $payload['signed_url'] ) : '';

    if ( empty( $signed_url ) ) {
      wp_send_json_error( array(
        'error_code' => 'missing_signed_url',
        'error_message' => __( 'Signed URL is required to continue download.', 'worry-proof-backup' ),
      ) );
    }

    try {
      // Initialize downloader (will load existing progress)
      $downloader = new WORRPB_Dummy_Pack_Downloader( array(
        'package_id' => $package_id,
        'remote_url' => $signed_url,
      ) );

      // Process one download step
      $result = $downloader->processStep();

      if ( is_wp_error( $result ) ) {
        wp_send_json_error( array(
          'error_code' => $result->get_error_code(),
          'error_message' => $result->get_error_message(),
        ) );
      }

      // Check if download is complete
      if ( $result['done'] && $result['status'] === 'completed' ) {
        // Clean up temporary chunks (keep final file)
        $downloader->cleanup( true );
        
        $result['download_step'] = 'completed';
        $result['next_step'] = true; // Move to next install step
      } else {
        $result['download_step'] = 'downloading';
        $result['next_step'] = false; // Continue downloading
      }

      wp_send_json_success( $result );

    } catch ( Exception $e ) {
      wp_send_json_error( array(
        'error_code' => 'exception',
        'error_message' => $e->getMessage(),
      ) );
    }
  }

  // Invalid download step
  wp_send_json_error( array(
    'error_code' => 'invalid_download_step',
    'error_message' => __( 'Invalid download step provided.', 'worry-proof-backup' ),
  ) );
}