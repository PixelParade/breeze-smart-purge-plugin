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

## SSH / SFTP

**Config:** `.env.deploy.local` (copy from `.env.deploy.example`)

| Good for | Examples |
|----------|----------|
| Pull plugin folder locally | `scripts/pull-from-staging.ps1` |
| Emergency hotfix to staging | `scp` / `pscp` to plugin path |
| GitHub Actions deploy | secrets below |
| WP-CLI on server | `wp plugin list` |

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
