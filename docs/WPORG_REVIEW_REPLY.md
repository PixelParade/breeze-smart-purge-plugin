# WordPress.org review reply (Jul 2026)

**Review ID:** R pixelparade-smart-purge-for-breeze-cache/kevpress88/3Jul26/T2 9Jul26/4.0.1

Upload the corrected package: **`pixelparade-smart-purge-for-breeze-cache-wporg.zip`** (built via `scripts/build-plugin-zips.ps1` or `.sh`). Do **not** SVN until the team confirms approval.

## Draft email reply

> Hi,
>
> Thank you for the review. I have uploaded a corrected build addressing all three items.
>
> **1. Directory assets in the plugin zip**  
> `assets/icon-128x128.png` and `assets/icon-256x256.png` (and the rest of `assets/wporg/`) are excluded from the wordpress.org plugin zip. Those files remain in our repo for MainWP/agency builds and for later upload to the SVN `assets/` folder only.
>
> **2. Text domain matches slug**  
> The package folder, main PHP file, plugin header `Text Domain:`, and all translation calls now use **`pixelparade-smart-purge-for-breeze-cache`**.
>
> **3. Unique prefix (4+ characters)**  
> All plugin functions, hooks, AJAX actions, active options, transients, and script handles now use the **`ppspb_`** prefix (PixelParade Smart Purge for Breeze). Constants use **`PPSPB_*`**. There are no `function bsp_*`, `wp_ajax_bsp_*`, or `define( 'BSP_*' )` symbols in the package.
>
> A small set of string literals still mention legacy `bsp_*` option/transient names only inside a one-time migration helper and uninstall cleanup (copy-then-delete old keys). Those are not active API prefixes.
>
> **Note on `breeze_token_name`:**  
> We intentionally keep `wp_localize_script( 'breeze-backend', 'breeze_token_name', … )` when enabling Breeze’s frontend admin-bar toolbar. That object name is required by Breeze’s own `breeze-main.js`. Renaming it would break the toolbar. Our wrapper script handle is `ppspb-ajaxurl`; only the Breeze-expected localize object name is unchanged for compatibility.
>
> The wordpress.org zip also omits `includes/github-updater.php` and `includes/agency/` (no `BSP_GITHUB_TOKEN` updater path in this package).
>
> Please reserve / continue with slug **`pixelparade-smart-purge-for-breeze-cache`**. Display name: **PixelParade Smart Purge for Breeze Cache** (unofficial Breeze add-on; PixelParade LLC is not affiliated with Cloudways or Breeze).
>
> Thanks,  
> Kevin

## Issues addressed in this build

| Review item | Fix |
|-------------|-----|
| Icons shipped inside plugin zip | Excluded via `.distignore.wporg` (`assets/icon-*.png`, `assets/wporg/`) |
| Text domain ≠ slug | Wporg transform sets slug + textdomain to `pixelparade-smart-purge-for-breeze-cache` |
| Prefix too short (`bsp`) | Active API uses `ppspb_` / `PPSPB_*`; legacy `bsp_*` option name strings only in migration/uninstall |
| `breeze_token_name` localize | Kept intentionally for Breeze JS compatibility (documented above) |
| Agency updater / GitHub token | Excluded from wporg zip via `.distignore.wporg` |

## Upload steps

1. Build: `powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1`  
   (or `bash scripts/build-plugin-zips.sh`)
2. Upload: **`pixelparade-smart-purge-for-breeze-cache-wporg.zip`**
3. Reply with the email draft above (as **kevpress88**)
4. Do **not** commit to SVN until approved

## Agency / MainWP lane

- Agency zip folder slug stays **`smart-purge-for-breeze-cache`** (existing clients).
- Same `ppspb_` code; settings migrate automatically from `bsp_*` options on upgrade.
- `BSP_GITHUB_TOKEN` in wp-config still works; prefer `PPSPB_GITHUB_TOKEN` for new installs.
