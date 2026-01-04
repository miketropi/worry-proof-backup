# Dummy Pack Downloader

Production-quality PHP class for downloading large dummy pack files with chunked download support, progress tracking, and robust error handling.

## Features

✅ **Chunked Downloads** - Downloads large files in manageable chunks (default 5MB)  
✅ **Progress Tracking** - Real-time progress monitoring with percentage and bytes downloaded  
✅ **Resume Support** - Automatically resumes interrupted downloads  
✅ **Error Handling** - Comprehensive error handling with WP_Error integration  
✅ **Memory Efficient** - Streams chunks to disk without loading entire file in memory  
✅ **WordPress Standards** - Follows WordPress coding standards and best practices  
✅ **Production Ready** - Fully documented with PHPCS compliance  

## Installation

The class is located at:
```
wp-content/plugins/worry-proof-backup/inc/libs/dummy-pack/download-dummy-pack.php
```

Include it in your plugin:
```php
require_once WORRPRBA_PLUGIN_PATH . 'inc/libs/dummy-pack/download-dummy-pack.php';
```

## Basic Usage

### 1. Start Download

```php
try {
    $downloader = new WORRPB_Dummy_Pack_Downloader([
        'package_id' => 'my-pack-123',
        'remote_url' => 'https://example.com/pack.zip?signature=abc',
        'chunk_size' => 5 * 1024 * 1024, // 5MB (optional)
    ]);

    $result = $downloader->startDownload();

    if (is_wp_error($result)) {
        echo $result->get_error_message();
    } else {
        echo "Download started! Total size: " . size_format($result['total_size'], 2);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### 2. Process Download Steps

```php
// Continue downloading chunks
while (true) {
    $result = $downloader->processStep();
    
    if (is_wp_error($result)) {
        echo "Error: " . $result->get_error_message();
        break;
    }
    
    // Show progress
    echo sprintf(
        "Progress: %d%% (%s / %s)\n",
        $result['progress'],
        size_format($result['downloaded_size'], 2),
        size_format($result['total_size'], 2)
    );
    
    if ($result['done']) {
        echo "Download complete: " . $result['file_path'];
        break;
    }
}
```

### 3. Cleanup

```php
// Clean up temporary chunks (keep final file)
$downloader->cleanup(true);

// Or remove everything including final file
$downloader->cleanup(false);
```

## Configuration

### Constructor Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `package_id` | string | Yes | - | Unique identifier for the package |
| `remote_url` | string | Yes | - | Remote file URL to download |
| `chunk_size` | int | No | 5MB | Download chunk size in bytes |

### Example with Custom Chunk Size

```php
$downloader = new WORRPB_Dummy_Pack_Downloader([
    'package_id' => 'large-pack',
    'remote_url' => 'https://cdn.example.com/pack.zip',
    'chunk_size' => 10 * 1024 * 1024, // 10MB chunks for faster downloads
]);
```

## Download Location

Files are downloaded to:
```
wp-content/uploads/worry-proof-backup-zip/chunks/{package_id}/
```

Final merged file:
```
wp-content/uploads/worry-proof-backup-zip/{package_id}.zip
```

## Progress Tracking

### Get Current Progress

```php
$progress = $downloader->getProgress();

/*
Array (
    [package_id] => my-pack-123
    [status] => downloading
    [total_size] => 104857600
    [downloaded_size] => 52428800
    [progress] => 50.00
    [current_offset] => 52428800
    [file_path] => /path/to/uploads/worry-proof-backup-zip/my-pack-123.zip
)
*/
```

### Check if Complete

```php
if ($downloader->isComplete()) {
    $file_path = $downloader->getFinalFilePath();
    echo "File ready at: $file_path";
}
```

## Status Values

| Status | Description |
|--------|-------------|
| `pending` | Download not yet started |
| `downloading` | Currently downloading chunks |
| `merging` | Merging chunks into final file |
| `completed` | Download complete and verified |
| `failed` | Download failed (check error) |

## Return Values

### startDownload()

Success:
```php
[
    'success' => true,
    'status' => 'downloading',
    'total_size' => 104857600,
    'downloaded_size' => 0,
    'progress' => 0,
    'package_id' => 'my-pack-123',
]
```

Error:
```php
WP_Error {
    'code' => 'remote_head_failed',
    'message' => 'Failed to get remote file info: ...',
}
```

### processStep()

Success (in progress):
```php
[
    'done' => false,
    'status' => 'downloading',
    'total_size' => 104857600,
    'downloaded_size' => 52428800,
    'progress' => 50.00,
    'chunk_count' => 10,
]
```

Success (completed):
```php
[
    'done' => true,
    'status' => 'completed',
    'total_size' => 104857600,
    'downloaded_size' => 104857600,
    'progress' => 100,
    'file_path' => '/path/to/final.zip',
]
```

## Ajax Integration

### PHP Handler

```php
add_action('wp_ajax_download_dummy_pack', function() {
    check_ajax_referer('my_nonce', 'nonce');
    
    $package_id = sanitize_key($_POST['package_id']);
    $signed_url = esc_url_raw($_POST['signed_url']);
    $action = sanitize_text_field($_POST['download_action']);
    
    try {
        $downloader = new WORRPB_Dummy_Pack_Downloader([
            'package_id' => $package_id,
            'remote_url' => $signed_url,
        ]);
        
        if ($action === 'start') {
            $result = $downloader->startDownload();
        } else {
            $result = $downloader->processStep();
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }
        
        if ($result['done']) {
            $downloader->cleanup(true);
        }
        
        wp_send_json_success($result);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});
```

### JavaScript Client

```javascript
async function downloadDummyPack(packageId, signedUrl) {
    // Start download
    let response = await fetch(ajaxurl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'download_dummy_pack',
            nonce: myNonce,
            package_id: packageId,
            signed_url: signedUrl,
            download_action: 'start',
        }),
    });
    
    let data = await response.json();
    if (!data.success) throw new Error(data.data.message);
    
    // Process chunks
    while (!data.data.done) {
        response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'download_dummy_pack',
                nonce: myNonce,
                package_id: packageId,
                signed_url: signedUrl,
                download_action: 'process',
            }),
        });
        
        data = await response.json();
        if (!data.success) throw new Error(data.data.message);
        
        // Update UI
        updateProgress(data.data.progress);
        
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    
    return data.data.file_path;
}
```

## Error Handling

All errors are returned as `WP_Error` objects or thrown as `Exception`:

```php
$result = $downloader->processStep();

