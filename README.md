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
├── includes/github-updater.php         # Private-repo updates only
├── assets/wporg/                      # Directory icons/banners (SVN assets/)
└── .github/workflows/                 # Staging deploy, Plugin Check, releases
```

## Development workflow

1. Clone the repo and copy `.env.deploy.example` → `.env.deploy.local` (gitignored).
2. Edit `smart-purge-for-breeze-cache.php`, commit, push to `main` — staging auto-deploys.
3. Tag `v*` for GitHub Release zip + MainWP rollout.
4. See [docs/GITHUB_SETUP.md](docs/GITHUB_SETUP.md) and [docs/WPORG_SUBMISSION.md](docs/WPORG_SUBMISSION.md).

## End-user documentation

[readme.txt](readme.txt)
