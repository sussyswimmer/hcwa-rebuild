#!/usr/bin/env python3
"""The Blender plaster and lime wash for the page itself (PLAN 9, "M6 walls"): News's wall (styles/openings.css
--wall-tex and --wall-wash).

    python3 -I scripts/encode-wall-css.py [blender_out=blender/out]

The page has no light model, so each map carries its own, as one hard-light layer (mix-blend-mode: hard-light) over
the wall's own colour: 50% grey leaves the wall as it is, darker multiplies it (a little warmer than grey, as old lime
darkens), lighter screens it. The token under it stays the wall's colour, so it works on any lime of the day.
  plaster-wall-<size>.webp   the plaster's grit, pores, trowel ridges and mottle: its tone relief (albedo luminance
                             over the map's mean) plus its normal map lit from the upper right (where the wall's Hoa
                             thị light comes from). Tiled at 640 px.
  limewash-wall-<size>.webp  the lime wash's broad loang and brush laps: its tone relief, low-passed (no grit), no
                             relief lighting. Tiled at 1037 px, so the two share no short period.
Neither carries a crack. A tiled crack is a stamp: the plaster bake has one crack network a tile, and at 640 px it
would sit on the wall every 640 px. The cracks are masked out (their dark core and the lips their normal gives them,
as walls-pack.js masks them for the world) and the tone under them is filled from around them. The wall's cracks,
patches and streaks are drawn once over the whole wall instead (openings.css --wall-marks).
Bounds (the text over it keeps 7:1 on lime-100; checked here on the two layers together, as the browser blends them):
at most 14% darker and 7% lighter in luminance (each map: plaster 10% and 4%, lime wash 5% and 3%).

Sources are the Blender PNGs, or the shipped WebPs (public/textures/<name>-1024.webp, <name>-n-1024.webp) without
them. Every filter wraps (FFT), so the maps stay seamless.
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
INK = np.array([0x1D, 0x21, 0x31]) / 255  # ink-900, the running text on it
LIGHT = np.array([0.62, 0.5])  # toward the light in the image plane (x right, y up)
# name: (tone gain, relief gain, darkest, lightest, low-pass sigma in px at 1024)
MAPS = {
    "plaster": (1.0, 0.42, 0.10, 0.04, 0.0),
    "limewash": (0.55, 0.0, 0.05, 0.03, 3.0),
}
BUDGET_KB = {"plaster": {512: 40, 1024: 120}, "limewash": {512: 12, 1024: 30}}
QUALITY = {"plaster": {1024: 70, 512: 74}, "limewash": {1024: 80, 512: 82}}
FLOOR = 0.86  # the two layers together: never more than 14% darker than lime-100


def lin(a):
    return np.where(a <= 0.04045, a / 12.92, ((a + 0.055) / 1.055) ** 2.4)


def lum(rgb):
    return lin(rgb) @ np.array([0.2126, 0.7152, 0.0722])


def load(png, webp):
    p = os.path.join(SRC, png)
    if not os.path.exists(p):
        p = os.path.join(OUT, webp)
    return np.asarray(Image.open(p).convert("RGB").resize((1024, 1024), Image.LANCZOS), dtype=np.float64) / 255


def blur(a, sigma):
    """Gaussian blur that wraps (the maps are seamless)."""
    if sigma <= 0:
        return a
    n = a.shape[0]
    f = np.fft.fftfreq(n)
    g = np.exp(-2 * (np.pi * sigma) ** 2 * (f[:, None] ** 2 + f[None, :] ** 2))
    return np.real(np.fft.ifft2(np.fft.fft2(a) * g))


def crack_mask(rel, size=1024):
    """walls-pack.js crackMask(): 1 on a crack's core (6-16% under the mean and darker), falling off linearly over 8
    texels at 1024 px (the normal's lips around a crack), wrapping."""
    t = np.clip((1 - rel - 0.06) / 0.1, 0, 1)
    a = t * t * (3 - 2 * t)
    R = max(1, round(8 * size / 1024))
    step = 1 / (R + 1)
    for _ in range(R):
        nb = np.maximum.reduce([np.roll(a, 1, 0), np.roll(a, -1, 0), np.roll(a, 1, 1), np.roll(a, -1, 1)]) - step
        a = np.maximum(a, nb)
    return a


def relief(name):
    tone_k, relief_k, dark, lift, sigma = MAPS[name]
    albedo = load(f"{name}-albedo.png", f"{name}-1024.webp")
    normal = load(f"{name}-normal.png", f"{name}-n-1024.webp") * 2 - 1
    l = lum(albedo)
    rel = l / l.mean()
    m = crack_mask(rel)
    # The tone under a crack, filled from around it (normalised convolution), then the map low-passed.
    keep = 1 - m
    fill = blur(rel * keep, 6) / np.maximum(blur(keep, 6), 1e-3)
    rel = blur(rel * keep + fill * m, sigma)
    tone = rel / rel.mean() - 1
    # OpenGL normal: +Y is up the image, so row 0 (the top) faces +y. No relief where the cracks were.
    shade = (normal[..., 0] * LIGHT[0] + normal[..., 1] * LIGHT[1]) * keep
    return 1 + np.clip(tone_k * tone + relief_k * shade, -dark, lift)


def hard_light(k):
    """The sRGB grey (per channel) that, hard-light blended over lime-100, scales its luminance by k."""
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
    return Image.fromarray(np.round(np.clip(g, 0, 1) * 255).astype(np.uint8), "RGB")


def blend(base, im):
    g = np.asarray(im, dtype=np.float64) / 255
    return np.where(g < 0.5, base * 2 * g, 1 - (1 - base) * (1 - (2 * g - 1)))


def contrast(a, b):
    la, lb = lum(a), lum(b)
    return (np.maximum(la, lb) + 0.05) / (np.minimum(la, lb) + 0.05)


def main():
    os.makedirs(OUT, exist_ok=True)
    ims = {name: hard_light(relief(name)) for name in MAPS}
    # Both layers over lime-100 as the browser blends them (sRGB bytes), at every offset of one over the other: the
    # darkest pixel of each, together.
    b = lum(WALL)
    worst = 1.0
    for name, im in ims.items():
        kk = lum(blend(WALL, im)) / b
        worst *= kk.min()
        print(f"wall-css: {name}: luminance over lime-100 {kk.min():.3f} .. {kk.max():.3f} (mean {kk.mean():.4f})")
    dark = WALL * (worst ** (1 / 2.2))  # a grey that dark, in sRGB, for the contrast bar
    print(f"wall-css: both layers' darkest together {worst:.3f}; ink-900 on it {contrast(dark, INK):.2f}:1")
    if worst < FLOOR or contrast(dark, INK) < 7:
        sys.exit("wall-css: the wall gets too dark for running text")
    failed = False
    for name, im in ims.items():
        for size in (1024, 512):
            out = im.resize((size, size), Image.LANCZOS) if size != 1024 else im
            buf = io.BytesIO()
            out.save(buf, "WEBP", quality=QUALITY[name][size], method=6)
            kb = len(buf.getvalue()) / 1024
            path = os.path.join(OUT, f"{name}-wall-{size}.webp")
            with open(path, "wb") as f:
                f.write(buf.getvalue())
            print(f"wall-css: {os.path.relpath(path, ROOT)} {kb:.1f} / {BUDGET_KB[name][size]} KB")
            failed |= kb > BUDGET_KB[name][size]
    if failed:
        sys.exit("wall-css: over budget")


if __name__ == "__main__":
    main()
