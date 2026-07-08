# WordPress.org release notes — agency 1.1.16 → wporg zip

Agency/MainWP is at **1.1.16** (`smart-purge-for-breeze-cache`). The **wordpress.org directory** remains at **1.0.0** until first public SVN tag. This doc lists what to fold from **1.1.15–1.1.16** into the next wporg upload after staging sign-off.

## What ships in the wporg zip (fold from agency)

These paths are **not** agency-only — include them in `smart-purge-for-breeze-cache-wporg.zip`:

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
| `smart-purge-for-breeze-cache-wporg.zip` | **Upload to wordpress.org** (pending slug; folder `smart-purge-for-breeze-cache`) |
| `pixelparade-smart-purge-for-breeze-cache-wporg.zip` | After slug approval — SVN trunk |

Verify before upload:

```bash
bash scripts/verify-plugin-zip.sh smart-purge-for-breeze-cache-wporg.zip
bash scripts/verify-plugin-zip.sh pixelparade-smart-purge-for-breeze-cache-wporg.zip pixelparade-smart-purge-for-breeze-cache
```

## Slug and submission status

- **Pending slug:** `pixelparade-smart-purge-for-breeze-cache` (see [WPORG_REVIEW_REPLY.md](WPORG_REVIEW_REPLY.md))
- **Resubmit** `smart-purge-for-breeze-cache-wporg.zip` after staging sign-off (complete)
- **No SVN** until directory approval — do not commit to `plugins.svn.wordpress.org` prematurely

## Plugin Check (CI note)

Latest `plugin-check.yml` on `main` may report `WordPress.WP.I18n.TextDomainMismatch` for strings that intentionally use Breeze's `breeze` text domain when mirroring Breeze admin bar labels. The wporg upload zip uses literal `'smart-purge-for-breeze-cache'` in plugin-owned strings; review any remaining Breeze-domain strings before resubmit.

## Related docs

- [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md) — checklist and SVN workflow
- [WPORG_REVIEW_REPLY.md](WPORG_REVIEW_REPLY.md) — draft reply and upload steps
- [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md) — agency fleet rollout (separate lane)
