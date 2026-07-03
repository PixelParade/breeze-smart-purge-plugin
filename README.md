# Smart Purge for Breeze Cache

Intelligently purges CPT archives, taxonomies, and page-builder hub pages when content changes in Breeze Cache.

**Org repo:** [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin)  
**License:** GPLv2 or later (see [LICENSE](LICENSE))

## Repository layout

```
breeze-smart-purge-plugin/
├── breeze-smart-purge.php     # Single-file plugin (bsp_ prefix)
├── readme.txt                 # WordPress.org readme
├── includes/github-updater.php  # Private-repo updates only (excluded from wp.org zip)
├── .github/workflows/         # Staging deploy, Plugin Check, releases
└── docs/                      # Access, GitHub setup, wp.org checklist
```

## Development workflow

1. Clone the repo and copy `.env.deploy.example` → `.env.deploy.local` (gitignored).
2. Edit `breeze-smart-purge.php`, commit, push to `main` — staging auto-deploys via GitHub Actions.
3. Tag `v*` for a release zip and client rollout (MainWP or Dashboard → Plugins).
4. See [docs/GITHUB_SETUP.md](docs/GITHUB_SETUP.md) for Actions secrets and `BSP_GITHUB_TOKEN`.
5. See [docs/WPORG_SUBMISSION.md](docs/WPORG_SUBMISSION.md) for the wordpress.org directory lane.

## End-user documentation

Plugin description, FAQ, and changelog: [readme.txt](readme.txt).
