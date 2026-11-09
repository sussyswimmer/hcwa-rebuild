import { cubicBezier } from "./ease.js";
import { bezier } from "./tokens.js";

// The home journey (05-DESIGN D5.3 S2, D6.2, D6.5): beats read from the DOM, the corridor they build,
// the passage's timeline and dwell(). No three.js here, so the tests read it in Node; sets/corridor.js
// and live.js turn it into geometry and a camera.

// A camera eases inside a beat (lantern.md): quick in, a slow drift while the card is read, quick out.
const sineOut = (x) => Math.sin((x * Math.PI) / 2);
const sineIn = (x) => 1 - Math.cos((x * Math.PI) / 2);
const smooth = (a, b, x) => { const t = Math.min(Math.max((x - a) / (b - a), 0), 1); return t * t * (3 - 2 * t); };
export const dwell = (u) => {
  const x = Math.min(Math.max(u, 0), 1);
  return x < 0.22 ? 0.45 * sineOut(x / 0.22) : x < 0.78 ? 0.45 + 0.1 * ((x - 0.22) / 0.56) : 0.55 + 0.45 * sineIn((x - 0.78) / 0.22);
};

// Each [data-beat] card is one door (D6.5): its unit, open or closed, its transom's emblem. The DOM
// cannot open Little Rose's door or give it a motif: whatever its attributes say, it is closed, its
// transom is the house's own Hoa thị, and its light is plain (doorLight in objects/door.js).
const EMBLEMS = new Set(["dotTre", "vomCau", "hoaThi"]);
export function readBeats(nodes) {
  return [...nodes].map((n) => {
    const d = n.dataset ?? {};
    const unit = String(d.unit ?? "");
    const lr = unit === "little-rose";
    return {
      unit,
      door: lr ? "closed" : d.door === "closed" ? "closed" : "open",
      emblem: lr ? "hoaThi" : EMBLEMS.has(d.emblem) ? d.emblem : "hoaThi",
    };
  });
}

// The corridor (D6.2): origin x = 100, running along −z. The screen on the left at x = SCREEN_X faces
// +x, the street and the sun beyond it; the door wall on the right at x = WALL_X. A 3 m lead-in, then one
// 6.5 m bay per door with its door at the centre and a shallow pier at its far end, then 2 m.
// The screen's blocks are laid so one hole is centred at (z 1.5, y 1.05): the camera comes out of it.
export const CORRIDOR = { screenX: 98.9, wallX: 101.3, ceiling: 3.1, lead: 3, bay: 6.5, tail: 2, start: 3, parapet: 0.9, rows: 7, pier: 0.32 };
export const EXIT_HOLE = { z: 1.5, y: 1.05 };
const B = 0.3;

export function corridorLayout(beats) {
  const n = beats.length, C = CORRIDOR;
  const end = -(C.bay * n + C.tail);
  const doors = beats.map((b, i) => ({ ...b, index: i, zc: -(C.bay * i + C.bay / 2), bay: [-(C.bay * (i + 1)), -C.bay * i] }));
  // Block columns from the exit hole outward, so a hole sits exactly where the camera passes.
  const z0 = EXIT_HOLE.z - B / 2 - B * Math.ceil((EXIT_HOLE.z - B / 2 - end) / B);
  const z1 = EXIT_HOLE.z + B / 2 + B * Math.floor((C.start - (EXIT_HOLE.z + B / 2)) / B);
  return {
    n, doors, end, start: C.start,
    screen: { sx: C.screenX, z0, z1, y0: C.parapet, y1: C.parapet + C.rows * B },
    piers: doors.map((d) => d.bay[0]),
  };
}

