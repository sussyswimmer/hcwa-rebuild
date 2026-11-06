import { Vector3 } from "three";

// The framing solver (05-DESIGN D6.6). The camera keeps its shot; setViewOffset slides the
// image (a lens shift, no rotation) until the screen silhouette touches no text rect and the
// floor sits under no body text, then checks that the hero block is inside .hero__stage.
// Everything is solved in the *frame*: the box the hero poster covers (the [data-world]
// section). The live canvas is that frame extended to the viewport, so a live rest frame
// lands on the poster pixel for pixel. Posters call the same function with the rects
// measured on the real page.

export const PAD = 24;
const ITER = 12;

// display: H1/H2, may sit over the floor (6.75:1 measured). opaque: buttons, never depend on
// the world. body: everything else (intro, nav), needs the lime wash (>= 7:1).
function kindOf(el) {
  const v = el.dataset.textSafe;
  if (v === "display" || v === "body" || v === "opaque") return v;
  if (/^H[12]$/.test(el.tagName)) return "display";
  if (el.matches("a, button") || (el.children.length && [...el.children].every((c) => c.matches("a, button")))) return "opaque";
  return "body";
}

// Header text is pinned: it has its own scrim (and turns solid once scrolled), so the solver
// keeps the silhouette off it when it can and gives it up when nothing else would fit.
function pinned(el) {
  for (let n = el; n && n !== document.body; n = n.parentElement) {
    const p = getComputedStyle(n).position;
    if (p === "fixed" || p === "sticky") return true;
  }
  return false;
}

const docRect = (r, dy = scrollY) => ({ x: r.left, y: r.top + dy, w: r.width, h: r.height });

// Rects at the rest pose (scrollY 0), document CSS px. Text uses its line boxes (a Range),
// so a balanced H1 is measured by its words, not its max-inline-size box.
export function readTextRects(root = document) {
  const out = [];
  const range = document.createRange();
  for (const el of root.querySelectorAll("[data-text-safe]")) {
    const kind = kindOf(el), pin = pinned(el);
    const boxes = kind === "opaque" && el.children.length ? [...el.children].map((c) => c.getBoundingClientRect()) : (range.selectNodeContents(el), [range.getBoundingClientRect()]);
    for (const r of boxes) if (r.width && r.height) out.push({ ...docRect(r, pin ? 0 : scrollY), kind, pinned: pin });
  }
  return out;
}

export function readBox(el) {
  const r = el?.getBoundingClientRect();
  return r?.width ? docRect(r) : null;
}

// Into frame coordinates. With `view` (a poster's frame size) the rects go into poster pixels:
// the browser scales the poster to cover the box, centred across and anchored to the bottom (the
// edge the hero text sits on: a shorter phone crops the ceiling, never moves the room under the
// text), so this is that, inverted.
export const frameOrigin = (box, view, k) => ({ x: (box.w - view[0] * k) / 2, y: box.h - view[1] * k });
export function toFrame(list, box, view) {
  const k = view ? Math.max(box.w / view[0], box.h / view[1]) : 1;
  const { x: ox, y: oy } = view ? frameOrigin(box, view, k) : { x: 0, y: 0 };
  const map = (r) => r && { ...r, x: (r.x - box.x - ox) / k, y: (r.y - box.y - oy) / k, w: r.w / k, h: r.h / k };
  return Array.isArray(list) ? list.map(map) : map(list);
}

// Projection to frame px, polygons clipped at the near plane first.
function toView(p, camera) {
  return p.clone().applyMatrix4(camera.matrixWorldInverse);
}
function clipNear(poly, near) {
  const out = [];
  for (let i = 0; i < poly.length; i++) {
    const a = poly[i], b = poly[(i + 1) % poly.length];
    const ina = -a.z >= near, inb = -b.z >= near;
    if (ina) out.push(a);
    if (ina !== inb) out.push(a.clone().lerp(b, (-near - a.z) / (b.z - a.z)));
  }
  return out;
}
function toPx(pv, camera, W, H) {
  const p = pv.clone().applyMatrix4(camera.projectionMatrix);
  return { x: (p.x * 0.5 + 0.5) * W, y: (-p.y * 0.5 + 0.5) * H };
}
export function projectPoly(points, camera, W, H) {
  camera.updateMatrixWorld();
  return clipNear(points.map((p) => toView(p, camera)), camera.near * 1.01).map((p) => toPx(p, camera, W, H));
}
export function projectPoint(p, camera, W, H) {
  camera.updateMatrixWorld();
  const pv = toView(p, camera);
  return -pv.z > camera.near ? toPx(pv, camera, W, H) : null;
}

