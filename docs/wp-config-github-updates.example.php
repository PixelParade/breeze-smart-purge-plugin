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

define( 'BSP_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );

// Optional override (default: PixelParade/breeze-smart-purge-plugin)
// define( 'BSP_GITHUB_REPO', 'PixelParade/breeze-smart-purge-plugin' );
