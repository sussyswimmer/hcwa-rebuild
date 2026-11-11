"""Print, age and pack the wall posters (M6; src/world/wall-posters-data.js, scripts/wall-posters.mjs).

    node scripts/wall-posters.mjs && python3 -I scripts/encode-wall-posters.py

Reads the flat rasters from node_modules/.cache/wall-posters/<id>.png (the 2048 atlas's print size) and gives each
the life of a print that has hung on a Saigon wall for a while: cream paper with fibre grain, the top third faded by
the sun (desaturated, lighter), two or three soft water stains, grime at the edges and a little more at the foot, a
crease or two. Then each goes into its atlas cell with two strips of tape at its top corners and the soft shadow it
casts on the wall, and the atlases are written:
    public/textures/wall-posters-2048.webp   Tier 2 and the stills   (budget 240 KB)
    public/textures/wall-posters-1024.webp   Tier 1                  (100 KB)
    public/textures/wall-posters-512.webp    phones under 600 px     (34 KB)
plus every print alone at 300 px wide (art/prints/<id>.webp: inputs for later stills, never shipped with the site),
and src/world/wall-posters-report.json: the bytes, a hash of each file, and the red and pink share of every print
(test/wall-posters.test.mjs fails on pink, on more than 2% red, on a stale report and over budget).
"""
import colorsys
import hashlib
import io
import json
import os
import re
import sys

import numpy as np
from PIL import Image, ImageFilter

HERE = os.path.dirname(os.path.abspath(__file__))
PROTO = os.path.dirname(HERE)
CACHE = os.path.join(PROTO, "node_modules", ".cache", "wall-posters")
OUT = os.path.join(PROTO, "public", "textures")
DATA = os.path.join(PROTO, "src", "world", "wall-posters-data.js")
REPORT = os.path.join(PROTO, "src", "world", "wall-posters-report.json")

src = open(DATA, encoding="utf8").read()
POSTERS = [(m.group(1), None if m.group(2) == "null" else int(m.group(2))) for m in re.finditer(r'\{ id: "([\w-]+)", word: [^}]*?atlas: (null|\d+)', src)]
CELL = {512: (168, 235, 2), 1024: (336, 470, 4), 2048: (672, 940, 8)}
BUDGET = {512: 34, 1024: 100, 2048: 240}
MARGIN = (36, 50)  # the print's inset in a 2048 cell (px)
COLS = 3


