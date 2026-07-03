# Testing strategy — Smart Purge for Breeze Cache

Three layers: **automated unit tests** (every push), **staging integration** (primary QA — all builder + purge testing), and **optional** post-rollout smoke on live client sites. MainWP Regression Testing is optional fleet monitoring, not a substitute for the first two.

**pixelparade.co is a live client site**, not the pre-release test environment. Test on staging before every `v*` tag; only spot-check pixelparade.co after rollout if you want production confirmation.

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

### Builder coverage on staging

Staging uses the **Bricks child theme** — do not install the **Divi theme** (hard conflict). Multiple builder **plugins** can coexist for QA; each fixture page uses one builder pattern.

| Builder | Staging approach | Plugin install? | Conflict risk |
|---------|------------------|-----------------|---------------|
| **Gutenberg** | Real Query Loop fixture | Core | None |
| **Bricks** | Real Posts element (`bsp-test-bricks-hub`) | Theme | None |
| **Elementor** | Real plugin + simulated meta fixture | wp.org — **installed** | Low (already active) |
| **Beaver Builder** | BB Lite + simulated meta fixture | wp.org — **installed** | Low |
| **Oxygen** | Simulated `ct_builder_json` meta | Paid — **not installed** | N/A |
| **WPBakery** | Shortcode fixture in `post_content` | Paid — **not on wp.org** | Plugin-only OK if you upload a license zip later |
| **Divi** | Shortcode fixture in `post_content` | Paid — **no Divi theme** | Divi **theme** conflicts with Bricks; Divi Builder plugin-only is OK if licensed |

**Do not install:** Divi theme, extra caching plugins, or duplicate page-builder stacks you do not need.

**Optional later:** If PixelParade has WPBakery or Divi Builder plugin zips (not the Divi theme), upload to staging via **Plugins → Add New → Upload** to test real editor saves — fixtures already cover scanner detection via shortcode/meta patterns.

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
| `bsp-test-beaver-hub` | Simulated `_fl_builder_data` |
| `bsp-test-elementor-hub` | Simulated `_elementor_data` |
| `bsp-test-bricks-hub` | Real Bricks Posts grid |
| `bsp-test-oxygen-hub` | Simulated `ct_builder_json` |
| `bsp-test-wpbakery-hub` | WPBakery-style `[vc_basic_grid post_type="…"]` shortcode |
| `bsp-test-divi-hub` | Divi-style `[et_pb_blog post_type="…"]` shortcode |
| Blog page (`page_for_posts`) | Standard post hub |

Then runs **Run Auto-Scanner** logic and prints `bsp_scanned_map`.

### Manual staging checklist (before tagging `v*`)

1. **Settings → Smart Purge** — confirm scanned map lists all `bsp-test-*-hub` paths for `bsp_test_project` (Gutenberg, Bricks, Elementor, Beaver, Oxygen, WPBakery, Divi).
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
| Oxygen (simulated meta) | https://breeze-smart-purge.pixelparade.dev/bsp-test-oxygen-hub/ |
| WPBakery (shortcode fixture) | https://breeze-smart-purge.pixelparade.dev/bsp-test-wpbakery-hub/ |
| Divi (shortcode fixture) | https://breeze-smart-purge.pixelparade.dev/bsp-test-divi-hub/ |

Persistent CPT registration: `wp-content/novamira-sandbox/bsp-test-cpt.php` on staging.

## Optional — live site smoke (after rollout, not before tag)

**pixelparade.co** (and other client sites) are for confirming a **production install** looks healthy — not for feature QA before release.

After a fleet or single-site update, a quick check is enough:

1. No duplicate-folder admin notice
2. **Settings → Smart Purge** loads; scanned map looks sane
3. One edit → save → hub cache clears

Do **not** use pixelparade.co as a substitute for the staging checklist before tagging.

## Release gate (before `v*` tag)

| Step | Command / action |
|------|------------------|
| Unit tests | `composer test` (or wait for CI green) |
| Plugin Check | CI `plugin-check` job |
| Zip smoke | CI `build-zips` job |
| Staging fixtures | `wp eval-file …/seed-staging-test-fixtures.php` |
| Staging manual | Checklist above (required before every `v*` tag) |
| Tag + release | `git tag vX.Y.Z && git push origin vX.Y.Z` | CI verifies zips |
| Live smoke (optional) | pixelparade.co or one client site after update | Not a pre-tag gate |
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

1. Add homepage + one hub page on **staging fixture URLs** (or a key client URL post-rollout) to Regression Testing targets.
2. Enable **auto-scan after plugin updates** with a 5–10 minute delay (let Breeze warm).
3. Keep PHPUnit + staging as the **merge gate**; use Regression Testing as **post-rollout** monitoring across the ~33-site fleet.

## Public GitHub repo

Agency clients no longer require `BSP_GITHUB_TOKEN` when the repo is public. Token sources in `includes/agency/github-token.php` remain for private forks, rate limits, or legacy installs.

Dual zip builds are unchanged: agency zip includes updater + agency; wp.org zip does not.
