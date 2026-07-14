import json
import sys
from pathlib import Path

chunk_dir = Path(__file__).resolve().parents[1] / "agent-tools" / "chunks"
idx = int(sys.argv[1])
data = json.loads((chunk_dir / f"chunk-{idx}.json").read_text(encoding="utf-8"))
# Emit only the PHP code for agent MCP invocation.
print(data["parameters"]["code"])
