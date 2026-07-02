# Breeze Smart Purge

WordPress plugin for intelligent Breeze cache purging. Developed in Cursor, versioned on GitHub under **PixelParade**, deployed to Cloudways staging and rolled out to client sites via MainWP.

**Repo:** [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin)

**Status:** Plugin source matches staging (`breeze-smart-purge.pixelparade.dev`).

## Project structure

```
breeze-smart-purge-plugin/
├── breeze-smart-purge.php          # Single-file plugin (all logic, bsp_ prefix)
├── readme.txt                      # WordPress.org-style readme
├── .cursor/
│   ├── mcp.json                    # Novamira staging MCP (gitignored — see .example)
│   └── rules/
├── .github/workflows/
│   ├── deploy-staging.yml          # Push to main → Cloudways staging
│   └── release.yml                 # Tag v* → GitHub Release zip for MainWP
├── docs/
└── scripts/                        # Pull/deploy helpers
```

## Quick start

1. Open this folder in Cursor (`C:\Users\kevin\Projects\breeze-smart-purge-plugin`)
2. Novamira MCP is configured in `.cursor/mcp.json` for staging — reload MCP in **Settings → MCP** if needed
3. Edit `breeze-smart-purge.php`, commit, push to `main` on [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin)
4. Follow [docs/GITHUB_SETUP.md](docs/GITHUB_SETUP.md) for MCP, collaborators, and Actions secrets

## Dev workflow

1. **Edit locally** — change `breeze-smart-purge.php` (single-file architecture; functions prefixed `bsp_`)
2. **Commit + push** — `git push origin main` triggers staging deploy via GitHub Actions
3. **Verify on staging** — Novamira MCP (`list-directory`, `read-file`, `run-wp-cli`) or browser at `https://breeze-smart-purge.pixelparade.dev`
4. **Release to production** — tag `v*` → GitHub Release zip → MainWP bulk update on client sites

### Re-pull from staging

If staging drifts ahead of git (manual edits on server):

1. Novamira `list-directory` → `wp-content/plugins/breeze-smart-purge/`
2. `read-file` for `breeze-smart-purge.php` and `readme.txt`
3. Commit: `Sync plugin source from staging`

Or use `scripts/pull-from-staging.ps1` with SSH credentials in `.env.deploy.local`.

## GitHub Actions secrets (staging deploy)

Set these in **GitHub → Settings → Secrets and variables → Actions**:

| Secret | Description |
|--------|-------------|
| `STAGING_SSH_HOST` | Cloudways SSH hostname |
| `STAGING_SSH_USER` | SSH username (usually `master`) |
| `STAGING_SSH_KEY` | Private SSH key (full PEM contents) |

Update `STAGING_APP_ID` in `.github/workflows/deploy-staging.yml` with your Cloudways application ID (`tyaxssmjcp` for current staging).

Remote deploy path:

```
/home/master/applications/tyaxssmjcp/public_html/wp-content/plugins/breeze-smart-purge/
```

## Release to production (MainWP)

1. Bump version in `breeze-smart-purge.php` plugin header
2. Commit and push to `main`
3. Create and push a tag: `git tag v1.0.1 && git push origin v1.0.1`
4. GitHub Actions builds `breeze-smart-purge.zip` and attaches it to the GitHub Release
5. In MainWP dashboard: **Plugins → Install** → use the release zip URL across child sites

## Staging verification checklist

After first deploy (requires Novamira MCP + GitHub secrets):

- [ ] `run-wp-cli` → `plugin list` shows `breeze-smart-purge` active
- [ ] Settings page loads at **Settings → Breeze Smart Purge**
- [ ] Breeze purge behavior works as expected on a test URL
- [ ] Error log shows no new PHP warnings

## Versioning

Use semantic versioning: `v1.0.0`, `v1.0.1`, `v1.1.0`. Tag format must start with `v` to trigger the release workflow.
