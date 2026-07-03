# Site access: Novamira MCP vs SSH

Use **both**, for different jobs. Do not put either credential set in git.

## Novamira MCP (WordPress-aware)

**Config:** `.cursor/mcp.json` → `novamira-breeze-smart-pur`

| Good for | Examples |
|----------|----------|
| Reading plugin source on the server | `list-directory`, `read-file` |
| Inspecting site context | `discover-abilities`, installed plugins |
| Targeted WP operations | options, posts, diagnostics via abilities |
| Safe agent workflows | hostname checks in `.cursor/rules/novamira-mcp-safety.mdc` |

**Not good for:** bulk full-tree sync, CI deploy, or when MCP session is disconnected.

Reload after config changes: **Cursor → Settings → MCP → reload** `novamira-breeze-smart-pur`.

## SSH / SFTP (filesystem)

**Config:** `.env.deploy.local` (copy from `.env.deploy.example`)

| Good for | Examples |
|----------|----------|
| Pull entire plugin folder locally | `scripts/pull-from-staging.ps1` |
| Push local changes to staging | rsync/scp before CI is wired |
| GitHub Actions deploy | SSH **key** in repo secrets (preferred over password) |
| WP-CLI on server | `wp plugin list` when proc_open allows |

**Not good for:** WordPress data model work (use Novamira abilities instead).

## GitHub Actions secrets

For automated deploy, use an SSH **key**, not the cursor-user password:

- `STAGING_SSH_HOST` — `45.76.227.59`
- `STAGING_SSH_USER` — `cursor-user` or deploy key user
- `STAGING_SSH_KEY` — private key (add public key to server)

Set `STAGING_APP_ID=tyaxssmjcp` in `.github/workflows/deploy-staging.yml`.

## WordPress plugin updates (GitHub Releases)

The plugin checks GitHub Releases for newer versions and surfaces **Dashboard → Plugins → Update available**.

| Site | Primary update path |
|------|---------------------|
| Staging | Push to `main` (CI rsync) for dev; tag releases to test WP update UI |
| Client sites | Tag `v*` → MainWP or native WP Updates |

While the org repo is **private**, add to `wp-config.php` on each site:

```php
define( 'BSP_GITHUB_TOKEN', 'your-read-only-github-pat' );
```

Template: [wp-config-github-updates.example.php](wp-config-github-updates.example.php). Full checklist: [GITHUB_SETUP.md](GITHUB_SETUP.md).

## Security

- Novamira app passwords and SSH passwords stay in **gitignored** local files only.
- Never add Novamira WordPress MCP to global `~/.cursor/mcp.json`.
- Rotate any credential shared in chat or tickets.

## Verify deploys and read errors

After pushing to `main` or any hotfix, confirm the site is alive — not only that GitHub Actions passed.

```powershell
# From .env.deploy.local credentials via plink:
cd public_html && wp plugin list | grep breeze-smart-purge
grep 'Version:' wp-content/plugins/breeze-smart-purge/breeze-smart-purge.php
```

| Log / check | Location |
|-------------|----------|
| PHP parse/fatal (fastest) | `wp plugin list` or any `wp` command |
| WordPress debug | `wp-content/debug.log` (requires `WP_DEBUG_LOG`; often off) |
| Server | `../logs/*error.log`, `../logs/php-app*.log` relative to `public_html` |
| CI | `gh run view <run-id> --log-failed` |

For UI features (admin bar, settings screens), verify in the browser while logged in.

Emergency rollback: `pscp` fixed files to `public_html/wp-content/plugins/breeze-smart-purge/` without waiting for CI.

Agent-facing checklist: `.cursor/rules/ops-lessons.mdc`.
