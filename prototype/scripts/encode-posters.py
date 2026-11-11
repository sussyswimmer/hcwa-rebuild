#!/usr/bin/env python3
"""Encode the world's poster renders into the hero stills (05-DESIGN D6.10, PLAN section 5).

Input: the PNG pairs scripts/render-posters.mjs writes, <shot>-<bucket>-base.png (the room in
shade) and <shot>-<bucket>-lit.png (the rest frame), and its render.json (each still's screen
outline). Output, per bucket and width (480/960/1600):
  <shot>-<bucket>-base-<w>.avif|webp|jpg         the shaded still, the LCP image
  <shot>-<bucket>-lightroom-<w>.avif|webp        the light in the room, with the screen cut out: it
                                                 drifts down the wall during the sun-in
  <shot>-<bucket>-lightscreen-<w>.avif|webp      the light over the screen: it holds still
Both are straight alpha. Drawn over the base in that order (CSS, sRGB) they give the lit frame
(the light layer's own fit, then split exactly): the room part has alpha a·(1−m) and the screen part a·m / (1 − a·(1−m)), where a is the
light layer's alpha and m the feathered screen mask, so (1 − a_room)(1 − a_screen) = 1 − a.
Each file gets the highest quality that fits its budget; the script exits 1 when a file cannot
fit at the lowest acceptable quality. Budgets are the AVIF numbers in PLAN section 5 / D6.7;
WebP and JPEG are fallbacks for browsers without AVIF and may use twice that. A light layer that
cannot fit at its width steps down in pixels (to 60% at most) under the same name: it is soft
light, scaled to cover the hero anyway (the screen's layer to 40%: haze that never moves). The base,
the LCP image, never does.

Usage: encode-posters.py <png dir> <out dir> [--shot hero] [--bucket portrait]
       encode-posters.py <png dir> <out dir> --stills     (inner headers, dusk, doors, 404, story)
"""
import argparse
import io
import json
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, features

WIDTHS = (480, 960, 1600)
BUDGET_KB = {"base": {480: 12, 960: 28, 1600: 60}, "lightroom": {480: 10, 960: 22, 1600: 45}, "lightscreen": {480: 6, 960: 12, 1600: 24}}
FALLBACK = 2.0
FLOOR = {"AVIF": 18, "WEBP": 30, "JPEG": 35}  # below these the stills turn to mush: fail instead
LIGHT_FLOOR = {"AVIF": 14, "WEBP": 25}         # the light layer is soft and only ever drawn over the base
STEPS = (1, 0.85, 0.72, 0.6)
SCREEN_STEPS = STEPS + (0.5, 0.4)  # the screen's light is soft haze that holds still: it may go softer
BUCKETS = ("portrait", "tablet", "landscape", "wide")


def blur(arr, radius):
    """Gaussian blur of a float image in [0, 1] (H×W or H×W×3), through 16-bit-free uint8 steps."""
    chans = arr[..., None] if arr.ndim == 2 else arr
    out = [np.asarray(Image.fromarray(np.round(np.clip(chans[..., i], 0, 1) * 255).astype(np.uint8), "L").filter(ImageFilter.GaussianBlur(radius)), dtype=np.float32) / 255 for i in range(chans.shape[2])]
    return out[0] if arr.ndim == 2 else np.dstack(out)