// ---- The corridor walk (D5.3 S2 part 2, PLAN H4) ----
// Stage progress q (0..1 while the stage's frame is pinned): the stage header over 0–0.08, the N beats over
// 0.08–0.92 (0.84/N each), then the walk comes to rest. Inside a beat (u 0..1) the card comes in at 0.18,
// once the camera has passed the midpoint from the door before (walkT i + 0.93), and goes at 0.97, once its
// door has slid out of the middle 60% of the frame on its way past (walkT i + 1.4; the next door is nearer
// the camera's look target only from i + 1.5), so a card is never over another center's door. The last card
// holds to `release` (critique r2: its door stays the subject no longer than its card), where the pane fades
// out over the stage's last 0.05 and the camera starts to leave the door (walkLeave), on into the plaster wash
// the corridor goes out through. An open door swings 0 → 72° over 0.10–0.45. motion/stages.js shows the cards
// from the same numbers (test/beats.test.mjs keeps them equal and checks every card against the camera):
// motion/ never imports world/.
export const WALK = { head: [0, 0.08], beats: [0.08, 0.92], cardIn: 0.18, cardOut: 0.97, release: 0.95, swing: [0.1, 0.45], sun: [0.25, 0.45] };
// The way out (live.js, critique r1 fix round 1): from `release` the camera walks on past the last door (sine-in,
// `leave` bays of walk) and, scrubbed over the stage's `tilt`, pitches `pitch`° down to the floor, so the gạch
// runner fills the frame as the pane lets go. From the stage's end the frame washes to the plaster below (lime-100,
// --ease-in-out) from the top of the frame down (renderer.js sweep, `feather` of the frame): ceiling and walls
// first, the runner last, still fading just above the work sheet while its tiles rise over it. The wash lasts
// until the sheet's top is `end` of a screen from the frame's top, and never less than `wash` screens (`washPhone`
// under 600 px wide; 05-DESIGN D0.5: 24svh / 16svh at least). No hard cut, no double exposure, no bare plaster.
export const WALK_OUT = { leave: 0.45, wash: 0.24, washPhone: 0.16, end: 0.12, feather: 0.6, pitch: 14, tilt: [0.92, 1] };
export const walkLeave = (x) => WALK_OUT.leave * sineIn(clamp01(x));
const sineInOut = (x) => 0.5 - 0.5 * Math.cos(Math.PI * x);
// Degrees the camera looks down at stage progress q (0 until 0.92, 14 from the stage's end on).
export const walkPitch = (q) => WALK_OUT.pitch * sineInOut(clamp01((q - WALK_OUT.tilt[0]) / (WALK_OUT.tilt[1] - WALK_OUT.tilt[0])));
// The wash's scroll span: from the stage's end (stageEnd, page px) until the next sheet's top (next) is `end` of a
// screen (h) from the top of the frame, at least the D0.5 minimum for the screen's width w.
export function washSpan(stageEnd, next, w, h) {
  const min = (w < 600 ? WALK_OUT.washPhone : WALK_OUT.wash) * h;
  return { y0: stageEnd, len: Math.max(next - WALK_OUT.end * h - stageEnd, min) };
}

const clamp01 = (x) => Math.min(Math.max(x, 0), 1);

// The beat q falls in (clamped to the doors there are) and how far through it, u < 0 before the first beat
// and u > 1 after the last.
const beatW = (n) => (WALK.beats[1] - WALK.beats[0]) / Math.max(n, 1);
const beatI = (q, n) => Math.min(Math.max(Math.floor((q - WALK.beats[0]) / beatW(n)), 0), Math.max(n - 1, 0));
export function beatAt(q, n) {
  const w = beatW(n), i = beatI(q, n);
  return { i, u: (q - WALK.beats[0]) / w - i, w };
}

// The leaflets keep away from Little Rose's beat (05-DESIGN D0.6.4, critique r1): scrubbed with the scroll (never
// time), their alpha and size go to 0 over the first 0.04 of its beat and come back over its last 0.04, so none
// is drawn anywhere in the frame while its door is read. lr: its index (−1: no Little Rose, always 1).
export const LEAF_FADE = [0.04, 0.96];
export function leafFade(q, n, lr) {
  if (lr < 0 || n <= 0) return 1;
  const u = (q - WALK.beats[0]) / beatW(n) - lr;
  return 1 - smooth(0, LEAF_FADE[0], u) + smooth(LEAF_FADE[1], 1, u);
}

