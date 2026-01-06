# Integration Guide: Dummy Pack Downloader

This guide shows how to integrate the new `WORRPB_Dummy_Pack_Downloader` class into your existing Worry Proof Backup plugin's Dummy Pack Center.

## 📁 Files Created

1. **`inc/libs/dummy-pack/download-dummy-pack.php`** - Main downloader class
2. **`inc/libs/dummy-pack/example-usage.php`** - Usage examples (reference only)
3. **`inc/libs/dummy-pack/README.md`** - Complete documentation
4. **`inc/libs/dummy-pack/test-download-dummy-pack.php`** - Unit tests

## 🔧 Integration Steps

### Step 1: Load the Class

Add to your main plugin file or init function:

```php
// In worry-proof-backup.php or inc/dummy-center.php
require_once WORRPRBA_PLUGIN_PATH . 'inc/libs/dummy-pack/download-dummy-pack.php';
```

### Step 2: Update Ajax Handler

Modify `inc/dummy-center.php` to use the new downloader:

```php
/**
 * Modified version of worrprba_ajax_download_dummy_pack
 * Now supports chunked downloads with progress tracking
 */
function worrprba_ajax_download_dummy_pack() {
    check_ajax_referer('worrprba_dummy_pack_center_nonce', 'installNonce');

    $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : array();
    $package_id = isset($payload['ID']) ? sanitize_key($payload['ID']) : '';
    $download_step = isset($payload['download_step']) ? sanitize_text_field($payload['download_step']) : 'init';

    // Step 1: Get signed URL from remote server
    if ($download_step === 'init') {
        $response = worrprba_dummy_pack_get_signed_url($package_id);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'error_code' => 'failed_to_fetch_signed_url',
                'error_message' => $response->get_error_message(),
            ));
        }

        // Extract signed URL from response
        $signed_url = isset($response['signed_url']) ? $response['signed_url'] : '';
        
        if (empty($signed_url)) {
            wp_send_json_error(array(
                'error_code' => 'invalid_signed_url',
                'error_message' => __('Invalid signed URL received.', 'worry-proof-backup'),
            ));
        }

        wp_send_json_success(array(
            'download_step' => 'start',
            'signed_url' => $signed_url,
        ));
    }

    // Step 2: Start chunked download
    if ($download_step === 'start') {
        $signed_url = isset($payload['signed_url']) ? esc_url_raw($payload['signed_url']) : '';

        if (empty($signed_url)) {
            wp_send_json_error(array(
                'error_code' => 'missing_signed_url',
                'error_message' => __('Signed URL is required.', 'worry-proof-backup'),
            ));
        }

        try {
            $downloader = new WORRPB_Dummy_Pack_Downloader(array(
                'package_id' => $package_id,
                'remote_url' => $signed_url,
                'chunk_size' => 5 * 1024 * 1024, // 5MB chunks
            ));

            $result = $downloader->startDownload();

            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'error_code' => $result->get_error_code(),
                    'error_message' => $result->get_error_message(),
                ));
            }

            $result['download_step'] = 'downloading';
            wp_send_json_success($result);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'error_code' => 'exception',
                'error_message' => $e->getMessage(),
            ));
        }
    }

    // Step 3: Continue downloading chunks
    if ($download_step === 'downloading') {
        $signed_url = isset($payload['signed_url']) ? esc_url_raw($payload['signed_url']) : '';

        try {
            $downloader = new WORRPB_Dummy_Pack_Downloader(array(
                'package_id' => $package_id,
                'remote_url' => $signed_url,
            ));

            $result = $downloader->processStep();

            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'error_code' => $result->get_error_code(),
                    'error_message' => $result->get_error_message(),
                ));
            }

            // If download complete, clean up chunks
            if ($result['done'] && $result['status'] === 'completed') {
                $downloader->cleanup(true); // Keep final file
                $result['download_step'] = 'completed';
            } else {
                $result['download_step'] = 'downloading';
            }

            wp_send_json_success($result);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'error_code' => 'exception',
                'error_message' => $e->getMessage(),
            ));
        }
    }

    wp_send_json_error(__('Invalid download step.', 'worry-proof-backup'));
}
```