def light_layer(base, lit, soft=1.5, crisp=False):
    """A straight-alpha layer L, a such that base·(1−a) + L·a gives the lit frame. Light does not only
    add: a sun patch (sun-200) has less blue than the cool shade it lands on, so L is the lit color
    itself, not base plus a difference. Where the light is strong, L is that pixel's lit color; it is
    smoothed (16 px, weighted by the square of how much the light changed the pixel) so it costs almost
    nothing to encode, and alpha is the least-squares fit of the change along base → L. Softened 1.5 px
    first (light is soft; this takes out the shafts' raymarch grain).
    crisp (a still with no shafts: patches and stripes that must keep their edges, D6.4): no softening; the
    fit's alpha carries the plaster's mottling as contour lines, so it is pushed to a plateau (0 off the
    light, 1 on it, the penumbra between) and L is solved per pixel there, so the composite is the lit
    frame itself, texture and all, wherever the light is."""
    b = np.asarray(base, dtype=np.float32) / 255
    l = np.asarray(lit, dtype=np.float32) / 255
    d = l - b if crisp else np.dstack([blur((l - b)[..., i] * 0.5 + 0.5, soft) * 2 - 1 for i in range(3)])
    w = np.abs(d).mean(axis=2)
    w = (w / max(w.max(), 1e-6)) ** 2
    lit_soft = np.clip(b + d, 0, 1)
    mean = (lit_soft * w[..., None]).sum(axis=(0, 1)) / max(w.sum(), 1e-6)
    weight = blur(w, 16)
    smooth = blur(lit_soft * w[..., None], 16) / np.maximum(weight, 1e-3)[..., None]
    # Far from any light the color is invisible: one flat value, no edges to encode.
    smooth = np.clip(np.where(weight[..., None] > 2e-3, smooth, mean), 0, 1)
    k = smooth - b
    # Regularized: where the light's color is nearly the base's (|k| small) the plain fit divides noise
    # by almost nothing and paints contour lines; there alpha stays as small as the change itself.
    a = np.clip((d * k).sum(axis=2) / ((k * k).sum(axis=2) + 2e-3), 0, 1)
    # Light never darkens: where the smoothed color is darker than the base (the sky in the holes),
    # the layer stays out.
    Y = np.array([0.2126, 0.7152, 0.0722], dtype=np.float32)
    a[(smooth @ Y) < (b @ Y)] = 0
    if crisp:
        t = np.clip((a - 0.06) / 0.78, 0, 1)
        a = t * t * (3 - 2 * t)
        solved = np.clip((l - b * (1 - a[..., None])) / np.maximum(a, 1e-3)[..., None], 0, 1)
        L = np.where(a[..., None] > 0.2, solved, smooth)
        rgba = np.dstack([L, a]) * 255
        return Image.fromarray(np.round(rgba).astype(np.uint8), "RGBA")
    # Weak light (the shafts' haze) is grain, not shape: blur it 5 px; patches keep their edges.
    keep = np.clip((a - 0.12) / 0.25, 0, 1)
    a = a * keep + blur(a, 5) * (1 - keep)
    # The faintest haze fades out on a ramp, not a cut: a hard threshold leaves contour lines.
    t = np.clip((a - 3 / 255) / (12 / 255), 0, 1)
    a = a * t * t * (3 - 2 * t)
    rgba = np.dstack([smooth, a]) * 255
    return Image.fromarray(np.round(rgba).astype(np.uint8), "RGBA")


def grow(poly, by):
    """A convex outline with each edge moved out by `by` along its normal, corners where neighbours meet."""
    c = poly.mean(axis=0)
    lines = []
    for i, a in enumerate(poly):
        b = poly[(i + 1) % len(poly)]
        d = b - a
        n = np.array([d[1], -d[0]]) / max(np.hypot(*d), 1e-9)
        if np.dot(n, a - c) < 0:
            n = -n
        lines.append((a + n * by, d))
    out = []
    for i, (q, e) in enumerate(lines):
        p, d = lines[i - 1]
        t = ((q[0] - p[0]) * e[1] - (q[1] - p[1]) * e[0]) / (d[0] * e[1] - d[1] * e[0])
        out.append(p + d * t)
    return np.array(out)


def screen_mask(size, outline, view, outlines=None):
    """The screen outline (poster px of a `view`-sized frame) as a soft mask at `size`: grown by 0.5% of
    the frame (the rim glow belongs to the screen) and feathered over ~6 px at 1600 w. A still may hold
    several openings (a transom and a doorway): `outlines` draws each."""
    w, h = size
    img = Image.new("L", size, 0)
    for o in outlines or [outline]:
        if len(o) < 3:
            continue
        pts = grow(np.array(o, dtype=np.float64) * (w / view[0]), max(w, h) * 0.005)
        ImageDraw.Draw(img).polygon([tuple(p) for p in pts], fill=255)
    # Feathered over ~40 px at 1600 w (was ~6 px). The room's light drifts during the sun-in while the
    # screen's holds still, so a narrow feather showed mid-animation as a hard white seam down the screen's
    # left edge (Maxwell, 11 Nov 2026). A wide feather hands one layer to the other gradually; at rest the
    # two layers still composite exactly (split_light).
    return np.asarray(img.filter(ImageFilter.GaussianBlur(w / 1600 * 20)), dtype=np.float32) / 255


