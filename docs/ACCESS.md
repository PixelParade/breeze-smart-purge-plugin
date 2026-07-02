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

## Security

- Novamira app passwords and SSH passwords stay in **gitignored** local files only.
- Never add Novamira WordPress MCP to global `~/.cursor/mcp.json`.
- Rotate any credential shared in chat or tickets.
