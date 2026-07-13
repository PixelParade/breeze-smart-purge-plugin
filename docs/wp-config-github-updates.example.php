<?php
/**
 * Add to wp-config.php (above "That's all, stop editing!") on **MainWP client sites**
 * (and optionally staging to test the release update path).
 *
 * Not used on external wordpress.org-only installs.
 *
 * WordPress plugin updater on MainWP client sites — NOT Cursor/GitHub CLI auth.
 * See wpcp-debug/docs/GITHUB_AUTH.md for local dev auth (gh OAuth).
 *
 * Use a fine-grained GitHub PAT with read access to the repo (Contents: Read-only).
 * Never commit the real token to git.
 */

// Option A — constant (highest priority):
define( 'PPSPB_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );
// Legacy alias still accepted: BSP_GITHUB_TOKEN

// Option B — Cloudways / server env (zero-touch for all apps on that server):
// Set PPSPB_GITHUB_TOKEN (or BSP_GITHUB_TOKEN) in the hosting panel, then:
// if ( ! defined( 'PPSPB_GITHUB_TOKEN' ) && getenv( 'PPSPB_GITHUB_TOKEN' ) ) {
//     define( 'PPSPB_GITHUB_TOKEN', getenv( 'PPSPB_GITHUB_TOKEN' ) );
// }
//
// Agency zip also reads getenv() automatically via includes/agency/github-token.php.

// Option C — Settings → Smart Purge → Agency GitHub Updates (encrypted DB option).
// No wp-config edit; paste PAT once per site after installing the agency zip.

// Optional override (default: PixelParade/breeze-smart-purge-plugin)
// define( 'PPSPB_GITHUB_REPO', 'PixelParade/breeze-smart-purge-plugin' );
