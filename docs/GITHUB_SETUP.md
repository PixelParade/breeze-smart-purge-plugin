# GitHub setup

**Canonical repo:** [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin) (private org repo — make public when ready for community)

**Legacy personal repo:** [Kevin-LeMasters-PixelParade/Breeze-Smart-Purge](https://github.com/Kevin-LeMasters-PixelParade/Breeze-Smart-Purge) — archive after consolidation.

Local folder: `C:\Users\kevin\Projects\breeze-smart-purge-plugin`

## Remotes

```powershell
git remote set-url origin https://github.com/PixelParade/breeze-smart-purge-plugin.git
git remote add personal https://github.com/Kevin-LeMasters-PixelParade/Breeze-Smart-Purge.git  # optional, for reference
git fetch --all
```

## GitHub MCP + `gh`

Set Windows user env var `GITHUB_PERSONAL_ACCESS_TOKEN` with a fine-grained PAT that has **PixelParade** org access. Restart Cursor after updating.

```powershell
gh auth login --with-token   # paste token, Enter, Ctrl+Z
gh api user/orgs -q ".[].login"
gh repo view PixelParade/breeze-smart-purge-plugin
```

## Collaborators

**Invite Josh:** [PixelParade → People → Invite member](https://github.com/orgs/PixelParade/people) → `swsjoshua` (Member or Maintainer).

Org API invite requires a PAT with **admin:org**; use the GitHub UI if `gh` returns 403.

## GitHub Actions secrets

Run (after PAT has PixelParade org access):

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-github-secrets.ps1
```

Or set manually on **PixelParade/breeze-smart-purge-plugin → Settings → Secrets → Actions**:

| Secret | Value |
|--------|-------|
| `STAGING_SSH_HOST` | `45.76.227.59` |
| `STAGING_SSH_USER` | `cursor-user` |
| `STAGING_SSH_KEY` | Contents of `%USERPROFILE%\.ssh\breeze-smart-purge-deploy` (private key) |

**Cloudways:** add the matching **public** key from `%USERPROFILE%\.ssh\breeze-smart-purge-deploy.pub` to the staging server SSH keys (Server → SSH/SFTP → Public Keys).

`STAGING_APP_ID=tyaxssmjcp` is in `.github/workflows/deploy-staging.yml`.

## Deploy

- Push to `main` → staging deploy
- Tag `v*` → release zip for MainWP / community

## Make public (when ready)

Repo **Settings → Danger zone → Change visibility → Public**.
