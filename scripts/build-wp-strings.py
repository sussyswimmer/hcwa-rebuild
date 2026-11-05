#!/usr/bin/env python3
"""Regenerate wp/plugins/hcwa-core/includes/strings-data.php from content/draft/ui.json.

Run from anywhere: python3 scripts/build-wp-strings.py
Standard library only. Keys keep their order; the "_note" key is dropped. Every entry
must have non-empty "vi" and "en" text, otherwise the script stops with an error.
"""

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "content" / "draft" / "ui.json"
TARGET = ROOT / "wp" / "plugins" / "hcwa-core" / "includes" / "strings-data.php"


def php_string(value: str) -> str:
    """Single-quoted PHP literal: only backslash and quote need escaping."""
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def main() -> int:
    data = json.loads(SOURCE.read_text(encoding="utf-8"))
    lines = [
        "<?php",
        "/**",
        " * UI dictionary generated from content/draft/ui.json. Do not edit by hand:",
        " * change ui.json, then run `python3 scripts/build-wp-strings.py`.",
        " * Staff edit live wording in Languages → Translations (Polylang), not here.",
        " *",
        " * @package hcwa-core",
        " */",
        "",
        "defined( 'ABSPATH' ) || exit;",
        "",
        "return array(",
    ]
    width = max(len(k) for k in data if k != "_note") + 2
    for key, entry in data.items():
        if key == "_note":
            continue
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
    lines.append(");")
    TARGET.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"Wrote {len(lines) - 13} strings to {TARGET.relative_to(ROOT)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
