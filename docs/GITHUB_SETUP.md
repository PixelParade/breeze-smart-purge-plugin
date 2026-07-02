# GitHub setup (manual)

Git and GitHub CLI are not available in the current shell PATH. Complete these steps once [Git for Windows](https://git-scm.com/download/win) is installed.

## 1. Initialize and push

```powershell
cd C:\Users\kevin\Projects\breeze-smart-purge-plugin
git init -b main
git add .
git commit -m "Initial scaffold for Breeze Smart Purge plugin."
```

## 2. Create GitHub repo

**Option A — GitHub website**

1. Create a new **private** repo: `pixelparade/breeze-smart-purge-plugin`
2. Do not initialize with README (this repo already has one)
3. Push:

```powershell
git remote add origin git@github.com:pixelparade/breeze-smart-purge-plugin.git
git push -u origin main
```

**Option B — GitHub CLI** (after `gh auth login`)

```powershell
gh repo create pixelparade/breeze-smart-purge-plugin --private --source=. --push
```

## 3. Configure GitHub Actions secrets

In the repo: **Settings → Secrets and variables → Actions → New repository secret**

| Secret | Value |
|--------|-------|
| `STAGING_SSH_HOST` | From Cloudways → Access Details |
| `STAGING_SSH_USER` | Usually `master` or app SSH user |
| `STAGING_SSH_KEY` | Full private key PEM |

## 4. Update deploy path (if needed)

Edit `.github/workflows/deploy-staging.yml` and set `remote_path` to your staging app's plugin directory if it differs from the placeholder.
