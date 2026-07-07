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

## Cloudways MCP (staging infrastructure)

**Config:** global `~/.cursor/mcp.json` — server `user-cloudways`. Rule: `.cursor/rules/cloudways-mcp.mdc`.

| Good for | Examples |
|----------|----------|
| Cache after deploy/test | `app_purge_cache` |
| php.ini (`disable_functions`, memory) | `server_settings_get` / `server_settings_update` |
| Per-app PHP-FPM pool | `app_fpm_settings_get` / `app_fpm_settings_update` (extended `apps` toolset) |
| SSH creds, service restarts | `app_credentials`, `service_restart` |

Before claiming a Cloudways capability is missing, run `list_available_toolsets` → `get_toolset_tools` → `execute_tool`. API v2 FPM docs: [Get FPM Settings](https://developers.cloudways.com/docs#tag/App-Management/operation/Get%20FPM%20Settings).

**php.ini vs PHP-FPM:** `disable_functions` (for Novamira `run-wp-cli`) is server php.ini — not PHP-FPM pool config. Prefer SSH WP-CLI; see below.

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
