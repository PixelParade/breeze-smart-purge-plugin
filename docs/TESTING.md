# Testing strategy — Smart Purge for Breeze Cache

Three layers: **automated unit tests** (every push), **staging integration** (primary QA — all builder + purge testing), and a **mandatory** pre-release smoke on **pixelparade.co** (MainWP site ID **16**) before agency commit/push and `v*` tag. MainWP Regression Testing is optional fleet monitoring, not a substitute for PHPUnit + staging.

**pixelparade.co** is a live client site — keep smoke **lightweight** (health + version + no duplicates). Deep feature QA stays on staging. Full agency gate: [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md) § Agency release checklist.

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

### One-time staging setup (CPT mu-plugin)

**Run Smart Scan** and purge-on-save tests need the `bsp_test_project` CPT registered on every request. The seed script registers it only during WP-CLI; a **must-use plugin** on staging provides persistence.

| Repo path | Staging install path |
|-----------|----------------------|
| `scripts/staging/bsp-staging-test-cpt.php` | `wp-content/mu-plugins/bsp-staging-test-cpt.php` |
| `scripts/staging/bsp-test-cpt.php` | `wp-content/mu-plugins/bsp-staging-test-cpt/bsp-test-cpt.php` |

**Not shipped:** `scripts/` is excluded from agency and wp.org zips (`.distignore`). MainWP clients never receive this CPT.

**Install (from repo root, with `.env.deploy.local`):**

```powershell
.\scripts\install-staging-test-mu-plugin.ps1
```

**Verify:**

```bash
wp post-type list --fields=name,public | grep bsp_test_project
```

The loader also checks legacy `wp-content/novamira-sandbox/bsp-test-cpt.php` if present. Prefer the mu-plugins layout above for new installs.

### Seed fixtures

After deploy and mu-plugin install (or via Novamira `run-wp-cli`):

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

### Run Smart Scan expectations

After mu-plugin + seed:

1. **Settings → Smart Purge → Run Smart Scan** — completes without error; log panel shows hub detection progress.
2. **Scanned map** (`bsp_scanned_map`) lists `bsp_test_project` with hub paths for all `bsp-test-*-hub` fixture pages (Gutenberg, Bricks, Elementor, Beaver, Oxygen, WPBakery, Divi).
3. **Re-scan** after editing a fixture page — map updates; no duplicate or missing hubs.
4. Without the mu-plugin, seed may succeed in WP-CLI but **Run Smart Scan in wp-admin will not detect CPT hubs** because `bsp_test_project` is not registered on web requests.

### Manual staging checklist (deeper QA — fixtures)

Use when changing scanner/purge behavior (in addition to the lightweight agency smoke below):

1. **Settings → Smart Purge** — confirm scanned map lists all `bsp-test-*-hub` paths for `bsp_test_project` (Gutenberg, Bricks, Elementor, Beaver, Oxygen, WPBakery, Divi).
2. Edit a `bsp_test_project` post → save → confirm Breeze purges hub URLs (Breeze debug log or cache headers).
3. **Admin bar** — frontend toolbar purge links work when logged in as editor/admin.
4. **Plugin Check** — CI already runs wp.org tree; spot-check staging with Plugin Check if you changed admin UI.
5. **GitHub updater** — after repo is public, Dashboard → Plugins shows update from Releases **without** `BSP_GITHUB_TOKEN`.

### Agency smoke checklist (mandatory before commit/push and `v*` tag)

Agents **must** run both smokes before an agency release commit/push and before tagging. Do not tag if either fails.

#### Staging (`breeze-smart-purge.pixelparade.dev`)

| Check | How |
|-------|-----|
| Version matches candidate | `wp plugin list` / `Version:` in deployed `smart-purge-for-breeze-cache.php` |
| Settings load | Settings → Smart Purge (or WP-CLI/options read) — no critical error |
| Admin assets | Network: `settings.js` and `settings.css` **200** (plugin dir mode **755**) |
| Scan / save (optional but preferred) | Run Smart Scan or WP-CLI simulate; settings save succeeds |
| No PHP fatal | Homepage + wp-admin load; `debug.log` clear of new fatals |

#### pixelparade.co (MainWP site ID **16**)

| Check | How |
|-------|-----|
| Plugin present / active | MainWP sync + `get_site_plugins` (or browser/SSO) |
| Expected version | Matches the live/expected release under test (post-updater: matches new tag) |
| Settings OK | Settings → Smart Purge loads when browser access available |
| No duplicates | Exactly one folder `smart-purge-for-breeze-cache` — no legacy `breeze-smart-purge` or junk `smart-purge-for-breeze-cache-*` |

Then: **commit + push** → **tag `v*`** → GitHub Release (clients update via updater — no MainWP zip fleet).

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

Persistent CPT registration: mu-plugin `wp-content/mu-plugins/bsp-staging-test-cpt.php` (source in `scripts/staging/`).

## Live site smoke (pixelparade.co — mandatory for agency releases)

**pixelparade.co** (MainWP site **16**) is a **required** smoke target in the agency release gate (with staging). It is **not** a substitute for staging fixture/builder QA.

**Before** agency commit/push and `v*` tag: run the **Agency smoke checklist** above.

**After** clients update via GitHub Releases (optional deeper spot-check):

1. No duplicate-folder admin notice
2. **Settings → Smart Purge** loads; scanned map looks sane
3. One edit → save → hub cache clears

## Release gate (before agency commit/push and `v*` tag)

| Step | Command / action |
|------|------------------|
| Unit tests | `composer test` (or wait for CI green) |
| Plugin Check | CI `plugin-check` job |
| Zip smoke | CI `build-zips` job (on push/tag) |
| Staging mu-plugin (one-time) | `.\scripts\install-staging-test-mu-plugin.ps1` |
| Staging fixtures | `wp eval-file …/seed-staging-test-fixtures.php` (when changing scanner/purge) |
| **Smoke staging** | Agency smoke checklist — **required** |
| **Smoke pixelparade.co** | Agency smoke checklist (site **16**) — **required** |
| Commit + push | Only after both smokes pass |
| Tag + release | `git tag vX.Y.Z && git push origin vX.Y.Z` — CI verifies zips |
| Post-updater spot-check | pixelparade.co at new version (optional deeper) |
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
