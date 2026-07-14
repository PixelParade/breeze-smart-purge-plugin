import json
import base64
from pathlib import Path

root = Path(__file__).resolve().parents[1]
php = (root / "smart-purge-for-breeze-cache.php").read_bytes()
chunk_size = 7000
chunks = [php[i : i + chunk_size] for i in range(0, len(php), chunk_size)]
calls = []
for idx, ch in enumerate(chunks):
    b64 = base64.b64encode(ch).decode("ascii")
    if idx == 0:
        code = (
            "$f = WP_CONTENT_DIR . '/novamira-sandbox/bsp-staging-deploy.php';\n"
            f"file_put_contents($f, base64_decode('{b64}'));\n"
            f"echo 'chunk{idx}:' . filesize($f);"
        )
    else:
        code = (
            "$f = WP_CONTENT_DIR . '/novamira-sandbox/bsp-staging-deploy.php';\n"
            f"file_put_contents($f, base64_decode('{b64}'), FILE_APPEND);\n"
            f"echo 'chunk{idx}:' . filesize($f);"
        )
    calls.append({"ability_name": "novamira/execute-php", "parameters": {"code": code}})

calls.append(
    {
        "ability_name": "novamira/execute-php",
        "parameters": {
            "code": (
                "$src = WP_CONTENT_DIR . '/novamira-sandbox/bsp-staging-deploy.php';\n"
                "$dest = WP_PLUGIN_DIR . '/smart-purge-for-breeze-cache/smart-purge-for-breeze-cache.php';\n"
                "$ok = copy($src, $dest);\n"
                "echo $ok ? ('OK:' . filesize($dest)) : 'COPY_FAIL';"
            )
        },
    }
)

out = Path(__file__).resolve().parents[1] / "agent-tools" / "php-chunk-calls.json"
out.parent.mkdir(exist_ok=True)
out.write_text(json.dumps(calls), encoding="utf-8")
print("chunks", len(chunks), "calls", len(calls))
