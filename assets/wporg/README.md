# WordPress.org directory assets

Upload these files to the **SVN `assets/` folder** (not inside `trunk/` or the plugin zip).

The plugin zip also ships copies at `assets/icon-128x128.png` and `assets/icon-256x256.png` (WordPress standard path for Plugins and Updates screens).

| File | Use |
|------|-----|
| `icon-128x128.png` | Plugin icon (required) |
| `icon-256x256.png` | Plugin icon @2x |
| `icon-512x512.png` | Plugin icon @4x (retina Updates / Favorites) |
| `banner-772x250.png` | Plugin page banner |
| `banner-1544x500.png` | Retina banner (optional) |

## Visual style

Derived from the official [Breeze Cache](https://wordpress.org/plugins/breeze/) wordpress.org artwork (cyan sky, wave landscape, breeze-line typography):

- **Icon:** Breeze-style rounded square — blue gradient, browser + speedometer gauge (per official Breeze icon), orange refresh badge for purge add-on.
- **Banner:** Same layout as Breeze banner — **SMART PURGE** / *for Breeze Cache* with refresh motion graphic.

**Dimensions must match filenames exactly** (WordPress squishes wrong aspect ratios in Updates / View details):

| File | Pixels |
|------|--------|
| `icon-128x128.png` | 128 × 128 |
| `icon-256x256.png` | 256 × 256 |
| `banner-772x250.png` | 772 × 250 |
| `banner-1544x500.png` | 1544 × 500 |

Regenerate `icon-256x256` and `icon-512x512` from the padded `icon-128x128.png` (do not scale edge-to-edge artwork to fill the canvas).

This is an **unofficial add-on** — not Cloudways branding. Do not reuse Breeze assets verbatim on the directory page; use only the files in this folder.

Reference URLs (local dev only, not committed):

- https://ps.w.org/breeze/assets/icon-128x128.gif
- https://ps.w.org/breeze/assets/banner-772x250.jpg

## SVN upload

```bash
svn co https://plugins.svn.wordpress.org/<your-slug>
cp assets/wporg/*.png assets/   # into SVN checkout assets/
svn add assets/*.png
svn commit -m "Add plugin icons and banner."
```
