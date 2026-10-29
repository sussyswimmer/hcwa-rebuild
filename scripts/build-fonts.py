"""Build the prototype's 4 self-hosted WOFF2 files (09-QA: fonts <= 4 files).

Each file holds Latin + Vietnamese in one subset, so a Vietnamese page loads one file
per face instead of two. Fraunces is pinned to SOFT 50 / WONK 0 and limited to
wght 500-700 (05-DESIGN uses 560-640); opsz stays variable.

Usage: .venv/Scripts/python -I scripts/build-fonts.py <source-dir> <out-dir>
Sources: github.com/google/fonts ofl/fraunces and ofl/bevietnampro (OFL 1.1).
"""
import sys
from pathlib import Path

from fontTools import subset
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer

# Google Fonts' "latin" + "vietnamese" unicode-range blocks, merged.
UNICODES = (
    "U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,"
    "U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD,"
    "U+0102-0103,U+0110-0111,U+0128-0129,U+0168-0169,U+01A0-01A1,U+01AF-01B0,"
    "U+0300-0301,U+0303-0304,U+0308-0309,U+0323,U+1EA0-1EF9,U+20AB"
)

FILES = {
    "Fraunces-VF.ttf": "fraunces-vf.woff2",
    "BeVietnamPro-Regular.ttf": "be-vietnam-pro-400.woff2",
    "BeVietnamPro-Medium.ttf": "be-vietnam-pro-500.woff2",
    "BeVietnamPro-SemiBold.ttf": "be-vietnam-pro-600.woff2",
}


def build(src: Path, out: Path) -> None:
    out.mkdir(parents=True, exist_ok=True)
    opts = subset.Options()
    opts.flavor = "woff2"
    opts.layout_features = ["*"]  # keep kerning, mark positioning for stacked diacritics, tnum
    opts.name_IDs = ["*"]
    opts.notdef_outline = True
    for name, target in FILES.items():
        font = TTFont(src / name)
        if "fvar" in font:
            font = instancer.instantiateVariableFont(
                font, {"SOFT": 50, "WONK": 0, "wght": (500, 700)}
            )
        sub = subset.Subsetter(opts)
        sub.populate(unicodes=subset.parse_unicodes(UNICODES))
        sub.subset(font)
        font.flavor = "woff2"
        font.save(out / target)
        check = TTFont(out / target).getBestCmap()
        assert all(c in check for c in (0x1EA0, 0x1EF9, 0x01B0, 0x0111, 0x20AB)), target
        print(f"{target}: {(out / target).stat().st_size // 1024} KB")


if __name__ == "__main__":
    build(Path(sys.argv[1]), Path(sys.argv[2]))
