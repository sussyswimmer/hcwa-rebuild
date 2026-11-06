"""Build and check the prototype's 4 self-hosted WOFF2 files (05-DESIGN D2: 4 files).

Each file holds Latin + Vietnamese in one subset, so a Vietnamese page loads one file
per face instead of two. Archivo is subset first, then instanced to wdth 78 with wght
kept variable over 600-700 (H1 640, H2 620, H3 600). Fraunces is gone (Phase 1).

Usage:
  python3 -I scripts/build-fonts.py <source-dir> <out-dir>     build, then check
  python3 -I scripts/build-fonts.py --check <out-dir>           check the committed files
  python3 -I scripts/build-fonts.py --metrics <out-dir> <fallback.ttf>
      print size-adjust / ascent / descent overrides for a local() fallback face
Sources: github.com/google/fonts ofl/archivo (Archivo[wdth,wght].ttf) and ofl/bevietnampro.
"""
import sys
import unicodedata
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

# source file -> (output, axis limits or None for static files)
FILES = {
    "Archivo[wdth,wght].ttf": ("archivo-w78-600-700.woff2", {"wdth": 78, "wght": (600, 700)}),
    "BeVietnamPro-Regular.ttf": ("be-vietnam-pro-400.woff2", None),
    "BeVietnamPro-Medium.ttf": ("be-vietnam-pro-500.woff2", None),
    "BeVietnamPro-SemiBold.ttf": ("be-vietnam-pro-600.woff2", None),
}

# 12 vowels x 6 tones (none, huyền, sắc, hỏi, ngã, nặng), lower and upper, plus Đ đ ₫ = 147.
VOWELS = "a ă â e ê i o ô ơ u ư y".split()
TONES = ["", "̀", "́", "̉", "̃", "̣"]
VI_FORMS = sorted(
    {unicodedata.normalize("NFC", v + t) for v in VOWELS for t in TONES}
    | {unicodedata.normalize("NFC", v.upper() + t) for v in VOWELS for t in TONES}
    | {"Đ", "đ", "₫"}
)
assert len(VI_FORMS) == 147, len(VI_FORMS)
# Sizes the 4-file budget in 05-DESIGN D6.7 was measured at.
BUDGET = 122_600


def check(out: Path) -> bool:
    ok, total = True, 0
    for target, _axes in FILES.values():
        path = out / target
        font = TTFont(path)
        cmap = font.getBestCmap()
        missing = [ch for ch in VI_FORMS if ord(ch) not in cmap]
        feats = {r.FeatureTag for r in font["GPOS"].table.FeatureList.FeatureRecord} if "GPOS" in font else set()
        marks = {"mark", "mkmk"} <= feats
        axes = {a.axisTag: (a.minValue, a.maxValue) for a in font["fvar"].axes} if "fvar" in font else {}
        size = path.stat().st_size
        total += size
        good = not missing and marks
        ok &= good
        print(f"{'ok ' if good else 'BAD'} {target}: {147 - len(missing)}/147 Vietnamese forms, "
              f"mark/mkmk {'yes' if marks else 'NO'}, axes {axes or 'static'}, {size:,} bytes"
              + (f", missing {''.join(missing)}" if missing else ""))
    print(f"{'ok ' if total <= BUDGET else 'BAD'} total {total:,} bytes (budget {BUDGET:,})")
    return ok and total <= BUDGET


def metrics(out: Path, fallback: Path) -> None:
    """Fallback @font-face overrides so the swap moves nothing (05-DESIGN D2)."""
    sample = "Cùng trẻ em xây dựng tương lai an toàn và tươi sáng hơn. Helping children build safer futures."
    fb = TTFont(fallback)
    fb_cmap, fb_hmtx, fb_upm = fb.getBestCmap(), fb["hmtx"], fb["head"].unitsPerEm

    def width(font, cmap, hmtx, upm, text):
        return sum(hmtx[cmap[ord(ch)]][0] for ch in unicodedata.normalize("NFC", text) if ord(ch) in cmap) / upm

    for target, axes in FILES.values():
        font = TTFont(out / target)
        if "fvar" in font:  # measure at the H1 weight
            font = instancer.instantiateVariableFont(font, {"wght": 640})
        upm = font["head"].unitsPerEm
        os2 = font["OS/2"]
        adjust = width(font, font.getBestCmap(), font["hmtx"], upm, sample) / width(fb, fb_cmap, fb_hmtx, fb_upm, sample)
        print(f"{target}: size-adjust {adjust * 100:.2f}%; ascent-override {os2.sTypoAscender / upm / adjust * 100:.2f}%; "
              f"descent-override {abs(os2.sTypoDescender) / upm / adjust * 100:.2f}%; line-gap-override 0%")


def build(src: Path, out: Path) -> None:
    out.mkdir(parents=True, exist_ok=True)
    opts = subset.Options()
    opts.flavor = "woff2"
    opts.layout_features = ["*"]  # keep kerning, mark positioning for stacked diacritics, tnum, lnum
    opts.name_IDs = ["*"]
    opts.notdef_outline = True
    for name, (target, axes) in FILES.items():
        font = TTFont(src / name, lazy=False)
        sub = subset.Subsetter(opts)
        sub.populate(unicodes=subset.parse_unicodes(UNICODES))
        sub.subset(font)
        if axes:
            font = instancer.instantiateVariableFont(font, axes)
        font.flavor = "woff2"
        font.save(out / target)
        print(f"{target}: {(out / target).stat().st_size:,} bytes")


if __name__ == "__main__":
    args = sys.argv[1:]
    if args[:1] == ["--check"]:
        sys.exit(0 if check(Path(args[1])) else 1)
    if args[:1] == ["--metrics"]:
        metrics(Path(args[1]), Path(args[2]))
        sys.exit(0)
    build(Path(args[0]), Path(args[1]))
    sys.exit(0 if check(Path(args[1])) else 1)
