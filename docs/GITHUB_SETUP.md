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

**PixelParade → People → Invite member** — add Josh (Member or Maintainer).

## GitHub Actions secrets

**PixelParade/breeze-smart-purge-plugin → Settings → Secrets → Actions**

| Secret | Value |
|--------|-------|
| `STAGING_SSH_HOST` | `45.76.227.59` |
| `STAGING_SSH_USER` | `cursor-user` |
| `STAGING_SSH_KEY` | Deploy key private PEM |

`STAGING_APP_ID=tyaxssmjcp` is in `.github/workflows/deploy-staging.yml`.

## Deploy

- Push to `main` → staging deploy
- Tag `v*` → release zip for MainWP / community

## Make public (when ready)

Repo **Settings → Danger zone → Change visibility → Public**.