// An open door's angle at q, in degrees: shut until its beat, 72° from 0.45 of it on (the door stays open
// once passed). Little Rose's is shut whatever is asked (sets/corridor.js setDoor). The swing is a push, not
// a fade: it leaves the frame at once and glides the rest of the way (--ease-out), so the door answers the
// scroll the moment its beat arrives.
export const DOOR_DEG = 72;
const push = cubicBezier(bezier.out);
export function doorAngle(i, q, n) {
  const u = (q - WALK.beats[0]) / beatW(n) - i;
  return DOOR_DEG * push(clamp01((u - WALK.swing[0]) / (WALK.swing[1] - WALK.swing[0])));
}

// Where along the walk the camera is: t = 0 is the pose the approach out of the passage settles into, and
// t = i + 1 reads door i. A beat runs t from i + 0.5 to i + 1.5 through dwell() (quick in, a 0.1 drift while
// its card is read, quick out). The first beat starts from where the stage header has carried the camera,
// with a sine-in whose end speed equals the beat's start speed, so the walk never jerks; after the last
// card is read the walk eases to a stop a step past its reading spot. Runs every frame: no allocations.
export function walkT(q, n) {
  if (n <= 0) return 0;
  const h1 = WALK.head[1], w = beatW(n), i = beatI(q, n), u = (q - WALK.beats[0]) / w - i;
  const tHead = (0.95 * h1) / (0.22 * w + h1);
  if (q <= h1) return tHead * sineIn(clamp01(q / h1));
  const uu = Math.min(Math.max(u, 0), 1);
  if (i === n - 1 && uu >= 0.78) {
    // Hermite from the drift (at its speed) to rest at n + 0.1.
    const qa = WALK.beats[0] + (n - 1 + 0.78) * w, x = clamp01((q - qa) / (1 - qa));
    const p0 = n + 0.05, p1 = n + 0.1, m0 = ((0.1 / 0.56) / w) * (1 - qa);
    return (2 * x ** 3 - 3 * x ** 2 + 1) * p0 + (x ** 3 - 2 * x ** 2 + x) * m0 + (3 * x ** 2 - 2 * x ** 3) * p1;
  }
  const d = dwell(uu);
  if (i > 0 || d >= 0.45) return i + 0.5 + d;
  return tHead + (0.95 - tHead) * (d / 0.45);
}

// The camera's spot along the corridor at walk position t: from the approach's z (the exit hole) to just
// short of door 0's reading spot, then one bay per unit of t, so the reading drift is the same 0.65 m at
// every door. view: VIEW[layout]; returns z.
export function walkZ(t, view, lay) {
  const z0 = EXIT_HOLE.z, read0 = (lay.doors[0]?.zc ?? -CORRIDOR.bay / 2) + view.d;
  const a = Math.min(read0 + 0.05 * CORRIDOR.bay, z0);
  return t <= 0.95 ? z0 + (a - z0) * (t / 0.95) : a - (t - 0.95) * CORRIDOR.bay;
}

// How the camera reads a door (D6.2: child's eye height, the door ahead on the right): x across the
// corridor, d before the door along it, yaw toward the door wall (degrees from −z toward +x), vertical fov.
// "side": the card beside the view (≥ 1024 px wide); "below": the card under it, the view a narrow window, so
// the camera walks the middle of the corridor looking straight down it. The yaw keeps the screen and its
// street in frame on the left; a lens shift (verticals stay vertical) puts the door's centre at `at` of the
// part of the frame its card leaves free: just left of a card beside it, the label by the door.
export const VIEW = {
  side: { x: 99.5, d: 4.4, yaw: 15, fov: 50, at: [0.8, 0.52] },
  below: { x: 100.05, d: 4.4, yaw: 2, fov: 78, at: [0.64, 0.44] },
};
export const DOOR_FOCUS_Y = 1.3; // the door with its transom, seen as one thing

