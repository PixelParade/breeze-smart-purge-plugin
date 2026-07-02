# Breeze Smart Purge

Intelligently purges CPT archives, taxonomies, and page-builder hub pages via Breeze and Cloudflare.

**Org repo:** [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin)  
**License:** GPLv2 or later (see [LICENSE](LICENSE))

Developed in Cursor, deployed to Cloudways staging, rolled out to client sites via MainWP.

## For contributors (Kevin, Josh)

```
breeze-smart-purge-plugin/
├── breeze-smart-purge.php     # Single-file plugin (bsp_ prefix)
├── readme.txt                 # WordPress.org-style readme
├── .cursor/                   # Novamira MCP + rules (mcp.json gitignored)
├── .github/workflows/         # Staging deploy + release zip
├── docs/                      # Access, GitHub setup
└── scripts/                   # Pull from staging helpers
```

1. Clone and open in Cursor
2. Copy `.cursor/mcp.json.example` → `.cursor/mcp.json` for staging MCP
3. Edit `breeze-smart-purge.php`, commit, push to `main` (staging auto-deploys via GitHub Actions)
4. Tag `v*` when ready for release zip + WP Updates / MainWP rollout
5. See [docs/GITHUB_SETUP.md](docs/GITHUB_SETUP.md) for Actions secrets, releases, and `BSP_GITHUB_TOKEN`

## Community

Plugin description, FAQ, and changelog for end users are in [readme.txt](readme.txt).

When ready to share publicly: make the org repo public and/or submit to the WordPress plugin directory.

![GitHub Downloads](https://img.shields.io/github/downloads/PixelParade/breeze-smart-purge-plugin/total?style=flat-square&color=2271b1)
