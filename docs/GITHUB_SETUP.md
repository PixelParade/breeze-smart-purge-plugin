# GitHub setup

**Auth (local machine):** [`wpcp-debug/docs/GITHUB_AUTH.md`](../../wpcp-debug/docs/GITHUB_AUTH.md) — `gh auth login` only. No Cursor MCP PAT or `GITHUB_PERSONAL_ACCESS_TOKEN` env var.

**Canonical repo:** [PixelParade/breeze-smart-purge-plugin](https://github.com/PixelParade/breeze-smart-purge-plugin) (**public** — agency zip updates work without `BSP_GITHUB_TOKEN`)

**Legacy personal repo:** [Kevin-LeMasters-PixelParade/Breeze-Smart-Purge](https://github.com/Kevin-LeMasters-PixelParade/Breeze-Smart-Purge) — archive after consolidation.

Local folder: `C:\Users\kevin\Projects\breeze-smart-purge-plugin`

## One repo, two builds, three lanes

| Lane | Audience | Trigger | Artifact | Where |
|------|----------|---------|----------|--------|
| **Staging dev** | Developers | Push to `main` | Agency file tree (SSH) | `breeze-smart-purge.pixelparade.dev` only |
| **Agency release** | MainWP client sites | Tag `v*` | `smart-purge-for-breeze-cache.zip` | GitHub Releases → child **GitHub updater** (Plugins → Update). MainWP Upload .zip = **initial seed only** |
| **wordpress.org** | External (non-clients) | Manual SVN | `smart-purge-for-breeze-cache-wporg.zip` | Plugin directory |

**MainWP clients** get early/special features (`includes/agency/`) and the GitHub updater. **wp.org users** get the public subset only. See [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md) for per-site rules and rollout steps.

Staging tracks `main` automatically — **not** the same as client updates. Clients only change when you **tag a release**; the agency **GitHub updater** then offers it under Plugins → Update (MainWP Upload .zip is **initial seed only**).

### Build excludes

| File | Purpose |
|------|---------|
| `.distignore` | Dev/repo files excluded from **both** zips |
| `.distignore.wporg` | Agency-only paths excluded from **wporg** zip only |

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-plugin-zips.ps1
```

## Remotes

```powershell
git remote set-url origin https://github.com/PixelParade/breeze-smart-purge-plugin.git
git remote add personal https://github.com/Kevin-LeMasters-PixelParade/Breeze-Smart-Purge.git  # optional, for reference
git fetch --all
```

## Connection status (check anytime)

See [`wpcp-debug/docs/GITHUB_AUTH.md`](../../wpcp-debug/docs/GITHUB_AUTH.md) for full auth reference. Quick check:

```powershell
gh auth status
gh api user/orgs -q ".[].login"                    # expect: PixelParade
gh repo view PixelParade/breeze-smart-purge-plugin # expect: repo metadata
gh secret list -R PixelParade/breeze-smart-purge-plugin
```

If org commands fail, run `gh auth refresh -h github.com -s read:org,repo,workflow` (details in GITHUB_AUTH.md).

## Collaborators

**Invite Josh:** [PixelParade → People → Invite member](https://github.com/orgs/PixelParade/people) → `swsjoshua` (Member or Maintainer).

Org API invite requires a PAT with **admin:org**; use the GitHub UI if `gh` returns 403.

## Staging auto-deploy checklist

Complete these once; after that, every push to `main` updates staging with the **agency** tree (`includes/github-updater.php` + `includes/agency/`).

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

Run (after `gh auth` has PixelParade org access):

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-github-secrets.ps1
```

Or set manually on **PixelParade/breeze-smart-purge-plugin → Settings → Secrets → Actions**:

| Secret | Value |
|--------|-------|
| `STAGING_SSH_HOST` | `45.76.227.59` |
| `STAGING_SSH_USER` | Application SSH user from Cloudways |
| `STAGING_SSH_KEY` | Contents of `%USERPROFILE%\.ssh\breeze-smart-purge-deploy` (private key) |

`STAGING_APP_ID=tyaxssmjcp` is in `.github/workflows/deploy-staging.yml`.

### 3. Verify deploy

```powershell
git push origin main
gh run list -R PixelParade/breeze-smart-purge-plugin --workflow deploy-staging.yml
```

Or trigger manually: **Actions → Deploy to Staging → Run workflow**.

### 4. WordPress update checker on staging (optional)

The repo is **public** — staging and MainWP clients receive GitHub Release updates without a token. Optionally add a read-only PAT for API rate limits:

See [docs/wp-config-github-updates.example.php](wp-config-github-updates.example.php).

After the repo is **public**, the token is optional (API rate limits still apply).

## Releases and MainWP client rollout

```powershell
# Bump Version: in smart-purge-for-breeze-cache.php header + readme.txt Stable tag, commit, then:
git tag v1.2.0
git push origin v1.2.0
```

GitHub Actions attaches **both** zips to the release:

| Asset | Use |
|-------|-----|
| `smart-purge-for-breeze-cache.zip` | **Agency clients** — GitHub updater downloads this name (tag `v*` → Release) |
| `smart-purge-for-breeze-cache-wporg.zip` / `pixelparade-…-wporg.zip` | **wordpress.org upload** (not agency) |

Do **not** re-roll agency versions via MainWP Upload .zip after the fleet has the updater. Full checklist: [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md).

## Make public (when ready)

Repo **Settings → Danger zone → Change visibility → Public**. MainWP clients can keep using `BSP_GITHUB_TOKEN` for rate limits; wp.org lane is unchanged.
