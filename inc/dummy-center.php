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

function worrprba_dummy_pack_header_meta_attached_api() {
  $theme = wp_get_theme();

  $parent = $theme->parent();
  $parent_version = $parent ? $parent->get( 'Version' ) : $theme->get( 'Version' );

  return apply_filters( 'worrprba_dummy_pack_center_header_meta_attached_api', [
    'domain' => get_home_url(),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'admin_email' => get_option( 'admin_email' ),
    'license_key' => worrprba_dummy_pack_center_get_license_key(),
    'wordpress_version' => wp_get_wp_version(),
    'php_version' => phpversion(),
    'theme_slug' => WORRPRBA_DUMMY_PACK_CENTER_THEME_SLUG,
    'theme_version' => $parent_version,
  ] );
}

/**
 * Enqueue script for dummy pack center.
 */
function worrprba_dummy_pack_center_enqueue_script() {

  // Prevent enqueuing if the current admin page is not for the Dummy Pack Center.
  // Allow filtering of allowed admin pages for enqueuing the dummy center scripts
  $allowed_pages = apply_filters('worrprba_dummy_pack_center_allowed_pages', array('dummy-pack-center'));
  if (
    ! isset( $_GET['page'] ) ||
    ! in_array( sanitize_key( wp_unslash( $_GET['page'] ) ), $allowed_pages, true )
  ) {
    return;
  }

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
    'header_meta_attached_api' => worrprba_dummy_pack_header_meta_attached_api(),
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
    'xxx-meta' => worrprba_dummy_pack_center_encode_payload(worrprba_dummy_pack_header_meta_attached_api()),
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
 * Encodes a payload similar to dummyPackLib.js encodePayload.
 * - JSON stringifies the object/array
 * - Reverses the string
 * - Encodes as base64 (url-safe)
 *
 * @param mixed $obj
 * @return string
 */
function worrprba_dummy_pack_center_encode_payload($obj) {
  // Convert to JSON
  $json = json_encode($obj);

  // Reverse the string (multibyte safe)
  $reversed = implode('', array_reverse(preg_split('//u', $json, -1, PREG_SPLIT_NO_EMPTY)));

  // Base64 encode (urlsafe, unescaped)
  $encoded = base64_encode($reversed);

  return $encoded;
}

add_action('wp_ajax_worrprba_ajax_dummy_pack_center_get_packs', 'worrprba_ajax_dummy_pack_center_get_packs');
function worrprba_ajax_dummy_pack_center_get_packs() {
  // check nonce
  check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

  $data = worrprba_dummy_pack_get_packs();

  if ( is_wp_error( $data ) ) {
    wp_send_json_error( array(
      'error_message' => $data->get_error_message(),
    ) );
  }

  wp_send_json_success( $data );  
};

/**
 * Get dummy packs list from remote API.
 * 
 * @return array|WP_Error
 */
function worrprba_dummy_pack_get_packs() {
  $endpoint = WORRPRBA_DUMMY_PACK_CENTER_ENDPOINT . 'packages/' . WORRPRBA_DUMMY_PACK_CENTER_THEME_SLUG;
  $headers = array(
    'Content-Type' => 'application/json',
    'xxx-meta' => worrprba_dummy_pack_center_encode_payload(worrprba_dummy_pack_header_meta_attached_api()),
  );

  $response = wp_remote_get( $endpoint, array( 'headers' => $headers ) );

  if ( is_wp_error( $response ) ) {
    return new WP_Error( 'error', $response->get_error_message() );
  }

  $code = wp_remote_retrieve_response_code( $response );
  $body = wp_remote_retrieve_body( $response );
  $data = json_decode( $body, true );

  if ( $code !== 200 ) {
    return new WP_Error( 'error', 'Failed to fetch dummy packs. Status code: ' . $code . ' - ' . $body );
  }

  if(isset($data['packages']) && count($data['packages'])) {
    // verify required plugins
    $data['packages'] = array_map(function($package) {
      if(isset($package['required_plugins']) && count($package['required_plugins'])) {
        $validated_required_plugins = worrprba_validate_required_plugins_versions($package['required_plugins']);
        $package['validated_required_plugins'] = $validated_required_plugins;

        // make array filter by $validated_required_plugins, installed = true and passed = true
        $skip_restore_plugins = array_values(array_filter($validated_required_plugins['results'], function($p) {
          return $p['installed'] && $p['passed'];
        }));

        $package['skip_restore_plugins'] = array_map(function($p) {
          return explode('/', $p['slug'])[0];
        }, $skip_restore_plugins);
      }
      return $package;
    }, $data['packages']);
  }

  return apply_filters( 'worrprba_dummy_pack_get_packs_data', $data );
}


/**
 * Hooks ajax install dummy process
 */
add_action( 'wp_ajax_worrprba_ajax_download_dummy_pack', 'worrprba_ajax_download_dummy_pack' );
add_action( 'wp_ajax_worrprba_ajax_unzip_dummy_pack', 'worrprba_ajax_unzip_dummy_pack' );
add_action( 'wp_ajax_worrprba_ajax_restore_read_dummy_pack_config_file', 'worrprba_ajax_restore_read_dummy_pack_config_file' );
add_action( 'wp_ajax_worrprba_ajax_restore_dummy_pack_uploads', 'worrprba_ajax_restore_dummy_pack_uploads' );
add_action( 'wp_ajax_worrprba_ajax_restore_dummy_pack_plugins', 'worrprba_ajax_restore_dummy_pack_plugins' );

add_action( 'wp_ajax_worrprba_ajax_restore_dummy_pack_database', 'worrprba_ajax_restore_dummy_pack_database' );
add_action( 'wp_ajax_nopriv_worrprba_ajax_restore_dummy_pack_database', 'worrprba_ajax_restore_dummy_pack_database' );

add_action( 'wp_ajax_worrprba_ajax_dummy_pack_install_done', 'worrprba_ajax_dummy_pack_install_done' );
add_action( 'wp_ajax_nopriv_worrprba_ajax_dummy_pack_install_done', 'worrprba_ajax_dummy_pack_install_done' );

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
        'chunk_size' => 2 * 1024 * 1024, // 2MB chunks
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
        'chunk_size' => 2 * 1024 * 1024, // 2MB chunks
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
        $result['__log_process_status'] = '👍';
      } else {
        $result['download_step'] = 'downloading';
        $result['next_step'] = false; // Continue downloading
        $result['__log_process_status'] = $result['progress'] ? round( $result['progress'], 2 ) . '%' : '0%';
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

function worrprba_ajax_unzip_dummy_pack() {
  // check nonce
  check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array();
  $file_path = isset($payload['file_path']) ? sanitize_text_field($payload['file_path']) : '';

  if ( empty( $file_path ) ) {
    wp_send_json_error( array(
      'error_code' => 'invalid_file_path',
      'error_message' => __( 'File path is required.', 'worry-proof-backup' ),
    ) );
  }

  // Validate file exists
  if ( ! file_exists( $file_path ) ) {
    wp_send_json_error( array(
      'error_code' => 'file_not_found',
      'error_message' => __( 'Zip file not found.', 'worry-proof-backup' ),
    ) );
  }

  // Get upload directory
  $upload_dir = wp_upload_dir();
  if ( empty( $upload_dir['basedir'] ) ) {
    wp_send_json_error( array(
      'error_code' => 'upload_dir_not_found',
      'error_message' => __( 'Upload directory not found.', 'worry-proof-backup' ),
    ) );
  }

  // Extract folder name from zip file name (without .zip extension)
  $folder_name_by_zip_name = pathinfo($file_path, PATHINFO_FILENAME);

  // Determine destination folder
  $destination_folder = $upload_dir['basedir'] . '/' . 'worry-proof-backup' . '/' . $folder_name_by_zip_name;

  try {
    $restorer = new WORRPB_Restore_File_System( array(
      'zip_file' => $file_path,
      'destination_folder' => $destination_folder,
      'overwrite_existing' => true,
      'batch_size' => 100,
      'restore_progress_file_name' => '__dummy-pack-unzip-progress.json',
    ) );

    $result = $restorer->runRestore();

    // check error $result
    if ( is_wp_error( $result ) ) {
      wp_send_json_error( array(
        'error_code' => $result->get_error_code(),
        'error_message' => $result->get_error_message(),
      ) );
    }

    if ( $result['done'] !== true ) {
      wp_send_json_success( array(
        'unzip_status' => 'is_running',
        'next_step' => false,
        'progress' => $result,
      ) );
    } else {
      wp_send_json_success( array(
        'unzip_status' => 'done',
        'extracted_folder' => $destination_folder,
        'next_step' => true,
      ) );
    }

  } catch ( Exception $e ) {
    wp_send_json_error( array(
      'error_code' => 'exception',
      'error_message' => $e->getMessage(),
    ) );
  }
}

/**
 * Read dummy pack config file.
 */
function worrprba_ajax_restore_read_dummy_pack_config_file() {
  // check nonce
  check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array();
  $extracted_folder = isset($payload['extracted_folder']) ? sanitize_text_field($payload['extracted_folder']) : '';
  
  if ( empty( $extracted_folder ) ) {
    wp_send_json_error( array(
      'error_code' => 'invalid_extracted_folder',
      'error_message' => __( 'Extracted folder is required.', 'worry-proof-backup' ),
    ) );
  }

  // Determine destination folder
  $folder_name_by_zip_name = pathinfo($extracted_folder, PATHINFO_FILENAME);

  // get config file
  $config_file = $extracted_folder . '/config.json';
  
  if ( ! file_exists( $config_file ) ) {
    wp_send_json_error( array(
      'error_code' => 'config_file_not_found',
      'error_message' => __( 'Config file not found.', 'worry-proof-backup' ),
    ) );
  }

  // get config file content
  $config_file_content = file_get_contents( $config_file );
  $config_data = json_decode( $config_file_content, true );

  wp_send_json_success( array(
    // 'config_data' => $config_data,
    'table_prefix' => $config_data['table_prefix'],
    'current_domain' => get_home_url(),
    'folder_name' => $folder_name_by_zip_name,
    'next_step' => true,
  ) );
}

/**
 * Restore uploads from dummy pack.
 */
function worrprba_ajax_restore_dummy_pack_uploads() {
  // check nonce
  check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array();
  $extracted_folder = isset($payload['extracted_folder']) ? sanitize_text_field($payload['extracted_folder']) : '';

  if ( empty( $extracted_folder ) ) {
    wp_send_json_error( array(
      'error_code' => 'invalid_extracted_folder',
      'error_message' => __( 'Extracted folder is required.', 'worry-proof-backup' ),
    ) );
  }

  $path_zip_file = $extracted_folder . '/uploads.zip';

  if ( ! file_exists( $path_zip_file ) ) {
    wp_send_json_error( array(
      'error_code' => 'file_not_found',
      'error_message' => __( 'Zip file not found.', 'worry-proof-backup' ),
    ) );
  }
  
  try {
    $restorer = new WORRPB_Restore_File_System( array(
      'zip_file' => $path_zip_file,
      'destination_folder' => WP_CONTENT_DIR . '/uploads/',
      'overwrite_existing' => true,
      'exclude' => ['worry-proof-backup', 'worry-proof-backup-cron-manager', 'worry-proof-backup-zip'],
      'restore_progress_file_name' => '__uploads-restore-progress.json',
    ) );

    $result = $restorer->runRestore();

    // check error $result
    if ( is_wp_error( $result ) ) {
      wp_send_json_error( array(
        'error_code' => $result->get_error_code(),
        'error_message' => $result->get_error_message(),
      ) );
    }

    // stats current index / total files
    $stats = $result['stats'];
    $current_index = $stats['current_index'];
    $total_files = $stats['total_files'];
    $progress = round( $current_index / $total_files * 100, 2 );
    
    if ( $result['done'] !== true ) {
      wp_send_json_success( array(
        'restore_uploads_status' => 'is_running',
        'next_step' => false,
        'progress' => $result,
        '__log_process_status' => $progress ? $progress . '%' : '0%',
      ) );
    } else {
      wp_send_json_success( array(
        'restore_uploads_status' => 'done',
        'next_step' => true,
        '__log_process_status' => '👍',
      ) );
    }

  } catch ( Exception $e ) {
    wp_send_json_error( array(
      'error_code' => 'exception',
      'error_message' => $e->getMessage(),
    ) );
  }

  wp_send_json_success( array(
    'restore_uploads_status' => 'done',
    'next_step' => true,
  ) );
}

/**
 * Restore plugins from dummy pack.
 */
function worrprba_ajax_restore_dummy_pack_plugins() {
  // check nonce
  check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array();
  $extracted_folder = isset($payload['extracted_folder']) ? sanitize_text_field($payload['extracted_folder']) : '';
  $skip_restore_plugins = isset($payload['skip_restore_plugins']) ? explode(',', $payload['skip_restore_plugins']) : array();

  if ( empty( $extracted_folder ) ) {
    wp_send_json_error( array(
      'error_code' => 'invalid_extracted_folder',
      'error_message' => __( 'Extracted folder is required.', 'worry-proof-backup' ),
    ) );
  }

  // plugins.zip
  $path_zip_file = $extracted_folder . '/plugins.zip';

  if ( ! file_exists( $path_zip_file ) ) {
    wp_send_json_error( array(
      'error_code' => 'file_not_found',
      'error_message' => __( 'Zip file not found.', 'worry-proof-backup' ),
    ) );
  }

  $exclude = array('worry-proof-backup');
  // check $skip_restore_plugins and convert to array push to $exclude
  if($skip_restore_plugins && count($skip_restore_plugins)) {
    $exclude = array_merge($exclude, $skip_restore_plugins);
  }
  
  try {
    $restorer = new WORRPB_Restore_File_System( array(
      'zip_file' => $path_zip_file,
      'destination_folder' => WP_PLUGIN_DIR,
      'overwrite_existing' => true,
      'exclude' => apply_filters('worrprba_restore_plugin_exclude_dummy_pack', $exclude), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
      'restore_progress_file_name' => '__plugins-restore-progress.json',
    ) );

    $result = $restorer->runRestore();

    // check error $result
    if ( is_wp_error( $result ) ) {
      wp_send_json_error( array(
        'error_code' => $result->get_error_code(),
        'error_message' => $result->get_error_message(),
      ) );
    }

    // stats current index / total files
    $stats = $result['stats'];
    $current_index = $stats['current_index'];
    $total_files = $stats['total_files'];
    $progress = round( $current_index / $total_files * 100, 2 );

    if ( $result['done'] !== true ) {
      wp_send_json_success( array(
        'restore_plugins_status' => 'is_running',
        'next_step' => false,
        'progress' => $result,
        '__log_process_status' => $progress ? $progress . '%' : '0%',
      ) );
    } else {
      wp_send_json_success( array(
        'restore_plugins_status' => 'done',
        'next_step' => true,
        '__log_process_status' => '👍',
      ) );
    }

  } catch ( Exception $e ) {
    wp_send_json_error( array(
      'error_code' => 'exception',
      'error_message' => $e->getMessage(),
    ) );
  }
}

/**
 * Restore database from dummy pack.
 */
function worrprba_ajax_restore_dummy_pack_database() {
  // check nonce
  // check_ajax_referer( 'worrprba_dummy_pack_center_nonce', 'installNonce' );

  # get payload
  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array();
  $extracted_folder = isset($payload['extracted_folder']) ? sanitize_text_field($payload['extracted_folder']) : '';
  $table_prefix = isset($payload['table_prefix']) ? sanitize_text_field($payload['table_prefix']) : '';
  $folder_name = isset($payload['folder_name']) ? sanitize_text_field($payload['folder_name']) : '';

  if ( empty( $extracted_folder ) ) {
    wp_send_json_error( array(
      'error_code' => 'invalid_extracted_folder',
      'error_message' => __( 'Extracted folder is required.', 'worry-proof-backup' ),
    ) );
  }

  if ( empty( $table_prefix ) ) {
    wp_send_json_error( array(
      'error_code' => 'invalid_table_prefix',
      'error_message' => __( 'Table prefix is required.', 'worry-proof-backup' ),
    ) );
  }

  // Check if extracted folder exists
  if ( ! is_dir( $extracted_folder ) ) {
    wp_send_json_error( array(
      'error_code' => 'extracted_folder_not_found',
      'error_message' => __( 'Extracted folder not found.', 'worry-proof-backup' ),
    ) );
  }

  // backup.sql.jsonl
  $backup_jsonl_file = $extracted_folder . '/backup.sql.jsonl';

  if ( ! file_exists( $backup_jsonl_file ) ) {
    wp_send_json_error( array(
      'error_code' => 'file_not_found',
      'error_message' => __( 'Backup JSONL file not found.', 'worry-proof-backup' ),
    ) );
  }

  global $wpdb;
  $exclude_tables = [
    $table_prefix . 'users',
    $table_prefix . 'usermeta',
    $wpdb->prefix . 'users',
    $wpdb->prefix . 'usermeta'
  ];
  $exclude_tables = apply_filters( 'worry-proof-backup:restore_database_exclude_tables_dummy_pack', $exclude_tables, $payload );

  try {
    $restore_database = new WORRPB_Restore_Database_JSON( $folder_name, $exclude_tables, $table_prefix );

    if ( ! isset( $payload['restore_database_ssid'] ) || empty( $payload['restore_database_ssid'] ) ) {
      $progress = $restore_database->startRestore();

      // check error $progress
      if ( is_wp_error( $progress ) ) {
        wp_send_json_error( array(
          'error_code' => $progress->get_error_code(),
          'error_message' => $progress->get_error_message(),
        ) );
      }

      wp_send_json_success( array(
        'restore_database_ssid' => $folder_name,
        'restore_database_status' => 'is_running',
        'next_step' => false,
      ) );
    } else {
      $progress = $restore_database->processStep();

      // check error $progress
      if ( is_wp_error( $progress ) ) {
        wp_send_json_error( array(
          'error_code' => $progress->get_error_code(),
          'error_message' => $progress->get_error_message(),
        ) );
      }

      if ( $progress['done'] ) {
        $result = $restore_database->finishRestore();

        // check error $result
        if ( is_wp_error( $result ) ) {
          wp_send_json_error( array(
            'error_code' => $result->get_error_code(),
            'error_message' => $result->get_error_message(),
          ) );
        }

        // create hook after restore database successfully
        do_action( 'worry-proof-backup:after_restore_database_success_dummy_pack', $payload );

        wp_send_json_success( array(
          'restore_database_ssid' => $folder_name,
          'restore_database_status' => 'done',
          'next_step' => true,
          '__log_process_status' => '👍',
        ) );
      } else {
        wp_send_json_success( array(
          'restore_database_ssid' => $folder_name,
          'restore_database_status' => 'is_running',
          'next_step' => false,
          'progress' => $progress,
          '__log_process_status' => $progress['percent'] ? round( $progress['percent'], 2 ) . '%' : '0%',
        ) );
      }
    }
  } catch ( Exception $e ) {
    wp_send_json_error( array(
      'error_code' => 'exception',
      'error_message' => $e->getMessage(),
    ) );
  }
}

function worrprba_ajax_dummy_pack_install_done() {

  $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array();
  do_action( 'worry-proof-backup:after_install_dummy_pack_done', $payload );

  wp_send_json_success( array(
    'install_done_status' => 'done',
    'next_step' => true,
  ) );
}

/**
 * Validate required plugin versions.
 *
 * @param array $required_plugins Array of plugins to validate in the format [
 *   [
 *     'slug'    => 'plugin-folder/plugin-main-file.php',
 *     'name'    => 'Plugin Name',
 *     'version' => 'required-minimum-version'
 *   ],
 *   ...
 * ]
 * @return array [
 *   'passed' => bool, // true if all passed, false if any failed
 *   'results' => [
 *       [
 *           'slug' => string,
 *           'name' => string,
 *           'installed' => bool,
 *           'current_version' => string|null,
 *           'required_version' => string,
 *           'passed' => bool,
 *       ],
 *       ...
 *   ]
 * ]
 */
function worrprba_validate_required_plugins_versions( $required_plugins ) {
  if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
  }
  $all_plugins = get_plugins();
  $active_plugins = (array) get_option( 'active_plugins', array() );
  // If multisite, merge with network active plugins
  if ( is_multisite() ) {
    $network_plugins = get_site_option( 'active_sitewide_plugins', array() );
    $active_plugins = array_unique( array_merge( $active_plugins, array_keys( $network_plugins ) ) );
  }
  $results = array();
  $all_passed = true;

  foreach ( $required_plugins as $plugin ) {
    $slug = isset( $plugin['slug'] ) ? $plugin['slug'] : '';
    $name = isset( $plugin['name'] ) ? $plugin['name'] : '';
    $required_version = isset( $plugin['version'] ) ? $plugin['version'] : '';
    $plugin_info = isset( $all_plugins[ $slug ] ) ? $all_plugins[ $slug ] : null;
    $installed = $plugin_info !== null;
    $current_version = $installed ? ( isset( $plugin_info['Version'] ) ? $plugin_info['Version'] : '' ) : null;
    $active = $installed && in_array( $slug, $active_plugins );
    $passed = true;

    if ( $installed ) {
      // Use version_compare, true if installed version >= required
      if ( version_compare( $current_version, $required_version, '>=' ) ) {
        $passed = true;
      } else {
        if( $active ) {
          $passed = false;
          $all_passed = false;
        } else {
          $passed = false;
          $all_passed = true;
        }
      }
    } else {
      // Not installed = passed
      $passed = true;
    }

    // Determine template type based on plugin status
    $template = worrprba_determine_plugin_template( $installed, $active, $passed );

    $results[] = array(
      'slug' => $slug, // string, plugin slug
      'name' => $name, // string, plugin name
      'installed' => $installed, // boolean, true if plugin installed
      'active' => $active, // boolean, true if plugin active
      'current_version' => $current_version, // string, plugin current version
      'required_version' => $required_version, // string, plugin required version
      'passed' => $passed, // boolean, true if installed version >= required version
      '__template' => $template, // string, template type for UI rendering
    );
  }

  return array(
    'passed' => $all_passed,
    'results' => $results,
  );
}

