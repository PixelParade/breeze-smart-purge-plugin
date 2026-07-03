# WordPress.org release backlog

Things to ship or verify on the **wordpress.org** lane after directory approval.  
Agency/MainWP is on **GitHub Releases** (`smart-purge-for-breeze-cache.zip`, currently **1.1.13**). The public lane uses **`smart-purge-for-breeze-cache-wporg.zip`** → SVN `trunk` / `tags/`.

See also: [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md), [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md).

---

## Versioning strategy

| Lane | Rule |
|------|------|
| **First wp.org release** | Tag **`1.0.0`** on SVN even if agency is ahead (per [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md)) |
| **Later wp.org releases** | Bump `Version:` + `Stable tag:` together; write a **public** changelog (no MainWP/GitHub internals) |
| **Agency** | May stay ahead indefinitely; wp.org users get updates only from the plugin directory |

**Suggested first public changelog:** fold agency features below into one **1.0.0** “initial release” entry, or split into **1.0.0** + **1.1.0** if you want parity with feature milestones.

---

## Code & behavior (already in wporg zip — publish via SVN)

These ship in `smart-purge-for-breeze-cache-wporg.zip` today (agency-only paths stripped by `.distignore.wporg`). Confirm each is mentioned in the public `readme.txt` changelog when you tag.

### Scanner & purge core

| Item | Agency ref | Notes for wp.org |
|------|------------|------------------|
| WPBakery shortcode grid detection | 1.1.4 | `includes/scanner-detection.php` |
| Divi shortcode grid detection | 1.1.4 | Same module + PHPUnit coverage in CI |
| Gutenberg, Elementor, Bricks, Beaver, Oxygen | 1.0.0+ | Already in baseline |
| Duplicate / legacy folder admin notice | 1.1.4 | Warns if `breeze-smart-purge` or junk `smart-purge-for-breeze-cache-*` dirs exist |
| Deferred scan on activation | 1.0.0+ | Avoids timeout on activate |
| Activation notice + **Review settings** button | 1.1.8 | Links to **Settings → Smart Purge** |
| Security hardening (scan log output) | 1.1.1 | |

### Settings UI (wp.org subset)

| Item | Agency ref | wp.org gets? |
|------|------------|--------------|
| Collapsible **“What does Smart Purge do?”** intro | 1.1.13 | **Yes** — main tab only |
| **Plugin Updates** tab (GitHub Releases panel) | 1.1.13 | **No** — gated on `BSP_AGENCY_BUILD` |
| PAT / `BSP_GITHUB_TOKEN` settings UI | 1.1.3 | **No** — `includes/agency/` excluded |

### Plugin icons (in zip + Updates screen)

| Item | Agency ref | wp.org action |
|------|------------|---------------|
| `assets/icon-128x128.png`, `icon-256x256.png` in plugin zip | 1.1.5+ | Included in wporg zip; directory also needs SVN `assets/` |
| `assets/icon-512x512.png` | 1.1.9 | Agency/GitHub only — **not required** for wp.org |
| `assets/admin/plugin-assets.css` (`object-fit: cover`, transparent icons) | 1.1.12 | Included in wporg zip |
| Fixed icon/banner dimensions (no squish) | 1.1.7 | Regenerate **SVN** assets from current artwork |

---

## SVN `assets/` (directory page — not inside plugin zip)

Upload to `plugins.svn.wordpress.org/<slug>/assets/` (see `assets/wporg/README.md`).

| File | Status / action |
|------|----------------|
| `icon-128x128.png` | Upload; match current agency artwork (`py scripts/build-padded-icons.py`) |
| `icon-256x256.png` | Upload @2x |
| `banner-772x250.png` | Upload |
| `banner-1544x500.png` | Upload retina banner |
| **Screenshots** | **Not done** — add `== Screenshots ==` to `readme.txt` + PNGs in SVN `assets/` |
| `icon-512x512.png` | Optional; wp.org does not use 512 in directory |

After agency icon changes (1.1.7–1.1.12), **re-copy** refreshed PNGs from `assets/wporg/` (or regenerate from `assets/icon-*.png`) before first SVN asset commit.

---

## `readme.txt` (public-facing copy)

Update before each SVN tag:

| Section | Action |
|---------|--------|
| `Contributors:` | `kevpress88` (wp.org username) |
| `Tested up to:` | Match current WordPress (now **7.0** — bump with each release) |
| `Requires Plugins: breeze` | Already in plugin header; keep in sync |
| `== Description ==` | Mention WPBakery + Divi in builder list (1.1.4) |
| `== Changelog ==` | **Public versions only** — no GitHub token, MainWP, or Favorites notes |
| `== Screenshots ==` | Add before launch if possible (settings screen, scan log, mappings table) |

---

## Pre-flight checklist (every wp.org release)

1. `powershell -File scripts/build-plugin-zips.ps1` → confirm `smart-purge-for-breeze-cache-wporg.zip`
2. `bash scripts/verify-plugin-zip.sh smart-purge-for-breeze-cache-wporg.zip`
3. CI **Plugin Check** green on wporg tree (`.distignore` + `.distignore.wporg`)
4. Confirm **absent** from wporg build:
   - `includes/github-updater.php`
   - `includes/agency/`
5. `wp plugin check smart-purge-for-breeze-cache` on a clean wporg tree
6. Smoke on a **non-MainWP** site with Breeze only (install from wporg zip, activate, run Smart Scan)
7. SVN: copy trunk → `tags/X.Y.Z`, commit; update `Stable tag:` in `readme.txt` on trunk

---

## Explicitly **not** for wordpress.org

Do **not** merge these into the wporg zip or public readme:

| Item | Why |
|------|-----|
| `includes/github-updater.php` | Custom updater violates directory guidelines; updates via wp.org only |
| `includes/agency/` (`BSP_GITHUB_TOKEN`, encrypted PAT option) | Agency/MainWP only |
| **Plugin Updates** settings tab | Agency UI only |
| `BSP_GITHUB_TOKEN` / `wp-config` examples | Document in agency docs only |
| `scripts/pp-mainwp-favorites-smart-purge-icon.php` | MainWP dashboard mu-plugin |
| `scripts/pp-mainwp-agency-rollout.php` | One-time fleet rollout |
| GitHub Release standalone `icon-*.png` attachments | MainWP Favorites workaround |
| Public-repo / MainWP rollout notes | [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md) stays internal |

---

## Agency → wp.org feature map (quick reference)

Use when writing the **first** public changelog(s):

| Agency version | Ship to wp.org? | Public summary |
|----------------|-----------------|----------------|
| 1.0.0 | Yes | Initial: scanner, manual/ignored URLs, taxonomy purge, Cloudflare sync option, frontend Breeze toolbar |
| 1.1.1 | Yes | Slug/settings alignment, security hardening |
| 1.1.2 | No | Private GitHub token fix — N/A on wp.org |
| 1.1.3 | No | Agency GitHub token bootstrap — N/A |
| 1.1.4 | Yes | WPBakery + Divi detection; duplicate-folder notice |
| 1.1.5–1.1.7 | Yes | Plugin icons in zip; correct dimensions |
| 1.1.8 | Partial | Review settings on activation — yes; simplified Plugin Updates panel — N/A |
| 1.1.9–1.1.11 | Partial | Icon display CSS + assets — yes; checkerboard experiments — superseded by 1.1.12 |
| 1.1.12 | Yes | Transparent icons + `object-fit: cover` in admin |
| 1.1.13 | Partial | Collapsible intro — yes; Plugin Updates tab — N/A |

---

## After approval — one-time setup

1. SVN checkout: `svn co https://plugins.svn.wordpress.org/<approved-slug>`
2. Copy wporg zip contents → `trunk/`
3. Upload `assets/wporg/*.png` → SVN `assets/`
4. `svn commit` trunk + `tags/1.0.0`
5. Confirm plugin appears on wordpress.org and **Plugin Check** passes on live listing
6. Add wp.org URL to repo README (optional)

---

## Ongoing sync discipline

When cutting an **agency** GitHub tag (`v*`):

1. Ask: “Does this change touch code **outside** `includes/agency/` and `includes/github-updater.php`?”
2. If **yes** → plan a wp.org SVN release (or batch into next public tag)
3. If **agency-only** → no wp.org action
4. If **icons/banners** changed → refresh `assets/wporg/` and SVN `assets/`
5. Keep `docs/TESTING.md` staging checklist as pre-tag QA for **both** lanes