// The portrait shot gives the H1's top edge 8 px instead of 24: the silhouette sits right above the
// title there, the only way the back wall, the floor and the screen all fit a phone (05-DESIGN D6.6).
export function edgePads(rects, bucket) {
  return bucket === "portrait" ? rects.map((r) => (r.kind === "display" && !r.pinned ? { ...r, padTop: 8 } : r)) : rects;
}

// Separating axis test, convex polygon (shifted by sx, sy) against a padded rect.
function hits(poly, sx, sy, r) {
  if (poly.length < 3) return false;
  const x0 = r.x - PAD - sx, x1 = r.x + r.w + PAD - sx, y0 = r.y - (r.padTop ?? PAD) - sy, y1 = r.y + r.h + PAD - sy;
  let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
  for (const p of poly) { minX = Math.min(minX, p.x); maxX = Math.max(maxX, p.x); minY = Math.min(minY, p.y); maxY = Math.max(maxY, p.y); }
  if (maxX < x0 || minX > x1 || maxY < y0 || minY > y1) return false;
  const corners = [[x0, y0], [x1, y0], [x1, y1], [x0, y1]];
  for (let i = 0; i < poly.length; i++) {
    const a = poly[i], b = poly[(i + 1) % poly.length];
    const nx = a.y - b.y, ny = b.x - a.x;
    let pMin = Infinity, pMax = -Infinity, rMin = Infinity, rMax = -Infinity;
    for (const p of poly) { const d = p.x * nx + p.y * ny; pMin = Math.min(pMin, d); pMax = Math.max(pMax, d); }
    for (const [cx, cy] of corners) { const d = cx * nx + cy * ny; rMin = Math.min(rMin, d); rMax = Math.max(rMax, d); }
    if (pMax < rMin || rMax < pMin) return false;
  }
  return true;
}
const hitsAny = (poly, sx, sy, rects) => rects.some((r) => hits(poly, sx, sy, r));

// Smallest value in [lo, hi] where ok() turns true (ok is monotonic), 12 halvings.
function bisect(ok, lo, hi) {
  if (ok(lo)) return lo;
  if (!ok(hi)) return null;
  for (let i = 0; i < ITER; i++) { const m = (lo + hi) / 2; ok(m) ? (hi = m) : (lo = m); }
  return hi;
}

// For each vertical shift that keeps the floor under the body text, the smallest push right
// that clears the silhouette; the smallest move overall wins.
function search(scr, flo, rects, body, W, H) {
  const clear = (sx, sy) => !hitsAny(scr, sx, sy, rects) && !hitsAny(flo, sx, sy, body);
  if (clear(0, 0)) return { sx: 0, sy: 0 };
  let best = null;
  for (let i = 0; i <= 24; i++) {
    const y = H * (-0.2 + (0.55 * i) / 24);
    if (hitsAny(flo, 0, y, body)) continue;
    const x = bisect((v) => !hitsAny(scr, v, y, rects), 0, W * 0.5);
    if (x == null) continue;
    const cost = Math.hypot(x, y);
    if (!best || cost < best.cost) best = { x, y, cost };
  }
  if (!best) return null;
  // The grid step is coarse: bring the vertical shift back toward zero at that push.
  const dir = Math.sign(best.y);
  const y = dir ? dir * (bisect((v) => clear(best.x, dir * v), 0, Math.abs(best.y)) ?? Math.abs(best.y)) : 0;
  return clear(best.x, y) ? { sx: best.x, sy: y } : { sx: best.x, sy: best.y };
}

// camera: the shot (aspect = frame), no view offset. rects and stage in frame px.
// Returns { sx, sy, ok, headerClear, heroInStage, debug }.
export function solveFrame({ camera, width: W, height: H, rects, silhouette, floor, heroBlock, stage }) {
  camera.clearViewOffset();
  camera.updateProjectionMatrix();
  const scr = projectPoly(silhouette, camera, W, H);
  const flo = projectPoly(floor, camera, W, H);
  const hb = heroBlock ? projectPoint(heroBlock, camera, W, H) : null;
  const hero = rects.filter((r) => !r.pinned);
  const body = hero.filter((r) => r.kind === "body");
  let found = search(scr, flo, rects, body, W, H), headerClear = true;
  if (!found) (found = search(scr, flo, hero, body, W, H)), (headerClear = false);
  const { sx, sy } = found ?? { sx: 0, sy: 0 };
  const ok = Boolean(found);
  const heroInStage = !hb || !stage || (hb.x + sx >= stage.x && hb.x + sx <= stage.x + stage.w && hb.y + sy >= stage.y && hb.y + sy <= stage.y + stage.h);
  return { sx, sy, ok, headerClear: ok && headerClear, heroInStage, debug: { scr, flo, hb, rects, stage } };
}

