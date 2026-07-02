#!/usr/bin/env node
/** Writes breeze-smart-purge.php from scripts/staging-php.json (MCP read-file payload). */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const payload = JSON.parse(
  fs.readFileSync(path.join(__dirname, 'staging-php.json'), 'utf8')
);
fs.writeFileSync(path.join(root, 'breeze-smart-purge.php'), payload.content, 'utf8');
console.log('wrote breeze-smart-purge.php', payload.size, 'bytes');