def split_light(light, m):
    """The light layer as a room part (drifts) and a screen part (holds still) that composite to it."""
    arr = np.asarray(light, dtype=np.float32) / 255
    rgb, a = arr[..., :3], arr[..., 3]
    a_room = a * (1 - m)
    a_screen = np.clip(a * m / np.maximum(1 - a_room, 1e-4), 0, 1)
    out = []
    for alpha in (a_room, a_screen):
        alpha = np.where(alpha < 2.5 / 255, 0, alpha)
        out.append(Image.fromarray(np.round(np.dstack([rgb, alpha]) * 255).astype(np.uint8), "RGBA"))
    return out


def resize(img, width):
    h = round(img.height * width / img.width)
    if img.mode == "RGBA":  # the layer's color is defined everywhere, so the planes resize apart
        rgb = img.convert("RGB").resize((width, h), Image.LANCZOS)
        return Image.merge("RGBA", (*rgb.split(), img.getchannel("A").resize((width, h), Image.LANCZOS)))
    return img.resize((width, h), Image.LANCZOS)


def encode(img, fmt, q):
    buf = io.BytesIO()
    if fmt == "AVIF":
        img.save(buf, "AVIF", quality=q, speed=6, subsampling="4:2:0")
    elif fmt == "WEBP":
        img.save(buf, "WEBP", quality=q, method=6, **({"alpha_quality": q} if img.mode == "RGBA" else {}))
    else:
        img.convert("RGB").save(buf, "JPEG", quality=q, optimize=True, progressive=True, subsampling=2)
    return buf.getvalue()


def fit(img, fmt, budget, floor):
    """Highest quality in [floor, 90] under budget (bytes), or None."""
    lo, hi, best = floor, 90, None
    while lo <= hi:
        mid = (lo + hi) // 2
        data = encode(img, fmt, mid)
        if len(data) <= budget:
            best, lo = (mid, data), mid + 1
        else:
            hi = mid - 1
    return best


def encode_layer(img, name, layer, w, budget_kb, formats, out, rows, failures):
    """One layer at one width in every format, the highest quality under budget. Light layers (and the
    JPEG fallback) may step down in pixels under the same name; an AVIF or WebP base never does. Returns
    the AVIF's size in KB (0 when none)."""
    avif_kb = 0
    for fmt in formats:
        budget = budget_kb * 1024 * (1 if fmt == "AVIF" else FALLBACK)
        floor = (LIGHT_FLOOR if layer.startswith("light") else FLOOR)[fmt]
        ext = {"AVIF": "avif", "WEBP": "webp", "JPEG": "jpg"}[fmt]
        fname = f"{name}-{layer}-{w}.{ext}"
        got = None
        # JPEG is the last-resort fallback (no AVIF, no WebP): it may step down like a light layer.
        steps = SCREEN_STEPS if layer == "lightscreen" else STEPS if layer == "lightroom" or fmt == "JPEG" else STEPS[:1]
        for step in steps:
            small = resize(img, round(w * step))
            got = fit(small, fmt, budget, floor)
            if got:
                break
        if not got:
            failures.append(f"{fname}: over {budget / 1024:.0f} KB even at quality {floor}")
            continue
        q, data = got
        (out / fname).write_bytes(data)
        rows.append(f"  {fname:<46} {small.width}×{small.height}  q{q:<3} {len(data) / 1024:5.1f} / {budget / 1024:.0f} KB")
        if fmt == "AVIF":
            avif_kb = len(data) / 1024
    return avif_kb


