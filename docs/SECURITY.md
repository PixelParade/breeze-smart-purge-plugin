# Security notes (maintainers)

Last reviewed: 2026-07. See also [WordPress Security APIs](https://developer.wordpress.org/apis/security/).

## Credential storage

| Item | Where it belongs | In git? |
|------|------------------|--------|
| `.env.deploy.local` | Local only | No (`.gitignore`) |
| `.cursor/mcp.json` | Local / per-developer | No (`.gitignore`) |
| `BSP_GITHUB_TOKEN` | `wp-config.php` on private client sites | No |
| GitHub Actions secrets | Repo → Settings → Secrets | No |
| `wp-config-github-updates.example.php` | Placeholder `ghp_xxx` only | Yes |

**Action if credentials were discussed outside a vault:** rotate staging SSH password, Novamira application password, and any PATs involved.

## Plugin surface (reviewed)

- **AJAX:** `check_ajax_referer` + `manage_options` on scan/save.
- **Frontend purge links:** logged-in users with toolbar capability; `check_admin_referer` on purge actions; Cloudflare purge requires `manage_options`.
- **Output:** settings UI uses `esc_html_e` / `esc_attr`; scan log escaped server-side (`esc_html`) and client-side before DOM insert.
- **No eval / shell_exec** in distributable plugin code.
- **Custom updater:** only in `includes/github-updater.php`, excluded from wordpress.org zip; loads only when `BSP_GITHUB_TOKEN` is defined.

## CI / deploy hygiene

- Prefer `STAGING_SSH_KEY` over password in Actions when Cloudways key auth is wired for the deploy user.
- `StrictHostKeyChecking=no` in deploy workflow trades MITM risk for automation — consider pinning host key via `ssh-keyscan` + `KnownHostsFile`.
- Do not commit `.deploy-chunks/`, `build/`, or one-off deploy scripts that read live credentials.

## Reporting

For wordpress.org: use the plugin support forum after launch. For PixelParade clients: internal support channel.
