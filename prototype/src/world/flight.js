import { Euler, MathUtils, Quaternion, Vector3 } from "three";
import { hits, projectPoly } from "./frame.js";
import { heroBlockFor } from "./sets/room.js";
import { EXIT, exitLook } from "./sets/places.js";
import { PASSAGE } from "./journey.js";

// S2 part 1 (05-DESIGN D5.3, D6.2): the camera from the hero's rest frame (K0) to the hero block (K1, K2),
// then, behind the wash, out of a hole in the corridor screen (K3 → K4). Everything is a function of the
// scroll position, so scrolling back plays it backwards at the visitor's speed. No roll, ever.
//
// The path is s in [0, 2]: 0 the rest frame, 1 the hero leaving (K1), 2 at the hole (K2). The rest frame
// is the poster's, lens shift included; the shift blends out as the camera leaves it.
//
// Text safety while the hero scrolls out (D6.6): the hero text scrolls at native speed over a fixed
// canvas, so the screen silhouette (and, under body text, the floor) must stay off every line still on
// screen. A table over the scroll range holds, for each position, how far along the path the camera may
// be and how far the image is carried up with the page (a lens shift, up to 1:1, the way the stills
// scroll), so that nothing touches the text. It is solved once per layout, then smoothed: the camera never
// goes back while the visitor scrolls on, and the carried image eases back (RELAX px per px scrolled).

// K1 portrait: on the way from the corner shot to its block (row 1, column 2), 1.1 m short of it.
export const K1 = {
  portrait: { pos: [1.52, 1.1, -3.05], fov: 56 },
  tablet: { pos: [0.3, 1.1, 0.4], fov: 50 },
  landscape: { pos: [0.2, 1.1, 0.6], fov: 42 },
  wide: { pos: [0.2, 1.1, 0.6], fov: 40 },
};
// K2: close to the hero block, the quatrefoil filling most of the frame.
export const K2 = { portrait: { dist: 0.5, fov: 56 }, tablet: { dist: 0.42, fov: 50 }, landscape: { dist: 0.35, fov: 42 }, wide: { dist: 0.35, fov: 40 } };
// From inside the hole the camera turns 12° down the corridor; a phone's narrow frame turns less, so the
// screen it came through stays in view on the left.
const EXIT_YAW = { portrait: [20, 8], tablet: [28, 14] };
export const exitYaw = (bucket) => EXIT_YAW[bucket] ?? [32, EXIT.yaw];
// px of carried image released per px scrolled. A phone carries its room furthest (the text rises into the
// screen above it) and has nothing left to read once the intro is gone: it lets the room down faster, so the
// screen and its light come back while the hero's last button leaves, never a bare corner (critique r2).
const RELAX = { portrait: 2.2 };
const relaxFor = (bucket) => RELAX[bucket] ?? 0.8;
const STEP = 6;    // px between table rows

const smooth = (a, b, x) => { const t = MathUtils.clamp((x - a) / (b - a), 0, 1); return t * t * (3 - 2 * t); };
const sineInOut = (x) => 0.5 - 0.5 * Math.cos(Math.PI * MathUtils.clamp(x, 0, 1));
const sineOut = (x) => Math.sin((MathUtils.clamp(x, 0, 1) * Math.PI) / 2);

// Frustum in tangent space (at distance 1), as three computes it, view offset included.
export function boundsOf(cam) {
  const top = Math.tan(MathUtils.degToRad(cam.fov / 2)) / cam.zoom;
  let h = 2 * top, w = cam.aspect * h, l = -w / 2, t = top;
  const v = cam.view;
  if (v?.enabled) { l += (v.offsetX * w) / v.fullWidth; t -= (v.offsetY * h) / v.fullHeight; w *= v.width / v.fullWidth; h *= v.height / v.fullHeight; }
  return { l, r: l + w, t, b: t - h };
}
export const centred = (fov, aspect, out) => { const t = Math.tan(MathUtils.degToRad(fov / 2)); return Object.assign(out, { l: -t * aspect, r: t * aspect, t, b: -t }); };
// The image carried up by `lift` canvas px: the window looks lower.
export function applyBounds(cam, b, lift, viewH) {
  const n = cam.near, dy = (lift * (b.t - b.b)) / viewH;
  cam.projectionMatrix.makePerspective(b.l * n, b.r * n, (b.t - dy) * n, (b.b - dy) * n, n, cam.far);
  cam.projectionMatrixInverse.copy(cam.projectionMatrix).invert();
}

