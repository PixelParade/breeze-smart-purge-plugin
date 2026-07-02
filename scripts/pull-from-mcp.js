#!/usr/bin/env node
/**
 * Pull plugin files from staging via Novamira MCP HTTP API.
 * Reads credentials from .cursor/mcp.json (never commit that file).
 */
const fs = require('fs');
const path = require('path');
const https = require('https');

const root = path.join(__dirname, '..');
const mcpPath = path.join(root, '.cursor', 'mcp.json');
const cfg = JSON.parse(fs.readFileSync(mcpPath, 'utf8')).mcpServers['novamira-breeze-smart-pur'].env;
const auth = Buffer.from(`${cfg.WP_API_USERNAME}:${cfg.WP_API_PASSWORD}`).toString('base64');
const baseUrl = new URL(cfg.WP_API_URL);

function mcpCall(toolName, args) {
  const body = JSON.stringify({
    jsonrpc: '2.0',
    id: 1,
    method: 'tools/call',
    params: { name: toolName, arguments: args },
  });

  return new Promise((resolve, reject) => {
    const req = https.request(
      {
        hostname: baseUrl.hostname,
        path: baseUrl.pathname + baseUrl.search,
        method: 'POST',
        headers: {
          Authorization: `Basic ${auth}`,
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(body),
        },
      },
      (res) => {
        let data = '';
        res.on('data', (c) => (data += c));
        res.on('end', () => {
          try {
            const parsed = JSON.parse(data);
            if (parsed.error) reject(new Error(JSON.stringify(parsed.error)));
            else resolve(parsed.result);
          } catch (e) {
            reject(e);
          }
        });
      }
    );
    req.on('error', reject);
    req.write(body);
    req.end();
  });
}

async function executeAbility(abilityName, parameters) {
  const result = await mcpCall('mcp-adapter-execute-ability', {
    ability_name: abilityName,
    parameters,
  });
  const text = result.content?.[0]?.text;
  if (!text) throw new Error(`No content from ${abilityName}`);
  const payload = JSON.parse(text);
  if (!payload.success) throw new Error(JSON.stringify(payload));
  return payload.data;
}

async function main() {
  const list = await executeAbility('novamira/list-directory', {
    path: 'wp-content/plugins/breeze-smart-purge',
    recursive: true,
  });

  const files = list.entries.filter((e) => e.type === 'file');
  console.log(`Pulling ${files.length} file(s) from staging...`);

  for (const entry of files) {
    const rel = entry.path.replace(/.*wp-content\/plugins\/breeze-smart-purge\//, '');
    const read = await executeAbility('novamira/read-file', {
      path: `wp-content/plugins/breeze-smart-purge/${rel}`,
    });
    const dest = path.join(root, rel);
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    if (read.encoding === 'base64') {
      fs.writeFileSync(dest, Buffer.from(read.content, 'base64'));
    } else {
      fs.writeFileSync(dest, read.content, 'utf8');
    }
    console.log(`  wrote ${rel} (${read.size} bytes)`);
  }

  console.log('Pull complete.');
}

main().catch((err) => {
  console.error(err.message || err);
  process.exit(1);
});
