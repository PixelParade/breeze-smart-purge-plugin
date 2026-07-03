<?php
/**
 * Add to wp-config.php (above "That's all, stop editing!") on **MainWP client sites**
 * (and optionally staging to test the release update path).
 *
 * Not used on external wordpress.org-only installs.
 *
 * Use a fine-grained GitHub PAT with read access to the repo (Contents: Read-only).
 * Never commit the real token to git.
 */

// Option A — constant (highest priority):
define( 'BSP_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );

// Option B — Cloudways / server env (zero-touch for all apps on that server):
// Set BSP_GITHUB_TOKEN in the hosting panel, then:
// if ( ! defined( 'BSP_GITHUB_TOKEN' ) && getenv( 'BSP_GITHUB_TOKEN' ) ) {
//     define( 'BSP_GITHUB_TOKEN', getenv( 'BSP_GITHUB_TOKEN' ) );
// }
//
// Agency zip also reads getenv() automatically via includes/agency/github-token.php.

// Option C — Settings → Smart Purge → Agency GitHub Updates (encrypted DB option).
// No wp-config edit; paste PAT once per site after installing the agency zip.

// Optional override (default: PixelParade/breeze-smart-purge-plugin)
// define( 'BSP_GITHUB_REPO', 'PixelParade/breeze-smart-purge-plugin' );
