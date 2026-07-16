# WordPress.org directory assets

Upload these files to the **SVN `assets/` folder** only (sibling of `trunk/` and `tags/`).

**Do not** put them in `trunk/`. **Do not** ship them inside the plugin zip — `.distignore.wporg` excludes `assets/icon-*.png` and this entire `assets/wporg/` folder.

| File | Use |
|------|-----|
| `icon-128x128.png` | Plugin icon (required) |
| `icon-256x256.png` | Plugin icon @2x |
| `banner-772x250.png` | Plugin page banner |
| `banner-1544x500.png` | Retina banner (optional but prepared) |

## Visual style

Derived from the official [Breeze Cache](https://wordpress.org/plugins/breeze/) wordpress.org artwork (cyan sky, wave landscape, breeze-line typography):

- **Icon:** Breeze-style rounded square — blue gradient, browser + speedometer gauge, orange refresh badge for purge add-on.
- **Banner:** Same layout as Breeze banner — **SMART PURGE** / *for Breeze Cache* with refresh motion graphic.

**Dimensions must match filenames exactly** (WordPress squishes wrong aspect ratios):

| File | Pixels |
|------|--------|
| `icon-128x128.png` | 128 × 128 |
| `icon-256x256.png` | 256 × 256 |
| `banner-772x250.png` | 772 × 250 |
| `banner-1544x500.png` | 1544 × 500 |

Regenerate icons with `py scripts/build-padded-icons.py` (transparent canvas, artwork cover-scaled to fill the square).

This is an **unofficial add-on** — not Cloudways branding. Do not reuse Breeze assets verbatim on the directory page; use only the files in this folder.

## SVN upload (after trunk + tags/1.0.0)

Full Windows steps: [docs/WPORG_SUBMISSION.md](../../docs/WPORG_SUBMISSION.md) § First SVN upload step 8.

```text
1. Copy these four PNGs into your SVN checkout's assets/ folder
2. TortoiseSVN → Add → Commit
```

Or command line:

```bash
cp assets/wporg/*.png /path/to/svn-checkout/assets/
cd /path/to/svn-checkout
svn add assets/*.png
svn commit assets -m "Add plugin icons and banners."
```
