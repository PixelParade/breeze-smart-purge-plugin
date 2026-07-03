# WordPress.org submission checklist

Use this for the **public** [wordpress.org plugin directory](https://wordpress.org/plugins/) lane. PixelParade client sites on GitHub Releases + MainWP keep using `includes/github-updater.php` and `BSP_GITHUB_TOKEN`.

## Apply for a plugin slug

1. Log in at [wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/).
2. **Plugin name (display):** `Smart Purge for Breeze Cache`
3. **Requested slug:** prefer `smart-purge-for-breeze-cache` if available (describes the add-on clearly and avoids implying official Breeze authorship).
4. Agree to the [plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).
5. Set `Contributors:` in `readme.txt` to your **wordpress.org username** (`kevpress88` for this project).

### Slug vs folder name

| Concept | Value |
|---------|--------|
| **Display name** | Smart Purge for Breeze Cache (`Plugin Name` header) |
| **Text domain** | `breeze-smart-purge` (unchanged — avoids breaking translations) |
| **Dev / staging folder** | `breeze-smart-purge` (current repo + deploy path) |
| **wordpress.org SVN folder** | Whatever slug WordPress approves — zip root folder **must match** |

If the approved slug is `smart-purge-for-breeze-cache`, rename the plugin folder inside the SVN `trunk/` zip before first commit. The GitHub/private lane can keep `breeze-smart-purge` until you choose to align folder names.

## First public version: 1.0.0

Yes — the **first release on wordpress.org should be `1.0.0`**, even if you used higher version numbers on private GitHub/MainWP builds beforehand.

| Lane | Versioning |
|------|------------|
| **wordpress.org** | Starts at **1.0.0**; bump `Version:` header and `Stable tag:` together |
| **GitHub / MainWP (private)** | Pre-wp.org tags (`v1.0.1`–`v1.1.0`) were internal beta; after wp.org launch, align semver (e.g. wp.org `1.0.1` = same code as GitHub `v1.1.1`) |

`readme.txt` changelog for wp.org lists only public releases — do not publish internal pre-release version numbers on the plugin directory page.

## Security (required for review)

Follow the [WordPress Security guide](https://developer.wordpress.org/apis/security/). Reviewers commonly check:

| Area | This plugin |
|------|-------------|
| **Direct file access** | `ABSPATH` guard at top of PHP files |
| **Capabilities** | `manage_options` for settings; `edit_post` for per-post cache clear |
| **Nonces** | AJAX `check_ajax_referer`; purge links use `wp_nonce_url` / `check_admin_referer` |
| **Input** | `sanitize_*`, `wp_unslash`, `map_deep` on POST arrays |
| **Output** | `esc_html__`, `esc_url`, `wp_kses_post` where appropriate |
| **No custom updater on wp.org** | `includes/github-updater.php` excluded via `.distignore` |
| **No secrets in code** | `BSP_GITHUB_TOKEN` only in wp-config on private sites |

Full maintainer checklist: [SECURITY.md](SECURITY.md).

Run Plugin Check before every SVN commit.

## Repo hygiene

| Item | Status |
|------|--------|
| Plugin Check CI (`.github/workflows/plugin-check.yml`) | Runs on `main` / PRs |
| Distributable zip excludes dev files (`.distignore`) | Yes |
| Custom GitHub updater excluded from wp.org zip | `includes/github-updater.php` in `.distignore` |
| `Requires Plugins: breeze` header | In main plugin file |
| `readme.txt` UTF-8, no mojibake | Yes |
| `Tested up to:` current WP | 7.0 (update with each release) |
| GPL-compatible `License` header | GPLv2 or later |

## Run Plugin Check

```bash
wp plugin install plugin-check --activate
wp plugin check breeze-smart-purge --require=wp-content/plugins/plugin-check/cli.php
```

CI builds a clean folder from `.distignore` then runs [wordpress/plugin-check-action](https://github.com/wordpress/plugin-check-action).

## SVN workflow (after approval)

1. `svn co https://plugins.svn.wordpress.org/<approved-slug>`
2. Copy distributable files into `trunk/` (`.distignore` build — no `includes/github-updater.php`, no dotfiles).
3. `svn add` / `svn commit` trunk.
4. Tag first release: `svn cp trunk tags/1.0.0` and commit.
5. Add banner/icon under SVN `assets/` — **not** inside the plugin zip.

## Assets for the plugin directory page

Prepared in **`assets/wporg/`** in this repo (upload to SVN `assets/` only):

- `icon-128x128.png` and `icon-256x256.png`
- `banner-772x250.png` and `banner-1544x500.png`
- Screenshots in `readme.txt` `== Screenshots ==` (optional, not yet added)

## Two release lanes

| Lane | Build | Updates |
|------|-------|---------|
| **wordpress.org** | `.distignore` zip (no GitHub updater) | SVN `trunk` / `tags/` |
| **PixelParade clients** | GitHub Release zip (`release.yml`, includes updater) | Dashboard → Plugins or MainWP |

Bump `Version:` in the plugin header and `Stable tag:` in `readme.txt` together for each wordpress.org release.
