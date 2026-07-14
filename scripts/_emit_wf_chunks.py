import json
from pathlib import Path

root = Path(__file__).resolve().parents[1]
chunk_dir = root / "agent-tools" / "chunks"

for i in range(2, 8):
    b64 = (chunk_dir / f"b64-{i}.txt").read_text(encoding="utf-8")
    wf = {
        "ability_name": "novamira/write-file",
        "parameters": {
            "path": f"wp-content/novamira-sandbox/chunk{i}.b64",
            "content": b64,
        },
    }
    (chunk_dir / f"wf-{i}.json").write_text(json.dumps(wf), encoding="utf-8")
    print(f"wf-{i}.json {len(b64)} chars")
