# GitHub setup

**Canonical repo:** [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin) (private org repo — make public when ready for community)

**Legacy personal repo:** [Kevin-LeMasters-PixelParade/Breeze-Smart-Purge](https://github.com/Kevin-LeMasters-PixelParade/Breeze-Smart-Purge) — archive after consolidation.

Local folder: `C:\Users\kevin\Projects\breeze-smart-purge-plugin`

## Two update lanes

| Lane | Trigger | Mechanism | Where |
|------|---------|-----------|--------|
| **Staging dev** | Push to `main` | GitHub Actions → SSH rsync | Staging only |
| **Versioned rollout** | Tag `v*` (e.g. `v1.0.2`) | GitHub Release zip | Staging (test), clients via MainWP or WP Updates |

Staging should **not** depend on Dashboard → Plugins → Update for day-to-day dev — that is automatic from `main`. Use WP Updates to **test the same zip path** clients get before MainWP rollout.

## Remotes

```powershell
git remote set-url origin https://github.com/PixelParade/breeze-smart-purge-plugin.git
git remote add personal https://github.com/Kevin-LeMasters-PixelParade/Breeze-Smart-Purge.git  # optional, for reference
git fetch --all
```

## Connection status (check anytime)

```powershell
gh auth status
gh api user/orgs -q ".[].login"                    # expect: PixelParade
gh repo view PixelParade/breeze-smart-purge-plugin # expect: repo metadata
gh secret list -R PixelParade/breeze-smart-purge-plugin
```

If org commands return **404** or empty orgs, your PAT is not authorized for **PixelParade**. Fix:

1. [Fine-grained tokens](https://github.com/settings/tokens?type=beta) → edit token
2. **Resource owner:** `PixelParade` (or authorize org on user-owned token)
3. **Repository access:** `breeze-smart-purge-plugin` or all org repos
4. **Permissions:** Contents, Metadata, Actions, Administration (for invites/secrets)
5. [Org approve](https://github.com/organizations/PixelParade/settings/personal-access-tokens)
6. `gh auth login --with-token` → update `GITHUB_PERSONAL_ACCESS_TOKEN` → restart Cursor

`git push` may work before `gh`/MCP do — they use different credentials until PAT is fixed.

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

## Staging auto-deploy checklist

Complete these once; after that, every push to `main` updates staging.

### 1. Cloudways SSH key

Add the **public** deploy key to Cloudways (Server → SSH/SFTP → Public Keys):

```powershell
Get-Content $env:USERPROFILE\.ssh\breeze-smart-purge-deploy.pub
```

Generate if missing:

```powershell
ssh-keygen -t ed25519 -f $env:USERPROFILE\.ssh\breeze-smart-purge-deploy -N '""' -C 'breeze-smart-purge-github-actions'
```

### 2. GitHub Actions secrets

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

`STAGING_APP_ID=tyaxssmjcp` is in `.github/workflows/deploy-staging.yml`.

### 3. Verify deploy

```powershell
git push origin main
gh run list -R PixelParade/breeze-smart-purge-plugin --workflow deploy-staging.yml
```

Or trigger manually: **Actions → Deploy to Staging → Run workflow**.

### 4. WordPress update checker on staging (optional test path)

While the repo is **private**, add a read-only GitHub PAT to staging `wp-config.php` so **Dashboard → Plugins** can see and install releases:

See [docs/wp-config-github-updates.example.php](wp-config-github-updates.example.php).

After the repo is **public**, the token is optional (API rate limits still apply).

## Releases and client rollout

```powershell
# Bump Version: in breeze-smart-purge.php header + readme.txt Stable tag, commit, then:
git tag v1.0.2
git push origin v1.0.2
```

GitHub Actions builds `breeze-smart-purge.zip` and attaches it to the release.

- **MainWP:** bulk update from the release asset URL
- **Native WP:** Plugins → Update available (plugin checks GitHub Releases API)

## Make public (when ready)

Repo **Settings → Danger zone → Change visibility → Public**. Then client sites can update without `BSP_GITHUB_TOKEN`.
