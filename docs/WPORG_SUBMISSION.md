# WordPress.org submission checklist

Use this for the **public** [wordpress.org plugin directory](https://wordpress.org/plugins/) lane. PixelParade client sites on GitHub Releases + MainWP keep using `includes/github-updater.php` and `BSP_GITHUB_TOKEN`.

## Apply for a plugin slug

1. Log in at [wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/).
2. **Plugin name (display):** `Smart Purge for Breeze Cache`
3. **Requested slug:** `pixelparade-smart-purge-for-breeze-cache` (distinctive prefix; unofficial add-on for Breeze Cache).
4. Agree to the [plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).
5. Set `Contributors:` in `readme.txt` to your **wordpress.org username** (`kevpress88` for this project).

### Slug vs folder name

| Concept | Value |
|---------|--------|
| **Display name** | Smart Purge for Breeze Cache (`Plugin Name` header) |
| **Text domain** | `pixelparade-smart-purge-for-breeze-cache` (wporg zip); agency source keeps `smart-purge-for-breeze-cache` until transform |
| **Folder / main file** | Wporg: `pixelparade-smart-purge-for-breeze-cache/`; Agency: `smart-purge-for-breeze-cache/` |
| **Prefix** | `ppspb_` / `PPSPB_*` |
| **wordpress.org SVN folder** | Whatever slug WordPress approves — zip root folder **must match** |

If the approved slug differs from `smart-purge-for-breeze-cache`, rename the folder and main PHP file to match before the first SVN commit.

## First public version: 1.0.0

Yes — the **first release on wordpress.org should be `1.0.0`**, even if you used higher version numbers on private GitHub/MainWP builds beforehand.

| Lane | Versioning |
|------|------------|
| **wordpress.org** | Starts at **1.0.0**; bump `Version:` header and `Stable tag:` together |
| **GitHub / MainWP (private)** | May run ahead of wp.org (e.g. **1.1.1** agency with slug alignment); wp.org directory can stay at **1.0.0** until you SVN-tag the first public release |

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
| **No custom updater on wp.org** | `includes/github-updater.php` excluded via `.distignore.wporg` |
| **No agency code on wp.org** | `includes/agency/` excluded via `.distignore.wporg` |
| **No secrets in code** | `BSP_GITHUB_TOKEN` only in wp-config on private sites |

Full maintainer checklist: [SECURITY.md](SECURITY.md).

Run Plugin Check before every SVN commit.

## Repo hygiene

| Item | Status |
|------|--------|
| Plugin Check CI (`.github/workflows/plugin-check.yml`) | Runs on `main` / PRs |
| Distributable zip excludes dev files (`.distignore`) | Yes |
| Agency-only paths excluded from wp.org (`.distignore.wporg`) | `includes/github-updater.php`, `includes/agency/` |
| Custom GitHub updater excluded from wp.org zip | `.distignore.wporg` |
| `Requires Plugins: breeze` header | In main plugin file |
| `readme.txt` UTF-8, no mojibake | Yes |
| `Tested up to:` current WP | 7.0 (update with each release) |
| GPL-compatible `License` header | GPLv2 or later |

## Run Plugin Check

```bash
wp plugin install plugin-check --activate
wp plugin check smart-purge-for-breeze-cache --require=wp-content/plugins/plugin-check/cli.php
```

CI builds a clean folder from `.distignore` then runs [wordpress/plugin-check-action](https://github.com/wordpress/plugin-check-action).

## SVN workflow (after approval)

1. `svn co https://plugins.svn.wordpress.org/<approved-slug>`
2. Copy distributable files into `trunk/` (wporg zip from `scripts/build-plugin-zips.ps1` or release asset `smart-purge-for-breeze-cache-wporg.zip`).
3. `svn add` / `svn commit` trunk.
4. Tag first release: `svn cp trunk tags/1.0.0` and commit.
5. Add banner/icon under SVN `assets/` — **not** inside the plugin zip.

## Assets for the plugin directory page

Prepared in **`assets/wporg/`** in this repo (upload to SVN `assets/` only):

- `icon-128x128.png` and `icon-256x256.png`
- `banner-772x250.png` and `banner-1544x500.png`
- Screenshots in `readme.txt` `== Screenshots ==` (optional, not yet added)

## Two release lanes (one repo, two builds)

| Lane | Build | Zip / deploy | Updates |
|------|-------|--------------|---------|
| **wordpress.org** | `.distignore` + `.distignore.wporg` | `pixelparade-smart-purge-for-breeze-cache-wporg.zip` → SVN `trunk` / `tags/` | Directory only |
| **MainWP clients** | `.distignore` only (agency) | `smart-purge-for-breeze-cache.zip` on GitHub Releases | `PPSPB_GITHUB_TOKEN` (legacy `BSP_GITHUB_TOKEN` still works) → `/releases/latest` |

Agency build includes `includes/github-updater.php` and `includes/agency/`. The wporg build omits both (see `.distignore.wporg`).

CI: `plugin-check.yml` tests the **wporg** tree. `release.yml` produces **both** zips on each `v*` tag.

Client rollout policy: [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md).

**Future wp.org releases:** feature backlog and checklists — [WPORG_BACKLOG.md](WPORG_BACKLOG.md).  
**Review response (Jul 2026):** upload checklist and reply draft — [WPORG_REVIEW_REPLY.md](WPORG_REVIEW_REPLY.md).

Bump `Version:` in the plugin header and `Stable tag:` in `readme.txt` together for each wordpress.org release. MainWP client versions may run ahead on GitHub tags.
