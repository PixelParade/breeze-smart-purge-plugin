import json
import re
from pathlib import Path

root = Path(__file__).resolve().parents[1]
chunk_dir = root / "agent-tools" / "chunks"
for i in range(2, 8):
    data = json.loads((chunk_dir / f"mcp-{i}.json").read_text(encoding="utf-8"))
    code = data["parameters"]["code"]
    match = re.search(r"base64_decode\('([^']+)'", code)
    if not match:
        raise SystemExit(f"chunk {i}: no base64 found")
    b64 = match.group(1)
    (chunk_dir / f"b64-{i}.txt").write_text(b64, encoding="utf-8")
    php = (
        f"$f = WP_CONTENT_DIR . '/novamira-sandbox/bsp-staging-deploy.php';\n"
        f"$b64 = file_get_contents(WP_CONTENT_DIR . '/novamira-sandbox/chunk{i}.b64');\n"
        f"file_put_contents($f, base64_decode($b64), FILE_APPEND);\n"
        f"echo 'chunk{i}:' . filesize($f);"
    )
    json.dump(
        {"ability_name": "novamira/execute-php", "parameters": {"code": php}},
        open(chunk_dir / f"append-{i}.json", "w", encoding="utf-8"),
    )
    print(i, len(b64))
