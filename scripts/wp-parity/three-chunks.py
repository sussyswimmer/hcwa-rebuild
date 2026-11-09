#!/usr/bin/env python3
"""Find the chunk(s) that carry three.js on each side, by content (docs/plan/11-WP-PORT.md 5.1).

prototype/scripts/check-hero.cjs finds the three.js chunk by reading prototype/dist/assets, so its
"three.js not requested" lines would pass on WordPress without testing anything. The parity driver
asks this script instead, for both builds: a JavaScript file in the assets folder that contains
"WebGLRenderer" is a three.js chunk, whatever its hashed name.

Usage:
  python3 -I scripts/wp-parity/three-chunks.py            # JSON on stdout
  python3 -I scripts/wp-parity/three-chunks.py --side wordpress

Output: {"prototype": {"dir": ..., "exists": bool, "chunks": [...]}, "wordpress": {...}}
A side whose dist folder does not exist yet reports exists: false and no chunks (the WordPress
build appears with the assets stream's export, npm --prefix prototype run export:wp).
"""
import argparse
import json
import sys
from pathlib import Path

REPO = Path(__file__).resolve().parent.parent.parent
DIRS = {
    "prototype": REPO / "prototype" / "dist" / "assets",
    "wordpress": REPO / "wp" / "themes" / "hcwa-2026" / "assets" / "dist" / "assets",
}
MARKER = b"WebGLRenderer"


def chunks(folder: Path) -> list[str]:
    if not folder.is_dir():
        return []
    return sorted(f.name for f in folder.iterdir() if f.suffix == ".js" and MARKER in f.read_bytes())


def find(sides=None) -> dict:
    out = {}
    for side, folder in DIRS.items():
        if sides and side not in sides:
            continue
        out[side] = {"dir": str(folder.relative_to(REPO)), "exists": folder.is_dir(), "chunks": chunks(folder)}
    return out


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    ap.add_argument("--side", choices=sorted(DIRS), action="append", help="only this side (repeatable)")
    args = ap.parse_args()
    json.dump(find(args.side), sys.stdout, indent=2)
    print()
    return 0


if __name__ == "__main__":
    sys.exit(main())
