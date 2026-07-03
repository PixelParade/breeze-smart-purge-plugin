<?php
/**
 * Agency-only bootstrap — MainWP client sites (GitHub Release zip).
 * Omitted from wordpress.org builds via .distignore.wporg.
 *
 * @package Smart_Purge_For_Breeze_Cache
 */

if (!defined('ABSPATH')) {
	exit;
}

if (defined('BSP_AGENCY_BUILD')) {
	return;
}
define('BSP_AGENCY_BUILD', true);

require_once __DIR__ . '/github-token.php';

// Define BSP_GITHUB_TOKEN from env or encrypted option before github-updater.php loads.
bsp_agency_bootstrap_github_credentials();

if (!defined('BSP_GITHUB_REPO')) {
	define('BSP_GITHUB_REPO', 'PixelParade/breeze-smart-purge-plugin');
}
