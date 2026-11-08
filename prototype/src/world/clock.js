import { Color, MathUtils, Vector3 } from "three";
import { tok } from "./colors.js";

// The narrative sun clock (05-DESIGN D6.4): t 0 = 7:00, 1 = 17:30, keyframed per set so the
// block pattern stays crisp on each set's signature surface. Noon is never shown.
// mirror: the corridor's screen faces +x, so its sun sits on the −x side.
const SETS = {
  // Room: 40° / 24° keeps the back wall, the hero's signature surface, at 54° incidence; the floor
  // takes a 66° stretch (check-sun-crisp.mjs accepts it there). A higher sun crisps the floor but drops
  // every patch below the text; the proof's 28° / 21° stretched the back wall's quatrefoils to blobs.
  room: { mirror: false, keys: [[0.08, 40, 24, "sunRoom"], [0.2, 36, 26, "sunPassage"]] },
  corridor: { mirror: true, keys: [[0.25, 8, 24, "sunCorridorA"], [0.45, -8, 28, "sunCorridorB"]] },
  shutters: { mirror: false, keys: [[0.85, -11, 25, "sunShuttersA"], [0.95, -16, 20, "sunShuttersB"]] },
};
const colors = Object.fromEntries(Object.values(SETS).flatMap((s) => s.keys.map((k) => [k[3], tok(k[3])])));

export const REST = { room: 0.08 };

// Pointer sun extremes (D5.3), which the light gate samples too (D1): ±6° azimuth, ±3° elevation,
// enough that the patches visibly follow the pointer across the H1.
export const POINTER_RANGE = { az: 6, el: 3 };

const tmpA = new Color(), tmpB = new Color();

// Writes the direction toward the sun and its linear color into `out` ({ dir, color }).
// nudge: { az, el } in degrees (pointer sun), added after the keyframe.
export function sunAt(set, t, out, nudge) {
  const s = SETS[set];
  const [a, b] = s.keys;
  const u = MathUtils.clamp((t - a[0]) / (b[0] - a[0]), 0, 1);
  const az = MathUtils.degToRad(MathUtils.lerp(a[1], b[1], u) + (nudge?.az ?? 0));
  const el = MathUtils.degToRad(MathUtils.lerp(a[2], b[2], u) + (nudge?.el ?? 0));
  out.dir.set(Math.cos(el) * Math.cos(az) * (s.mirror ? -1 : 1), Math.sin(el), Math.cos(el) * Math.sin(az)).normalize();
  out.color.copy(tmpA.copy(colors[a[3]])).lerp(tmpB.copy(colors[b[3]]), u);
  return out;
}

export const makeSun = () => ({ dir: new Vector3(), color: new Color() });

// Suns of the stills (05-DESIGN D6.10, PLAN section 4): one fixed sun per shot at its hour, from the same
// formula. Each keeps its signature surface crisp: incidence ≤ 60° where the pattern lands (D6.4).
// mirror: the sun on the −x side (the corridor's screen faces +x).
export const STILL_SUNS = {
  frontal: { az: -18, el: 42, color: "sunCorridorA" },   // the screen seen frontally (the 404): high enough to crisp the floor
  ourWork: { az: 46, el: 24, color: "sunCorridorA" },    // 10:30 (t ≈ 0.30): raking through a side screen onto the back wall (incidence ≈ 50°)
  door: { az: 6, el: 14, color: "sunCorridorA", mirror: true }, // morning, nearly head-on to the doors
  hub: { az: 4, el: 15, color: "sunCorridorA", mirror: true },   // the gallery behind Our Work's doors (t ≈ 0.30): low enough that the screen behind the camera lands its holes at mid-height
  gallery: { az: 50, el: 12, color: "sunCorridorA", mirror: true }, // Green Bamboo's gallery wall: low and raking from a screen in the side wall
  louvers: { az: 50, el: 18, color: "sunCorridorA" },    // late morning through the lam: the stripes cross the back wall at mid-height (incidence 43°)
  shutters: { az: -48, el: 16, color: "sunShuttersB" },  // 17:00, raking: the window's patch climbs the side wall
  street: { az: -58, el: 20, color: "sunShuttersA" },    // 16:30, raking along the street face
  story: { az: 40, el: 24, color: "sunRoom" },           // the hero room at rest
};

export function sunFixed({ az, el, mirror = false }, out = new Vector3()) {
  const a = MathUtils.degToRad(az), e = MathUtils.degToRad(el);
  return out.set(Math.cos(e) * Math.cos(a) * (mirror ? -1 : 1), Math.sin(e), Math.cos(e) * Math.sin(a)).normalize();
}
