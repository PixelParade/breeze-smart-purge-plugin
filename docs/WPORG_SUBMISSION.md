# WordPress.org submission checklist

Use this for the **public** [wordpress.org plugin directory](https://wordpress.org/plugins/) lane. PixelParade client sites on GitHub Releases + MainWP keep using `includes/github-updater.php` and `PPSPB_GITHUB_TOKEN` / `BSP_GITHUB_TOKEN`.

**Status (Jul 2026):** Plugin **approved** and **live** — slug `pixelparade-smart-purge-for-breeze-cache` (contributor `kevpress88`). First SVN upload completed from Cursor (trunk **1.0.0**, tag `tags/1.0.0`, icons/banners in SVN `assets/`). Public: https://wordpress.org/plugins/pixelparade-smart-purge-for-breeze-cache

**Future releases:** rebuild wporg zip → update SVN `trunk` → tag `tags/X.Y.Z` → bump `Stable tag:`. Agency/MainWP stays on GitHub Releases (separate version line). See ops lessons: first SVN upload (Jul 2026).

**GitHub vs SVN:** Doc/helper commits to this git repo do **not** change the directory. SVN commits do **not** require a prior `git push`.

| Item | Value |
|------|--------|
| **SVN URL** | https://plugins.svn.wordpress.org/pixelparade-smart-purge-for-breeze-cache |
| **Public URL** | https://wordpress.org/plugins/pixelparade-smart-purge-for-breeze-cache |
| **First directory version** | **1.0.0** (wporg transform always sets this) |
| **Zip to use** | `pixelparade-smart-purge-for-breeze-cache-wporg.zip` (repo root after build) |

---

## First SVN upload (Windows / Kevin) — do this once

**Do not skip waiting for SVN access.** After approval email, WordPress needs about an hour (sometimes longer) before your account can commit. You also need an SVN password set on your profile (separate from your normal wordpress.org login password).

### 1. Wait for SVN access + set your SVN password

1. Open [wordpress.org/plugins/developers/](https://wordpress.org/plugins/developers/) while logged in as **kevpress88**. Confirm the plugin appears in your list.
2. Open [profiles.wordpress.org](https://profiles.wordpress.org/) → your account → **Account** / password settings.
3. Set or reset your **SVN / Subversion password** (WordPress.org calls this out separately from the website password). Save it somewhere safe — TortoiseSVN will ask for it on commit.
4. Wait ~1 hour after the approval email (or until SVN checkout works without an auth error). If checkout fails with “Authorization failed”, wait longer or reset the SVN password again.

### 2. Rebuild the wporg zip (confirm 1.0.0)

1. Open **PowerShell**.
2. Go to the repo:

```powershell
cd C:\Users\kevin\Projects\breeze-smart-purge-plugin
```

3. Build both zips:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\build-plugin-zips.ps1
```

4. Confirm this file exists:

`C:\Users\kevin\Projects\breeze-smart-purge-plugin\pixelparade-smart-purge-for-breeze-cache-wporg.zip`

5. Quick sanity check (optional but recommended) — in PowerShell:

```powershell
Expand-Archive -Path .\pixelparade-smart-purge-for-breeze-cache-wporg.zip -DestinationPath .\build\svn-check -Force
Select-String -Path .\build\svn-check\pixelparade-smart-purge-for-breeze-cache\*.php -Pattern "Version:|Text Domain:|PPSPB_VERSION" | Select-Object -First 10
Get-ChildItem -Recurse .\build\svn-check | Where-Object { $_.Name -like 'icon-*.png' }
```

You want:

- `Version: 1.0.0` and `PPSPB_VERSION` `1.0.0`
- `Text Domain: pixelparade-smart-purge-for-breeze-cache`
- **No** `icon-*.png` files inside the extract (icons go to SVN `assets/` later, not trunk)

### 3. Install TortoiseSVN (easiest on Windows)

1. Download TortoiseSVN from https://tortoisesvn.net/downloads.html (64-bit).
2. Install it. **Reboot** if the installer asks.
3. You will use right-click menus in File Explorer after that.

*(Optional command-line: install the “command line client tools” checkbox in the TortoiseSVN installer, or use Git Bash `svn`. The Tortoise steps below are enough.)*

### 4. Check out the empty SVN repo

1. Create a folder, for example: `C:\Users\kevin\svn\pixelparade-smart-purge-for-breeze-cache`
2. Right-click that empty folder → **SVN Checkout…**
3. URL of repository:

```
https://plugins.svn.wordpress.org/pixelparade-smart-purge-for-breeze-cache
```

4. Checkout directory: the folder you created.
5. Click **OK**. You should get empty (or nearly empty) folders: `trunk`, `tags`, `assets` (and sometimes `branches`).

Username: **kevpress88**  
Password: your **SVN password** from step 1.

### 5. Copy plugin files into `trunk/` (not a nested folder)

**Important:** WordPress expects plugin files **directly inside** `trunk/`, like this:

```
trunk/
  pixelparade-smart-purge-for-breeze-cache.php
  readme.txt
  assets/admin/...
  includes/...
```

**Wrong:** `trunk/pixelparade-smart-purge-for-breeze-cache/pixelparade-smart-purge-for-breeze-cache.php`

Steps:

1. Extract `pixelparade-smart-purge-for-breeze-cache-wporg.zip` (double-click or right-click → Extract All).
2. Open the extracted folder `pixelparade-smart-purge-for-breeze-cache\`.
3. Select **everything inside** that folder (the `.php` file, `readme.txt`, `assets`, `includes`).
4. Copy those items into your SVN working copy’s `trunk\` folder.
5. Confirm there is **no** extra nested plugin-slug folder inside `trunk`.

### 6. Commit `trunk` (first code upload)

1. Right-click the local `trunk` folder → **TortoiseSVN** → **Add…** → check all new files → OK.
2. Right-click `trunk` again → **SVN Commit…**
3. Message example:

```
Initial trunk commit — PixelParade Smart Purge for Breeze Cache 1.0.0
```

4. Click **OK** and authenticate if prompted.

### 7. Tag release `1.0.0` (makes the directory version installable)

`Stable tag: 1.0.0` in `readme.txt` must match a real folder under `tags/`.

1. Right-click local `trunk` → **TortoiseSVN** → **Branch/Tag…**
2. **To path / URL:** change so it ends with `/tags/1.0.0`  
   Full example:

```
https://plugins.svn.wordpress.org/pixelparade-smart-purge-for-breeze-cache/tags/1.0.0
```

3. Create from: working copy (or HEAD) of trunk you just committed.
4. Log message example: `Tagging version 1.0.0`
5. Click **OK**.

After CDN/cache catch-up (often minutes, sometimes longer), the public page should list **1.0.0**.

### 8. Commit directory artwork to SVN `assets/` (separate from trunk)

Icons and banners are **not** inside the plugin zip and **must not** go in `trunk/`. They live only in the SVN top-level `assets/` folder.

Prepared files in this repo:

`C:\Users\kevin\Projects\breeze-smart-purge-plugin\assets\wporg\`

| File | Purpose |
|------|---------|
| `icon-128x128.png` | Directory icon |
| `icon-256x256.png` | Directory icon @2x |
| `banner-772x250.png` | Plugin page banner |
| `banner-1544x500.png` | Retina banner |

Steps:

1. Copy those four PNGs into your SVN working copy’s **`assets\`** folder (sibling of `trunk`, not inside it).
2. Right-click `assets` → **TortoiseSVN** → **Add…** → select the PNGs.
3. Right-click `assets` → **SVN Commit…**  
   Message example: `Add plugin directory icons and banners.`

Details: [assets/wporg/README.md](../assets/wporg/README.md).

### 9. Confirm it worked

1. Open https://wordpress.org/plugins/pixelparade-smart-purge-for-breeze-cache
2. Confirm version **1.0.0**, description, and icons/banner (assets can lag behind code).
3. Optional: install on a throwaway site from the directory (not on MainWP agency clients).

---

## Agency lane reminder (separate from wordpress.org)

| Lane | How updates ship |
|------|------------------|
| **wordpress.org** | This SVN trunk + `tags/1.0.0` (and later tags) |
| **MainWP / agency clients** | GitHub Release zip `smart-purge-for-breeze-cache.zip` + built-in updater — **not** the wporg slug or SVN |

Do **not** MainWP-install the wordpress.org plugin on agency sites. Agency folder stays `smart-purge-for-breeze-cache`; agency versions may be **1.1.x** while the directory stays at **1.0.0** until you choose to bump SVN.

See [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md).

---

## Apply for a plugin slug (already done)

1. Log in at [wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/).
2. **Plugin name (display):** `Smart Purge for Breeze Cache` / PixelParade-prefixed as approved.
3. **Requested slug:** `pixelparade-smart-purge-for-breeze-cache` (**approved**).
4. Agree to the [plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).
5. Set `Contributors:` in `readme.txt` / `readme.wporg.txt` to **kevpress88**.

### Slug vs folder name

| Concept | Value |
|---------|--------|
| **Display name** | PixelParade Smart Purge for Breeze Cache |
| **Text domain** | `pixelparade-smart-purge-for-breeze-cache` (wporg zip); agency source keeps `smart-purge-for-breeze-cache` until transform |
| **Folder / main file** | Wporg: `pixelparade-smart-purge-for-breeze-cache/`; Agency: `smart-purge-for-breeze-cache/` |
| **Prefix** | `ppspb_` / `PPSPB_*` |
| **wordpress.org SVN folder** | `pixelparade-smart-purge-for-breeze-cache` — zip root folder **must match** |

## First public version: 1.0.0

Yes — the **first release on wordpress.org should be `1.0.0`**, even if you used higher version numbers on private GitHub/MainWP builds beforehand.

| Lane | Versioning |
|------|------------|
| **wordpress.org** | Starts at **1.0.0**; bump `Version:` header and `Stable tag:` together |
| **GitHub / MainWP (private)** | May run ahead of wp.org (e.g. **1.1.17** agency); wp.org directory stays at **1.0.0** until you SVN-tag a newer public release |

`readme.wporg.txt` changelog lists only public releases — do not publish internal pre-release version numbers on the plugin directory page.

The build script’s wporg transform **forces** `Version:` / `PPSPB_VERSION` to `1.0.0` and copies `readme.wporg.txt` → `readme.txt`.

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
| **No icons in plugin zip** | `assets/icon-*.png` and `assets/wporg/` excluded via `.distignore.wporg` |
| **No secrets in code** | Tokens only in wp-config on private sites |

Full maintainer checklist: [SECURITY.md](SECURITY.md).

Run Plugin Check before every SVN commit of new code.

## Repo hygiene

| Item | Status |
|------|--------|
| Plugin Check CI (`.github/workflows/plugin-check.yml`) | Runs on `main` / PRs |
| Distributable zip excludes dev files (`.distignore`) | Yes |
| Agency-only paths excluded from wp.org (`.distignore.wporg`) | `includes/github-updater.php`, `includes/agency/`, icons |
| `Requires Plugins: breeze` header | In main plugin file |
| `readme.wporg.txt` UTF-8, Stable tag 1.0.0 | Yes |
| `Tested up to:` current WP | 7.0 (update with each release) |
| GPL-compatible `License` header | GPLv2 or later |

## Run Plugin Check

```bash
wp plugin install plugin-check --activate
wp plugin check pixelparade-smart-purge-for-breeze-cache --require=wp-content/plugins/plugin-check/cli.php
```

CI builds a clean folder from `.distignore` then runs [wordpress/plugin-check-action](https://github.com/wordpress/plugin-check-action). `WordPress.WP.I18n.TextDomainMismatch` is ignored for intentional `breeze` domain on frontend toolbar mirror labels.

## Command-line SVN (optional alternative to Tortoise)

```bash
svn co https://plugins.svn.wordpress.org/pixelparade-smart-purge-for-breeze-cache svn-wporg
# Extract zip; copy *contents* of pixelparade-smart-purge-for-breeze-cache/ into svn-wporg/trunk/
cd svn-wporg
svn add trunk/*
svn commit trunk -m "Initial trunk commit — 1.0.0"
svn cp trunk tags/1.0.0
svn commit tags -m "Tagging version 1.0.0"
# Later:
cp ../assets/wporg/*.png assets/
svn add assets/*.png
svn commit assets -m "Add plugin directory icons and banners."
```

Use `--username kevpress88` if prompted.

## Two release lanes (one repo, two builds)

| Lane | Build | Zip / deploy | Updates |
|------|-------|--------------|---------|
| **wordpress.org** | `.distignore` + `.distignore.wporg` | `pixelparade-smart-purge-for-breeze-cache-wporg.zip` → SVN `trunk` / `tags/` | Directory only |
| **MainWP clients** | `.distignore` only (agency) | `smart-purge-for-breeze-cache.zip` on GitHub Releases | `PPSPB_GITHUB_TOKEN` → `/releases/latest` |

Agency build includes `includes/github-updater.php` and `includes/agency/`. The wporg build omits both (see `.distignore.wporg`).

CI: `plugin-check.yml` tests the **wporg** tree. `release.yml` produces **both** zips on each `v*` tag.

Client rollout policy: [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md).

**Future wp.org releases:** feature backlog — [WPORG_BACKLOG.md](WPORG_BACKLOG.md).  
**Review history:** [WPORG_REVIEW_REPLY.md](WPORG_REVIEW_REPLY.md).  
**What folded into 1.0.0 from agency:** [WPORG_RELEASE_NOTES_1.1.16.md](WPORG_RELEASE_NOTES_1.1.16.md).

Bump `Version:` in the plugin header and `Stable tag:` in `readme.wporg.txt` together for each **wordpress.org** release (and adjust the transform default if you leave `1.0.0`). MainWP client versions may run ahead on GitHub tags.
