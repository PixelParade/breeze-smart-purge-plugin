# Agency-only code (MainWP clients)

PHP in this folder ships in the **agency** GitHub Release zip (`smart-purge-for-breeze-cache.zip`) and on **staging**, but **not** in the wordpress.org zip (`smart-purge-for-breeze-cache-wporg.zip`) or SVN trunk.

- Entry point: `bootstrap.php` (loaded from `smart-purge-for-breeze-cache.php` when the file exists).
- Add early features, PixelParade client customizations, and experiments here.
- Keep wp.org behavior stable; external installs never receive this directory.

See [docs/MAINWP_ROLLOUT.md](../../docs/MAINWP_ROLLOUT.md) and [docs/GITHUB_SETUP.md](../../docs/GITHUB_SETUP.md).
