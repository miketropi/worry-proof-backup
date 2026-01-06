import { __request } from './lib';

const { 
  endpoint, 
  theme_slug, 
  nonce, 
  ajax_url, 
  php_version, 
  parent_theme_version, 
  wordpress_version, 
  license_key,
  header_meta_attached_api } = worrprba_dummy_pack_center_data;

export const encodePayload = (obj) => {
  const json = JSON.stringify(obj);
  const reversed = json.split('').reverse().join('');
  return btoa(unescape(encodeURIComponent(reversed)));
}

export const decodePayload = (encoded) => {
  try {
    const reversed = decodeURIComponent(escape(atob(encoded)));
    const json = reversed.split('').reverse().join('');
    return JSON.parse(json);
  } catch (e) {
    console.error('Decode failed', e);
    return null;
  }
}

const headers = {
  'Content-Type': 'application/json',
  // 'license_key': license_key
  'xxx-meta': encodePayload(header_meta_attached_api),
};

export const getDummyPacks = async () => {
  const response = await __request(`${ endpoint }packages/${ theme_slug }`, {
    method: 'GET',
    headers,
  });

  return response;
};

export const getDummyPacks2 = async () => {
  const response = await __request(`${ ajax_url }?action=worrprba_ajax_dummy_pack_center_get_packs&installNonce=${ nonce }`);
  return response;
}

export const getDownloadPackUrl = async (packID) => {
  const response = await __request(`${ endpoint }packages/${ theme_slug }/${ packID }`, {
    method: 'GET',
    headers,
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

export const doInstallProcess = async (process) => {
  const { action, payload } = process;

  const response = await __request(ajax_url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action,
      ...Object.fromEntries(Object.entries(payload).map(([key, value]) => [`payload[${key}]`, value])),
      installNonce: nonce,
    }),
  });

  return response;
}

/**
 * Compare current installed plugins with required plugins to check for required slugs and their version requirements.
 * 
 * @param {Array<Object>} currentPlugins - Array of objects: [{slug, name, version}]
 * @param {Array<Object>} requiredPlugins - Array of objects: [{slug, version}]
 * @returns {Object} {
 *   status: "ok" | "missing" | "version_mismatch",
 *   missing: string[],         // missing required plugin slugs
 *   incompatible: Array<{slug, requiredVersion, installedVersion}> // wrong versions
 * }
 */
export function checkPluginsRequirements(currentPlugins, requiredPlugins) {
  if (!Array.isArray(currentPlugins)) currentPlugins = [];
  if (!Array.isArray(requiredPlugins)) requiredPlugins = [];

  const installedMap = {};
  currentPlugins.forEach(plg => {
    installedMap[plg.slug] = plg.version || "";
  });

  const missing = [];
  const incompatible = [];

  requiredPlugins.forEach(req => {
    const reqSlug = req.slug;
    const reqVersion = (req.version || "").toString();

    if (!(reqSlug in installedMap)) {
      missing.push(reqSlug);
    } else if (reqVersion && reqVersion !== "") {
      // Compare versions (installed >= required)
      const installedVer = (installedMap[reqSlug] || "").toString();
      if (!isVersionGte(installedVer, reqVersion)) {
        incompatible.push({
          slug: reqSlug,
          requiredVersion: reqVersion,
          installedVersion: installedVer
        });
      }
    }
  });

  let status = "ok";
  if (missing.length > 0) status = "missing";
  else if (incompatible.length > 0) status = "version_mismatch";

  return {
    status,
    missing,
    incompatible
  };
}

/**
 * Returns true if v1 >= v2 (both dot-separated, e.g. "2.1.3")
 */
export function isVersionGte(v1, v2) {
  const splitToNum = v => v.split('.').map(x => parseInt(x, 10) || 0);
  const a = splitToNum(v1);
  const b = splitToNum(v2);
  const len = Math.max(a.length, b.length);
  for (let i = 0; i < len; i++) {
    if ((a[i] || 0) > (b[i] || 0)) return true;
    if ((a[i] || 0) < (b[i] || 0)) return false;
  }
  return true;
}