// The frustum (tangent space at distance 1: l, r, t, b) that puts point P, seen from C with `yaw` (no pitch),
// at the frame fraction (fx, fy) (0..1 from the top-left), for a vertical fov and aspect.
export function lensFor(C, yaw, P, fov, aspect, fx, fy) {
  const y = (yaw * Math.PI) / 180, dx = P[0] - C[0], dy = P[1] - C[1], dz = P[2] - C[2];
  const depth = dx * Math.sin(y) - dz * Math.cos(y), across = dx * Math.cos(y) + dz * Math.sin(y);
  const th = Math.tan((fov * Math.PI) / 360), tw = th * aspect;
  const px = across / depth, py = dy / depth;
  // Window [l, r] × [b, t] of the same size as the centred one, slid so P lands at (fx, fy).
  const l = px - fx * 2 * tw, t = py + fy * 2 * th;
  return { l, r: l + 2 * tw, t, b: t - 2 * th };
}

// The camera's pose at walk position t for a layout: { pos, yaw, fov }, into `out` when given (per frame).
export function walkPose(t, layout, lay, out = { pos: [0, 0, 0], yaw: 0, fov: 0 }) {
  const v = VIEW[layout];
  out.pos[0] = v.x; out.pos[1] = 1.1; out.pos[2] = walkZ(t, v, lay);
  out.yaw = v.yaw; out.fov = v.fov;
  return out;
}

// The passage's timeline (D5.3 S2), in passage progress: approach the hero block, the wash rises over
// the last part of it, a plateau where no scene is drawn and the room is swapped for the corridor, then
// the camera pulls back out of a corridor hole while the wash clears.
// The wash clears quickly once the statement has gone (a cubic ease-out over 0.78-0.94): the corridor is
// already half there at 0.82, so the way out of the hole is never a blank screen; the camera's pull-back
// keeps the whole exit, 0.78-1.
export const PASSAGE = { approach: [0, 0.28], washIn: [0.18, 0.28], plateau: [0.28, 0.78], exit: [0.78, 1], clear: [0.78, 0.94], read: [0.3, 0.72] };
const clearing = (p) => { const x = clamp01((p - PASSAGE.clear[0]) / (PASSAGE.clear[1] - PASSAGE.clear[0])); return (1 - x) ** 3; };
export const wash = (p) => (p <= PASSAGE.plateau[0] ? smooth(...PASSAGE.washIn, p) : p <= PASSAGE.plateau[1] ? 1 : clearing(p));
// Which set the camera is in: the swap happens behind a full wash, halfway through the plateau.
export const setAt = (p) => (p < (PASSAGE.plateau[0] + PASSAGE.plateau[1]) / 2 ? "room" : "corridor");

// ---- S3, the shutters (05-DESIGN D5.3 S3, PLAN H7) ----
// Stage progress p (0..1 while the frame is pinned): the slats turn 0 → 65° over 0–0.35 (the gold lines on
// the floor widen into bars), the leaves swing 0 → 110° into the room over 0.35–0.75 while the light rises,
// the flood fires once at 0.78 (time-based, 900 ms: exposure overshoot, shaft flare, mote burst; it plays
// back at 1.6× if the visitor scrolls back under 0.70), and the frame blends into flat sun-100 over
// 0.75–0.90, where the help section arrives. The sun runs 0.85 → 0.95 (15:55 → 17:00). Before the frame is
// pinned the camera already moves: the approach (a 0..1) is the section rising into place.
// motion/shutters.js shows the stills from the same numbers (test/beats.test.mjs keeps them equal).
// The live room does not wait for the pin: over the approach the camera pushes in from 6 m to 2.8 m (done by
// a 0.45) and the slats already turn (a 0.15 → 0.50), the gold lines on the floor widening and brightening with
// their angle, so the section rising into place is already the light coming in. The stage keeps its numbers
// for the stills (motion/shutters.js), which only exist pinned.
export const SHUTTERS = {
  slats: [0, 0.35], leaves: [0.35, 0.75], flood: 0.78, unflood: 0.7, blend: [0.75, 0.9], sun: [0.85, 0.95],
  push: 0.45, approachSlats: [0.15, 0.5],
  slatDeg: 65, leafDeg: 110, floodMs: 900, back: 1.6,
};
export const slatAt = (p) => SHUTTERS.slatDeg * smooth(...SHUTTERS.slats, p);
// The live slats over the approach (a) and the stage (p): already turned by the time the stage pins.
export const slatLive = (a, p) => Math.max(SHUTTERS.slatDeg * smooth(...SHUTTERS.approachSlats, a), slatAt(p));
export const leafAt = (p) => SHUTTERS.leafDeg * smooth(...SHUTTERS.leaves, p);
export const blendAt = (p) => smooth(...SHUTTERS.blend, p);
export const s3Sun = (p) => SHUTTERS.sun[0] + (SHUTTERS.sun[1] - SHUTTERS.sun[0]) * clamp01(p);

