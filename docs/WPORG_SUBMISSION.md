# WordPress.org submission checklist

Use this for the **public** wordpress.org lane. Client sites on GitHub Releases + MainWP keep using `includes/github-updater.php` and `BSP_GITHUB_TOKEN`.

## Before you apply

1. Create a wordpress.org account and agree to the [plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).
2. Reserve the slug via the [plugin developer handbook](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/) — slug should match folder name: `breeze-smart-purge`.
3. Set `Contributors:` in `readme.txt` to your **wordpress.org username** (not company name).

## Repo hygiene (this project)

| Item | Status |
|------|--------|
| Plugin Check CI (`.github/workflows/plugin-check.yml`) | Runs on `main` / PRs |
| Distributable zip excludes dev files (`.distignore`) | Yes |
| Custom GitHub updater excluded from wp.org zip | `includes/github-updater.php` in `.distignore` |
| `Requires Plugins: breeze` header | In main plugin file |
| `readme.txt` UTF-8, no mojibake | Fix before SVN import |
| `Tested up to:` current WP | Match staging (e.g. 7.0) |
| GPL-compatible `License` header | GPLv2 or later |

## Run Plugin Check locally / on staging

```bash
wp plugin install plugin-check --activate
wp plugin check breeze-smart-purge --require=wp-content/plugins/plugin-check/cli.php
```

CI builds a clean folder from `.distignore` then runs [wordpress/plugin-check-action](https://github.com/wordpress/plugin-check-action).

## SVN workflow (after approval)

1. `svn co https://plugins.svn.wordpress.org/breeze-smart-purge`
2. Copy **only** distributable files into `trunk/` (same set as `.distignore` build — no `includes/github-updater.php`).
3. `svn add` / `svn commit` trunk.
4. Tag release: `svn cp trunk tags/1.1.0` and commit.
5. Add plugin assets (banner, icon) under `assets/` in SVN — not in the plugin zip.

## Assets for wordpress.org plugin page

- `banner-772x250.png` (and optional `banner-1544x500.png`)
- `icon-128x128.png` and `icon-256x256.png`
- Screenshots referenced in `readme.txt` `== Screenshots ==` section (optional but recommended)

## Two release lanes

| Lane | Build | Updates |
|------|-------|---------|
| **wordpress.org** | `.distignore` zip (no GitHub updater) | WordPress.org SVN |
| **PixelParade clients** | GitHub Release zip (`release.yml`, includes updater) | Dashboard → Plugins or MainWP |

Bump `Version:` in the plugin header and `Stable tag:` in `readme.txt` together for each release.
