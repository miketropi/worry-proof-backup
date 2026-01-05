const { ajax_url, nonce } = worrprba_php_data;

export const __request = async (url, options) => {
  const response = await fetch(url, options);

  if (!response.ok) {
    // throw new Error(`HTTP error! status: ${response.status}`);
    return {
      success: false,
      data: `HTTP error! status: ${response.status}`,
    };
  }

  // Check if the response is JSON
  return response.json();
};

export const __ajax = async (action, data = {}, type = 'POST') => {
  return await jQuery.ajax({
    url: ajax_url,
    type,
    dataType: 'json',
    data: {
      action,
      ...data,
      nonce: nonce.worrprba_nonce,
    },
  })
};

export const getBackups = async () => {
  return await __ajax('worrprba_ajax_get_backups');
};

export const doBackupProcess = async (process) => {
  const { action, payload } = process;

  const response = await __request(ajax_url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action,
      ...Object.fromEntries(Object.entries(payload).map(([key, value]) => [`payload[${key}]`, value])),
      nonce: nonce.worrprba_nonce,
    }),
  });

  return response;
}

/**
 * Delete backup folder
 * @param {string} name_folder
 * @returns {Promise<boolean>}
 */
export const deleteBackupFolder = async (name_folder) => {
  return await __ajax('worrprba_ajax_delete_backup_folder', {
    payload: { name_folder }
  });
}

/**
 * Returns a human-friendly relative time string for a given date, using the provided server current datetime.
 * Examples: "just now", "1m ago", "5h ago", "yesterday", "3d ago", "2w ago", "Mar 5", "Mar 5, 2023"
 * @param {string|Date|number} inputDatetime - The date to format.
 * @param {string|Date|number} serverCurrentDatetime - The current datetime from the server.
 * @returns {string}
 */
export function friendlyDateTime(inputDatetime, serverCurrentDatetime) {
  const now = serverCurrentDatetime instanceof Date
    ? serverCurrentDatetime
    : new Date(serverCurrentDatetime);
  const date = inputDatetime instanceof Date
    ? inputDatetime
    : new Date(inputDatetime);

  if (isNaN(date.getTime()) || isNaN(now.getTime())) return "";

  const diffMs = now - date;
  const diffSec = Math.floor(diffMs / 1000);
  const diffMin = Math.floor(diffSec / 60);
  const diffHr = Math.floor(diffMin / 60);
  const diffDay = Math.floor(diffHr / 24);
  const diffWk = Math.floor(diffDay / 7);

  if (diffSec < 60) return "just now";
  if (diffMin < 60) return `${diffMin}m ago`;
  if (diffHr < 24) return `${diffHr}h ago`;
  if (diffDay === 1) return "yesterday";
  if (diffDay < 7) return `${diffDay}d ago`;
  if (diffWk < 4) return `${diffWk}w ago`;

  // If this year, show "Mar 5"
  const options = { month: "short", day: "numeric" };
  if (date.getFullYear() === now.getFullYear()) {
    return date.toLocaleDateString(undefined, options);
  }
  // Else, show "Mar 5, 2023"
  return date.toLocaleDateString(undefined, { ...options, year: "numeric" });
}

export const doRestoreProcess = async (process) => {
  const { action, payload } = process;

  const response = await __request(ajax_url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action,
      ...Object.fromEntries(Object.entries(payload).map(([key, value]) => [`payload[${key}]`, value])),
      nonce: nonce.worrprba_nonce,
      wp_restore_nonce: nonce.wp_restore_nonce,
    }),
  });

  return response;
}

export const sendReportEmail = async (payload) => {
  return await __ajax('worrprba_ajax_send_report_email', {
    payload,
  });
};

export const uploadFileWithProgress = (file, onProgress) => {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    const formData = new FormData();

    formData.append('action', 'worrprba_ajax_upload_backup_file');
    formData.append('nonce', nonce.worrprba_nonce);
    formData.append('file', file);

    xhr.open('POST', ajax_url);

    // Handle upload progress
    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        const percent = Math.round((event.loaded / event.total) * 100);
        onProgress(percent);
      }
    };

    xhr.onload = () => {
      if (xhr.status === 200) {
        const res = JSON.parse(xhr.responseText);
        if (res.success) resolve(res);
        else reject(res.data || 'Unknown error');
      } else {
        reject('Upload failed');
      }
    };

    xhr.onerror = () => reject('Upload error');
    xhr.send(formData);
  });
};

export const getBackupDownloadZipPath = async (folder_name) => {
  const response = await __request(ajax_url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'worrprba_ajax_get_backup_download_zip_path',
      ...Object.fromEntries(Object.entries({ folder_name }).map(([key, value]) => [`payload[${key}]`, value])),
      nonce: nonce.worrprba_nonce,
    }),
  });

  return response;
};

export const createBackupZip = async (folder_name) => {
  const response = await __request(ajax_url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'worrprba_ajax_create_backup_zip',
      ...Object.fromEntries(Object.entries({ folder_name }).map(([key, value]) => [`payload[${key}]`, value])),
      nonce: nonce.worrprba_nonce,
    }),
  });

  return response;
};

export const saveBackupScheduleConfig = async (payload) => {
  return await __ajax('worrprba_ajax_save_backup_schedule_config', {
    payload,
  });
}

export const getBackupScheduleConfig = async () => {
  return await __ajax('worrprba_ajax_get_backup_schedule_config');

  // const endpoint = `${ajax_url}?action=worrprba_ajax_get_backup_schedule_config`;
  // const response = await __request(endpoint, {
  //   method: 'POST',
  //   headers: {
  //     'Content-Type': 'application/json',
  //   },
  //   body: JSON.stringify({
  //     nonce: nonce.worrprba_nonce
  //   }),
  // });

  // return response;
}