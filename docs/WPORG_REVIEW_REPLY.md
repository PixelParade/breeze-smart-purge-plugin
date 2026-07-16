# WordPress.org review reply (Jul 2026)

**Review ID:** R pixelparade-smart-purge-for-breeze-cache/kevpress88/3Jul26/T2 9Jul26/4.0.1

**Status:** **APPROVED** — slug `pixelparade-smart-purge-for-breeze-cache` (kevpress88).

Next step is the **first SVN upload** (not another zip upload to the review form). Follow the Windows checklist in [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md).

Zip for trunk: **`pixelparade-smart-purge-for-breeze-cache-wporg.zip`** (built via `scripts/build-plugin-zips.ps1`). Version inside: **1.0.0**.

## Draft email reply (historical — used during review)

> Hi,
>
> Thank you for the review. I have uploaded a corrected build.
>
> A few notes for context:
>
> - Directory icons (`assets/icon-*.png`) are excluded from the plugin zip. Those stay in our repo for later upload to the SVN `assets/` folder only (after approval).
> - We intentionally keep `wp_localize_script( 'breeze-backend', 'breeze_token_name', … )` when enabling Breeze’s frontend admin-bar toolbar. That object name is required by Breeze’s own `breeze-main.js`; renaming it would break the toolbar. Our own script handle is `ppspb-ajaxurl`; only the Breeze-expected localize object name is unchanged.
> - Please reserve / continue with slug **`pixelparade-smart-purge-for-breeze-cache`**. Display name: **PixelParade Smart Purge for Breeze Cache** (unofficial Breeze add-on; PixelParade LLC is not affiliated with Cloudways or Breeze).
>
> Thanks,  
> Kevin

## Issues addressed in the approved build

| Review item | Fix |
|-------------|-----|
| Icons shipped inside plugin zip | Excluded via `.distignore.wporg` (`assets/icon-*.png`, `assets/wporg/`) |
| Text domain ≠ slug | Wporg transform sets slug + textdomain to `pixelparade-smart-purge-for-breeze-cache` |
| Prefix too short (`bsp`) | Active API uses `ppspb_` / `PPSPB_*`; legacy `bsp_*` option name strings only in migration/uninstall |
| `breeze_token_name` localize | Kept intentionally for Breeze JS compatibility (documented above) |
| Agency updater / GitHub token | Excluded from wporg zip via `.distignore.wporg` |

## After approval (current)

1. Build: `powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1`
2. Use: **`pixelparade-smart-purge-for-breeze-cache-wporg.zip`**
3. Follow [WPORG_SUBMISSION.md](WPORG_SUBMISSION.md) — First SVN upload (Windows)
4. Commit trunk → tag `1.0.0` → then SVN `assets/` icons

## Agency / MainWP lane

- Agency zip folder slug stays **`smart-purge-for-breeze-cache`** (existing clients).
- Same `ppspb_` code; settings migrate automatically from `bsp_*` options on upgrade.
- `BSP_GITHUB_TOKEN` in wp-config still works; prefer `PPSPB_GITHUB_TOKEN` for new installs.
- Routine agency updates: tag `v*` + GitHub Release (`smart-purge-for-breeze-cache.zip`) — **not** MainWP Upload .zip (that was initial seed only).
