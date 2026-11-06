// D6.4 gate: the block pattern must stay crisp on each set's signature surface while that set is on
// screen. For every clock position a set shows, traces the sun through a grid of points on the screen
// to the surface and checks
//   incidence <= 60°  (the pattern stretches by 1 / cos(incidence): 2x at 60°)
//   penumbra  <= limit x the quatrefoil lobe radius, at the median throw (the shader's own width:
//              0.0025 + 0.0093 · 0.6 · distance, the sun's angular size)
// A surface may carry its own incidence limit when the design accepts the stretch there (the room's
// floor: a sun high enough to crisp it drops every patch below the hero text). Sets that are not
// built yet are listed and skipped. Pointer extremes are reported, not gated: they last only while
// the pointer sits in a corner. Usage: node scripts/check-sun-crisp.mjs (exit 1 on fail)
import { makeSun, POINTER_RANGE, sunAt } from "../src/world/clock.js";
import { HOA_THI } from "../src/world/glsl/blocks.js";
import { BOX, SCREEN } from "../src/world/sets/room.js";

const MAX_INCIDENCE = 60;
const LOBE = HOA_THI.r;
const penumbra = (s) => 0.0025 + 0.0093 * 0.6 * s;
const deg = (r) => (r * 180) / Math.PI;

// The room's signature surfaces: the back wall (z = BOX.min z, facing +z) and the floor (y = 0).
const ROOM_SURFACES = [
  { name: "back wall", normal: [0, 0, 1], limit: 0.6, hit: (Q, L) => {
    const s = (Q.z - BOX.min[2]) / L.z, y = Q.y - L.y * s, x = Q.x - L.x * s;
    return s > 0 && y >= 0 && y <= BOX.max[1] && x >= BOX.min[0] && x <= BOX.max[0] ? s : null;
  } },
  { name: "floor", normal: [0, 1, 0], limit: 1.0, maxIncidence: 68, hit: (Q, L) => {
    const s = Q.y / L.y, z = Q.z - L.z * s, x = Q.x - L.x * s;
    return s > 0 && z >= BOX.min[2] && z <= BOX.max[2] && x >= BOX.min[0] && x <= BOX.max[0] ? s : null;
  } },
];

// The clock range each set shows: the hero from rest to the end of its scroll (live.js), the passage
// key only as the camera leaves the room through a hole (its signature is the holes themselves).
const SETS = [
  { set: "room", ts: [0.08, 0.11, 0.14], surfaces: ROOM_SURFACES },
  { set: "corridor", pending: "corridor set not built yet (day 5-6)" },
  { set: "shutters", pending: "shutters set not built yet (day 8)" },
];

function throws(L, surface) {
  const out = [];
  for (let i = 0; i <= 60; i++) for (let j = 0; j <= 30; j++) {
    const Q = { x: SCREEN.sx, z: SCREEN.z0 + ((SCREEN.z1 - SCREEN.z0) * i) / 60, y: SCREEN.y0 + ((SCREEN.y1 - SCREEN.y0) * j) / 30 };
    const s = surface.hit(Q, L);
    if (s != null) out.push(s);
  }
  return out.sort((a, b) => a - b);
}

function measure(set, t, surface, nudge) {
  const sun = sunAt(set, t, makeSun(), nudge);
  const L = sun.dir;
  const cos = L.x * surface.normal[0] + L.y * surface.normal[1] + L.z * surface.normal[2];
  const incidence = deg(Math.acos(Math.max(-1, Math.min(1, cos))));
  const s = throws(L, surface);
  const median = s.length ? s[Math.floor(s.length / 2)] : null;
  const ratio = median == null ? null : penumbra(median) / LOBE;
  return { incidence, median, ratio, lit: s.length };
}

let fail = 0;
for (const entry of SETS) {
  if (entry.pending) { console.log(`skip  ${entry.set}: ${entry.pending}`); continue; }
  for (const surface of entry.surfaces) for (const t of entry.ts) {
    const m = measure(entry.set, t, surface);
    const maxInc = surface.maxIncidence ?? MAX_INCIDENCE;
    const pass = m.lit > 0 && m.incidence <= maxInc + 1e-6 && m.ratio <= surface.limit;
    if (!pass) fail++;
    console.log(`${pass ? "PASS" : "FAIL"}  ${entry.set} t ${t.toFixed(2)} ${surface.name}: incidence ${m.incidence.toFixed(1)}° (≤ ${maxInc}${surface.maxIncidence ? ", stretch accepted" : ""}, stretch ${(1 / Math.cos((m.incidence * Math.PI) / 180)).toFixed(2)}x) · penumbra ${m.ratio == null ? "-" : m.ratio.toFixed(2)} x lobe at ${m.median?.toFixed(2) ?? "-"} m (≤ ${surface.limit}) · ${m.lit} rays land`);
    if (t === entry.ts[0]) for (const [az, el] of [[-1, -1], [1, -1], [-1, 1], [1, 1]]) {
      const p = measure(entry.set, t, surface, { az: az * POINTER_RANGE.az, el: el * POINTER_RANGE.el });
      console.log(`info    pointer ${az > 0 ? "+" : "−"}${POINTER_RANGE.az}° az ${el > 0 ? "+" : "−"}${POINTER_RANGE.el}° el: incidence ${p.incidence.toFixed(1)}° · penumbra ${p.ratio?.toFixed(2) ?? "-"} x lobe`);
    }
  }
}
console.log(fail ? `check-sun-crisp: ${fail} FAIL` : "check-sun-crisp: all PASS");
process.exitCode = fail ? 1 : 0;