def noise(h, w, seed, octaves=4, base=64):
    """Smooth value noise in 0..1 (bilinear-upsampled random grids, several octaves)."""
    r = np.random.default_rng(seed)
    out = np.zeros((h, w), np.float32)
    amp, tot = 1.0, 0.0
    for o in range(octaves):
        g = max(2, base >> o)
        small = r.random((h // g + 2, w // g + 2)).astype(np.float32)
        big = np.asarray(Image.fromarray((small * 255).astype(np.uint8)).resize((w + 2 * g, h + 2 * g), Image.BILINEAR), np.float32) / 255
        out += amp * big[g:g + h, g:g + w]
        tot += amp
        amp *= 0.5
    return out / tot


def age(img, seed):
    a = np.asarray(img.convert("RGB"), np.float32) / 255
    h, w, _ = a.shape
    yy, xx = np.mgrid[0:h, 0:w].astype(np.float32)
    # Fibre grain: multi-octave noise, about 4-6% either way, stronger on bare paper than on ink.
    lum = a.mean(axis=2, keepdims=True)
    grain = (noise(h, w, seed, octaves=3, base=8) - 0.5) * 0.10 + (noise(h, w, seed + 1, octaves=2, base=3) - 0.5) * 0.03
    a = a * (1 + grain[..., None] * (0.6 + 0.4 * lum))
    # Sun fade: the top 30% desaturated by 25% and lightened by 8%, eased into the rest.
    t = np.clip(1 - yy / (0.38 * h), 0, 1)
    t = (t * t * (3 - 2 * t))[..., None]
    grey = a.mean(axis=2, keepdims=True)
    a = a + (grey - a) * 0.25 * t
    a = a + (1 - a) * 0.08 * t
    # Water stains: soft rings toward the wood's brown.
    r = np.random.default_rng(seed + 7)
    stain = np.array([0.55, 0.42, 0.28], np.float32)
    for _ in range(int(r.integers(2, 4))):
        cx, cy, rad = r.random() * w, r.random() * h, (0.08 + r.random() * 0.12) * w
        d = np.hypot(xx - cx, yy - cy) / rad
        wob = 1 + (noise(h, w, seed + int(cx), octaves=2, base=24) - 0.5) * 0.5
        d = d * wob
        ring = np.exp(-((d - 1) ** 2) / 0.006) * 0.10 + np.clip(1 - d, 0, 1) * 0.025
        a = a * (1 - ring[..., None]) + stain * ring[..., None]
    # Edge grime: 8% darker within 12 px of the edges, and a little more along the foot.
    e = np.minimum(np.minimum(xx, w - 1 - xx), np.minimum(yy, h - 1 - yy))
    edge = np.clip(1 - e / 12, 0, 1) ** 1.5 * 0.08 + np.clip(1 - (h - 1 - yy) / (0.12 * h), 0, 1) ** 2 * 0.05
    edge = edge * (0.6 + 0.8 * noise(h, w, seed + 3, octaves=3, base=16))
    a = a * (1 - edge[..., None])
    # Creases: a fold across the sheet, a dark line and a light one beside it.
    for k in range(int(r.integers(1, 3))):
        ang = r.random() * np.pi
        nx, ny = np.cos(ang), np.sin(ang)
        c = (xx - w / 2) * nx + (yy - h / 2) * ny - (r.random() - 0.5) * 0.6 * w
        a = a * (1 - 0.07 * np.exp(-(c ** 2) / 1.2)[..., None]) + 0.05 * np.exp(-((c - 2) ** 2) / 1.5)[..., None]
    return Image.fromarray((np.clip(a, 0, 1) * 255 + 0.5).astype(np.uint8), "RGB")


def tape(size, seed):
    """A strip of cream tape, translucent (0.55), a little darker and frayed at its ends."""
    w, h = size
    r = np.random.default_rng(seed)
    xx = np.arange(w, dtype=np.float32)[None, :].repeat(h, 0)
    ends = np.minimum(xx, w - 1 - xx) / (0.14 * w)
    alpha = 0.55 * np.clip(ends * 1.6, 0, 1) * (0.9 + 0.1 * r.random((h, w)))
    col = np.ones((h, w, 3), np.float32) * np.array([0.96, 0.93, 0.84], np.float32)
    col *= (0.93 + 0.07 * np.clip(ends, 0, 1))[..., None]
    rgba = np.dstack([col, alpha]).clip(0, 1)
    return Image.fromarray((rgba * 255 + 0.5).astype(np.uint8), "RGBA")


def cell(print_img, seed):
    cw, ch, _ = CELL[2048]
    out = Image.new("RGBA", (cw, ch), (0, 0, 0, 0))
    mx, my = MARGIN
    pw, ph = print_img.size
    # The shadow it casts on the wall: soft, a little down and right of the sheet, 26% at its core.
    sh = Image.new("L", (cw, ch), 0)
    sh.paste(255, (mx + 3, my + 6, mx + 3 + pw, my + 6 + ph))
    sh = sh.filter(ImageFilter.GaussianBlur(7))
    shadow = Image.new("RGBA", (cw, ch), (38, 30, 22, 0))
    shadow.putalpha(sh.point(lambda v: int(v * 0.26)))
    out = Image.alpha_composite(out, shadow)
    out.paste(print_img.convert("RGBA"), (mx, my))
    r = np.random.default_rng(seed)
    for side in (-1, 1):
        t = tape((86, 26), seed + side).rotate(side * (32 + r.random() * 10), resample=Image.BICUBIC, expand=True)
        x = mx - t.width // 2 + 6 if side < 0 else mx + pw - t.width // 2 - 6
        y = my - t.height // 2 + 6
        out.alpha_composite(t, (x, y))
    return out


def resize(img, size):
    return img.convert("RGBa").resize(size, Image.LANCZOS).convert("RGBA")


def colour_share(img):
    """Red (hue 345-15°, saturation > 0.45) and pink (hue 300-345°, saturation > 0.2) shares of a print."""
    a = np.asarray(img.convert("RGB").resize((150, 210)), np.float32) / 255
    hsv = np.array([colorsys.rgb_to_hsv(*p) for p in a.reshape(-1, 3)])
    hue, sat, val = hsv[:, 0] * 360, hsv[:, 1], hsv[:, 2]
    red = ((hue >= 345) | (hue <= 15)) & (sat > 0.45) & (val > 0.2)
    pink = (hue > 300) & (hue < 345) & (sat > 0.2) & (val > 0.2)
    return float(red.mean()), float(pink.mean())


def webp(img, quality, alpha_q=70):
    b = io.BytesIO()
    img.save(b, "WEBP", quality=quality, alpha_quality=alpha_q, method=6)
    return b.getvalue()


def main():
    prints, report = {}, {"atlases": {}, "prints": {}}
    PRINTS = os.path.join(PROTO, "art", "prints")
    os.makedirs(PRINTS, exist_ok=True)
    for i, (pid, _) in enumerate(POSTERS):
        p = os.path.join(CACHE, pid + ".png")
        if not os.path.exists(p):
            sys.exit(f"encode-wall-posters: {p} is missing: run node scripts/wall-posters.mjs first")
        aged = age(Image.open(p), 1000 + 37 * i)
        prints[pid] = aged
        red, pink = colour_share(aged)
        small = aged.resize((300, round(300 * aged.height / aged.width)), Image.LANCZOS)
        data = webp(small, 80)
        open(os.path.join(PRINTS, pid + ".webp"), "wb").write(data)
        report["prints"][pid] = {"red": round(red, 4), "pink": round(pink, 4), "bytes": len(data), "sha": hashlib.sha256(data).hexdigest()[:16]}
    cells = {idx: cell(prints[pid], 500 + idx) for pid, idx in POSTERS if idx is not None}
    full = Image.new("RGBA", (2048, 2048), (0, 0, 0, 0))
    cw, ch, g = CELL[2048]
    for idx, c in cells.items():
        full.paste(c, ((idx % COLS) * (cw + g), (idx // COLS) * (ch + g)))
    for size in (2048, 1024, 512):
        img = full if size == 2048 else resize(full, (size, size))
        # The highest quality that keeps the budget.
        for q in (84, 80, 76, 72, 68, 64, 60, 56, 50, 45, 40):
            data = webp(img, q, 80 if size > 512 else 60)
            if len(data) <= BUDGET[size] * 1024:
                break
        if len(data) > BUDGET[size] * 1024:
            sys.exit(f"encode-wall-posters: {size} px atlas is {len(data) // 1024} KB > {BUDGET[size]} KB")
        open(os.path.join(OUT, f"wall-posters-{size}.webp"), "wb").write(data)
        report["atlases"][str(size)] = {"bytes": len(data), "quality": q, "sha": hashlib.sha256(data).hexdigest()[:16]}
        print(f"wall-posters-{size}.webp  {len(data) / 1024:.1f} KB (q {q})")
    for pid, r in report["prints"].items():
        print(f"prints/{pid}.webp  {r['bytes'] / 1024:.1f} KB  red {r['red']:.2%}  pink {r['pink']:.2%}")
    json.dump(report, open(REPORT, "w"), indent=1)
    print(f"report → {REPORT}")


if __name__ == "__main__":
    main()
