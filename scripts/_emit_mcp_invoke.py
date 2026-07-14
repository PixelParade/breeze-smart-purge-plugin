"""Emit mcp-adapter-execute-ability arguments for chunk N."""
import json
import sys
from pathlib import Path

chunk = int(sys.argv[1])
path = Path(__file__).resolve().parents[1] / "agent-tools" / "chunks" / f"mcp-{chunk}.json"
data = json.loads(path.read_text(encoding="utf-8"))
print(json.dumps({"ability_name": data["ability_name"], "parameters": data["parameters"]}))
