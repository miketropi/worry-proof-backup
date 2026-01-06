# Updated Ajax Handler - Usage Guide

## ✅ What Changed

The `worrprba_ajax_download_dummy_pack()` function now supports **three-step download process** with automatic progression:

### Step Flow

```
1. init → Get signed URL
   ↓
2. start → Initialize download
   ↓
3. downloading → Download chunks (loop until done)
   ↓
4. completed → next_step: true (proceed to next install step)
```

## 📤 Request Format

### Step 1: Get Signed URL

```javascript
{
  action: 'worrprba_ajax_download_dummy_pack',
  installNonce: nonce,
  payload: {
    ID: 'package-123',
    download_step: 'init'
  }
}
```

**Response:**
```javascript
{
  success: true,
  data: {
    download_step: 'start',
    signed_url: 'https://cdn.example.com/pack.zip?signature=...',
    next_step: false
  }
}
```

---

### Step 2: Start Download

```javascript
{
  action: 'worrprba_ajax_download_dummy_pack',
  installNonce: nonce,
  payload: {
    ID: 'package-123',
    signed_url: 'https://cdn.example.com/pack.zip?signature=...',
    download_step: 'start'
  }
}
```

**Response:**
```javascript
{
  success: true,
  data: {
    download_step: 'downloading',
    status: 'downloading',
    total_size: 104857600,
    downloaded_size: 0,
    progress: 0,
    package_id: 'package-123',
    next_step: false
  }
}
```

---

### Step 3: Download Chunks (Loop)

```javascript
{
  action: 'worrprba_ajax_download_dummy_pack',
  installNonce: nonce,
  payload: {
    ID: 'package-123',
    signed_url: 'https://cdn.example.com/pack.zip?signature=...',
    download_step: 'downloading'
  }
}
```

**Response (In Progress):**
```javascript
{
  success: true,
  data: {
    done: false,
    download_step: 'downloading',
    status: 'downloading',
    total_size: 104857600,
    downloaded_size: 52428800,
    progress: 50.00,
    chunk_count: 10,
    next_step: false  // ← Continue looping
  }
}
```

**Response (Complete):**
```javascript
{
  success: true,
  data: {
    done: true,
    download_step: 'completed',
    status: 'completed',
    total_size: 104857600,
    downloaded_size: 104857600,
    progress: 100,
    file_path: '/path/to/package-123.zip',
    next_step: true  // ← Move to next install step!
  }
}
```

---

## 🎯 Frontend JavaScript Integration

### Update `src/util/dummyPackLib.js`

```javascript
/**
 * Download dummy pack with chunked download support
 * @param {Object} payload - Pack data with ID
 * @returns {Promise<Object>} Download result
 */
export const downloadDummyPack = async (payload) => {
  const { ID } = payload;
  
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
  let downloadData = response.data.payload;
  
  while (!downloadData.next_step) {
    // Update progress (you can emit events here for UI updates)
    console.log(`Download progress: ${downloadData.progress}%`);
    
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
    
    downloadData = response.data.payload;
    
    // Small delay to prevent server overload
    await new Promise(resolve => setTimeout(resolve, 100));
  }
  
  return downloadData;
};
```

---

### Update `src/components/dummy-pack/InstallProcess.jsx`

```jsx
const installProcessHandler = async (process) => {
  const { action, payload } = process;
  
  if (action === 'worrprba_ajax_download_dummy_pack') {
    try {
      // Use new chunked download with progress
      const result = await downloadDummyPack(payload);
      
      // result.next_step will be true when download is complete
      if (result.next_step) {
        setInstallProcessInProgressStep(inProgressStep + 1);
      }
      
    } catch (error) {
      setError(error.message);
    }
  } else {
    // Handle other install steps
    const response = await doInstallProcess(process);
    
    if (response.success && response.data.payload.next_step) {
      setInstallProcessInProgressStep(inProgressStep + 1);
    } else {
      setError(response.data?.error_message || 'Install failed');
    }
  }
};
```

