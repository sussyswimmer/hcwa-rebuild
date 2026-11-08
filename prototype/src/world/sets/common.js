import { PerspectiveCamera, Vector3 } from "three";
import { capUniforms } from "../materials/caps.js";
import { mixc, tok, vec } from "../colors.js";

// Shared by the still sets and the live corridor: the same surface model, the same colors.

// A light target recolored by a sun relative to the room's rest sun (as room.js setSun), at strength k.
export const tint = (c, sun, k = 1, rest = tok("sunRoom")) => c.clone().lerp(c.clone().multiply(sun).multiply({ r: 1 / rest.r, g: 1 / rest.g, b: 1 / rest.b }), k);

// Surface targets in shade and in each light (display colors from the world tokens, as surface.js).
export function surfaceUniforms({ box, fillDir, sun, shade, sunT, spillT, refs, lime, terr }) {
  return {
    ...capUniforms(),
    uShade: { value: shade.map(vec) },
    uSunT: { value: sunT.map(vec) },
    uSpillT: { value: spillT.map(vec) },
    uGlare: { value: vec(tok("sky")) },
    uBoxMin: { value: new Vector3(...box.min) },
    uBoxMax: { value: new Vector3(...box.max) },
    uFillDir: { value: new Vector3(...fillDir).normalize() },
    uRef: { value: refs },
    uI: { value: [1, 1] },
    uAoK: { value: 1 },
    uLime: { value: lime },
    uTerr: { value: terr },
    // The shafts pass reads these.
    uSunCol: { value: new Vector3(sun.r, sun.g, sun.b) },
    uSunI: { value: 1 },
    uWind: { value: new Vector3() },
    uTime: { value: 0 },
  };
}

export const roomShade = () => [tok("wallShade"), tok("floorShade"), tok("ceiling")];
export const roomSun = (sun, k = 1) => [tint(tok("wallSun"), sun, k), tint(tok("wallSun"), sun, k).multiplyScalar(0.8), tint(tok("wallSun"), sun, k)];
export const street = (low = tok("sky"), high = mixc(tok("sky"), tok("shade-100"), 0.35), canopy = mixc(tok("sky"), tok("unit-green-bamboo"), 0.2), amt = 0.55) => ({
  uBehindLow: { value: vec(low) }, uBehindHigh: { value: vec(high) }, uBehindY: { value: [0.6, 6] },
  uCanopyC: { value: vec(canopy) }, uCanopyAmt: { value: amt }, uBehindDist: { value: 6.4 },
});

// Backlit like the room's screen: the logo-blue silhouette, the street blazing in the holes.
export function screenColors(u, { face = tok("screenFace"), glow = tok("sky") } = {}) {
  u.uFace.value.copy(vec(face));
  u.uRimC.value.copy(vec(mixc(tok("wallSun"), glow, 0.3)));
  u.uWallC.value.copy(vec(mixc(face, glow, 0.38)));
}

export function camera(c, aspect) {
  const cam = new PerspectiveCamera(c.fov, aspect, 0.05, 80);
  cam.position.set(...c.pos);
  cam.lookAt(...c.look);
  cam.updateProjectionMatrix();
  cam.updateMatrixWorld();
  return cam;
}
