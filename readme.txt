=== PixelParade Smart Purge for Breeze Cache ===
Contributors: kevpress88
Tags: cache, purge, breeze, cloudflare, performance
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.1.17
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Intelligently purges CPT archives, taxonomies, and page-builder hub pages when content changes in Breeze Cache.

== Description ==

By default, Breeze aggressively caches content. When you update a custom post type, it only clears the cache for that specific post. This leaves your important hub pages - like post grids, custom taxonomy archives, and page builder layouts - serving stale content to users.

**PixelParade Smart Purge for Breeze Cache** acts as a traffic controller for your cache. The built-in Auto-Scanner detects which pages are querying specific post types, ensuring Breeze safely clears the cache for the parent pages and associated taxonomies whenever a post is updated.

### Key Features
* **Smart Auto-Scanner:** Automatically detects Gutenberg, Elementor, Bricks, Beaver Builder, Oxygen, WPBakery, and Divi post grids to map custom post types to their respective hub pages.
* **Manual Overrides:** Easily define custom URL paths that should be purged when a specific post type is updated.
* **Synchronous Cloudflare Purging:** Optionally bypass the default WP-Cron delay so cache purges happen instantly on "Update".
* **Automated Taxonomy Purging:** Automatically detects and purges associated taxonomy archive URLs when a post is modified.
* **Frontend Breeze toolbar:** Logged-in editors see the same Breeze purge menu on the public site, plus per-page clear cache.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` (folder name must match your install path), or install through the WordPress plugins screen from a zip file.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Ensure the [Breeze](https://wordpress.org/plugins/breeze/) cache plugin is active.
4. Navigate to **Settings > Smart Purge** to run your first Smart Scan and configure your mappings.

== Frequently Asked Questions ==

= Does this replace the Breeze cache plugin? =
No. This is an add-on for [Breeze Cache](https://wordpress.org/plugins/breeze/). Breeze must be installed and active.

= Which page builders does the Auto-Scanner detect? =
Native Gutenberg blocks, Elementor, Bricks Builder, Beaver Builder, Oxygen (JSON meta), WPBakery, and Divi (shortcode grids). Other builders can use manual URL mappings in the settings screen.

== Changelog ==

= 1.1.17 =

* Prefix migration: `bsp_*` → `ppspb_*` (wordpress.org review); one-time options migration for existing sites.
* wordpress.org package: text domain and folder slug `pixelparade-smart-purge-for-breeze-cache`; directory icons excluded from plugin zip.

= 1.1.16 =

* Settings: expandable per-type checkboxes to hide individual utility CPTs from the table and scans.
* Docs: staging SSH user matrix and efficient agent workflow in ACCESS.md.

= 1.1.15 =

* Admin settings: fix footer-enqueued scan/save handlers when DOMContentLoaded already fired.
* Live scan log: progress transient and status polling during Smart Scan.
* Staging deploy: chmod plugin directory after SCP so admin CSS/JS are not 403.

= 1.1.14 =

* WordPress.org review: enqueued admin settings CSS/JS; literal text domains for Plugin Check.
* Frontend Breeze toolbar without ReflectionClass; ajaxurl via wp_enqueue.
* Display name: PixelParade Smart Purge for Breeze Cache; dual wporg zip build (pending vs approved slug).

= 1.1.13 =

* Settings screen uses tabs: Smart Purge (main) and Plugin Updates (agency builds).
* Problem/Solution intro is collapsed behind "What does Smart Purge do?" on the main tab.

= 1.1.12 =

* Plugin icons use a fully transparent background (no checkerboard frame); admin and MainWP display uses object-fit: cover.

= 1.1.11 =

* Plugin icons bake checkerboard padding into the PNG so spacing shows on Updates, View details, and MainWP Favorites (CSS cannot paint through img transparency).
* Added scripts/build-padded-icons.py to regenerate icon sizes from artwork.

= 1.1.10 =

* Plugin icon assets include transparent padding on all sides; admin CSS uses contain and a checkerboard tile behind the icon.

= 1.1.9 =

* Plugin icon uses object-fit: cover on Updates and View details; ships 512px retina icon asset.

= 1.1.8 =

* Activation notice adds a Review settings button linking to Settings > Smart Purge.
* Simplified Plugin Updates panel for public GitHub Releases (PAT form removed from settings UI).

= 1.1.7 =

* Fix squished plugin icon and banner in Dashboard → Updates and View details (correct 256×256 and 1544×500 asset dimensions).

= 1.1.6 =

* GitHub Releases include standalone `icon-128x128.png` and `icon-256x256.png` assets for MainWP Favorites.
* MainWP dashboard mu-plugin script seeds the Favorites / Manage Plugins icon for agency installs.

= 1.1.5 =

* Updates screen and Plugins list now show the plugin icon (GitHub updater passes icons; standard assets path).
* GitHub update cache clears when WordPress checks for updates; Refresh update check button on settings screen.

= 1.1.4 =

* Auto-scanner: detect WPBakery and Divi shortcode post grids (in addition to Gutenberg, Elementor, Bricks, Beaver Builder, Oxygen).
* Admin notice when legacy or duplicate plugin folders are present after fleet rollout.
* Public GitHub releases: agency updater works without BSP_GITHUB_TOKEN.

= 1.1.3 =
* Agency bootstrap auto-resolves GitHub update token (wp-config, server env, or encrypted settings field).
* Settings screen shows agency GitHub update status for MainWP client installs.

= 1.1.2 =
* Fix GitHub Release plugin updates when the repository is private (authenticated asset download).

= 1.1.1 =
* Plugin folder and text domain aligned to `smart-purge-for-breeze-cache` (wordpress.org slug).
* Settings screen under **Settings → Smart Purge** (admin page slug updated).
* Security hardening for admin scan log output.

= 1.0.0 =
* Initial public release.
* Smart Auto-Scanner for Gutenberg, Elementor, Bricks, and Beaver Builder hub pages.
* Manual and ignored URL mappings per post type.
* Automated taxonomy archive purging.
* Optional synchronous Cloudflare purge on post update.
* Frontend Breeze admin bar integration with per-page clear cache.
* Requires the Breeze Cache plugin (`Requires Plugins: breeze`).