---

### Alternative: Simple Implementation

If you want to keep the existing pattern from other install steps:

```javascript
// In InstallProcess.jsx
const installProcessHandler = async (process) => {
  const response = await doInstallProcess(process);
  
  if (response.success) {
    const { next_step } = response.data.payload;
    
    if (next_step) {
      // Move to next step
      setInstallProcessInProgressStep(inProgressStep + 1);
    } else {
      // Continue current step (for chunked downloads)
      // Re-call same step until next_step becomes true
      setTimeout(() => installProcessHandler(process), 100);
    }
  } else {
    setError(response.data?.error_message || 'Failed');
  }
};
```

---

## 📊 Response Fields Explanation

| Field | Type | Description |
|-------|------|-------------|
| `download_step` | string | Current step: 'init', 'start', 'downloading', 'completed' |
| `next_step` | boolean | **false**: Continue same step, **true**: Move to next install step |
| `done` | boolean | true when download is 100% complete |
| `status` | string | 'pending', 'downloading', 'completed', 'failed' |
| `progress` | number | Download percentage (0-100) |
| `total_size` | number | Total file size in bytes |
| `downloaded_size` | number | Downloaded bytes so far |
| `chunk_count` | number | Current chunk number |
| `file_path` | string | Final file path (only when complete) |
| `signed_url` | string | Remote URL (only in init response) |

---

## 🔄 Complete Flow Example

```javascript
// Complete frontend implementation
async function installDummyPack(packData) {
  const { ID } = packData;
  let signed_url = null;
  let download_step = 'init';
  
  while (true) {
    const response = await fetch(ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'worrprba_ajax_download_dummy_pack',
        installNonce: nonce,
        'payload[ID]': ID,
        'payload[download_step]': download_step,
        ...(signed_url && { 'payload[signed_url]': signed_url }),
      }),
    });
    
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.data.error_message);
    }
    
    const result = data.data.payload;
    
    // Update UI with progress
    if (result.progress !== undefined) {
      updateProgressBar(result.progress);
    }
    
    // Check if we should move to next install step
    if (result.next_step) {
      console.log('Download complete!', result.file_path);
      return result; // Proceed to next install step
    }
    
    // Update for next iteration
    download_step = result.download_step;
    if (result.signed_url) {
      signed_url = result.signed_url;
    }
    
    // Small delay
    await new Promise(r => setTimeout(r, 100));
  }
}
```

---

## ⚠️ Error Handling

### Error Response Format

```javascript
{
  success: false,
  data: {
    error_code: 'download_chunk_failed',
    error_message: 'Failed to download chunk: Network error'
  }
}
```

### Common Error Codes

| Code | Description | Action |
|------|-------------|--------|
| `invalid_package_id` | Package ID missing | Show error to user |
| `failed_to_fetch_signed_url` | Can't get signed URL | Retry or show error |
| `invalid_signed_url` | Signed URL is invalid | Contact support |
| `missing_signed_url` | Signed URL not provided | Bug - check frontend |
| `download_chunk_failed` | Network error | Retry download |
| `exception` | Unexpected error | Show error message |

---

## ✅ Key Features

✅ **Automatic progression** - `next_step: true` signals completion  
✅ **Progress tracking** - Real-time `progress` percentage  
✅ **Resume support** - Automatically resumes on failure  
✅ **Error handling** - Comprehensive error messages  
✅ **Consistent API** - Matches other install steps pattern  

---

## 🎉 Summary

The updated function now:

1. ✅ Handles all three download steps (init → start → downloading)
2. ✅ Returns `next_step: false` while downloading
3. ✅ Returns `next_step: true` when download is complete
4. ✅ Automatically cleans up chunks after completion
5. ✅ Provides progress tracking throughout
6. ✅ Follows the same pattern as other install steps

**You can now use it in your install process just like other steps!**

