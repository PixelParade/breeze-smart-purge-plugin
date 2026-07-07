# Site access

Use **SSH** for filesystem deploys and **WordPress REST / remote tools** for in-app reads. Never commit credentials to git.

## WordPress remote API (staging)

**Config:** `.cursor/mcp.json` from `.cursor/mcp.json.example` (gitignored when it contains secrets).

| Good for | Examples |
|----------|----------|
| Reading plugin source on the server | list directory, read file |
| Inspecting site context | installed plugins, options |
| Targeted WP operations | WP-CLI via approved abilities |

**Not good for:** bulk full-tree sync or production mutations without review.

Hostname must be `breeze-smart-purge.pixelparade.dev` before any staging operation. See `.cursor/rules/novamira-mcp-safety.mdc`.

## Cloudways MCP (staging)

**Canonical reference:** `C:\Users\kevin\Projects\wpcp-debug\docs\CLOUDWAYS-MCP.md`  
**Rule:** `.cursor/rules/cloudways-mcp.mdc`  
**Config:** global `~/.cursor/mcp.json` — `user-cloudways` (email + API key headers; never commit)

**Staging:** server `1305358`, app `6528457` (`breeze-smart-purge.pixelparade.dev`).

| Task | Where documented |
|------|------------------|
| Cache purge, php.ini, FPM, discovery | `CLOUDWAYS-MCP.md` |
| WP Manager SSO / browser admin | Below + `cloudways-mcp.mdc` |
| SSH WP-CLI vs Novamira `proc_open` | SSH / SFTP section below |

**php.ini vs PHP-FPM:** `disable_functions` for Novamira is php.ini — prefer SSH WP-CLI on production clients.

## SSH / SFTP

**Config:** `.env.deploy.local` (copy from `.env.deploy.example`)

| Good for | Examples |
|----------|----------|
| Pull plugin folder locally | `scripts/pull-from-staging.ps1` |
| Emergency hotfix to staging | `scp` / `pscp` to plugin path |
| GitHub Actions deploy | secrets below |
| WP-CLI on server | `wp plugin list` (SSH) or Novamira `run-wp-cli` (see below) |

### Staging WP-CLI (Cloudways)

**SSH (recommended):** WP-CLI is available in the application SSH shell — no PHP changes needed.

```bash
cd applications/<app-id>/public_html
wp plugin list | grep -iE 'smart-purge|elementor|beaver|breeze'
```

**Novamira `run-wp-cli`:** Requires PHP to spawn the `wp` binary. On staging only, remove these from `disable_functions` in **Application → PHP Settings**:

- `proc_open`
- `proc_close`

If `run-wp-cli` errors with `Call to undefined function proc_close()`, `proc_open` was enabled but `proc_close` was not — enable both. Do **not** enable these on MainWP child / production sites.

## GitHub Actions secrets

Preferred: **SSH key** deploy (see `scripts/setup-github-secrets.ps1`).

| Secret | Purpose |
|--------|---------|
| `STAGING_SSH_HOST` | Staging server host |
| `STAGING_SSH_USER` | Application SSH user |
| `STAGING_SSH_KEY` | Private deploy key (preferred) |
| `STAGING_SSH_PASSWORD` | Fallback only — rotate if exposed |

Current workflow deploys the **agency** tree (`smart-purge-for-breeze-cache.php`, `readme.txt`, `includes/`) to `public_html/wp-content/plugins/smart-purge-for-breeze-cache/`.

MainWP client rollout: [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md).

## MainWP REST API (dashboard)

**Dashboard:** https://mainwp.pixelparade.co  
**Setup:** [MAINWP_API.md](MAINWP_API.md) — **Setup 2:** MCP uses Application Password in `.cursor/mcp.json`; REST scripts use `MAINWP_TOKEN` in `.env.mainwp.local`.

| Good for | Examples |
|----------|----------|
| Scripted fleet checks | `scripts/test-mainwp-api.ps1` |
| REST v2 integrations | `curl` with `Authorization: Bearer …` |
| MCP with token auth | `MAINWP_TOKEN` in `.cursor/mcp.json` (or keep `MAINWP_USER` + `MAINWP_APP_PASSWORD`) |

The first API key must be created in the dashboard UI; v2 returns `401` until then.

## Private-repo plugin updates

While the GitHub org repo is private, client sites may define in `wp-config.php`:

```php
define( 'BSP_GITHUB_TOKEN', 'your-read-only-github-pat' );
```

Template: [wp-config-github-updates.example.php](wp-config-github-updates.example.php). Not used on wordpress.org builds.

## Security

- Application passwords, SSH passwords, and PATs belong in **gitignored** local files or GitHub Actions secrets only.
- Rotate any credential that may have been shared outside the team password manager.
- Do not duplicate staging MCP config into a global IDE config with production credentials.

## Browser admin verification (staging)

WP-CLI and Novamira can exercise AJAX handlers without a browser session. For **click-through** (toasts, enqueued `bsp-settings` assets), you need wp-admin logged in.

| Method | Status (Jul 2026) |
|--------|-------------------|
| WP-CLI / Novamira | Works — scan/save AJAX simulated successfully on staging v1.1.14 |
| Cloudways MCP SSO tool | Not exposed — no `wp_manager` toolset in live MCP |
| Cloudways API v2 SSO REST | Documented in [WP Manager User SSO Login](https://developers.cloudways.com/docs#tag/WP-Manager/operation/WP%20Manager%20User%20SSO%20Login); automated agents have not yet resolved the correct path/body (OAuth succeeds; SSO returns *incorrect URL*) |
| **Dashboard SSO (fastest manual)** | Cloudways → app **6528457** → **Access Details** / WP Manager → **Get SSO login URL** for `kevin@pixelparade.co` → open link → **Settings → Smart Purge** |

Settings page: `wp-admin/options-general.php?page=smart-purge-for-breeze-cache`

## Verify deploys

```powershell
cd public_html && wp plugin list | grep smart-purge
grep 'Version:' wp-content/plugins/smart-purge-for-breeze-cache/smart-purge-for-breeze-cache.php
```

| Log / check | Location |
|-------------|----------|
| PHP parse/fatal (fastest) | `wp plugin list` |
| WordPress debug | `wp-content/debug.log` (if `WP_DEBUG_LOG`) |
| Server | `../logs/*error.log` relative to `public_html` |
| CI | `gh run view <run-id> --log-failed` |

Operational notes: `.cursor/rules/ops-lessons.mdc`.
