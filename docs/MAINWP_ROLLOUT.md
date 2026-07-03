# MainWP client rollout

PixelParade **client sites** (MainWP child sites) use the **agency** build: early features, `includes/agency/`, and GitHub Release updates. **External** installs use the **wordpress.org** build only — no MainWP, no `BSP_GITHUB_TOKEN`, no agency code.

Canonical repo: [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin)

## Three environments, one repo

| Environment | Site(s) | Code source | Update mechanism |
|-------------|---------|-------------|------------------|
| **Staging** | `breeze-smart-purge.pixelparade.dev` | `main` branch (CI SSH deploy) | Automatic on every push to `main` |
| **MainWP clients** | PixelParade child sites (e.g. `pixelparade.co`) | GitHub Release **agency** zip | Tag `v*` → `smart-purge-for-breeze-cache.zip` → MainWP or WP Updates |
| **External / wp.org** | Not on MainWP | SVN **wporg** zip | Manual SVN from `smart-purge-for-breeze-cache-wporg.zip` |

**GitHub repo is public** — agency zip updates work without `BSP_GITHUB_TOKEN` on client sites. Dual zip builds are unchanged.

```text
                    breeze-smart-purge-plugin (one repo)
                                    │
          ┌─────────────────────────┼─────────────────────────┐
          ▼                         ▼                         ▼
    push main                  tag v*                    SVN publish
          │                         │                         │
          ▼                         ▼                         ▼
   Staging (agency)          Agency zip                 wporg zip
   instant test              MainWP clients             External users
```

**Staging does not push to clients.** **Clients do not track `main`.** They only change when you tag a release and run an update.

## Two builds from one repo

| Build | Zip filename | Used for | Includes |
|-------|--------------|----------|----------|
| **Agency** | `smart-purge-for-breeze-cache.zip` | MainWP, `BSP_GITHUB_TOKEN` sites | `includes/github-updater.php`, `includes/agency/` |
| **wordpress.org** | `smart-purge-for-breeze-cache-wporg.zip` | SVN trunk/tags, external installs | Core plugin only (no updater, no agency) |

Exclude rules:

- `.distignore` — dev/repo files (both builds)
- `.distignore.wporg` — agency-only paths (wporg build only)