/**
 * Determine plugin template type based on installation, activation, and version status.
 *
 * Template Types:
 * - 'success': Plugin is installed, active, and meets version requirements
 * - 'auto-install': Plugin is not installed (will be auto-installed)
 * - 'warning': Plugin is active but doesn't meet version requirements (requires manual action)
 * - 'auto-update': Plugin is installed but inactive with wrong version (will be auto-updated)
 * - 'auto-activate': Plugin is installed with correct version but not active (will be activated)
 *
 * @param bool $installed Whether plugin is installed
 * @param bool $active Whether plugin is active
 * @param bool $passed Whether plugin meets version requirements
 * @return string Template type
 */
function worrprba_determine_plugin_template( $installed, $active, $passed ) {
  // CASE 1: All requirements met ✓
  // Plugin is installed, active, and meets version requirements
  if ( $passed === true && $installed === true && $active === true ) {
    return 'success';
  }

  // CASE 2: Plugin not installed 📥
  // Plugin needs to be installed
  if ( $installed === false ) {
    return 'auto-install';
  }

  // CASE 3: Plugin installed but ACTIVE with wrong version ⚠️
  // Requires manual action - user must update or deactivate
  // Cannot auto-update active plugins to avoid breaking the site
  if ( $installed === true && $active === true && $passed === false ) {
    return 'warning';
  }

  // CASE 4: Plugin installed but INACTIVE with wrong version 🔄
  // Can be auto-updated since it's not active
  if ( $installed === true && $active === false && $passed === false ) {
    return 'auto-update';
  }

  // CASE 5: Plugin installed and passed, but not active (will be activated)
  if ( $installed === true && $active === false && $passed === true ) {
    return 'auto-activate';
  }

  // Fallback for any unexpected state
  return 'unknown';
}