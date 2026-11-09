#!/usr/bin/env python3
"""Regenerate wp/plugins/hcwa-core/includes/strings-data.php from content/draft/ui.json
and content/draft/ui-world.json.

Run from anywhere: python3 scripts/build-wp-strings.py
Standard library only. ui.json keys keep their order, then the ui-world.json keys follow in
their own order; the "_note" keys are dropped. A key in both files is an error, as in the
prototype's content loader (prototype/src/lib/content.mjs). Every entry must have non-empty
"vi" and "en" text, otherwise the script stops with an error.
"""

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SOURCES = (
    ROOT / "content" / "draft" / "ui.json",
    ROOT / "content" / "draft" / "ui-world.json",
)
TARGET = ROOT / "wp" / "plugins" / "hcwa-core" / "includes" / "strings-data.php"


def php_string(value: str) -> str:
    """Single-quoted PHP literal: only backslash and quote need escaping."""
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def load() -> dict:
    merged: dict = {}
    for source in SOURCES:
        data = json.loads(source.read_text(encoding="utf-8"))
        clash = [k for k in data if k != "_note" and k in merged]
        if clash:
            print(f"{source.name} repeats keys of an earlier file: {', '.join(clash)}", file=sys.stderr)
            sys.exit(1)
        for key, entry in data.items():
            if key != "_note":
                merged[key] = entry
    return merged


def main() -> int:
    data = load()
    lines = [
        "<?php",
        "/**",
        " * UI dictionary generated from content/draft/ui.json and ui-world.json. Do not edit by",
        " * hand: change the JSON, then run `python3 scripts/build-wp-strings.py`.",
        " * Staff edit live wording in Languages → Translations (Polylang), not here.",
        " *",
        " * @package hcwa-core",
        " */",
        "",
        "defined( 'ABSPATH' ) || exit;",
        "",
        "return array(",
    ]
    header = len(lines)
    width = max(len(k) for k in data) + 2
    for key, entry in data.items():
        if not isinstance(entry, dict):
            print(f"{key}: expected an object with vi and en", file=sys.stderr)
            return 1
        for lang in ("vi", "en"):
            if not isinstance(entry.get(lang), str) or not entry[lang].strip():
                print(f"{key}: missing {lang} text", file=sys.stderr)
                return 1
        lines.append(
            f"\t{php_string(key).ljust(width)} => array( 'vi' => {php_string(entry['vi'])}, 'en' => {php_string(entry['en'])} ),"
        )
    count = len(lines) - header
    lines.append(");")
    TARGET.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"Wrote {count} strings to {TARGET.relative_to(ROOT)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