Local build:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1
```

CI builds both zips on every `v*` tag (`release.yml`) and smoke-tests them on every push to `main` (`plugin-check.yml`).

Agency-only PHP lives under `includes/agency/` and loads via `includes/agency/bootstrap.php` when present.

## Per-site policy (MainWP clients)

Apply this to **every** PixelParade child site in MainWP. Record in site notes if helpful.

| Rule | MainWP client | External (wp.org) |
|------|---------------|-------------------|
| Plugin folder | `smart-purge-for-breeze-cache` | Same slug |
| Install from wordpress.org | **Never** | Yes |
| `BSP_GITHUB_TOKEN` | **Optional** (public repo). Use for private fork / rate limits — see token priority below | **No** |
| `BSP_GITHUB_REPO` override | Only if using a fork (default: org repo) | N/A |
| Update source | GitHub Releases → `smart-purge-for-breeze-cache.zip` | wordpress.org API |
| Agency features (`includes/agency/`) | Yes | No |
| MainWP plugin management | Yes | N/A |

### GitHub token (MainWP clients only)

The agency zip includes `includes/agency/github-token.php`, which defines `BSP_GITHUB_TOKEN` automatically when possible. **Priority:**

| Priority | Source | Best for |
|----------|--------|----------|
| 1 | `define( 'BSP_GITHUB_TOKEN', … )` in `wp-config.php` | Single-site override |
| 2 | Server env `BSP_GITHUB_TOKEN` (Cloudways application variable) | **Zero-touch** — all apps on that server |
| 3 | **Settings → Smart Purge → Agency GitHub Updates** | One-time paste per site (encrypted in DB) |

Template: [wp-config-github-updates.example.php](wp-config-github-updates.example.php)

```php
// Cloudways (recommended bulk approach):
if ( ! defined( 'BSP_GITHUB_TOKEN' ) && getenv( 'BSP_GITHUB_TOKEN' ) ) {
    define( 'BSP_GITHUB_TOKEN', getenv( 'BSP_GITHUB_TOKEN' ) );
}
```

Or paste a read-only PAT on the Smart Purge settings screen after installing the agency zip (v1.1.3+).

The updater fetches **`/releases/latest`** and downloads the asset named **`smart-purge-for-breeze-cache.zip`** (agency build). It does **not** read the `main` branch.

Remove `BSP_GITHUB_TOKEN` only when intentionally moving a site to the wordpress.org lane (not planned for clients).

## What does *not* happen automatically

| Action | Staging | MainWP clients | wp.org users |
|--------|---------|----------------|--------------|
| Push to `main` | Updates immediately | No change | No change |
| Tag `v1.2.0` | No change (unless you update manually) | Update **available** when WP checks | No change |
| Publish to SVN | No change | No change | Update available |
| MainWP bulk update | N/A | Updates **when you run it** | N/A |

WordPress does not silently install plugin updates unless auto-updates are enabled for that plugin.

## Rollout workflow (MainWP batch)

### Easy path — MainWP Favorites + public GitHub release (recommended)

The repo is **public**. Use **MainWP Favorites** with a **direct zip download URL** — not the **Install Plugins → Plugin URL** tab (that field is for wordpress.org plugin pages only).

**One-time setup on the MainWP dashboard** (requires **Favorites extension 5.2+** for external zip URLs):

1. **Tag a verified release first** (CI runs `verify-plugin-zip.sh` on every tag).
2. **MainWP → Add-ons → Favorites → Add New**
3. Paste a **direct** agency zip URL:

   **Pinned (safer for first fleet rollout — exact version):**
   ```
   https://github.com/PixelParade/breeze-smart-purge-plugin/releases/download/v1.1.3/smart-purge-for-breeze-cache.zip
   ```

   **Latest (after fleet is healthy — auto-tracks new tags):**
   ```
   https://github.com/PixelParade/breeze-smart-purge-plugin/releases/latest/download/smart-purge-for-breeze-cache.zip
   ```

4. Name it e.g. `Smart Purge for Breeze Cache (agency)` → **Save**.
5. **Favorites icon (custom plugin):** MainWP cannot fetch icons from wordpress.org for agency builds. Copy `scripts/pp-mainwp-favorites-smart-purge-icon.php` to `wp-content/mu-plugins/` on **mainwp.pixelparade.co**, then open **Favorites** and click **Update Version Info** on the Smart Purge row. Icons are also bundled in the zip at `assets/icon-128x128.png` (v1.1.5+).

   Stable icon URLs (also attached to every GitHub Release since v1.1.6):

   ```
   https://github.com/PixelParade/breeze-smart-purge-plugin/releases/latest/download/icon-128x128.png
   ```

**If you get `Extraction failed: Incompatible Archive` on the GitHub URL** (known MainWP Favorites quirk with some external URLs — the zip itself is fine):

1. Confirm **MainWP Favorites extension is 5.2+** (Dashboard → Plugins — update the Favorites add-on).
2. **Workaround (recommended):** download the zip once, then **Favorites → Add New → upload the `.zip` file directly** (not URL). MainWP stores it under `wp-content/uploads/mainwp/.../favorites/` on the dashboard site.
3. Or skip Favorites for the first rollout: **Install Plugins → Upload .zip** and select the downloaded file.

Download link to save locally:

```
https://github.com/PixelParade/breeze-smart-purge-plugin/releases/download/v1.1.3/smart-purge-for-breeze-cache.zip
```

**Install on child sites:**

1. **MainWP → Plugins → Install Plugins → Install Favorites** (or Favorites tab).
2. Select the saved favorite.
3. Select only sites that **do not already** have `smart-purge-for-breeze-cache` (skip sites already migrated).
4. Check **Activate plugin after installation**.
5. **Complete Installation** — MainWP installs per site in the background (no 524 timeout).

**Remove the old slug** (same batch of sites):

6. **MainWP → Plugins** → find **Breeze Smart Purge** (`breeze-smart-purge`).
7. **Deactivate**, then **Delete** on those sites.
8. **MainWP → Sync**.

No `BSP_GITHUB_TOKEN` required on child sites (public repo). Routine updates use GitHub Releases / Dashboard → Plugins → Update — **not** a second Favorites install.

### Alternate path — Upload .zip from your machine

Use when Favorites URL fetch fails or you need an unreleased build:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1
bash scripts/verify-plugin-zip.sh smart-purge-for-breeze-cache.zip
```

1. **MainWP → Plugins → Install Plugins → Upload .zip**
2. Choose `smart-purge-for-breeze-cache.zip` from the repo root.
3. Same site selection, activate, delete old slug, sync as above.

Or add the zip via **Favorites → Add New → direct ZIP upload** (stored under `wp-content/uploads/mainwp/.../favorites/` on the dashboard).

**Legacy backup URL (avoid for Favorites if GitHub works):**  
`https://pixelparade.co/wp-content/uploads/pp-releases/smart-purge-for-breeze-cache.zip`

### Release + routine updates (after everyone is on the new slug)