// The flood is a one-shot inside a scrub (review-animations): the scroll only arms it. s = { on, v }:
// v is the tween's position (0..1 over 900 ms forward, 1.6× faster back). Between 0.70 and 0.78 it holds,
// so a visitor hovering at the threshold never sets it flickering. Paused (D6.10: "no flood"), it goes
// straight to where it would settle. Returns whether anything changed.
export function floodStep(s, p, dt, paused = false) {
  if (!s.on && p >= SHUTTERS.flood) s.on = true;
  else if (s.on && p < SHUTTERS.unflood) s.on = false;
  const to = s.on ? 1 : 0;
  if (s.v === to) return false;
  if (paused) { s.v = to; return true; }
  const step = ((dt * 1000) / SHUTTERS.floodMs) * (s.on ? 1 : SHUTTERS.back);
  s.v = s.on ? Math.min(s.v + step, 1) : Math.max(s.v - step, 0);
  return true;
}
// The flood's shape at tween position v: `peak` rises on --ease-sun's fast front (to 1 at 0.3) and sinks
// back to 0 (the overshoot: exposure 1.55 → 1.75, shafts → 2.0, the motes thrown outward and brightened);
// `held` is what stays once it has passed (exposure 1.6). Into `out`: runs every frame.
const sunFront = (x) => 1 - (1 - x) ** 4; // close to cubic-bezier(0.16, 1, 0.3, 1) and cheaper
export function floodAt(v, out = { peak: 0, held: 0 }) {
  out.peak = v < 0.3 ? sunFront(v / 0.3) : 1 - smooth(0.3, 1, v);
  out.held = smooth(0, 0.5, v);
  return out;
}
// Exposure along S3 (D5.3): 1.22 at rest, 1.55 once the leaves are open, 1.75 at the flood's peak, 1.6
// after it. The set turns it into light: a gain on the sun past full (lit plaster runs toward sun-100,
// never white) and the room's shade warming with the light that bounces in.
// How much of the window lets light in (0 shut, 1 open): the slats give a quarter of it, the leaves the rest.
export const s3Open = (p, slat = slatAt(p)) => 0.25 * (slat / SHUTTERS.slatDeg) + 0.75 * smooth(...SHUTTERS.leaves, p);
export const s3Exposure = (p, f) => 1.22 + 0.33 * smooth(...SHUTTERS.leaves, p) + 0.05 * f.held + 0.15 * f.peak;

