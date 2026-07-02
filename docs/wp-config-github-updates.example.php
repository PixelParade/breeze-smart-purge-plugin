<?php
/**
 * Add to wp-config.php (above "That's all, stop editing!") on staging and client sites
 * while PixelParade/breeze-smart-purge-plugin is private.
 *
 * Use a fine-grained GitHub PAT with read access to the repo (Contents: Read-only).
 * Never commit the real token to git.
 */

define( 'BSP_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );

// Optional override (default: PixelParade/breeze-smart-purge-plugin)
// define( 'BSP_GITHUB_REPO', 'PixelParade/breeze-smart-purge-plugin' );
