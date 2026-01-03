<?php 
/**
 * Worry Proof Backup REST API
 * 
 * @author: Mike Tropi
 * @since: 0.1.9
 */

/**
 * Hooks
 * - rest_api_init
 */
add_action('rest_api_init', function() {

  // upload chunk
  register_rest_route('worry-proof-backup/v1', '/upload-chunk', [
    'methods' => 'POST',
    'callback' => 'worrprba_cli_upload_chunk',
    'permission_callback' => function() {
      if ( ! current_user_can('manage_options') ) {
        // The WP REST API expects WP_Error for permission_callback errors; response will be handled automatically.
        return new WP_Error(
          'rest_forbidden',
          esc_html__( 'Sorry, you are not allowed to perform this action.', 'worry-proof-backup' ),
          array( 'status' => 403 )
        );
      }
      return true;
    },
    'args' => [
      'file_id' => [
        'type' => 'string',
        'required' => true,
        'description' => 'Unique identifier for the file upload session.',
      ],
      'chunk_index' => [
        'type' => 'string',
        'required' => true,
        'description' => 'Current chunk index being uploaded (as string).',
      ],
      'total_chunks' => [
        'type' => 'string',
        'required' => true,
        'description' => 'Total number of chunks for this upload (as string).',
      ],
    ],
  ]);

  // upload finish
  register_rest_route('worry-proof-backup/v1', '/upload-finalize', [
    'methods' => 'POST',
    'callback' => 'worrprba_cli_upload_finish',
    'permission_callback' => function() {
      return current_user_can('manage_options');
    },
  ]);

  // restore start
  register_rest_route('worry-proof-backup/v1', '/restore-start', [
    'methods' => 'POST',
    'callback' => 'worrprba_cli_restore_start',
    'permission_callback' => function() {
      return current_user_can('manage_options');
    },
    'args' => [
      'zip_path' => [
        'type' => 'string',
        'required' => true,
        'description' => esc_html__('Path to the zip file to restore.', 'worry-proof-backup'),
      ],
    ],
  ]);

  // restore run
  register_rest_route('worry-proof-backup/v1', '/restore-run', [
    'methods' => 'POST',
    'callback' => 'worrprba_cli_restore_run',
    'permission_callback' => function() {
      return current_user_can('manage_options');
    },
    'args' => [
      'zip_path' => [
        'type' => 'string',
        'required' => true,
        'description' => esc_html__('Path to the zip file to restore.', 'worry-proof-backup'),
      ],
    ],
  ]);
});


/**
 * Upload chunk
 * 
 */
function worrprba_cli_upload_chunk( $req ) {
  $file_id       = sanitize_text_field($req->get_param('file_id'));
  $chunk_index   = intval($req->get_param('chunk_index'));
  $total_chunks  = intval($req->get_param('total_chunks'));

  if (empty($_FILES['chunk'])) {
    return new WP_Error('no_chunk', 'Missing chunk');
  }

  $upload_dir = wp_upload_dir();
  $chunk_dir  = $upload_dir['basedir'] . "/worry-proof-backup-zip/chunks/{$file_id}";

  if (!file_exists($chunk_dir)) {
    wp_mkdir_p($chunk_dir);
  }

  $chunk_path = "{$chunk_dir}/part-{$chunk_index}";
  move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_path);

  // if last chunk, merge chunks
  if ($chunk_index + 1 === $total_chunks) {
    return worrprba_cli_merge_chunks($chunk_dir, $file_id, $total_chunks);
  }

  return ['success' => true];
}

/**
 * Merge chunks
 * 
 * @param string $chunk_dir Directory containing uploaded chunks
 * @param string $file_id File ID
 * @param int $total_chunks Total number of chunks for this upload
 * @return void
 */
function worrprba_cli_merge_chunks($chunk_dir, $file_id, $total_chunks) {
  $upload_dir = wp_upload_dir();
  $new_file_name = 'backup_' . wp_generate_uuid4() . '_' . gmdate('Y-m-d_H-i-s');
  $final_path = $upload_dir['basedir'] . "/worry-proof-backup-zip/{$new_file_name}.zip";

  $out = fopen($final_path, 'wb');

  for ($i = 0; $i < $total_chunks; $i++) {
    $part = "{$chunk_dir}/part-{$i}";
    $in = fopen($part, 'rb');
    stream_copy_to_stream($in, $out);
    fclose($in);
    unlink($part);
  }

  fclose($out);
  rmdir($chunk_dir);

  return [
    'success' => true,
    'zip_path' => $final_path,
  ];
}

/**
 * Restore start
 * 
 * @param WP_REST_Request $request
 */
function worrprba_cli_restore_start( $req ) {
  $zip = $req->get_param('zip_path');
  $folder_name_by_zip_name = pathinfo($zip, PATHINFO_FILENAME);

  $upload_dir = wp_upload_dir();
  $dest = $upload_dir['basedir'] . '/' . 'worry-proof-backup' . '/' . $folder_name_by_zip_name;

  if (!file_exists($dest)) {
    wp_mkdir_p($dest);
  }

  $restorer = new WORRPB_Restore_File_System([
    'zip_file' => $zip,
    'destination_folder' => $dest,
    'batch_size' => 1000,
    'overwrite_existing' => true,
  ]);

  return [
    'success' => true,
    'message' => esc_html__('Restore initialized', 'worry-proof-backup'),
  ];
}

/**
 * Upload finish
 * 
 * @param WP_REST_Request $request
 */
function worrprba_cli_upload_finish( $req ) {
  
}

/**
 * Restore run
 * 
 * @param WP_REST_Request $request
 */
function worrprba_cli_restore_run( $req ) {
  $zip = $req->get_param('zip_path');
  $folder_name_by_zip_name = pathinfo($zip, PATHINFO_FILENAME);

  $upload_dir = wp_upload_dir();
  $dest = $upload_dir['basedir'] . '/' . 'worry-proof-backup' . '/' . $folder_name_by_zip_name;

  $restorer = new WORRPB_Restore_File_System([
    'zip_file' => $zip,
    'destination_folder' => $dest,
    'batch_size' => 1000,
    'overwrite_existing' => true,
  ]);

  $result = $restorer->runRestore();

  return $result;
}