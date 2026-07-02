# GitHub setup

## 1. Git + GitHub CLI (installed)

Git and `gh` are installed via winget. Open a **new** terminal so PATH picks them up, then verify:

```powershell
git --version
gh --version
```

## 2. Authenticate with GitHub

```powershell
gh auth login
```

Choose: **GitHub.com** → **HTTPS** → **Login with a web browser** (or paste a token).

Verify:

```powershell
gh auth status
```

## 3. GitHub MCP (global Cursor)

Added to `~/.cursor/mcp.json` (remote official server). Set a **fine-scoped PAT** as a Windows user environment variable:

1. Create a token: [github.com/settings/tokens](https://github.com/settings/tokens)  
   Scopes: `repo`, `read:org` (and `workflow` if you want Actions visibility from chat).
2. **Windows → Environment Variables → User → New:**
   - Name: `GITHUB_PERSONAL_ACCESS_TOKEN`
   - Value: your `github_pat_...` or `ghp_...` token
3. **Restart Cursor** completely.
4. **Settings → MCP** — confirm `github` shows a green dot.
5. Test in chat: *"List my GitHub repositories"*

> MCP uses the PAT for GitHub API access from chat. `gh auth login` handles git push separately — both can use the same token if you prefer.

## 4. Initialize repo (done locally)

If starting fresh on another machine:

```powershell
cd C:\Users\kevin\Projects\breeze-smart-purge-plugin
git init -b main
git add .
git commit -m "Import Breeze Smart Purge plugin from staging."
```

## 5. Create GitHub repo and push

**Option A — GitHub CLI** (after `gh auth login`):

```powershell
gh repo create pixelparade/breeze-smart-purge-plugin --private --source=. --remote=origin --push
```

If the org repo name differs, adjust `pixelparade/breeze-smart-purge-plugin`.

**Option B — GitHub website**

1. Create a new **private** repo: `pixelparade/breeze-smart-purge-plugin`
2. Do not initialize with README (this repo already has one)
3. Push:

```powershell
git remote add origin https://github.com/pixelparade/breeze-smart-purge-plugin.git
git push -u origin main
```

## 6. GitHub Actions secrets

In the repo: **Settings → Secrets and variables → Actions → New repository secret**

| Secret | Value |
|--------|-------|
| `STAGING_SSH_HOST` | `45.76.227.59` |
| `STAGING_SSH_USER` | `cursor-user` (or deploy user) |
| `STAGING_SSH_KEY` | Full private key PEM (deploy key — not your GitHub login key) |

Generate a deploy key for CI:

```powershell
ssh-keygen -t ed25519 -C "breeze-smart-purge-deploy" -f "$env:USERPROFILE\.ssh\breeze-smart-purge-deploy"
```

Add the `.pub` file to Cloudways SSH keys; paste the private key into `STAGING_SSH_KEY`.

## 7. Deploy path

`STAGING_APP_ID` is set to `tyaxssmjcp` in `.github/workflows/deploy-staging.yml`. Confirm the `remote_path` matches your Cloudways app layout.

## 8. First deploy

Push to `main` triggers staging deploy. Verify with Novamira MCP (`plugin list`) or **Settings → Breeze Smart Purge** on staging.
