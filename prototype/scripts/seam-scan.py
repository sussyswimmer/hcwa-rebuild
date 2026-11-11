"""Hard-line and dither scan for page captures (05-DESIGN D0.5, M6 gradient audit).

    python3 -I scripts/seam-scan.py [--step 6] [--span 0.4] [--json out.json] shot.png [shot.png ...]

For each screenshot:
  rows    a horizontal line: one row pair (2 px apart) where the luminance changes by more than --step levels,
          the same way, over one unbroken run of more than --span of the width (gaps of 3 px allowed). Text
          and art change in scattered columns and both ways, so they do not make a run; a CSS band, a mask that
          ends in an edge or a sheet's straight top does.
  cols    the same for columns over the height (a vertical seam: the Contact still's silhouette, a mask edge).
  dither  16 x 16 blocks in otherwise smooth areas whose fine detail (Laplacian variance) is more than 3x that of
          the blocks around them, while their coarse content is flat: stipple from a screen-door discard or a
          dither threshold, not texture.
Prints one line per file and exits 1 when any file has a row or column line (dither is reported, not failed:
real plaster grain can trip it; the captures are looked at too).
"""
import json
import sys

import numpy as np
from PIL import Image


def luma(path):
    a = np.asarray(Image.open(path).convert("RGB"), dtype=np.float32)
    return 0.2126 * a[..., 0] + 0.7152 * a[..., 1] + 0.0722 * a[..., 2]


def longest_run(mask, gap=3):
    """Longest run of True in each row of a 2-D bool array, bridging gaps of up to `gap` False."""
    best = np.zeros(mask.shape[0], dtype=np.int32)
    for r in range(mask.shape[0]):
        row = mask[r]
        if not row.any():
            continue
        idx = np.flatnonzero(row)
        breaks = np.flatnonzero(np.diff(idx) > gap + 1)
        starts = np.concatenate(([idx[0]], idx[breaks + 1]))
        ends = np.concatenate((idx[breaks], [idx[-1]]))
        best[r] = int((ends - starts + 1).max())
    return best


def lines(L, step, span, axis):
    """Lines along `axis` (0: rows, a step between y and y+2; 1: columns)."""
    if axis == 1:
        L = L.T
    d = L[2:, :] - L[:-2, :]
    found = []
    for sign in (1, -1):
        m = (d * sign) > step
        run = longest_run(m)
        n = L.shape[1]
        for r in np.flatnonzero(run > span * n):
            found.append({"at": int(r + 1), "run": round(float(run[r]) / n, 3), "sign": sign})
    # One entry per line (a 2 px step shows on neighbouring rows too).
    found.sort(key=lambda f: f["at"])
    merged = []
    for f in found:
        if merged and f["at"] - merged[-1]["at"] <= 3 and f["sign"] == merged[-1]["sign"]:
            if f["run"] > merged[-1]["run"]:
                merged[-1] = f
            continue
        merged.append(f)
    return merged


def dither(L, block=16):
    h, w = (L.shape[0] // block) * block, (L.shape[1] // block) * block
    L = L[:h, :w]
    lap = np.zeros_like(L)
    lap[1:-1, 1:-1] = 4 * L[1:-1, 1:-1] - L[:-2, 1:-1] - L[2:, 1:-1] - L[1:-1, :-2] - L[1:-1, 2:]
    by, bx = h // block, w // block
    v = lap.reshape(by, block, bx, block).var(axis=(1, 3))
    coarse = L.reshape(by, block, bx, block).mean(axis=(1, 3))
    # Coarse flatness: the block's 4 x 4 means vary by under 6 levels.
    q = L.reshape(by, 4, block // 4, bx, 4, block // 4).mean(axis=(2, 5))
    flat = (q.max(axis=(1, 3)) - q.min(axis=(1, 3))) < 6
    hits = []
    for y in range(1, by - 1):
        for x in range(1, bx - 1):
            ring = np.delete(v[y - 1:y + 2, x - 1:x + 2].ravel(), 4)
            ref = max(float(np.median(ring)), 1.0)
            if flat[y, x] and v[y, x] > 3 * ref and v[y, x] > 12:
                hits.append([x * block, y * block, round(float(v[y, x] / ref), 1), round(float(coarse[y, x]))])
    return hits


def main(argv):
    step, span, out, files = 6.0, 0.4, None, []
    it = iter(argv)
    for a in it:
        if a == "--step":
            step = float(next(it))
        elif a == "--span":
            span = float(next(it))
        elif a == "--json":
            out = next(it)
        else:
            files.append(a)
    report, bad = {}, 0
    for f in files:
        L = luma(f)
        r = {"rows": lines(L, step, span, 0), "cols": lines(L, step, span, 1), "dither": dither(L)}
        report[f] = r
        flag = "LINE" if r["rows"] or r["cols"] else "ok  "
        bad += bool(r["rows"] or r["cols"])
        print(f"{flag} {f}: rows {[x['at'] for x in r['rows']]} cols {[x['at'] for x in r['cols']]} dither {len(r['dither'])}")
    if out:
        with open(out, "w") as fh:
            json.dump(report, fh, indent=1)
    print(f"{bad}/{len(files)} with a hard line (step > {step} over > {span:.0%})")
    return 1 if bad else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
