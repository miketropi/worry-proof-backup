import { __request } from './lib';

const { endpoint, theme_slug, nonce, ajax_url, php_version, parent_theme_version, wordpress_version, license_key } = worrprba_dummy_pack_center_data;

export const getDummyPacks = async () => {
  const response = await __request(`${ endpoint }packages/${ theme_slug }`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'license_key': license_key
    },
  });

  return response;
};

export const getDownloadPackUrl = async (packID) => {
  const response = await __request(`${ endpoint }packages/${ theme_slug }/${ packID }`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'license_key': license_key
    },
  });

  return response;
};

/**
 * Validate package version requirements
 * @param {string} type - theme_version | php_version
 * @param {string} version - Required version string
 * @returns {boolean} - true if the current version meets the requirement, false otherwise
 */
export const validateVersionPackageRequirements = (type, version) => {
  if (!type || !version) {
    return false;
  }

  let currentVersion = '';

  if (type === 'theme_version') {
    currentVersion = parent_theme_version || '';
  } else if (type === 'php_version') {
    currentVersion = php_version || '';
  } else if (type === 'wordpress_version') {
    currentVersion = wordpress_version || '';
  } else {
    return false;
  }

  if (!currentVersion) {
    return false;
  }

  // Compare versions: current version should be >= required version
  const currentParts = currentVersion.toString().split('.').map(Number);
  const requiredParts = version.toString().split('.').map(Number);
  const maxLength = Math.max(currentParts.length, requiredParts.length);
  
  for (let i = 0; i < maxLength; i++) {
    const currentPart = currentParts[i] || 0;
    const requiredPart = requiredParts[i] || 0;
    
    if (currentPart > requiredPart) return true;
    if (currentPart < requiredPart) return false;
  }
  
  return true; // Versions are equal
};