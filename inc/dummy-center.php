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

// enqueue script for dummy pack center
function worrprba_dummy_pack_center_enqueue_script() {
  // worry-proof-backup.bundle.css
  wp_enqueue_style( 'worry-proof-backup-dummy-center', WORRPRBA_PLUGIN_URL . 'dist/css/worry-proof-backup.bundle.css', array(), WORRPRBA_PLUGIN_VERSION, 'all' );

  wp_enqueue_script( 'worry-proof-backup-dummy-center', WORRPRBA_PLUGIN_URL . 'dist/dummy-center.bundle.js', array('jquery'), WORRPRBA_PLUGIN_VERSION, true );

  $theme = wp_get_theme();

  $parent = $theme->parent();
  $parent_version = $parent ? $parent->get( 'Version' ) : $theme->get( 'Version' );
  $license_key = apply_filters( 'worrprba_dummy_pack_center_license_key', 'xxx' );

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