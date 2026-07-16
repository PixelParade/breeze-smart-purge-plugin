# WordPress.org release notes — agency 1.1.16 → wporg zip

Agency/MainWP may be at **1.1.16+** (`smart-purge-for-breeze-cache`). The **wordpress.org directory** first public tag is **1.0.0**. Plugin is **approved**; use [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md) for the first SVN upload.

## What ships in the wporg zip (fold from agency)

These paths are **not** agency-only — included in `pixelparade-smart-purge-for-breeze-cache-wporg.zip`:

| Area | Files | Notes |
|------|-------|-------|
| Scan UI fix | `smart-purge-for-breeze-cache.php`, `assets/admin/settings.js`, `assets/admin/settings.css` | `bspInitSettingsUi()` runs when DOM is ready; footer-enqueued JS no longer dead on load |
| Live scan log | Same + `readme.txt` changelog | Progress transient + `bsp_ajax_scan_status` polling during Smart Scan |
| Per-type utility CPT toggles | `smart-purge-for-breeze-cache.php`, `assets/admin/settings.js`, `assets/admin/settings.css` | Expandable checkboxes to hide individual utility CPTs from table and scans |
| Changelog | `readme.wporg.txt` (copied into wporg zip as `readme.txt`) | Summarize 1.0.0 public-facing items only — do not publish internal 1.1.x version numbers on the directory page |

## Excluded from wporg zip (agency / staging only)

Do **not** include in the public build:

- `includes/github-updater.php`, `includes/agency/` (`.distignore.wporg`)
- `scripts/staging/`, `scripts/install-staging-test-mu-plugin.ps1`, `scripts/seed-staging-test-fixtures.php`
- Staging mu-plugin `bsp-staging-test-cpt` (never deployed to clients or wp.org)

## Rebuild command

From repo root:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1
```

Outputs:

| Artifact | Use |
|----------|-----|
| `smart-purge-for-breeze-cache.zip` | MainWP / GitHub Releases (agency) |
| `pixelparade-smart-purge-for-breeze-cache-wporg.zip` | **SVN trunk** (approved slug; version forced to **1.0.0**) |
| `smart-purge-for-breeze-cache-wporg.zip` | Compatibility alias (same bytes as pixelparade zip) |

Verify before SVN:

```bash
bash scripts/verify-plugin-zip.sh pixelparade-smart-purge-for-breeze-cache-wporg.zip pixelparade-smart-purge-for-breeze-cache
```

## Slug and submission status

- **Approved slug:** `pixelparade-smart-purge-for-breeze-cache` (see [WPORG_REVIEW_REPLY.md](WPORG_REVIEW_REPLY.md))
- **First SVN version:** **1.0.0** — checklist in [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md)
- Icons/banners: repo `assets/wporg/` → SVN `assets/` only (not trunk)

## Plugin Check (CI note)

Latest `plugin-check.yml` on `main` may report `WordPress.WP.I18n.TextDomainMismatch` for strings that intentionally use Breeze's `breeze` text domain when mirroring Breeze admin bar labels. That ignore is intentional; plugin-owned strings use the pixelparade slug textdomain after transform.

## Related docs

- [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md) — checklist and SVN workflow
- [WPORG_REVIEW_REPLY.md](WPORG_REVIEW_REPLY.md) — draft reply and upload steps
- [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md) — agency fleet rollout (separate lane)
