import { PerspectiveCamera, Vector3, Vector4 } from "three";
import { capUniforms } from "../materials/caps.js";
import { MAX_DOORS } from "../glsl/blocks.js";
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

// The Saigon mái ấm (05-DESIGN D0.6.2): yellow lime wash, the tiles' cream ground, the warm ceiling.
// The house's sun targets are the corridor's own (D0.6.1 measured them at its morning sun, t 0.25): they shift
// with the sun relative to sun-corridor-a, not the room's warmer rest sun, so a patch on the mint dado stays mint.
const houseTint = (c, sun, k) => tint(c, sun, k, tok("sunCorridorA"));
export const houseShade = () => [tok("vangShade"), tok("gachShade"), tok("ceilingWarm")];
export const houseSun = (sun, k = 0.5) => [houseTint(tok("vangSun"), sun, k), houseTint(tok("sun-100"), sun, k), houseTint(tok("vangSun"), sun, k)];
// The dado in a sun patch, and its painted line in shade and sun (the line's paint, lit as the dado is).
const dadoSun = (sun, k) => houseTint(tok("dadoSun"), sun, k);
const lineSun = (sun, k) => { const s = dadoSun(sun, k), d = tok("dadoShade"); return tok("dadoLine").multiply({ r: s.r / d.r, g: s.g / d.g, b: s.b / d.b }); };
export const HOUSE = { dado: 0.9, line: 0.035, doorBreak: 0.1, tile: 0.2, runner: 1.6, threshold: 0.6, wear: { sigma: 0.35, lift: 0.18 }, loang: 0.2 };
// Uniforms of stillSurface's house: gach the baked textures (or nulls in tests), runnerX the wall the runner
// starts from (runner 0: plain tiles only), doorWall the x of the wall whose doors break the dado, doors their
// centres (z) when the set has no corridor door light of its own, half the door's outer half-width; threshold:
// the plain tiles before each door, either side of its centre's joint (0: the runner runs on; critique r1).
export function houseUniforms({ gach = null, runnerX = 0, runner = HOUSE.runner, threshold = HOUSE.threshold, doorWall = 0, half, doors = null, sun, k = 0.5 }) {
  const U = {
    uGach: { value: gach?.field ?? null }, uGachB: { value: gach?.border ?? null },
    uGachP: { value: new Vector4(runnerX, threshold, HOUSE.tile, runner) },
    // The chalk lò cò (D6.8, staging only; sets/corridor.js): z of its first square's edge, the hop's direction
    // along z, x of the band's centre, on. Blue chalk, never pink near anyone's door.
    uTrace: { value: new Vector4(0, 0, 0, 0) }, uChalk: { value: vec(chalkRel()) },
    uHouse: { value: new Vector4(HOUSE.dado, HOUSE.line, half + HOUSE.doorBreak, doorWall) },
    uWearP: { value: new Vector4(0, HOUSE.wear.sigma, HOUSE.wear.lift, HOUSE.loang) },
    uDadoShade: { value: vec(tok("dadoShade")) }, uDadoSun: { value: vec(dadoSun(sun, k)) },
    uDadoLine: { value: vec(tok("dadoLine")) }, uDadoLineSun: { value: vec(lineSun(sun, k)) },
  };
  if (doors) {
    U.uDoors = { value: Array.from({ length: MAX_DOORS }, (_, i) => new Vector4(doors[i] ?? 0, 0, 0, 0)) };
    U.uDoorN = { value: doors.length };
  }
  return U;
}
// Blue chalk as albedo relative to the tiles' cream ground (the floor's targets are the cream's).
const chalkRel = () => { const c = mixc(tok("tile-indigo-lit"), tok("lime-50"), 0.45), g = tok("tile-cream"); return c.multiply({ r: 1 / g.r, g: 1 / g.g, b: 1 / g.b }); };
// The dado's lit targets when the sun moves (the corridor's clock).
export function setHouseSun(U, sun, k = 0.5) {
  const d = dadoSun(sun, k), l = lineSun(sun, k);
  U.uDadoSun.value.set(d.r, d.g, d.b);
  U.uDadoLineSun.value.set(l.r, l.g, l.b);
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
