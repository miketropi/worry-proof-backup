# 🚀 Quick Reference Card

## Installation

```php
require_once WORRPRBA_PLUGIN_PATH . 'inc/libs/dummy-pack/download-dummy-pack.php';
```

## Basic Usage

```php
// Create downloader
$downloader = new WORRPB_Dummy_Pack_Downloader([
    'package_id' => 'my-pack-123',
    'remote_url' => 'https://example.com/pack.zip',
    'chunk_size' => 5 * 1024 * 1024, // Optional, defaults to 5MB
]);

// Start download
$result = $downloader->startDownload();

// Process chunks in a loop
while (true) {
    $result = $downloader->processStep();
    
    if (is_wp_error($result)) {
        echo $result->get_error_message();
        break;
    }
    
    echo "Progress: {$result['progress']}%\n";
    
    if ($result['done']) break;
}

// Clean up
$downloader->cleanup(true); // Keep final file
```

## Common Patterns

### Ajax Handler

```php
add_action('wp_ajax_download_pack', function() {
    check_ajax_referer('nonce', 'security');
    
    $downloader = new WORRPB_Dummy_Pack_Downloader([
        'package_id' => $_POST['package_id'],
        'remote_url' => $_POST['url'],
    ]);
    
    if ($_POST['action_type'] === 'start') {
        $result = $downloader->startDownload();
    } else {
        $result = $downloader->processStep();
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    if ($result['done']) {
        $downloader->cleanup(true);
    }
    
    wp_send_json_success($result);
});
```

### JavaScript Client

```javascript
async function download(packageId, url) {
    // Start
    let res = await fetch(ajaxurl, {
        method: 'POST',
        body: new URLSearchParams({
            action: 'download_pack',
            security: nonce,
            package_id: packageId,
            url: url,
            action_type: 'start',
        }),
    });
    let data = await res.json();
    
    // Process
    while (!data.data.done) {
        console.log(`${data.data.progress}%`);
        
        res = await fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'download_pack',
                security: nonce,
                package_id: packageId,
                url: url,
                action_type: 'process',
            }),
        });
        data = await res.json();
        
        await new Promise(r => setTimeout(r, 100));
    }
    
    return data.data.file_path;
}
```

## Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `startDownload()` | `array\|WP_Error` | Initialize download |
| `processStep()` | `array\|WP_Error` | Download one chunk |
| `getProgress()` | `array` | Get current progress |
| `isComplete()` | `bool` | Check if done |
| `getFinalFilePath()` | `string\|null` | Get file path |
| `cleanup($keep)` | `void` | Remove temp files |

## Return Values

### Success Response
```php
[
    'done' => false,               // true when complete
    'status' => 'downloading',     // pending, downloading, completed, failed
    'progress' => 45.67,           // Percentage (0-100)
    'total_size' => 104857600,     // Bytes
    'downloaded_size' => 47185920, // Bytes
    'chunk_count' => 9,            // Current chunk number
]
```

### Error Response
```php
WP_Error {
    'code' => 'download_chunk_failed',
    'message' => 'Failed to download chunk: ...',
}
```

## Error Codes

| Code | Retry? | Description |
|------|--------|-------------|
| `remote_head_failed` | ✅ | Can't get file size |
| `download_chunk_failed` | ✅ | Network error |
| `chunk_save_failed` | ❌ | Disk write error |
| `size_mismatch` | ❌ | File verification failed |
| `mkdir_failed` | ❌ | Can't create directory |
| `invalid_state` | ❌ | Not initialized |

## File Locations

```
wp-content/uploads/worry-proof-backup-zip/
├── chunks/
│   └── {package-id}/
│       ├── part-0
│       ├── part-1
│       ├── part-N
│       └── __download-progress.json
└── {package-id}.zip (final file)
```

## Configuration

```php
// Small files, fast network
'chunk_size' => 10 * 1024 * 1024  // 10MB

// Large files, slow network
'chunk_size' => 2 * 1024 * 1024   // 2MB

// Shared hosting (avoid timeouts)
'chunk_size' => 1 * 1024 * 1024   // 1MB

// CLI/Cron (more aggressive)
'chunk_size' => 20 * 1024 * 1024  // 20MB
```

## Debugging

```php
// Get progress
$progress = $downloader->getProgress();
error_log(print_r($progress, true));

// Check if complete
if ($downloader->isComplete()) {
    error_log('Download complete: ' . $downloader->getFinalFilePath());
}

// Check files
$upload_dir = wp_upload_dir();
$chunks_dir = $upload_dir['basedir'] . '/worry-proof-backup-zip/chunks/';
error_log('Chunks: ' . print_r(scandir($chunks_dir), true));
```

## Testing

```bash
# Run unit tests
wp eval-file inc/libs/dummy-pack/test-download-dummy-pack.php

# Test with small file
wp eval 'require_once "inc/libs/dummy-pack/download-dummy-pack.php"; 
$d = new WORRPB_Dummy_Pack_Downloader([
    "package_id" => "test",
    "remote_url" => "https://httpbin.org/bytes/1024"
]);
$d->startDownload();
while(true){$r=$d->processStep();if($r["done"])break;}
echo $d->getFinalFilePath();'
```

## Tips

✅ **Always use signed URLs** with expiration  
✅ **Verify user capabilities** before downloads  
✅ **Clean up after completion** with `cleanup(true)`  
✅ **Show progress** to users for better UX  
✅ **Handle errors gracefully** with user messages  
✅ **Monitor disk space** before starting large downloads  
✅ **Use appropriate chunk size** for your server  

## Common Issues

**"Upload directory not found"**
```php
// Check permissions
ls -la wp-content/uploads/
```

**"Failed to download chunk"**
```php
// Test URL manually
curl -I "https://example.com/file.zip"
```

**"Size mismatch"**
```php
// Clear and retry
$downloader->cleanup(false);
$downloader->startDownload();
```

## Documentation

| File | Purpose |
|------|---------|
| `README.md` | Full API documentation |
| `INTEGRATION.md` | Integration guide |
| `ARCHITECTURE.md` | System design |
| `SUMMARY.md` | Feature overview |
| `QUICK-REFERENCE.md` | This file |
| `example-usage.php` | Code examples |

## Support Matrix

| PHP Version | WordPress | Status |
|-------------|-----------|--------|
| 7.4+ | 5.9+ | ✅ Fully supported |
| 7.2-7.3 | 5.6+ | ⚠️ Should work |
| < 7.2 | Any | ❌ Not supported |

## Performance

| File Size | Traditional | This Class |
|-----------|-------------|------------|
| 10MB | ✅ | ✅ |
| 50MB | ⚠️ | ✅ |
| 100MB | ❌ | ✅ |
| 500MB+ | ❌ | ✅ |

## Security Checklist

- [x] Input sanitization
- [x] URL validation
- [x] Nonce verification
- [x] Capability checks
- [x] Path traversal prevention
- [x] Safe error messages
- [x] Signed URLs support

---

**Need more details?** Read the full `README.md`

**Need help integrating?** Check `INTEGRATION.md`

**Want to understand the design?** See `ARCHITECTURE.md`