def encode_stills(src, out):
    """The named stills of src/world/still-shots.js (render-posters.mjs --still writes stills.json with
    each crop's widths, layers, AVIF budgets and the openings' outlines). Lit headers: base + lightroom
    (+ lightscreen when the opening is in frame), composited exactly as the hero's; plain stills: the lit
    frame as `still`."""
    report = json.loads((src / "stills.json").read_text())
    out.mkdir(parents=True, exist_ok=True)
    failures, rows = [], []
    for s in report["stills"]:
        name = f"{s['still']}-{s['crop']}"
        lit = Image.open(src / f"{name}-lit.png").convert("RGB")
        if "base" in s["layers"]:
            base = Image.open(src / f"{name}-base.png").convert("RGB")
            light = light_layer(base, lit, crisp=not s.get("shafts", True))
            layers = {"base": base}
            if "lightscreen" in s["layers"]:
                room, screen = split_light(light, screen_mask(base.size, None, s["view"], s.get("silhouettes") or []))
                layers.update(lightroom=room, lightscreen=screen)
            else:
                layers["lightroom"] = light
        else:
            layers = {"still": lit}
        # A lit header's budget is for its layers together (D6.7): what a plain wall leaves of the base's share
        # goes to the room's light, so a crisp pattern is not encoded into mush.
        for w in s["widths"]:
            spare = 0
            for layer, img in layers.items():
                formats = ["AVIF", "WEBP"] if layer.startswith("light") else ["AVIF", "WEBP", "JPEG"]
                budget = s["budgets"][layer][str(w)] + (spare if layer == "lightroom" else 0)
                kb = encode_layer(img, name, layer, w, budget, formats, out, rows, failures)
                if layer == "base" and kb:
                    spare = max(0.0, s["budgets"][layer][str(w)] - kb - 0.5)
    print("encode-posters (stills):\n" + "\n".join(rows))
    if failures:
        print("encode-posters: FAILED\n  " + "\n  ".join(failures), file=sys.stderr)
        sys.exit(1)


def main():
    ap = argparse.ArgumentParser(description=__doc__.split("\n")[0])
    ap.add_argument("src", type=Path)
    ap.add_argument("out", type=Path)
    ap.add_argument("--shot", default="hero")
    ap.add_argument("--bucket", choices=BUCKETS)
    ap.add_argument("--stills", action="store_true", help="encode the named stills (stills.json) instead of the hero")
    args = ap.parse_args()
    if not features.check("avif"):
        sys.exit("encode-posters: this Pillow has no AVIF support (needs Pillow >= 11.3 with libavif)")
    if args.stills:
        return encode_stills(args.src, args.out)
    formats = {"base": ["AVIF", "WEBP", "JPEG"], "lightroom": ["AVIF", "WEBP"], "lightscreen": ["AVIF", "WEBP"]}
    report = json.loads((args.src / "render.json").read_text()) if (args.src / "render.json").exists() else {"buckets": []}
    outlines = {r["bucket"]: (r["silhouette"], r["view"]) for r in report["buckets"] if r.get("layer") == "lit"}
    args.out.mkdir(parents=True, exist_ok=True)
    failures, rows = [], []
    for bucket in [args.bucket] if args.bucket else BUCKETS:
        base_png, lit_png = (args.src / f"{args.shot}-{bucket}-{k}.png" for k in ("base", "lit"))
        if not base_png.exists() or not lit_png.exists():
            failures.append(f"{bucket}: missing {base_png.name} or {lit_png.name}")
            continue
        base = Image.open(base_png).convert("RGB")
        light = light_layer(base, Image.open(lit_png).convert("RGB"))
        if bucket not in outlines:
            failures.append(f"{bucket}: no screen outline in render.json (run npm run posters)")
            continue
        room, screen = split_light(light, screen_mask(base.size, *outlines[bucket]))
        layers = {"base": base, "lightroom": room, "lightscreen": screen}
        for layer, img in layers.items():
            for w in WIDTHS:
                for fmt in formats[layer]:
                    budget = BUDGET_KB[layer][w] * 1024 * (1 if fmt == "AVIF" else FALLBACK)
                    floor = (FLOOR if layer == "base" else LIGHT_FLOOR)[fmt]
                    ext = {"AVIF": "avif", "WEBP": "webp", "JPEG": "jpg"}[fmt]
                    name = f"{args.shot}-{bucket}-{layer}-{w}.{ext}"
                    got = None
                    for step in STEPS[:1] if layer == "base" else SCREEN_STEPS if layer == "lightscreen" else STEPS:
                        small = resize(img, round(w * step))
                        got = fit(small, fmt, budget, floor)
                        if got:
                            break
                    if not got:
                        failures.append(f"{name}: over {budget / 1024:.0f} KB even at quality {floor}")
                        continue
                    q, data = got
                    (args.out / name).write_bytes(data)
                    rows.append(f"  {name:<34} {small.width}×{small.height}  q{q:<3} {len(data) / 1024:5.1f} / {budget / 1024:.0f} KB")
    print("encode-posters:\n" + "\n".join(rows))
    if failures:
        print("encode-posters: FAILED\n  " + "\n  ".join(failures), file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
