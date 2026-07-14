# MainWP client rollout

PixelParade **client sites** (MainWP child sites) use the **agency** build: early features, `includes/agency/`, and GitHub Release updates. **External** installs use the **wordpress.org** build only — no MainWP, no `BSP_GITHUB_TOKEN`, no agency code.

Canonical repo: [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin)

## Three environments, one repo

| Environment | Site(s) | Code source | Update mechanism |
|-------------|---------|-------------|------------------|
| **Staging** | `breeze-smart-purge.pixelparade.dev` | `main` branch (CI SSH deploy) | Automatic on every push to `main` |
| **MainWP clients** | PixelParade child sites (e.g. `pixelparade.co`) | GitHub Release **agency** zip | Tag `v*` → agency zip on Releases → **GitHub updater** on each child (`Plugins → Update`) |
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

**Staging does not push to clients.** **Clients do not track `main`.** They only change when you tag a GitHub Release; each site’s agency **GitHub updater** then offers the new version under **Plugins → Update**.

### Initial install vs routine updates (read this first)

| Phase | When | Method |
|-------|------|--------|
| **Initial install only** | Site does **not** yet have `smart-purge-for-breeze-cache` (or needs the agency zip that includes the updater) | MainWP **Upload .zip** / Favorites — once per site |
| **Routine agency updates** | Site already has the agency build + GitHub updater | Tag `v*` + GitHub Release with `smart-purge-for-breeze-cache.zip` — **do not** re-Upload .zip or bulk-install |

After the fleet has the updater, **never** treat MainWP zip install as the default release path. That was only to seed sites with a build that includes `includes/github-updater.php`.

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

## Agency release checklist (mandatory gate)

Sites that already have the agency plugin **already include the GitHub updater**. For every agency version bump, **do not commit/push the release or tag `v*` until both smokes pass**:

| Step | Action | Pass criteria |
|------|--------|---------------|
| 1 | **Smoke staging** (`breeze-smart-purge.pixelparade.dev`) | Candidate on staging (SCP/Novamira or CI); version header matches; Settings → Smart Purge loads; `settings.js`/`settings.css` **200** (or WP-CLI scan/save); no PHP fatal — see [TESTING.md](TESTING.md) § Agency smoke checklist |
| 2 | **Smoke pixelparade.co** (MainWP site ID **16**) | MainWP sync / `get_site_plugins` (or browser/SSO): plugin active at expected version; Settings OK; exactly **one** folder `smart-purge-for-breeze-cache` |
| 3 | **Commit + push** to `main` | Only after steps 1–2 pass; CI redeploys staging |
| 4 | **Tag `v*` + GitHub Release** | Agency zip auto-built; clients get **Plugins → Update** — **no** MainWP zip fleet |

```powershell
git tag v1.1.17
git push origin v1.1.17
```

5. **Confirm** the release has asset **`smart-purge-for-breeze-cache.zip`** (agency). That is what `/releases/latest` serves.
6. On child sites: WordPress **Dashboard → Plugins → Update** (or MainWP **Updates** for that plugin only). Optional: `BSP_GITHUB_TOKEN` / `PPSPB_GITHUB_TOKEN` for rate limits — not required on the public repo.
7. Optional post-updater spot-check on pixelparade.co that the **new** version is active.

**Bump** `Version:` in `smart-purge-for-breeze-cache.php` (keep `PPSPB_VERSION` in sync) and `Stable tag:` in `readme.txt` as part of the release commit in step 3.

**Do not** MainWP Upload .zip / Favorites re-install / bulk zip install for routine releases — that was for initial seeding only and risks duplicate folders.

## Standard path — routine agency updates (default after fleet is seeded)

Follow the **Agency release checklist** above for every new agency version. Summary: smoke staging → smoke pixelparade.co → commit/push → tag `v*` + GitHub Release → clients update via the GitHub updater.

## Initial install only — seed a site that lacks the updater

Use this **once** when a child site does not yet have `smart-purge-for-breeze-cache` (or lacks the agency zip that ships `includes/github-updater.php`). After that, switch permanently to the standard path above.

### MainWP Favorites or Upload .zip (first install)

The repo is **public**. Prefer **MainWP Favorites** with a **direct zip download URL** — not the **Install Plugins → Plugin URL** tab (that field is for wordpress.org plugin pages only).

**One-time Favorites setup** (requires **Favorites extension 5.2+**):

1. Tag a verified release first (CI runs `verify-plugin-zip.sh` on every tag).
2. **MainWP → Add-ons → Favorites → Add New**
3. Paste a **direct** agency zip URL (pinned tag or `.../releases/latest/download/smart-purge-for-breeze-cache.zip`).
4. Save, then **Install Favorites** only on sites that **do not already** have the plugin.
5. Activate; deactivate/delete legacy `breeze-smart-purge` if present; MainWP sync.

**If Favorites URL extract fails:** download the zip, then **Favorites → Add New → upload the `.zip`**, or **Install Plugins → Upload .zip** (initial install only).

Icons: bundled at `assets/icon-128x128.png`, or release asset URLs under `/releases/latest/download/icon-*.png`. Optional MU-plugin: `scripts/pp-mainwp-favorites-smart-purge-icon.php` on **mainwp.pixelparade.co**.

**Legacy backup URL (avoid if GitHub works):**  
`https://pixelparade.co/wp-content/uploads/pp-releases/smart-purge-for-breeze-cache.zip`

### Advanced / emergency only

- **SFTP** single site: `public_html/wp-content/plugins/smart-purge-for-breeze-cache/`
- **`scripts/pp-mainwp-agency-rollout.php`** — historical one-time MU plugin; do **not** use for routine updates. Remove from dashboard `mu-plugins/` if still present.

MainWP MCP can sync/activate/deactivate/delete plugins but **cannot upload zips** — initial install still uses the dashboard UI once.

## Fleet rollout safety (learned Jul 2026)

Bulk install to ~33 child sites caused **duplicate plugin folders** when the new slug was added before the old one was removed, and when a **Windows zip with backslash paths** created extra junk directories. Two active copies can **double-run purge hooks**.

### Never

| Don't | Why |
|-------|-----|
| Install new slug without deleting `breeze-smart-purge` | Two plugins, same `bsp_*` options, double hooks |
| Use MainWP **Plugin URL** on Install Plugins for a `.zip` | Wrong tab — Favorites / Upload .zip only for **initial** install |
| Re-run bulk zip install on sites that already have the updater | Temp folders / duplicates — use GitHub Release + Plugins → Update |
| Fire `pp-mainwp-agency-rollout.php` across 30+ sites in one HTTP hit | Timeouts, partial failure; initial install via MainWP UI only |
| Ship a zip without `scripts/verify-plugin-zip.sh` | Backslash entries → undeletable junk on Linux |

### Always (before an **initial** fleet install only — not for routine tags)

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
