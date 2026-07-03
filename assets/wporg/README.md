# WordPress.org directory assets

Upload these files to the **SVN `assets/` folder** (not inside `trunk/` or the plugin zip).

| File | Use |
|------|-----|
| `icon-128x128.png` | Plugin icon (required) |
| `icon-256x256.png` | Plugin icon @2x |
| `banner-772x250.png` | Plugin page banner |
| `banner-1544x500.png` | Retina banner (optional) |

Visual style is inspired by [Breeze Cache](https://wordpress.org/plugins/breeze/) (cyan/blue breeze motif) with an orange purge-arrow accent to indicate an add-on — not official Cloudways branding.

After approval:

```bash
svn co https://plugins.svn.wordpress.org/<your-slug>
cp assets/wporg/* assets/   # from repo root, into SVN assets/
svn add assets/*
svn commit -m "Add plugin icons and banner."
```
