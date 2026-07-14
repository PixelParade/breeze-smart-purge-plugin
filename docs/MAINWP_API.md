# MainWP REST API (v2)

MainWP Dashboard: **https://mainwp.pixelparade.co**

Use the [REST API v2](https://docs.mainwp.com/api-reference/rest-api/overview) for scripts, CI, and `@mainwp/mcp` bearer-token auth. This is separate from WordPress Application Passwords (which power the Abilities API / MCP today).

## Prerequisites

1. **Permalinks** — In wp-admin go to **Settings → Permalinks** and choose anything **except Plain** (Post name is fine). Required for `/wp-json/` routes.
2. **HTTPS** — Dashboard must be served over HTTPS (already true for `mainwp.pixelparade.co`).

## Create the first API key (dashboard UI)

The **first** key must be created in the MainWP UI. Until at least one key exists and is enabled, v2 endpoints return `mainwp_rest_authentication_disabled_key`.

1. Log in to **https://mainwp.pixelparade.co/wp-admin/** (password gate, then WordPress admin).
2. Go to **MainWP Dashboard → API Access → API Keys**.
3. Click **Add API Keys**.
4. **Name:** e.g. `Cursor MCP` or `PixelParade automation`.
5. **Permissions** (check all that apply for your use case):

   | Permission | Allows |
   |------------|--------|
   | **Read** | GET requests (list sites, plugins, updates) |
   | **Write** | POST, PUT, PATCH (sync, activate, install actions) |
   | **Write & Delete** | DELETE (remove plugins, disconnect sites) |

   For Cursor + `@mainwp/mcp` fleet management, enable **Read**, **Write**, and **Write & Delete**.

6. Click **Save Key**.
7. **Copy the Bearer token immediately** — it is shown **once** and cannot be retrieved later. Store it in your password manager.

Optional: enable **MainWP REST API v1 Compatibility** only if you need legacy `consumer_key` / `consumer_secret` for an old integration. New work should use the v2 Bearer token only.

## Store credentials locally

Never commit tokens. Use gitignored files only.

### Project default — Setup 2 (MCP + REST separate)

| Use | Credential | Where |
|-----|------------|--------|
| **Cursor MCP** | Application Password | `.cursor/mcp.json` → `MAINWP_USER` + `MAINWP_APP_PASSWORD` |
| **Scripts / curl / REST v2** | Bearer token | `.env.mainwp.local` → `MAINWP_TOKEN` |

Do **not** add `MAINWP_TOKEN` to `.cursor/mcp.json` when using this setup — if both are present, MCP prefers the application password and the token in MCP would be ignored anyway.

### Option A — Cursor MCP only (Application Password)

Edit **`.cursor/mcp.json`** (gitignored when it contains secrets). Use **either** bearer token **or** application password — not both (`@mainwp/mcp` prefers application password if both are set).

**Bearer token (REST API key):**

```json
"mainwp-pixelparade": {
  "command": "npx",
  "args": ["-y", "@mainwp/mcp"],
  "env": {
    "MAINWP_URL": "https://mainwp.pixelparade.co",
    "MAINWP_TOKEN": "paste-bearer-token-here"
  }
}
```

**Application password (Abilities API — current default):**

```json
"env": {
  "MAINWP_URL": "https://mainwp.pixelparade.co",
  "MAINWP_USER": "your-email@example.com",
  "MAINWP_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
}
```

After changing `.cursor/mcp.json`, restart MCP in Cursor (or reload the window).

### Option B — Scripts / curl

Copy `.env.mainwp.example` to `.env.mainwp.local` (gitignored) and set:

```bash
MAINWP_URL=https://mainwp.pixelparade.co
MAINWP_TOKEN=your-bearer-token
```

Test:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/test-mainwp-api.ps1
```

## Verify with curl

Replace `YOUR_BEARER_TOKEN`:

```bash
curl -s -H "Authorization: Bearer YOUR_BEARER_TOKEN" \
  "https://mainwp.pixelparade.co/wp-json/mainwp/v2/sites?per_page=1"
```

Expect JSON with site objects, not `401` or `mainwp_rest_authentication_disabled_key`.

## API keys vs application passwords

| Credential | Auth header | Best for |
|------------|-------------|----------|
| **API key (v2)** | `Authorization: Bearer …` | REST API v2, scripts, `MAINWP_TOKEN` in MCP |
| **Application password** | HTTP Basic (`user:app-password`) | `@mainwp/mcp` with `MAINWP_USER` + `MAINWP_APP_PASSWORD`, Abilities API |

See [MainWP API introduction](https://docs.mainwp.com/api-reference/introduction) for MCP, Abilities, and CLI options.

## Rotate or revoke

- **Dashboard → API Access → API Keys** — disable or delete a compromised key, then create a new one.
- Update `.cursor/mcp.json` or `.env.mainwp.local` and restart MCP.
- Old Bearer tokens stop working as soon as the key is deleted or disabled.

## Related docs

- [ACCESS.md](ACCESS.md) — staging SSH, Novamira MCP, secrets policy
- [MAINWP_ROLLOUT.md](MAINWP_ROLLOUT.md) — client plugin rollout (MainWP UI + MCP limits)
