# WordPress.org review reply (Jul 2026)

Use after uploading the corrected build from `smart-purge-for-breeze-cache-wporg.zip` (built via `scripts/build-plugin-zips.sh`).

## Slug reservation (required in reply)

Please reserve slug **`pixelparade-smart-purge-for-breeze-cache`**.

Display name: **PixelParade Smart Purge for Breeze Cache**

This is an unofficial add-on for [Breeze Cache](https://wordpress.org/plugins/breeze/). PixelParade LLC is not affiliated with Cloudways or Breeze.

## Draft email reply

> Hi,
>
> I uploaded a corrected build. Please reserve slug **pixelparade-smart-purge-for-breeze-cache**.
>
> PixelParade Smart Purge for Breeze Cache is an unofficial Breeze add-on; we are not affiliated with Cloudways/Breeze.
>
> Thanks,
> Kevin

## Issues addressed in the build

| Review item | Fix |
|-------------|-----|
| Trademark / generic name | Display name prefixed with **PixelParade**; wporg zip uses new slug |
| readme encoding | ASCII only (`-` and `>` instead of em dash / arrow) |
| Inline `<script>` / `<style>` | Moved to `assets/admin/settings.js` + `settings.css`; enqueued via `admin_enqueue_scripts` |
| Frontend `ajaxurl` echo | `wp_register_script` + `wp_add_inline_script` |
| `Breeze_Admin` reflection | Replaced with `bsp_register_breeze_frontend_admin_bar()` mirroring Breeze toolbar nodes |
| Prefixes | Existing `bsp_` prefix retained |

## Upload steps

1. `powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1` (or `bash scripts/build-plugin-zips.sh`)
2. **Upload now:** `smart-purge-for-breeze-cache-wporg.zip` (matches pending submission slug; passes automated Plugin Check)
3. **After slug approved:** use `pixelparade-smart-purge-for-breeze-cache-wporg.zip` for SVN
4. Log in as **kevpress88** → [Add your plugin](https://wordpress.org/plugins/developers/add/) → upload zip
5. Reply to the review email (do **not** SVN until approved)

## Plugin Check note

Text domains in `__()` / `esc_html__()` must be **string literals** (not `BSP_TEXT_DOMAIN` constants). The wporg upload zip uses `'smart-purge-for-breeze-cache'` until the new slug is allocated.

## Agency / MainWP lane

GitHub Releases and MainWP keep slug **`smart-purge-for-breeze-cache`** unchanged. Only the wporg zip is transformed at build time.