// The camera (D6.2: eye height 1.10, the window ahead): along the scroll coordinate s (0..1 the approach,
// 1..2 the stage) it comes round from the side of the room, where the slat light lies across the floor in
// front of it, to square on to the window as the leaves open, then on into the light while the frame
// blends away. Keys at the approach's start, the stage's start, the slats open, the leaves open, the end:
// pos and look relative to the window's centre (x from the wall, y, z across), fov per frame shape. Yaw
// turns 32° over 2.3 screens of scroll (D5.3: at most 40° per 60svh), the fov never changes, no roll, and
// the camera only ever comes closer.
export const S3_VIEW = {
  // Keys (S3_KEYS): the approach's start, 0.45 of it (the push in is done: 6 m → 2.8 m), the stage's start, the
  // slats' end, the leaves open, the end. A phone's narrow frame (critique r2) stops its dolly at 3.8 m on the
  // axis with a wider lens: both leaves, folded back, keep about a fifth of its width each through the blend,
  // the view outside between them, never a blank gold frame.
  portrait: { fov: 72, pos: [[-5.93, -0.9], [-4.4, -0.45], [-4.15, -0.35], [-3.95, -0.22], [-3.85, -0.06], [-3.8, 0]], look: [[-0.4, 0.55, 0.15], [-0.38, 0.6, 0.12], [-0.35, 0.62, 0.1], [-0.2, 0.72, 0.05], [0, 0.95, 0], [0, 1.05, 0]] },
  tablet: { fov: 54, pos: [[-5.8, -1.65], [-2.75, -0.95], [-2.55, -0.75], [-2.3, -0.42], [-1.95, -0.12], [-1.5, 0]], look: [[-0.5, 0.55, 0.35], [-0.42, 0.6, 0.18], [-0.38, 0.62, 0.12], [-0.25, 0.72, 0.08], [0, 0.93, 0.03], [0, 1.04, 0]] },
  landscape: { fov: 48, pos: [[-5.43, -2.55], [-2.56, -1.15], [-2.4, -0.9], [-2.2, -0.5], [-1.9, -0.15], [-1.6, 0]], look: [[-0.5, 0.55, 0.45], [-0.4, 0.6, 0.12], [-0.35, 0.62, 0.1], [-0.2, 0.72, 0.05], [0, 0.95, 0.03], [0, 1.05, 0]] },
  wide: { fov: 46, pos: [[-5.43, -2.55], [-2.56, -1.15], [-2.4, -0.9], [-2.2, -0.5], [-1.9, -0.15], [-1.6, 0]], look: [[-0.5, 0.58, 0.45], [-0.4, 0.62, 0.12], [-0.35, 0.64, 0.1], [-0.2, 0.74, 0.05], [0, 0.96, 0.03], [0, 1.06, 0]] },
};
// Pose at approach a and stage p for a frame shape, relative to the window (centre z zc, wall x 0, room at
// x < 0): { pos, look, fov }, into `out` when given.
const S3_KEYS = [0, SHUTTERS.push, 1, 1 + SHUTTERS.slats[1], 1 + SHUTTERS.leaves[1], 2];
// Monotone cubic through the keys (Fritsch–Carlson): no stop at a key, no overshoot, never back.
function monotone(xs, ys, x) {
  const n = xs.length, i = Math.min(Math.max(xs.findLastIndex((k) => k <= x), 0), n - 2);
  const slope = (j) => (ys[j + 1] - ys[j]) / (xs[j + 1] - xs[j]);
  const tan = (j) => {
    if (j === 0) return slope(0);
    if (j === n - 1) return slope(n - 2);
    const a = slope(j - 1), b = slope(j);
    return a * b <= 0 ? 0 : (3 * (xs[j + 1] - xs[j - 1])) / ((2 * xs[j + 1] - xs[j] - xs[j - 1]) / a + (xs[j + 1] + xs[j] - 2 * xs[j - 1]) / b);
  };
  const h = xs[i + 1] - xs[i], t = clamp01((x - xs[i]) / h), t2 = t * t, t3 = t2 * t;
  return (2 * t3 - 3 * t2 + 1) * ys[i] + (t3 - 2 * t2 + t) * h * tan(i) + (-2 * t3 + 3 * t2) * ys[i + 1] + (t3 - t2) * h * tan(i + 1);
}
const keyCol = (keys, k) => keys.map((x) => x[k]);
export function s3Pose(a, p, bucket, zc, out = { pos: [0, 0, 0], look: [0, 0, 0], fov: 0 }) {
  const v = S3_VIEW[bucket] ?? S3_VIEW.landscape, s = clamp01(a) + clamp01(p);
  out.pos[0] = monotone(S3_KEYS, keyCol(v.pos, 0), s); out.pos[1] = 1.1; out.pos[2] = zc + monotone(S3_KEYS, keyCol(v.pos, 1), s);
  for (let k = 0; k < 3; k++) out.look[k] = monotone(S3_KEYS, keyCol(v.look, k), s) + (k === 2 ? zc : 0);
  out.fov = v.fov;
  return out;
}
