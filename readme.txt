=== Smart Purge for Breeze Cache ===
Contributors: kevpress88
Tags: cache, purge, breeze, cloudflare, performance
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Intelligently purges CPT archives, taxonomies, and page-builder hub pages when content changes in Breeze Cache.

== Description ==

By default, Breeze aggressively caches content. When you update a custom post type, it only clears the cache for that specific post. This leaves your important hub pages—like post grids, custom taxonomy archives, and page builder layouts—serving stale content to users.

**Smart Purge for Breeze Cache** acts as a traffic controller for your cache. The built-in Auto-Scanner detects which pages are querying specific post types, ensuring Breeze safely clears the cache for the parent pages and associated taxonomies whenever a post is updated.

### Key Features
* **Smart Auto-Scanner:** Automatically detects Gutenberg, Elementor, Bricks, and Beaver Builder post grids to map custom post types to their respective hub pages.
* **Manual Overrides:** Easily define custom URL paths that should be purged when a specific post type is updated.
* **Synchronous Cloudflare Purging:** Optionally bypass the default WP-Cron delay so cache purges happen instantly on "Update".
* **Automated Taxonomy Purging:** Automatically detects and purges associated taxonomy archive URLs when a post is modified.
* **Frontend Breeze toolbar:** Logged-in editors see the same Breeze purge menu on the public site, plus per-page clear cache.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` (folder name must match your install path), or install through the WordPress plugins screen from a zip file.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Ensure the [Breeze](https://wordpress.org/plugins/breeze/) cache plugin is active.
4. Navigate to **Settings ? Smart Purge** to run your first Smart Scan and configure your mappings.

== Frequently Asked Questions ==

= Does this replace the Breeze cache plugin? =
No. This is an add-on for [Breeze Cache](https://wordpress.org/plugins/breeze/). Breeze must be installed and active.

= Which page builders does the Auto-Scanner detect? =
Native Gutenberg blocks, Elementor, Bricks Builder, and Beaver Builder. Other builders can use manual URL mappings in the settings screen.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Smart Auto-Scanner for Gutenberg, Elementor, Bricks, and Beaver Builder hub pages.
* Manual and ignored URL mappings per post type.
* Automated taxonomy archive purging.
* Optional synchronous Cloudflare purge on post update.
* Frontend Breeze admin bar integration with per-page clear cache.
* Settings screen under **Settings ? Smart Purge**.
* Requires the Breeze Cache plugin (`Requires Plugins: breeze`).