1. **Develop** on `main` → verify on staging (`breeze-smart-purge.pixelparade.dev`).
2. **Bump** `Version:` in `smart-purge-for-breeze-cache.php` and `Stable tag:` in `readme.txt`.
3. **Tag** and push:
   ```powershell
   git tag v1.0.1
   git push origin v1.0.1
   ```
4. **Confirm** GitHub Release has `smart-purge-for-breeze-cache.zip` (agency).
5. **MainWP → Updates** bulk update (or per-site **Plugins → Update** where `BSP_GITHUB_TOKEN` is set).

### Advanced / emergency only

- **SFTP** single site: `public_html/wp-content/plugins/smart-purge-for-breeze-cache/`
- **`scripts/pp-mainwp-agency-rollout.php`** — one-time MU plugin; batch in small groups if ever needed. Remove from `wp-content/mu-plugins/` on the dashboard after use.

MainWP MCP can **sync, activate, deactivate, and delete** plugins but **cannot upload zips** — use the dashboard for install.

## Fleet rollout safety (learned Jul 2026)

Bulk install to ~33 child sites caused **duplicate plugin folders** when the new slug was added before the old one was removed, and when a **Windows zip with backslash paths** created extra junk directories. Two active copies can **double-run purge hooks**.

### Never

| Don't | Why |
|-------|-----|
| Install new slug without deleting `breeze-smart-purge` | Two plugins, same `bsp_*` options, double hooks |
| Use MainWP **Plugin URL** on Install Plugins for a `.zip` | Wrong tab — use **Favorites** with direct zip URL or **Upload .zip** |
| Re-run bulk install on sites already on `smart-purge-for-breeze-cache` | Temp folders / confused state |
| Fire `pp-mainwp-agency-rollout.php` across 30+ sites in one HTTP hit | Timeouts, partial failure; use MainWP UI |
| Ship a zip without `scripts/verify-plugin-zip.sh` | Backslash entries → undeletable junk on Linux |

### Always (before fleet push)

1. **Build + verify zip**
   ```powershell
   powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1
   bash scripts/verify-plugin-zip.sh smart-purge-for-breeze-cache.zip
   ```
2. **Sample audit** — 2–3 sites (include one non-Cloudways if any):
   ```bash
   wp plugin list | grep -iE 'smart-purge|breeze-smart'
   ls wp-content/plugins/ | grep -E 'smart-purge|breeze-smart'
   ```
   Expect **one** folder: `smart-purge-for-breeze-cache`.
3. **Slug migration order** (same sites in one MainWP batch):
   - Upload zip → install **new** slug → **activate** new
   - **Deactivate** `breeze-smart-purge` → **delete** `breeze-smart-purge`
   - MainWP **Sync**
4. **Post-rollout** — confirm no red **duplicate folders** notice in wp-admin (plugin self-check).

### Junk folders (bad zip cleanup)

If `wp-content/plugins/` has `smart-purge-for-breeze-cache-XXXXXX/` or filenames with `\`:

- WP Admin **Delete plugin** often **fails**
- Remove via **SFTP/SSH**: delete each file (escape backslashes), then `rmdir` the folder
- Non-Cloudways hosts: file manager in hosting panel

### Plugin self-check

If legacy `breeze-smart-purge` or extra `smart-purge-for-breeze-cache-*` directories exist, wp-admin shows an error notice naming folders to remove.

## Version numbering when lanes diverge

| Lane | Typical versions | Notes |
|------|------------------|-------|
| MainWP clients | `1.0.0`, `1.0.1`, … | Full semver on GitHub tags |
| wordpress.org | May start at `1.0.0` | Public changelog only; can lag behind clients |
| Staging | Header on `main` | May not match latest tag until you bump header |

Client sites compare versions only against **GitHub Releases**, not wp.org.

## Site inventory (fill in MainWP)

Keep a simple register (MainWP site notes or spreadsheet):

| MainWP site ID | Domain | Lane | Plugin version | Token set | Last rollout | Notes |
|----------------|--------|------|--------------|-----------|--------------|-------|
| 16 | pixelparade.co | Agency | 1.1.2 | wp-config | 2026-07-03 | GitHub updater verified; v1.1.3+ supports env + settings UI |
| | | | | | | |

## Emergency hotfix

Bypass CI/releases: SFTP agency files to  
`public_html/wp-content/plugins/smart-purge-for-breeze-cache/`  
then sync MainWP. Follow up with a proper tag when stable.

## Related docs

- [GITHUB_SETUP.md](GITHUB_SETUP.md) — repo, staging CI, releases, secrets
- [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md) — directory / SVN lane
- [ACCESS.md](ACCESS.md) — staging SSH and Novamira MCP
- [SECURITY.md](SECURITY.md) — tokens and credentials
