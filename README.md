# Smart Purge for Breeze Cache

Intelligently purges CPT archives, taxonomies, and page-builder hub pages when content changes in Breeze Cache.

**Org repo:** [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin)  
**wordpress.org slug:** `smart-purge-for-breeze-cache`  
**License:** GPLv2 or later (see [LICENSE](LICENSE))

## Repository layout

```
breeze-smart-purge-plugin/
├── smart-purge-for-breeze-cache.php   # Main plugin file (bsp_ prefix)
├── readme.txt                         # WordPress.org readme
├── includes/
│   ├── github-updater.php             # Agency / MainWP builds only
│   └── agency/                        # Early features — MainWP clients only
├── assets/wporg/                      # Directory icons/banners (SVN assets/)
├── .distignore                        # Dev exclusions (both builds)
├── .distignore.wporg                  # Excludes agency paths from wp.org zip
└── .github/workflows/                 # Staging deploy, Plugin Check, releases
```

## Audiences

| Audience | Lane | Doc |
|----------|------|-----|
| MainWP client sites | Agency GitHub Release zip | [docs/MAINWP_ROLLOUT.md](docs/MAINWP_ROLLOUT.md) |
| Staging | `main` branch CI | [docs/ACCESS.md](docs/ACCESS.md) |
| External users | wordpress.org | [docs/WPORG_SUBMISSION.md](docs/WPORG_SUBMISSION.md) |

## Development workflow

1. Clone the repo and copy `.env.deploy.example` → `.env.deploy.local` (gitignored).
2. Edit `smart-purge-for-breeze-cache.php` (agency hooks in `includes/agency/`), commit, push to `main` — staging auto-deploys.
3. Tag `v*` for agency GitHub Release zip + MainWP rollout.
4. Publish wporg zip to SVN when the public subset is ready.
5. See [docs/GITHUB_SETUP.md](docs/GITHUB_SETUP.md), [docs/MAINWP_ROLLOUT.md](docs/MAINWP_ROLLOUT.md), and [docs/WPORG_SUBMISSION.md](docs/WPORG_SUBMISSION.md).

## End-user documentation

[readme.txt](readme.txt)
