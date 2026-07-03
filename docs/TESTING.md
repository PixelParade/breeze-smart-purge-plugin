# Testing strategy — Smart Purge for Breeze Cache

Three layers: **automated unit tests** (every push), **staging integration** (Bricks + simulated builders), and **production-adjacent BB smoke** (`pixelparade.co`). MainWP Regression Testing is optional post-release monitoring, not a substitute for the first two.

## Layer 1 — PHPUnit (CI, no WordPress)

Pure scanner detection lives in `includes/scanner-detection.php`. Tests run without a full WP bootstrap.

```powershell
composer install
composer test
```

CI job `phpunit` in `.github/workflows/plugin-check.yml` runs on every push to `main` and on pull requests.

**What it covers**

- Gutenberg Query block `postType` detection
- Elementor explicit + implicit post widgets
- Bricks query loops
- Beaver Builder `_fl_builder_data` JSON patterns
- Oxygen JSON references

**What it does not cover**

- `bsp_master_breeze_strategy` URL merging (needs WP + Breeze)
- Admin UI / AJAX
- Cloudflare flush hooks

## Layer 2 — Staging (`breeze-smart-purge.pixelparade.dev`)

Staging deploys the **agency** build from `main` on every push. Site stack: **Bricks**, **Breeze**, **Elementor**, **Beaver Builder Lite** (wp.org plugins for real scanner QA), ACF, ASE, etc.

### Builder plugins on staging

Elementor and Beaver Builder Lite are installed from wordpress.org on the staging site only — they do not ship to client sites. Use them to test **real** `_elementor_data` / `_fl_builder_data` detection (not just simulated meta fixtures).

| Builder | Staging | Client BB smoke |
|---------|---------|-----------------|
| Bricks | Native (theme) | N/A |
| Elementor | wp.org plugin | Optional |
| Beaver Builder | BB Lite (wp.org) | **pixelparade.co** (full BB) |

### Seed fixtures

After deploy (or via Novamira `run-wp-cli`):

```bash
wp eval-file wp-content/plugins/smart-purge-for-breeze-cache/scripts/seed-staging-test-fixtures.php
```

The script is idempotent. It creates:

| Fixture | Purpose |
|---------|---------|
| CPT `bsp_test_project` | Archive + hub mapping target |
| 3 sample CPT posts | Purge-on-save targets |
| `bsp-test-gutenberg-hub` | Real Gutenberg Query Loop |
| `bsp-test-beaver-hub` | Simulated `_fl_builder_data` (no BB plugin needed) |
| `bsp-test-elementor-hub` | Simulated `_elementor_data` |
| Blog page (`page_for_posts`) | Standard post hub |

Then runs **Run Auto-Scanner** logic and prints `bsp_scanned_map`.

### Manual staging checklist (before tagging `v*`)

1. **Settings → Smart Purge** — confirm scanned map lists `/bsp-test-gutenberg-hub/`, `/bsp-test-beaver-hub/`, `/bsp-test-elementor-hub/` for `bsp_test_project`.
2. Edit a `bsp_test_project` post → save → confirm Breeze purges hub URLs (Breeze debug log or cache headers).
3. **Admin bar** — frontend toolbar purge links work when logged in as editor/admin.
4. **Plugin Check** — CI already runs wp.org tree; spot-check staging with Plugin Check if you changed admin UI.
5. **GitHub updater** — after repo is public, Dashboard → Plugins shows update from Releases **without** `BSP_GITHUB_TOKEN`.

### Staging fixture URLs (live on `breeze-smart-purge.pixelparade.dev`)

| Page | URL |
|------|-----|
| Gutenberg hub | https://breeze-smart-purge.pixelparade.dev/bsp-test-gutenberg-hub/ |
| Beaver (simulated meta) | https://breeze-smart-purge.pixelparade.dev/bsp-test-beaver-hub/ |
| Elementor (simulated meta) | https://breeze-smart-purge.pixelparade.dev/bsp-test-elementor-hub/ |
| Bricks (real builder) | https://breeze-smart-purge.pixelparade.dev/bsp-test-bricks-hub/ |

Persistent CPT registration: `wp-content/novamira-sandbox/bsp-test-cpt.php` on staging.

## Layer 3 — Beaver Builder (`pixelparade.co`)

Staging does **not** run Beaver Builder. Use **pixelparade.co** (low traffic, already on MainWP) for real BB integration:

1. Ensure Smart Purge agency build is active.
2. Create or use a page with a **BB Posts** module targeting a real CPT (or `post`).
3. Run Auto-Scanner on the site; confirm Beaver Builder detection in scan log.
4. Publish/edit a child post → verify hub page cache is cleared.

Record the test page URL in MainWP site notes for repeatability.

## Release gate (before `v*` tag)

| Step | Command / action |
|------|------------------|
| Unit tests | `composer test` (or wait for CI green) |
| Plugin Check | CI `plugin-check` job |
| Zip smoke | CI `build-zips` job |
| Staging fixtures | `wp eval-file …/seed-staging-test-fixtures.php` |
| Staging manual | Checklist above |
| BB smoke (periodic) | pixelparade.co hub + purge |
| Tag + release | `git tag vX.Y.Z && git push origin vX.Y.Z` | CI verifies zips (no backslash paths) |
| Fleet audit (after slug migration) | `wp plugin list` + `ls wp-content/plugins/` on sample sites | Exactly one `smart-purge-for-breeze-cache` folder |

## MainWP Regression Testing — worth it?

**Yes, as a fleet safety net — not as primary QA.**

| Use it for | Skip it for |
|------------|-------------|
| Post-update HTML drift on key client URLs | Scanner logic / purge URL correctness |
| Catching theme or unrelated plugin breakage after bulk updates | Replacing PHPUnit or staging fixtures |
| Scheduled monitoring of homepage + 2–3 critical pages per site | Deep Breeze cache verification |

The extension compares **rendered HTML** (structure, meta, inline JS/CSS), not cache headers or purge behavior. Smart Purge changes rarely alter front-end HTML unless purge fails and stale content appears — regression testing might catch that **indirectly**, but only if you monitor pages that change when CPT content updates.

**Recommended setup for PixelParade**

1. Add **pixelparade.co** BB test hub + homepage to Regression Testing targets.
2. Enable **auto-scan after plugin updates** with a 5–10 minute delay (let Breeze warm).
3. Keep PHPUnit + staging as the **merge gate**; use Regression Testing as **post-rollout** monitoring across the ~33-site fleet.

## Public GitHub repo

Agency clients no longer require `BSP_GITHUB_TOKEN` when the repo is public. Token sources in `includes/agency/github-token.php` remain for private forks, rate limits, or legacy installs.

Dual zip builds are unchanged: agency zip includes updater + agency; wp.org zip does not.