if (is_wp_error($result)) {
    $code = $result->get_error_code();
    $message = $result->get_error_message();
    
    switch ($code) {
        case 'download_chunk_failed':
            // Handle network error
            break;
        case 'size_mismatch':
            // Handle verification error
            break;
        default:
            // Handle other errors
            break;
    }
}
```

### Common Error Codes

| Code | Description | Retry? |
|------|-------------|--------|
| `remote_head_failed` | Can't fetch file info | Yes |
| `download_chunk_failed` | Chunk download failed | Yes |
| `chunk_save_failed` | Can't write chunk to disk | No |
| `size_mismatch` | File size verification failed | No |
| `mkdir_failed` | Can't create directory | No |
| `invalid_state` | startDownload() not called | No |

## Resume Support

Downloads automatically resume if interrupted:

```php
// First attempt (interrupted)
$downloader = new WORRPB_Dummy_Pack_Downloader([...]);
$downloader->startDownload();
$downloader->processStep(); // Downloads chunk 0
$downloader->processStep(); // Downloads chunk 1
// Script times out...

// Second attempt (resumes automatically)
$downloader = new WORRPB_Dummy_Pack_Downloader([...]); // Same package_id
$result = $downloader->processStep(); // Resumes from chunk 2
```

Progress is saved in:
```
wp-content/uploads/worry-proof-backup-zip/chunks/{package_id}/__download-progress.json
```

## WP-CLI Usage

```bash
# Download via CLI
wp eval-file download-pack.php package_id "https://example.com/pack.zip?sig=abc"
```

## Performance Tips

### Optimize Chunk Size

- **Slow connections**: Use smaller chunks (2-5MB)
- **Fast connections**: Use larger chunks (10-20MB)
- **Shared hosting**: Use smaller chunks to avoid timeouts

```php
$chunk_size = wp_doing_ajax() 
    ? 5 * 1024 * 1024   // 5MB for Ajax
    : 20 * 1024 * 1024; // 20MB for CLI

$downloader = new WORRPB_Dummy_Pack_Downloader([
    'chunk_size' => $chunk_size,
    // ...
]);
```

### Limit Concurrent Downloads

Only download one package at a time to avoid overwhelming the server:

```php
// Check if any download is in progress
$upload_dir = wp_upload_dir();
$chunks_dir = $upload_dir['basedir'] . '/worry-proof-backup-zip/chunks/';

if (is_dir($chunks_dir)) {
    $active_downloads = glob($chunks_dir . '*/__download-progress.json');
    if (count($active_downloads) > 0) {
        wp_send_json_error(['message' => 'Another download is in progress']);
    }
}
```

## Testing

```php
// Test with small file
$downloader = new WORRPB_Dummy_Pack_Downloader([
    'package_id' => 'test-pack',
    'remote_url' => 'https://example.com/test.zip',
    'chunk_size' => 1024 * 1024, // 1MB chunks for testing
]);

$start = $downloader->startDownload();
assert(!is_wp_error($start));

$step = $downloader->processStep();
assert(!is_wp_error($step));
assert($step['progress'] > 0);

// Test resume
$progress_before = $downloader->getProgress();
$downloader2 = new WORRPB_Dummy_Pack_Downloader([
    'package_id' => 'test-pack',
    'remote_url' => 'https://example.com/test.zip',
]);
$progress_after = $downloader2->getProgress();
assert($progress_before['current_offset'] === $progress_after['current_offset']);
```

## Security Considerations

1. **Validate URLs**: Always use signed URLs with expiration
2. **Check Permissions**: Verify user capabilities before downloads
3. **Nonce Verification**: Use nonces for Ajax requests
4. **Sanitize Input**: Sanitize all user input
5. **Disk Space**: Check available disk space before starting

```php
// Check disk space
$required_space = 100 * 1024 * 1024; // 100MB
$upload_dir = wp_upload_dir();
$free_space = disk_free_space($upload_dir['basedir']);

if ($free_space < $required_space) {
    wp_send_json_error(['message' => 'Insufficient disk space']);
}
```

## Troubleshooting

### Downloads Keep Failing

1. Check server timeout settings (increase `max_execution_time`)
2. Reduce chunk size
3. Check available disk space
4. Verify URL is accessible
5. Check file permissions on uploads directory

### Progress Not Updating

1. Ensure progress file is writable
2. Check if chunks directory exists
3. Verify no permission issues

### File Size Mismatch

1. Server may not support range requests
2. URL may have expired
3. File may have changed on server

### Memory Issues

The downloader streams directly to disk, so memory usage should be minimal. If you experience issues:

1. Reduce chunk size
2. Increase PHP memory limit
3. Check for other plugins consuming memory

## Support

For issues or questions, check the example usage file:
```
inc/libs/dummy-pack/example-usage.php
```

## License

Part of Worry Proof Backup plugin. All rights reserved.

