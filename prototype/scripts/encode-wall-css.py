#!/usr/bin/env python3
"""The Blender plaster for the page itself (PLAN 9, "M5 stills"): News's wall (styles/openings.css --wall-tex).

    python3 -I scripts/encode-wall-css.py [blender_out=blender/out]

The page has no light model, so the map carries its own: the plaster's tone relief (its albedo's luminance over
the map's mean) plus its normal map lit from the upper right (where the wall's Hoa thị light comes from), as one
hard-light layer (mix-blend-mode: hard-light) over the wall's own colour: 50% grey leaves the wall as it is,
darker multiplies it (cracks, pits, the shadow side of every ridge; a little warmer than grey, as old lime
darkens), lighter screens it. The token under it stays the wall's colour, so it works on any lime of the day.
Bounds (the text over it keeps 7:1): at most 16% darker on a crack, 4% lighter on a ridge, in luminance over
lime-100. An opaque RGB map costs a tenth of a straight-alpha one with the same detail.

Writes public/textures/plaster-wall-<size>.webp at 512 and 1024 px (seamless, as the Blender bake is). Sources are
the Blender PNGs, or the shipped WebPs (public/textures/plaster-1024.webp, plaster-n-1024.webp) without them.
"""
import io
import os
import sys

import numpy as np
from PIL import Image

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
SRC = os.path.abspath(sys.argv[1]) if len(sys.argv) > 1 else os.path.join(ROOT, "blender", "out")
OUT = os.path.join(ROOT, "public", "textures")
WALL = np.array([0xEF, 0xE7, 0xDA]) / 255  # lime-100, the wall the bounds are measured on
DARK, LIFT = 0.16, 0.04
TONE, RELIEF = 1.0, 0.42
LIGHT = np.array([0.62, 0.5])  # toward the light in the image plane (x right, y up)
BUDGET_KB = {512: 40, 1024: 120}


def lin(a):
    return np.where(a <= 0.04045, a / 12.92, ((a + 0.055) / 1.055) ** 2.4)


def lum(rgb):
    return lin(rgb) @ np.array([0.2126, 0.7152, 0.0722])


def load(name, png, webp):
    p = os.path.join(SRC, png)
    if not os.path.exists(p):
        p = os.path.join(OUT, webp)
    return np.asarray(Image.open(p).convert("RGB").resize((1024, 1024), Image.LANCZOS), dtype=np.float64) / 255


def main():
    albedo = load("plaster", "plaster-albedo.png", "plaster-1024.webp")
    normal = load("plaster", "plaster-normal.png", "plaster-n-1024.webp") * 2 - 1
    l = lum(albedo)
    tone = l / l.mean() - 1
    # OpenGL normal: +Y is up the image, so row 0 (the top) faces +y.
    shade = normal[..., 0] * LIGHT[0] + normal[..., 1] * LIGHT[1]
    r = TONE * tone + RELIEF * shade
    # Luminance factor over the wall, bounded; then the sRGB factor per channel that gives it on lime-100
    # (multiply below 1, screen above), the dark side a little warmer (blue darkens most).
    k = 1 + np.clip(r, -DARK, LIFT)
    b = lum(WALL)
    grid = np.linspace(0.6, 1.0, 801)
    fk = np.interp(np.minimum(k, 1), [lum(WALL * f) / b for f in grid], grid)
    warm = np.array([0.82, 1.0, 1.25])
    mult = 1 - (1 - fk)[..., None] * warm
    # screen: out = 1 - (1 - b)(1 - s) = b(1 + lift)  →  s = 1 - (1 - b·k) / (1 - b), on the wall's sRGB value
    bv = WALL.mean()
    target = np.clip(lin(np.array([bv]))[0] * np.maximum(k, 1), 0, 1)
    tv = np.where(target <= 0.0031308, target * 12.92, 1.055 * target ** (1 / 2.4) - 0.055)
    scr = np.clip(1 - (1 - tv) / (1 - bv), 0, 1)
    # hard-light: g < .5 multiplies by 2g, g > .5 screens with 2g − 1
    g = np.where((k < 1)[..., None], mult / 2, 0.5 + scr[..., None] / 2 * np.ones(3))
    im = Image.fromarray(np.round(np.clip(g, 0, 1) * 255).astype(np.uint8), "RGB")
    # Check the bounds on lime-100 as the browser blends it (sRGB bytes).
    gq = np.asarray(im, dtype=np.float64) / 255
    over = np.where(gq < 0.5, WALL * 2 * gq, 1 - (1 - WALL) * (1 - (2 * gq - 1)))
    kk = lum(over) / b
    print(f"wall-css: luminance over lime-100 {kk.min():.3f} .. {kk.max():.3f} (mean {kk.mean():.4f})")
    for size, q in ((1024, 70), (512, 74)):
        out = im.resize((size, size), Image.LANCZOS) if size != 1024 else im
        buf = io.BytesIO()
        out.save(buf, "WEBP", quality=q, method=6)
        kb = len(buf.getvalue()) / 1024
        path = os.path.join(OUT, f"plaster-wall-{size}.webp")
        with open(path, "wb") as f:
            f.write(buf.getvalue())
        print(f"wall-css: {os.path.relpath(path, ROOT)} {kb:.1f} / {BUDGET_KB[size]} KB")
        if kb > BUDGET_KB[size]:
            sys.exit(f"wall-css: {path} over budget")


if __name__ == "__main__":
    main()
