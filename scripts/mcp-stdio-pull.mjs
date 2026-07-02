import { readFileSync, writeFileSync, mkdirSync, rmSync, existsSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const mcp = JSON.parse(readFileSync(join(root, '.cursor', 'mcp.json'), 'utf8'));
const env = mcp.mcpServers['novamira-breeze-smart-pur'].env;

const transport = new StdioClientTransport({
  command: 'npx',
  args: ['-y', '@automattic/mcp-wordpress-remote@latest'],
  env: { ...process.env, ...env },
});

const client = new Client({ name: 'bsp-pull', version: '1.0.0' }, { capabilities: {} });
await client.connect(transport);

async function readStaging(rel) {
  const result = await client.callTool({
    name: 'mcp-adapter-execute-ability',
    arguments: {
      ability_name: 'novamira/read-file',
      parameters: { path: `wp-content/plugins/breeze-smart-purge/${rel}` },
    },
  });
  const text = result.content?.[0]?.text;
  if (!text) throw new Error(`No content for ${rel}`);
  const payload = JSON.parse(text);
  if (!payload.success) throw new Error(JSON.stringify(payload));
  return payload.data;
}

for (const rel of ['breeze-smart-purge.php', 'readme.txt']) {
  const data = await readStaging(rel);
  const dest = join(root, rel);
  mkdirSync(dirname(dest), { recursive: true });
  if (data.encoding === 'base64') {
    writeFileSync(dest, Buffer.from(data.content, 'base64'));
  } else {
    writeFileSync(dest, data.content, 'utf8');
  }
  console.log(`wrote ${rel} (${data.size} bytes)`);
}

for (const p of ['includes', 'index.php']) {
  const target = join(root, p);
  if (existsSync(target)) {
    rmSync(target, { recursive: true, force: true });
    console.log('removed', p);
  }
}

await client.close();