### Step 3: Update Frontend JavaScript

Modify `src/util/dummyPackLib.js` to handle the new download process:

```javascript
/**
 * Download dummy pack with chunked download support
 * @param {Object} process - Process object containing action and payload
 * @returns {Promise} Download result
 */
export const downloadDummyPack = async (packData) => {
    const { ID } = packData;
    
    // Step 1: Get signed URL
    let response = await doInstallProcess({
        action: 'worrprba_ajax_download_dummy_pack',
        payload: {
            ID,
            download_step: 'init',
        },
    });
    
    if (!response.success) {
        throw new Error(response.data?.error_message || 'Failed to get signed URL');
    }
    
    const { signed_url } = response.data.payload;
    
    // Step 2: Start download
    response = await doInstallProcess({
        action: 'worrprba_ajax_download_dummy_pack',
        payload: {
            ID,
            signed_url,
            download_step: 'start',
        },
    });
    
    if (!response.success) {
        throw new Error(response.data?.error_message || 'Failed to start download');
    }
    
    // Step 3: Download chunks until complete
    let downloadComplete = false;
    let progressData = response.data.payload;
    
    while (!downloadComplete) {
        // Update progress UI (you can emit events or call callbacks here)
        console.log(`Download progress: ${progressData.progress}%`);
        
        response = await doInstallProcess({
            action: 'worrprba_ajax_download_dummy_pack',
            payload: {
                ID,
                signed_url,
                download_step: 'downloading',
            },
        });
        
        if (!response.success) {
            throw new Error(response.data?.error_message || 'Download failed');
        }
        
        progressData = response.data.payload;
        downloadComplete = (progressData.download_step === 'completed');
        
        // Small delay to prevent overwhelming server
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    
    return progressData;
};
```

### Step 4: Update Install Process Component

Modify `src/components/dummy-pack/InstallProcess.jsx` to show download progress:

```jsx
import { useState, useEffect } from 'react';
import { downloadDummyPack } from '../../util/dummyPackLib';

const installProcessHandler = async (process) => {
    const { action, payload } = process;
    
    if (action === 'worrprba_ajax_download_dummy_pack') {
        try {
            // Use new chunked download
            const result = await downloadDummyPack(payload);
            
            setInstallProcessInProgressStep(inProgressStep + 1);
        } catch (error) {
            setError(error.message);
        }
    } else {
        // Handle other install steps
        const response = await doInstallProcess(process);
        
        if (response.success) {
            setInstallProcessInProgressStep(inProgressStep + 1);
        } else {
            setError(response.data?.error_message || 'Install failed');
        }
    }
};
```

### Step 5: Add Progress Display (Optional)

Add a progress bar to show download progress:

```jsx
const [downloadProgress, setDownloadProgress] = useState(0);

// In downloadDummyPack, add progress callback:
const onProgress = (progress) => {
    setDownloadProgress(progress.progress);
};

// In your JSX:
{isCurrent && inProgress && process[inProgressStep].action === 'worrprba_ajax_download_dummy_pack' && (
    <div className="tw-mt-2">
        <div className="tw-w-full tw-bg-gray-200 tw-rounded-full tw-h-2">
            <div 
                className="tw-bg-blue-600 tw-h-2 tw-rounded-full tw-transition-all tw-duration-300"
                style={{ width: `${downloadProgress}%` }}
            />
        </div>
        <p className="tw-text-xs tw-text-gray-500 tw-mt-1">
            {downloadProgress.toFixed(1)}% downloaded
        </p>
    </div>
)}
```

## 🎯 Process Flow

