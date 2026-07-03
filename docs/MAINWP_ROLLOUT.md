# MainWP client rollout

PixelParade **client sites** (MainWP child sites) use the **agency** build: early features, `includes/agency/`, and GitHub Release updates. **External** installs use the **wordpress.org** build only — no MainWP, no `BSP_GITHUB_TOKEN`, no agency code.

Canonical repo: [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin)

## Three environments, one repo

| Environment | Site(s) | Code source | Update mechanism |
|-------------|---------|-------------|------------------|
| **Staging** | `breeze-smart-purge.pixelparade.dev` | `main` branch (CI SSH deploy) | Automatic on every push to `main` |
| **MainWP clients** | PixelParade child sites (e.g. `pixelparade.co`) | GitHub Release **agency** zip | Tag `v*` → `smart-purge-for-breeze-cache.zip` → MainWP or WP Updates |
| **External / wp.org** | Not on MainWP | SVN **wporg** zip | Manual SVN from `smart-purge-for-breeze-cache-wporg.zip` |

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
| `BSP_GITHUB_TOKEN` in `wp-config.php` | **Yes** (while repo is private) | **No** |
| `BSP_GITHUB_REPO` override | Only if using a fork (default: org repo) | N/A |
| Update source | GitHub Releases → `smart-purge-for-breeze-cache.zip` | wordpress.org API |
| Agency features (`includes/agency/`) | Yes | No |
| MainWP plugin management | Yes | N/A |

### wp-config (MainWP clients only)

Template: [wp-config-github-updates.example.php](wp-config-github-updates.example.php)

```php
define( 'BSP_GITHUB_TOKEN', 'read-only-github-pat' );
// Optional: define( 'BSP_GITHUB_REPO', 'PixelParade/breeze-smart-purge-plugin' );
```

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

### Easy path — bulk ZIP install (recommended)

Use the **MainWP dashboard** for slug migrations and first installs. Do **not** run a single HTTP script across 30+ sites (Cloudflare/origin timeouts).

**Build the zip locally (once):**

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1
```

This creates `smart-purge-for-breeze-cache.zip` in the repo root (agency build, current header version).

**Install on all client sites (MainWP UI):**

1. Log in to [mainwp.pixelparade.co](https://mainwp.pixelparade.co/) (password gate → wp-admin).
2. Go to **MainWP → Plugins → Install Plugins**.
3. Open the **Upload .zip** tab (not **Plugin URL** — that field is for wordpress.org plugin pages, not a `.zip` link).
4. Choose **`smart-purge-for-breeze-cache.zip`** from your computer (repo root after running the build script above).
5. Select the **~33 child sites** that still have `breeze-smart-purge` (or select all clients; skip sites that never had the plugin).
6. Check **Activate plugin after installation**.
7. Click **Complete Installation** and wait for the green checkmarks (MainWP installs in the background per site — no 524 timeout).

**Remove the old slug:**

8. Go to **MainWP → Plugins** (manage installed plugins).
9. Find **Breeze Smart Purge** (`breeze-smart-purge`).
10. **Deactivate** on the same sites, then **Delete** (or use Cursor + MainWP MCP for bulk deactivate/delete after step 7).

**Finish:**

11. **MainWP → Sync** on updated sites (or **Sync all**).
12. Spot-check: **Settings → Smart Purge**, Breeze toolbar, homepage loads.

Settings (`bsp_*` options) survive the slug change — same option prefix in both plugins.

**Optional:** Add the zip to **MainWP → Plugins → Favorites** so the next rollout is upload-once, install-many.

**Pre-built zip on PixelParade (backup copy only):**  
`https://pixelparade.co/wp-content/uploads/pp-releases/smart-purge-for-breeze-cache.zip`  
Do not paste this into MainWP **Plugin URL** — use **Upload .zip** with the file from your machine (or download that link first, then upload the file).

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
| 16 | pixelparade.co | Agency | 1.1.1 | Yes | 2026-07-03 | Slug `smart-purge-for-breeze-cache`; remove legacy `breeze-smart-purge` |
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
