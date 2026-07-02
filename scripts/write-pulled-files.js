#!/usr/bin/env node
/** Writes staging files from MCP read-file JSON saved beside this script. */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const payload = JSON.parse(fs.readFileSync(path.join(__dirname, '_staging-files.json'), 'utf8'));

for (const [rel, data] of Object.entries(payload)) {
  const dest = path.join(root, rel);
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  if (data.encoding === 'base64') {
    fs.writeFileSync(dest, Buffer.from(data.content, 'base64'));
  } else {
    fs.writeFileSync(dest, data.content, 'utf8');
  }
  console.log('wrote', rel, data.size, 'bytes');
}