```
1. User clicks "Install Pack"
   ↓
2. Frontend: Request signed URL (download_step: 'init')
   ↓
3. Backend: Get signed URL from remote server
   ↓
4. Frontend: Start download (download_step: 'start')
   ↓
5. Backend: Initialize downloader, get file size
   ↓
6. Frontend: Loop - download chunks (download_step: 'downloading')
   ↓
7. Backend: Download chunk, update progress
   ↓
8. Repeat step 6-7 until done
   ↓
9. Backend: Merge chunks, verify file
   ↓
10. Frontend: Proceed to next install step
```

## 📊 Benefits Over Old Approach

| Feature | Old Method | New Method |
|---------|-----------|------------|
| **Large Files** | ❌ Timeout issues | ✅ Handles any size |
| **Progress** | ❌ No feedback | ✅ Real-time progress |
| **Resume** | ❌ Start over | ✅ Auto-resume |
| **Memory** | ❌ Loads full file | ✅ Streams to disk |
| **Reliability** | ❌ Network issues fail | ✅ Retry chunks |
| **User Experience** | ❌ Hanging UI | ✅ Progress feedback |

## 🧪 Testing

Run the test suite to verify installation:

```bash
wp eval-file wp-content/plugins/worry-proof-backup/inc/libs/dummy-pack/test-download-dummy-pack.php
```

Expected output:
```
========================================
WORRPB_Dummy_Pack_Downloader Test Suite
========================================

Test: Constructor Validation
  ✅ PASS: Correctly validates missing package_id
  ✅ PASS: Correctly validates invalid URL
  ✅ PASS: Accepts valid parameters

Test: Directory Creation
  ✅ PASS: Successfully creates download directory

Test: Progress Tracking
  ✅ PASS: Initial status is pending
  ✅ PASS: Status changes to downloading after start
  ✅ PASS: Total size is set correctly

Test: Cleanup
  ✅ PASS: Successfully removes chunks directory
  ✅ PASS: Successfully removes final file when keep_final=false

========================================
Tests: 8 passed, 0 failed
========================================
```

## 🔍 Debugging

Enable debug logging:

```php
add_action('worrprba_dummy_pack_download_progress', function($progress) {
    error_log('Download progress: ' . print_r($progress, true));
});
```

Check download directory:
```bash
# List chunks
ls -lh wp-content/uploads/worry-proof-backup-zip/chunks/

# Check progress file
cat wp-content/uploads/worry-proof-backup-zip/chunks/PACKAGE_ID/__download-progress.json
```

## 🚨 Error Handling

Common issues and solutions:

1. **"Upload directory not found"**
   - Check `wp-content/uploads` permissions
   - Verify `wp_upload_dir()` returns valid path

2. **"Failed to download chunk"**
   - Check signed URL hasn't expired
   - Verify server supports range requests
   - Check firewall/security settings

3. **"Size mismatch"**
   - File may have changed on server
   - Incomplete chunk download
   - Solution: Clear chunks and retry

## 📝 Migration Checklist

- [ ] Back up existing code
- [ ] Load new class file
- [ ] Update Ajax handler
- [ ] Update JavaScript download function
- [ ] Add progress display (optional)
- [ ] Run tests
- [ ] Test with small file first
- [ ] Test with large file (>100MB)
- [ ] Test interrupted download resume
- [ ] Update documentation
- [ ] Deploy to production

## 🎉 Next Steps

After integration:

1. Monitor error logs for any issues
2. Collect user feedback on download experience
3. Adjust chunk size based on server performance
4. Consider adding download speed display
5. Add cleanup cron job for abandoned downloads

## 📚 Additional Resources

- Main class: `inc/libs/dummy-pack/download-dummy-pack.php`
- Full documentation: `inc/libs/dummy-pack/README.md`
- Usage examples: `inc/libs/dummy-pack/example-usage.php`
- Tests: `inc/libs/dummy-pack/test-download-dummy-pack.php`

---

**Questions or Issues?**
Check the README.md file or review the example-usage.php for more detailed examples.