const yawPitch = (from, to) => { const d = to.clone().sub(from).normalize(); return [Math.atan2(-d.x, -d.z), Math.asin(MathUtils.clamp(d.y, -1, 1))]; };
const angleLerp = (a, b, t) => { let d = b - a; while (d > Math.PI) d -= 2 * Math.PI; while (d < -Math.PI) d += 2 * Math.PI; return a + d * t; };
function catmull(p0, p1, p2, p3, t, out) {
  const t2 = t * t, t3 = t2 * t;
  for (const k of ["x", "y", "z"]) out[k] = 0.5 * (2 * p1[k] + (p2[k] - p0[k]) * t + (2 * p0[k] - 5 * p1[k] + 4 * p2[k] - p3[k]) * t2 + (3 * p1[k] - p0[k] - 3 * p2[k] + p3[k]) * t3);
  return out;
}

export function createFlight() {
  const k0 = { pos: new Vector3(), yaw: 0, pitch: 0, fov: 46, bounds: { l: -1, r: 1, t: 1, b: -1 } };
  const k1 = new Vector3(), k2 = new Vector3();
  let bucket = "landscape", aspect = 1, viewH = 1, target = heroBlockFor("landscape");
  let k1Yaw = 0, k1Pitch = 0;
  const pos = new Vector3(), euler = new Euler(0, 0, 0, "YXZ"), q = new Quaternion(), b = { l: 0, r: 0, t: 0, b: 0 }, c = { l: 0, r: 0, t: 0, b: 0 };
  const tmp = new Vector3();

  // The rest frame, taken from the room's camera once the framing solver has placed it.
  const setRest = (cam, name, view) => {
    bucket = name;
    aspect = view.w / view.h;
    viewH = view.h;
    k0.pos.copy(cam.position);
    const f = new Vector3(0, 0, -1).applyQuaternion(cam.quaternion);
    k0.yaw = Math.atan2(-f.x, -f.z);
    k0.pitch = Math.asin(MathUtils.clamp(f.y, -1, 1));
    k0.fov = cam.fov;
    Object.assign(k0.bounds, boundsOf(cam));
    target = heroBlockFor(bucket);
    k1.set(...K1[bucket].pos);
    // K2 on the line from K1 to the block, turned a third of the way toward square-on: the approach is a
    // dolly with little turn left in it, and the hole shows its sunlit inner rim.
    tmp.copy(k1).sub(target).setY(0).normalize().lerp(new Vector3(-1, 0, 0), 0.35).normalize();
    k2.copy(target).addScaledVector(tmp, K2[bucket].dist).setY(1.1);
    [k1Yaw, k1Pitch] = yawPitch(k1, target);
  };

  // The room camera at path position s (0..2), carried up by `lift` px. pointer: 0..1 of the rest pose's
  // parallax still applied (it fades as the camera leaves).
  const room = (cam, s, lift = 0) => {
    if (s <= 1) catmull(k0.pos, k0.pos, k1, k2, s, pos);
    else catmull(k0.pos, k1, k2, k2, s - 1, pos);
    cam.position.copy(pos);
    let yaw, pitch, fov;
    if (s <= 1) {
      // Turn toward the hero block through the hero segment, more of it late, when the text has gone.
      const r = sineInOut(s);
      const [ty, tp] = yawPitch(pos, target);
      const aim = smooth(0.6, 1, s);
      yaw = angleLerp(k0.yaw, angleLerp(k1Yaw, ty, aim), r);
      // Pitch last: lifting the gaze brings the screen down toward the text, so it waits for the text to go.
      pitch = MathUtils.lerp(k0.pitch, MathUtils.lerp(k1Pitch, tp, aim), smooth(0.45, 1, s));
      fov = MathUtils.lerp(k0.fov, K1[bucket].fov, r);
    } else {
      [yaw, pitch] = yawPitch(pos, target);
      fov = MathUtils.lerp(K1[bucket].fov, K2[bucket].fov, s - 1);
    }
    cam.quaternion.copy(q.setFromEuler(euler.set(pitch, yaw, 0)));
    cam.fov = fov;
    cam.updateMatrixWorld();
    // The poster's lens shift blends out over the first part of the move.
    const k = smooth(0, 0.7, s);
    centred(fov, aspect, c);
    for (const key of ["l", "r", "t", "b"]) b[key] = MathUtils.lerp(k0.bounds[key], c[key], k);
    applyBounds(cam, b, lift, viewH);
    return 1 - k;
  };

  // The corridor camera coming out of the exit hole: u 0 (inside it) → 1 (K4).
  const exit = (cam, u) => {
    const e = sineOut(u);
    cam.position.lerpVectors(EXIT.from, EXIT.to, e);
    cam.fov = EXIT.fov[bucket];
    cam.aspect = aspect;
    const [y0, y1] = exitYaw(bucket);
    cam.lookAt(exitLook(cam.position, MathUtils.lerp(y0, y1, e)));
    cam.updateMatrixWorld();
    applyBounds(cam, centred(cam.fov, aspect, c), 0, viewH);
  };

  // ---- the text-safety table ----
  let table = null;
  // hero: { top, height } of the hero section, document px; approach: px of scroll for passage 0 → 0.28;
  // rects: hero text rects at rest (document px; kind display or body); shapes: silhouette, floor.
  // top: the compact header's height; text under its opaque bar is not on screen.
  const solve = (cam, { heroH, approach, rects, shapes, view, top: barH = 0 }) => {
    const end = heroH + approach, n = Math.ceil(end / STEP) + 1;
    const free = heroH + 0.6 * approach; // carried image fully released by here, before the wash
    const want = (y) => (y <= heroH ? Math.pow(MathUtils.clamp(y / heroH, 0, 1), 1.3) : 1 + sineInOut((y - heroH) / approach));
    const text = rects.filter((r) => r.kind !== "opaque" && !r.pinned);
    const clear = (s, lift, y) => {
      room(cam, s, lift);
      const scr = projectPoly(shapes.silhouette, cam, view.w, view.h);
      const flo = projectPoly(shapes.floor, cam, view.w, view.h);
      for (const r of text) {
        const top = r.y - y, edge = y > 80 ? barH : 0; // the bar turns opaque after 80 px (ui/header.js)
        if (top + r.h <= edge || top >= view.h) continue;
        const v = { x: r.x, y: Math.max(top, edge), w: r.w, h: Math.min(top + r.h, view.h) - Math.max(top, edge) };
        if (hits(scr, 0, 0, v) || (r.kind === "body" && hits(flo, 0, 0, v))) return false;
      }
      return true;
    };
    const bisect = (ok, lo, hi) => { for (let i = 0; i < 10; i++) { const m = (lo + hi) / 2; ok(m) ? (hi = m) : (lo = m); } return hi; };
    // Per row: the camera first waits on its path (nothing moves under the text); only when even the rest
    // pose would touch a line (a phone, where the text rises into the screen above it) is the image carried
    // up with the page, as little as clears it, and the camera goes as far as that allows.
    const furthest = (sd, lift, y) => (clear(sd, lift, y) ? sd : clear(0, lift, y) ? sd - bisect((d) => clear(sd - d, lift, y), 0, sd) : 0);
    const rows = [];
    for (let i = 0; i < n; i++) {
      const y = Math.min(i * STEP, end), sd = want(y), cap = y < free ? y : 0;
      let s = sd, lift = 0;
      if (y < heroH && !clear(sd, 0, y)) {
        if (clear(0, 0, y)) s = furthest(sd, 0, y);
        else {
          lift = cap > 0 && clear(0, cap, y) ? bisect((e) => clear(0, e, y), 0, cap) : cap;
          s = furthest(sd, lift, y);
        }
      }
      rows.push({ y, s, lift, need: lift });
    }
    // Smooth: the carried image rises at most with the page and settles back at RELAX; the camera never
    // goes back along the path while scrolling on (a smaller s later wins earlier).
    for (let i = 1; i < n; i++) rows[i].lift = Math.max(rows[i].need, rows[i - 1].lift - relaxFor(bucket) * STEP);
    for (let i = n - 2; i >= 0; i--) rows[i].lift = Math.min(rows[i].y, Math.max(rows[i].lift, rows[i + 1].lift - STEP));
    for (const r of rows) if (r.y >= free) r.lift = 0;
    // A smoothed lift can undo a row's clearance: that row's camera waits where it is clear again.
    for (const r of rows) {
      if (r.y >= heroH || r.lift === r.need || clear(r.s, r.lift, r.y)) continue;
      r.s = clear(0, r.lift, r.y) ? r.s - bisect((d) => clear(r.s - d, r.lift, r.y), 0, r.s) : 0;
    }
    for (let i = n - 2; i >= 0; i--) rows[i].s = Math.min(rows[i].s, rows[i + 1].s);
    // A camera held back catches up no faster than the vestibular limit (D5.3: yaw <= 40° per 60svh of
    // scroll). Measured on the turn itself: the end of the path is a straight dolly and may run.
    const maxTurn = MathUtils.degToRad(40) * STEP / (0.6 * view.h);
    const yawOf = (sv) => { room(cam, sv, 0); tmp.set(0, 0, -1).applyQuaternion(cam.quaternion); return Math.atan2(-tmp.x, -tmp.z); };
    let prevYaw = yawOf(rows[0].s);
    for (let i = 1; i < n; i++) {
      const lo = rows[i - 1].s;
      const turn = (sv) => Math.abs(angleLerp(0, yawOf(sv) - prevYaw, 1));
      if (rows[i].s > lo && turn(rows[i].s) > maxTurn) rows[i].s = lo + (rows[i].s - lo) * (1 - bisect((d) => turn(rows[i].s - (rows[i].s - lo) * d) <= maxTurn, 0, 1));
      prevYaw = yawOf(rows[i].s);
    }
    table = { rows, end };
    return { rows: n, held: rows.filter((r) => r.s < want(r.y) - 1e-3).length, carried: rows.filter((r) => r.lift > 0).length };
  };
  // Path position and carried image at scroll y (document px past the hero's top).
  const at = (y, out) => {
    if (!table) return Object.assign(out, { s: 0, lift: 0 });
    const { rows } = table;
    const f = MathUtils.clamp(y / STEP, 0, rows.length - 1), i = Math.floor(f), j = Math.min(i + 1, rows.length - 1), t = f - i;
    out.s = MathUtils.lerp(rows[i].s, rows[j].s, t);
    out.lift = MathUtils.lerp(rows[i].lift, rows[j].lift, t);
    return out;
  };

  return { setRest, room, exit, solve, at, get bucket() { return bucket; }, get table() { return table; } };
}

// Where the page is in the journey, from the scroll position and the sections' boxes (document px).
// hero/passage: { top, len } (len: px of scroll the section's timeline spans).
export function journeyAt(y, { hero, passage }, out) {
  out.hero = MathUtils.clamp((y - hero.top) / hero.len, 0, 1);
  out.passage = MathUtils.clamp((y - passage.top) / passage.len, 0, 1);
  out.y = y - hero.top;
  return out;
}
export { PASSAGE };