// Whether another shift (a still's, say) keeps a solved frame's hero text clear: the silhouette off the
// hero's rects and the floor under no body text. Header text is not asked: it keeps its scrim over every
// sun state, as it does over the still the visitor has already seen.
export function clearAt(result, sx, sy) {
  if (!result.ok) return false;
  const { scr, flo, rects } = result.debug;
  const hero = rects.filter((r) => !r.pinned);
  return !hitsAny(scr, sx, sy, hero) && !hitsAny(flo, sx, sy, hero.filter((r) => r.kind === "body"));
}

// Whether the frame stays clear with the camera turned (the pointer's parallax): the silhouette and
// the floor projected through `camera` as it is now (no view offset), shifted like the solved frame.
export function clearTurned(result, camera, shapes, W, H) {
  if (!result.ok) return false;
  const scr = projectPoly(shapes.silhouette, camera, W, H), flo = projectPoly(shapes.floor, camera, W, H);
  const hero = result.debug.rects.filter((r) => !r.pinned);
  return !hitsAny(scr, result.sx, result.sy, hero) && !hitsAny(flo, result.sx, result.sy, hero.filter((r) => r.kind === "body"));
}

// The canvas shows the frame (in canvas px) shifted by the solution and extended to the
// canvas size: setViewOffset with the frame as the full view.
export function applyFrame(camera, frame, canvas, { sx, sy }) {
  camera.setViewOffset(frame.w, frame.h, -sx - frame.x, -sy - frame.y, canvas.w, canvas.h);
  camera.updateProjectionMatrix();
}

// ?debug=frame: safe zones, the projected silhouette and floor, the stage, the hero block.
// frame: where the poster frame sits on the canvas; k: canvas px per frame px.
export function drawFrameOverlay(result, frame, canvas, k = 1) {
  let c = document.getElementById("world-frame-debug");
  if (!c) {
    c = document.createElement("canvas");
    c.id = "world-frame-debug";
    c.setAttribute("aria-hidden", "true");
    c.style.cssText = "position:fixed;inset:0;pointer-events:none;z-index:2147483647";
    document.body.append(c);
  }
  c.width = canvas.w; c.height = canvas.h;
  c.style.width = canvas.w + "px"; c.style.height = canvas.h + "px";
  const g = c.getContext("2d");
  g.setTransform(k, 0, 0, k, frame.x, frame.y);
  const { scr, flo, hb, rects, stage } = result.debug;
  const poly = (pts, fill, stroke) => {
    if (pts.length < 3) return;
    g.beginPath(); pts.forEach((p, i) => g[i ? "lineTo" : "moveTo"](p.x + result.sx, p.y + result.sy)); g.closePath();
    g.fillStyle = fill; g.fill(); g.strokeStyle = stroke; g.lineWidth = 2; g.stroke();
  };
  poly(flo, "rgba(255,140,0,.12)", "rgba(255,140,0,.9)");
  poly(scr, "rgba(60,60,200,.18)", "rgba(60,60,200,.95)");
  for (const r of rects) {
    g.strokeStyle = r.kind === "body" ? "rgba(0,160,80,.95)" : r.kind === "display" ? "rgba(0,120,200,.95)" : "rgba(120,120,120,.9)";
    const top = r.padTop ?? PAD;
    g.setLineDash(r.pinned ? [2, 4] : [6, 4]); g.strokeRect(r.x - PAD, r.y - top, r.w + 2 * PAD, r.h + PAD + top); g.setLineDash([]);
  }
  if (stage) { g.strokeStyle = "rgba(200,0,120,.8)"; g.strokeRect(stage.x, stage.y, stage.w, stage.h); }
  if (hb) { g.fillStyle = result.heroInStage ? "rgba(0,160,80,1)" : "rgba(220,0,0,1)"; g.beginPath(); g.arc(hb.x + result.sx, hb.y + result.sy, 7, 0, 7); g.fill(); }
  g.setTransform(1, 0, 0, 1, 0, 0);
  g.fillStyle = result.ok ? (result.headerClear ? "rgba(0,160,80,.9)" : "rgba(230,150,0,.9)") : "rgba(220,0,0,.9)";
  g.fillRect(0, 0, canvas.w, 4);
}
