const { ajax_url, nonce } = wp_backup_php_data;

export const __request = async (url, options) => {
  const response = await fetch(url, options);
  return response.json();
};

export const getBackups = async () => {
  const response = await __request(ajax_url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'wp_backup_ajax_get_backups',
      nonce: nonce.wp_backup_nonce,
    }),
  });
  return response;
};