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

// Register agency-only features here (hooks, filters, admin UI).
// PixelParade MainWP clients receive this file via the agency GitHub Release zip only.

if (defined('BSP_AGENCY_BUILD')) {
	return;
}
define('BSP_AGENCY_BUILD', true);
