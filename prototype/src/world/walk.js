import { MathUtils, Vector3 } from "three";
import { EXIT } from "./sets/places.js";
import { applyBounds, centred, exitYaw } from "./flight.js";
import { CORRIDOR, DOOR_FOCUS_Y, DWELL_TILT, VIEW, dwellTilt, lensFor, walkLeave, walkPose, walkT } from "./journey.js";

// S2 part 2 (05-DESIGN D5.3, PLAN H4): the camera from K4, where the passage leaves it, down the corridor
// door by door. Everything is a function of the scroll (never time), so it reverses at the visitor's speed.
// While the stage rises into place (the approach) the camera turns from looking down the corridor to its
// reading yaw; once the stage is pinned walkT() walks it. A lens shift keeps each door in the part of the
// frame its card leaves free (read from the card panel), so a card never covers the door it is about, and
// verticals stay vertical. No roll, the fov changes by under 15%; the only pitch is the way out's look down to
// the floor (journey.js walkPitch), once the last card is going.

const smooth = (x) => { const t = MathUtils.clamp(x, 0, 1); return t * t * (3 - 2 * t); };
const TILT = Math.tan(MathUtils.degToRad(DWELL_TILT));

export function createWalk() {
  const pos = new Vector3(), look = new Vector3(), at = { pos: [0, 0, 0], yaw: 0, fov: 0 };
  const k4 = { l: 0, r: 0, t: 0, b: 0 }, b = { l: 0, r: 0, t: 0, b: 0 };
  let layout = "side", lens = null, aspect = 1, viewH = 1, bucket = "landscape", lay = null;

  // view: canvas px; free: the part of the frame the card panel leaves (x0, x1, y0, y1 in canvas px); name: a
  // VIEW layout; corridor: corridorLayout(beats). Every door is read from the same relative pose, so one lens
  // serves them all.
  const setLayout = ({ bucket: bk, view, free, name, corridor }) => {
    bucket = bk; layout = name; lay = corridor;
    aspect = view.w / view.h; viewH = view.h;
    const p = walkPose(1, layout, lay), zc = lay.doors[0]?.zc ?? p.pos[2] - VIEW[layout].d;
    const [ax, ay] = VIEW[layout].at, fx = (free.x0 + ax * (free.x1 - free.x0)) / view.w, fy = (free.y0 + ay * (free.y1 - free.y0)) / view.h;
    lens = lensFor(p.pos, p.yaw, [CORRIDOR.wallX, DOOR_FOCUS_Y, zc], p.fov, aspect, fx, fy);
  };

  // a: the approach (0 at K4, 1 settled); q: the stage's progress; n: doors; out: the way out past the last
  // door (0..1, journey.js WALK_OUT); pitch: degrees down (journey.js walkPitch); lift: px the frame has scrolled up
  // since the stage let go, the image carried up with it (the runner stays just above the sheet that follows).
  const pose = (cam, a, q, n, out = 0, pitch = 0, lift = 0) => {
    const p = walkPose(walkT(q, n) + walkLeave(out), layout, lay, at), e = smooth(a);
    const yaw = MathUtils.degToRad(MathUtils.lerp(exitYaw(bucket)[1], p.yaw, e));
    const fov = MathUtils.lerp(EXIT.fov[bucket], p.fov, e);
    pos.set(MathUtils.lerp(EXIT.to.x, p.pos[0], e), 1.1, MathUtils.lerp(EXIT.to.z, p.pos[2], e));
    cam.position.copy(pos);
    cam.fov = fov;
    cam.aspect = aspect;
    const pt = MathUtils.degToRad(pitch), c = Math.cos(pt) * 10;
    cam.lookAt(look.set(pos.x + Math.sin(yaw) * c, 1.1 - Math.sin(pt) * 10, pos.z - Math.cos(yaw) * c));
    cam.updateMatrixWorld();
    // The lens shifts in as the camera settles, scaled to the fov in between.
    centred(fov, aspect, k4);
    const s = Math.tan(MathUtils.degToRad(fov / 2)) / Math.tan(MathUtils.degToRad(p.fov / 2));
    for (const key of ["l", "r", "t", "b"]) b[key] = MathUtils.lerp(k4[key], lens[key] * s, e);
    // The dwell's look down, beside the card (journey.js dwellTilt): the window slides down, verticals stay vertical.
    if (layout === "side") { const k = TILT * dwellTilt(q, n) * e; b.t -= k; b.b -= k; }
    applyBounds(cam, b, lift, viewH);
  };

  return { setLayout, pose, get layout() { return layout; }, get lens() { return lens; } };
}
