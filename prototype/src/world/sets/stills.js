import { Mesh, Scene, Vector3 } from "three";
import { apertureCorners, aperturesGLSL, apertureUniforms } from "../glsl/apertures.js";
import { BLOCK, DEPTH } from "../glsl/blocks.js";
import { litRoom, stillSurface } from "../materials/still-surface.js";
import { glowMaterial, paintMaterial } from "../materials/paint.js";
import { createShafts } from "../materials/shafts.js";
import { latticePanel } from "../objects/lattice.js";
import { boxRoom, rect, shellGeometry } from "../objects/shell.js";
import { DOOR, doorLight, doorMeshes, doorSpec, leafAperture } from "../objects/door.js";
import { bakeLimeWash, bakeTerrazzo } from "../textures.js";
import { bakeGachBong } from "../gach.js";
import { STILL_SUNS, sunFixed } from "../clock.js";
import { mixc, tok, vec } from "../colors.js";
import { BOX as ROOM_BOX, SCREEN as ROOM_SCREEN } from "./room.js";
import { camera, houseShade, houseSun, houseUniforms, roomShade, roomSun, screenColors, street, surfaceUniforms, tint } from "./common.js";
import { corridorScene, DOOR_OPEN, EXIT, exitLook } from "./corridor.js";
import { WIN_Z, shuttersScene } from "./shutters.js";
import { CORRIDOR, DOOR_FOCUS_Y, DWELL_TILT, SHUTTERS, WALK, beatAt, corridorLayout, leafAt, lensFor, readBeats, s3Exposure, s3Open, s3Pose, s3Sun, slatAt, walkPose } from "../journey.js";
import { STILLS } from "../still-shots.js";

// The still sets (05-DESIGN D6.10, PLAN sections 4 and 5): framings of the same world for the inner-page
// headers, the footer's dusk, the door cards, the 404 and the story slot. Same surfaces, blocks, sun
// maths and colors as the live room; only the lights are general (glsl/apertures.js), because a door has a
// transom and a doorway and a window has its leaves. No people, no text, no traces in any still.
// Each scene returns its lights and objects; a shot is a scene plus one camera per crop.

const sunColor = (name) => tok(name);

// The home corridor's cards in their order (content/draft/work.json centers; test/beats.test.mjs keeps
// the two in step), as the live world reads them from the page.
export const HOME_BEATS = readBeats([
  { dataset: { unit: "green-bamboo", door: "open", emblem: "dotTre" } },
  { dataset: { unit: "little-rose", door: "closed", emblem: "hoaThi" } },
  { dataset: { unit: "cau-han", door: "open", emblem: "vomCau" } },
]);

// A screen of Hoa thị blocks in an x-plane wall, as the light sees it and as the eye does (backlit).
function screenAperture(sx, s, pattern = "hoaThi", params) {
  return { mode: "window", pattern, corner: [sx, s.y0, s.z0], u: [0, 0, 1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0], depth: DEPTH, params };
}

// ---- Scenes ---------------------------------------------------------------------------------------

// The room's screen seen frontally (Our Work at 10:00; the 404 with one block gone). The screen sits in
// the right of the wall; the left is plain lime wash for the page's H1.
function frontal({ lime, terr, missing = null }) {
  const box = { min: [-5.6, 0, -7], max: [2.6, 3.3, 6.2] };
  const s = { z0: -0.6, z1: -0.6 + 20 * BLOCK, y0: 0.9, y1: 3.3 };
  const sunSpec = STILL_SUNS.frontal, L = sunFixed(sunSpec), sun = sunColor(sunSpec.color);
  const pattern = missing ? "hoaThiMissing" : "hoaThi", params = missing ? [...missing, 0, 0] : undefined;
  const lights = [{ dir: L, apertures: [screenAperture(box.max[0], s, pattern, params)] }];
  const U = {
    ...surfaceUniforms({ box, fillDir: [1, 0, 0], sun, shade: roomShade(), sunT: roomSun(sun), spillT: roomShade(), refs: [L.y, 1], lime, terr }),
    ...apertureUniforms(lights, [L, new Vector3(0, 1, 0)]),
  };
  const shell = new Mesh(shellGeometry(boxRoom(box, { cut: { wall: "xMax", holes: [[s.z0, s.z1, s.y0, s.y1]] } })), stillSurface(U, lights));
  const panel = latticePanel(U, lights, {
    corner: [box.max[0], s.y0, s.z0], u: [0, 0, 1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0], pattern,
    own: { ...street(), uOne: { value: missing ?? [-1, -1] } },
  });
  screenColors(panel.material.uniforms);
  return { box, lights, U, objects: [shell, panel], subject: [lights[0].apertures[0]], glow: [lights[0].apertures[0]], shafts: 2 };
}

// Lime wash in a lit header (critique r1): never the morning rooms' cool shade grey. The plaster in shade
// takes the warm mortar of lime-300 (deep enough that a sun patch still reads on it), the ceiling the
// plaster's lime-100.
const warmShade = (k = 0.6) => [mixc(tok("wallShade"), tok("lime-300"), k), mixc(tok("floorShade"), tok("lime-300"), k * 0.6), mixc(tok("ceiling"), tok("lime-100"), k * 0.8)];

// Our Work's header (critique r1): the screen as a side wall, receding on the right with its edges out of
// frame at the top and right, the 10:30 sun raking through it across the back wall, so the quatrefoils
// land on the wall's right part at mid-height (above the page's fade), beside the H1, never under it.
function sideScreen({ lime, terr }) {
  const box = { min: [-3.0, 0, -6.4], max: [2.4, 3.3, 4] };
  const s = { z0: -6.1, z1: -6.1 + 30 * BLOCK, y0: 0.9, y1: 3.3 };
  const sunSpec = STILL_SUNS.ourWork, L = sunFixed(sunSpec), sun = sunColor(sunSpec.color);
  // A thin cast block (2.5 cm) for this raking sun: at 46° a 6 cm block closes every lobe to a sliver.
  const ap = { ...screenAperture(box.max[0], s), depth: 0.025 };
  const lights = [{ dir: L, apertures: [ap] }];
  const shade = warmShade();
  const U = {
    ...surfaceUniforms({ box, fillDir: [1, 0, 0], sun, shade, sunT: roomSun(sun), spillT: shade, refs: [Math.max(L.z, 0.05), 1], lime, terr }),
    ...apertureUniforms(lights, [L, new Vector3(0, 1, 0)]),
  };
  const shell = new Mesh(shellGeometry(boxRoom(box, { cut: { wall: "xMax", holes: [[s.z0, s.z1, s.y0, s.y1]] } })), stillSurface(U, lights));
  const panel = latticePanel(U, lights, { corner: [box.max[0], s.y0, s.z0], u: [0, 0, 1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0], pattern: "hoaThi", own: street() });
  screenColors(panel.material.uniforms);
  return { box, lights, U, objects: [shell, panel], subject: [lights[0].apertures[0]], glow: [lights[0].apertures[0]], shafts: false };
}

// The hero room (05-DESIGN D6.2), for the story still at rest and the dusk band.
function room({ lime, terr, dusk = false }) {
  const box = ROOM_BOX, s = ROOM_SCREEN;
  let L, sun, pattern = "hoaThi", params, depth = DEPTH;
  if (dusk) {
    // 18:00: the sun low through one block, its quatrefoil on the back wall (incidence 36°, crisp
    // enough at 3.9 m). The block is the one on the ray back from that spot.
    L = sunFixed({ az: 55, el: 8 });
    const spot = new Vector3(0.4, 1.7, box.min[2]);
    const t = (s.sx - spot.x) / L.x, q = spot.clone().addScaledVector(L, t);
    params = [Math.floor((q.z - s.z0) / BLOCK), Math.floor((q.y - s.y0) / BLOCK), 0, 0];
    pattern = "hoaThiOne";
    depth = 0.02; // the last ray: a full quatrefoil, not the sliver a 6 cm block leaves at 55°
    sun = mixc(tok("sun-400"), tok("sun-200"), 0.3);
  } else {
    L = sunFixed(STILL_SUNS.story);
    sun = sunColor(STILL_SUNS.story.color);
  }
  const ap = { ...screenAperture(s.sx, s, pattern, params), depth };
  const lights = [{ dir: L, apertures: [ap] }];
  const shade = dusk ? duskShade() : roomShade();
  const sunT = dusk ? [sun, sun.clone().multiplyScalar(0.8), sun] : roomSun(sun);
  const U = {
    ...surfaceUniforms({ box, fillDir: [1, 0, 0], sun, shade, sunT, spillT: shade, refs: [dusk ? L.z : Math.max(L.z, 0.05), 1], lime, terr }),
    ...apertureUniforms(lights, [L, new Vector3(0, 1, 0)]),
  };
  if (dusk) U.uGlare.value.copy(vec(tok("sun-200")));
  const shell = new Mesh(shellGeometry(boxRoom(box, { cut: { wall: "xMax", holes: [[s.z0, s.z1, s.y0, s.y1]] } })), stillSurface(U, lights));
  const behind = dusk ? street(mixc(tok("sun-400"), tok("on-dusk-2"), 0.55), mixc(tok("blue-700"), tok("on-dusk-2"), 0.42)) : street();
  const panel = latticePanel(U, lights, { corner: [s.sx, s.y0, s.z0], u: [0, 0, 1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0], pattern: "hoaThi", own: behind });
  if (dusk) {
    const u = panel.material.uniforms;
    u.uFace.value.copy(vec(tok("blue-950")));
    u.uWallC.value.copy(vec(mixc(tok("blue-950"), tok("blue-700"), 0.5)));
    u.uRimC.value.copy(vec(mixc(tok("blue-700"), tok("on-dusk-2"), 0.5)));
    u.uHalo.value = 0.25;
  } else screenColors(panel.material.uniforms);
  return { box, lights, U, objects: [shell, panel], subject: [ap], glow: [ap], shafts: !dusk };
}

// The footer's dusk (critique r1): the room's screen from inside at 18:00, in perspective, its top, foot and
// near end out of frame. No sun is left in the room: every hole holds the street's last gold, low (sun-400
// at about a third, into the dusk blue), dying toward the far end; the blocks are the night's silhouette.
function duskScreen({ lime, terr }) {
  const box = ROOM_BOX, s = ROOM_SCREEN;
  const L = sunFixed({ az: 55, el: 8 });
  const ap = screenAperture(s.sx, s);
  const lights = [{ dir: L, apertures: [ap] }];
  const shade = duskShade();
  const U = {
    ...surfaceUniforms({ box, fillDir: [1, 0, 0], sun: tok("sun-400"), shade, sunT: shade, spillT: shade, refs: [1, 1], lime, terr }),
    ...apertureUniforms(lights, [L, new Vector3(0, 1, 0)]),
  };
  U.uI.value = [0, 0];
  const shell = new Mesh(shellGeometry(boxRoom(box, { cut: { wall: "xMax", holes: [[s.z0, s.z1, s.y0, s.y1]] } })), stillSurface(U, lights));
  // sun-400 at about a third of its light low in the holes, the dusk blue above and toward the far end.
  const gold = tok("sun-400").multiplyScalar(0.34), high = mixc(tok("blue-900"), tok("blue-700"), 0.6);
  const panel = latticePanel(U, lights, {
    corner: [s.sx, s.y0, s.z0], u: [0, 0, 1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0], pattern: "hoaThi",
    // The near end is z1 (the camera stands by it); the gold falls off toward z0.
    own: { ...street(gold, high), uBehindY: { value: [1.4, 6] }, uCanopyAmt: { value: 0 }, uFall: { value: new Vector3(s.z1 - s.z0 - 0.6, 0.6, 0.85) } },
  });
  const u = panel.material.uniforms;
  u.uFace.value.copy(vec(tok("blue-950")));
  u.uWallC.value.copy(vec(mixc(tok("blue-950"), tok("blue-900"), 0.6)));
  u.uRimC.value.copy(vec(mixc(tok("blue-900"), tok("sun-400"), 0.18)));
  u.uHalo.value = 0.12;
  return { box, lights, U, objects: [shell, panel], subject: [], glow: [ap], shafts: false };
}

// The light gone blue: walls, floor and ceiling in the dusk of the logo blue.
// Deep enough that the band's lower edge sinks into the footer's blue-900.
const duskShade = () => [mixc(mixc(tok("blue-900"), tok("blue-700"), 0.16), tok("shade-300"), 0.07), mixc(tok("blue-950"), tok("blue-900"), 0.4), mixc(tok("blue-950"), tok("blue-900"), 0.3)];

// A unit's door in a wall facing a wide gallery (05-DESIGN D6.5): the room behind it lit (light 1, through
// the transom's emblem and the open doorway; untinted daylight), the corridor's own sun from the screen
// behind the camera (light 0) laying soft Hoa thị patches on the wall and the door.
const DOOR_Z = 1.6;
function door({ lime, terr, gach = null, unit, hinge = 1 }) {
  const box = { min: [-5.2, 0, -7], max: [0, 3.1, 6] };
  const d = doorSpec({ wx: 0, zc: DOOR_Z, unit, hinge });
  const lr = unit === "little-rose", light = doorLight(unit);
  const sunSpec = STILL_SUNS.door, L = sunFixed(sunSpec), sun = sunColor(sunSpec.color);
  const Li = sunFixed({ az: 0, el: 57 });
  // The corridor's screen behind the camera: only its stretch around the door throws light, so the
  // wall left of the door stays plain for the H1.
  const corridorScreen = { mode: "window", pattern: "hoaThi", corner: [box.min[0], 0.9, DOOR_Z - 1.4], u: [0, 0, 1], v: [0, 1, 0], size: [18 * BLOCK, box.max[1] - 0.9], depth: DEPTH, feather: 0.8 };
  // Little Rose's transom lets out the opening's plain light, softened, never the block's pattern.
  const plain = light.transomPattern === "open";
  const transom = { mode: "window", pattern: light.transomPattern, corner: [d.wx, d.transom.y0, d.transom.z0], u: [0, 0, 1], v: [0, 1, 0], size: [d.transom.z1 - d.transom.z0, d.transom.y1 - d.transom.y0], depth: plain ? 0 : DEPTH, ...(plain ? { feather: 0.12 } : {}) };
  const doorway = { mode: "window", pattern: "open", corner: [d.wx, 0, d.doorway.z0], u: [0, 0, 1], v: [0, 1, 0], size: [d.doorway.z1 - d.doorway.z0, d.doorway.y1], depth: 0.12 };
  const inside = lr ? [transom] : [transom, doorway, leafAperture(d)];
  // Trees in the street past the corridor's screen: the patches come and go as on the home page.
  const trees = { mode: "occluder", pattern: "canopy", corner: [box.min[0] - 3, -2, -10], u: [0, 0, 1], v: [0, 1, 0], size: [20, 9], depth: 0, params: [0.9, 0.56, 1.3, 4.1] };
  const lights = [{ dir: L, apertures: [corridorScreen, trees] }, { dir: Li, apertures: inside }];
  // Daylight from the room: untinted (sky toward the wall's sun); open doors may carry ≤ 15% of the unit
  // color in their spill (D6.5); Little Rose never.
  const unitColor = tok("unit-" + unit);
  const day = mixc(tok("sky"), tok("wallSun"), 0.45);
  const spill = mixc(day, unitColor, light.spillTint);
  const spillT = [spill, spill.clone().multiplyScalar(0.86), spill];
  // The corridor's house (D0.6.2): wash, dado broken at the door, warm ceiling, plain cream tiles, no fan.
  const U = {
    ...surfaceUniforms({ box, fillDir: [-1, 0, 0], sun, shade: houseShade(), sunT: houseSun(sun, 0.4), spillT, refs: [Math.abs(L.x), Li.y], lime, terr }),
    ...apertureUniforms(lights, [L, Li]),
    ...houseUniforms({ gach, runner: 0, doorWall: d.wx, half: DOOR.w / 2 + DOOR.frame, doors: [d.zc], sun, k: 0.4 }),
  };
  U.uI.value = [light.corridorSun ? 0.85 : 0, lr ? 0.7 : 1];
  const shell = new Mesh(shellGeometry(boxRoom(box, { cut: { wall: "xMax", holes: d.holes } })), stillSurface(U, lights, { house: true }));
  const paint = paintMaterial(U, lights, {
    uPaint: { value: vec(unitColor) }, uWood: { value: vec(tok("wood")) }, uCool: { value: vec(tok("wallShade")) },
    uSunTint: { value: vec(tint(tok("lime-50"), sun)) }, uSpillTint: { value: vec(mixc(tok("lime-50"), spill, 0.5)) },
    uShadeK: { value: 0.58 }, uWear: { value: lr ? 0.3 : 0.7 },
  }, { leafWear: true });
  const glow = glowMaterial({ low: lr ? day : mixc(tok("sun-200"), unitColor, 0.08), high: lr ? tok("sky") : mixc(tok("sun-100"), unitColor, 0.05), y0: 0, y1: 2.4 });
  const panelPaint = paint.clone();
  panelPaint.uniforms = { ...paint.uniforms, uWear: { value: 0.25 } };
  const meshes = doorMeshes(d, { paint, panel: panelPaint, glow });
  const panel = latticePanel(U, lights, {
    corner: [d.wx, d.transom.y0, d.transom.z0], u: [0, 0, 1], v: [0, 1, 0], size: [d.transom.z1 - d.transom.z0, d.transom.y1 - d.transom.y0], pattern: d.emblem,
    own: { uBehindLow: { value: vec(lr ? day : mixc(tok("sun-100"), unitColor, 0.06)) }, uBehindHigh: { value: vec(lr ? tok("sky") : tok("sun-100")) }, uBehindY: { value: [2, 3] } },
  });
  const u = panel.material.uniforms;
  // Backlit like the screen: a dark cement silhouette, the emblem glowing.
  u.uFace.value.copy(vec(mixc(tok("wallShade"), tok("screenFace"), 0.7)));
  u.uWallC.value.copy(vec(mixc(tok("wallShade"), tok("screenFace"), 0.35)));
  u.uRimC.value.copy(vec(day));
  u.uHalo.value = 0.18;
  // Framing keeps the whole door clear of the text; what holds still is the light in the openings.
  const glowing = lr ? [transom] : [transom, doorway];
  return { box, lights, U, objects: [shell, meshes, panel], subject: lr ? [transom, doorway] : inside, glow: glowing, shafts: false };
}

// ---- A center's door, as the hub and the center's own header build it (critique r2) ----

const f4 = (n) => n.toFixed(4);
// Soft contact where things stand against the plaster (stillSurface `contact`): each frame (2 cm proud) and
// transom on the door wall (x = wx), each open leaf's foot on the floor.
function doorContact(doors) {
  const half = DOOR.w / 2, F = DOOR.frame, top = DOOR.h + F;
  const frames = doors.map((d) => `a *= wallAo(P, vec2(${f4(d.zc)}, ${f4(top / 2)}), vec2(${f4(half + F)}, ${f4(top / 2)}), .26);
    a *= wallAo(P, vec2(${f4(d.zc)}, ${f4((d.transom.y0 + d.transom.y1) / 2)}), vec2(${f4((d.transom.z1 - d.transom.z0) / 2)}, ${f4((d.transom.y1 - d.transom.y0) / 2)}), .16);`);
  const feet = doors.filter((d) => d.open && d.swing !== -1).map((d) => { const l = leafAperture(d); return `a *= footAo(P, vec2(${f4(l.corner[0])}, ${f4(l.corner[2])}), vec2(${f4(l.corner[0] + l.u[0] * DOOR.w)}, ${f4(l.corner[2] + l.u[2] * DOOR.w)}));`; });
  const wx = f4((doors[0]?.wx ?? 0) - 0.012);
  return /* glsl */ `
float wallAo(vec3 P, vec2 c, vec2 h, float k){ if (P.x < ${wx}) return 1.; float d = sdBox2(P.zy - c, h); return d < 0. ? 1. : 1. - k * (1. - smoothstep(0., .1, d)); }
float footAo(vec3 P, vec2 a, vec2 b){ if (P.y > 0.01) return 1.; vec2 p = P.xz - a, e = b - a; float d = length(p - e * clamp(dot(p, e) / dot(e, e), 0., 1.)); return 1. - .38 * (1. - smoothstep(.0, .16, d)); }
float contact(vec3 P){ float a = 1.; ${[...frames, ...feet].join("\n    ")} return a; }`;
}
// The light out of a door (light 1): its transom in its emblem (Little Rose: plain, softened to a glow, never
// a lit bar), and where it stands open the doorway past its leaf.
function doorOpenings(d) {
  const light = doorLight(d.unit), plain = light.transomPattern === "open";
  const transom = { mode: "window", pattern: light.transomPattern, corner: [d.wx, d.transom.y0, d.transom.z0], u: [0, 0, 1], v: [0, 1, 0], size: [d.transom.z1 - d.transom.z0, d.transom.y1 - d.transom.y0], depth: plain ? 0 : DEPTH, ...(plain ? { feather: 0.3 } : {}) };
  const doorway = { mode: "window", pattern: "open", corner: [d.wx, 0, d.doorway.z0], u: [0, 0, 1], v: [0, 1, 0], size: [d.doorway.z1 - d.doorway.z0, d.doorway.y1], depth: 0.12 };
  return !d.open ? [transom] : d.swing === -1 ? [transom, doorway] : [transom, doorway, leafAperture(d)];
}
// Frame, leaf (4 cm, raised panels, soft contact at its hinge and foot), the lit room past an open doorway
// and the transom. Little Rose: no wear, a 6% daylight gradient down its shut leaf, darker toward its frame.
function doorKit(d, { U, lights, sun, day, terr, room = [2.4, 1.05] }) {
  const isLr = d.unit === "little-rose", unitColor = tok("unit-" + d.unit), half = DOOR.w / 2;
  const own = {
    uPaint: { value: vec(unitColor) }, uWood: { value: vec(tok("wood")) }, uCool: { value: vec(tok("wallShade")) },
    uSunTint: { value: vec(tint(tok("lime-50"), sun)) }, uSpillTint: { value: vec(mixc(tok("lime-50"), day, 0.5)) },
    uShadeK: { value: isLr ? 0.66 : 0.6 }, uWear: { value: isLr ? 0 : 0.3 }, uGrad: { value: isLr ? 0.06 : 0 },
  };
  // A shut leaf darkens toward its frame (it sits 5 cm back in it); an open one at its hinge and foot.
  const l = leafAperture(d);
  const ao = isLr
    ? `float paintAo(vec3 P){ float e = min(min(P.z - ${f4(d.zc - half)}, ${f4(d.zc + half)} - P.z), ${f4(DOOR.h)} - P.y); return 1. - .3 * (1. - smoothstep(0., .07, e)); }`
    : `float paintAo(vec3 P){ float h = length(P.xz - vec2(${f4(l.corner[0])}, ${f4(l.corner[2])})); return (1. - .24 * (1. - smoothstep(0., .3, h))) * (1. - .16 * (1. - smoothstep(0., .14, P.y))); }`;
  const paint = paintMaterial(U, lights, own, { ao, leafWear: true });
  const panelPaint = paintMaterial(U, lights, { ...own, uWear: { value: isLr ? 0 : 0.12 } }, { ao, leafWear: true });
  const frame = paintMaterial(U, lights, { ...own, uWear: { value: isLr ? 0 : 0.2 }, uGrad: { value: 0 } }, { leafWear: true });
  const out = [doorMeshes(d, { paint, frame, panel: panelPaint, glow: null })];
  // The room past an open doorway: room[0] deep, wider than the door so an oblique camera finds a side wall,
  // its light the transom's (sun-100 on the back wall, a few percent of the unit, D6.5 ≤ 15%).
  if (d.open) {
    const rbox = { min: [d.wx + DOOR.reveal - 0.02, 0, d.zc - room[1]], max: [d.wx + room[0], 2.75, d.zc + room[1]] };
    const warm = (c, k) => mixc(c, unitColor, k);
    const mat = litRoom({ box: rbox, back: vec(warm(tok("sun-100"), 0.04)), side: vec(warm(mixc(tok("sun-200"), tok("lime-300"), 0.5), 0.05)), floor: vec(mixc(tok("sun-200"), tok("lime-300"), 0.3)), ceiling: vec(mixc(tok("lime-100"), tok("lime-300"), 0.3)), terr });
    out.push(new Mesh(shellGeometry(boxRoom(rbox, { skip: ["xMin"] })), mat));
  }
  const panel = latticePanel(U, lights, {
    corner: [d.wx, d.transom.y0, d.transom.z0], u: [0, 0, 1], v: [0, 1, 0], size: [d.transom.z1 - d.transom.z0, d.transom.y1 - d.transom.y0], pattern: d.emblem,
    // Seen from metres below it, a 6 cm block shows only its hole walls: a thinner cast lets the emblem glow.
    depth: 0.03,
    // Little Rose's transom is the house's block with a quiet daylight behind it: never the wall's brightest.
    own: { uBehindLow: { value: vec(isLr ? mixc(day, tok("wallShade"), 0.5) : mixc(tok("sun-100"), unitColor, 0.05)) }, uBehindHigh: { value: vec(isLr ? mixc(day, tok("wallShade"), 0.42) : tok("sun-100")) }, uBehindY: { value: [2, 3] } },
  });
  const u = panel.material.uniforms;
  // Backlit cast cement against the warm wall: the house's blue-grey silhouette, the emblem glowing in it.
  u.uFace.value.copy(vec(mixc(mixc(tok("wallShade"), tok("lime-300"), 0.5), tok("screenFace"), 0.72)));
  u.uWallC.value.copy(vec(mixc(mixc(tok("wallShade"), tok("lime-300"), 0.5), tok("screenFace"), 0.4)));
  u.uRimC.value.copy(vec(day));
  u.uHalo.value = isLr ? 0.08 : 0.18;
  out.push(panel);
  return out;
}

// Our Work's door wall (critique r1, r2): the three centers' doors in one warm lime wall of a gallery, seen
// from one camera at a child's eye height (HUB), 0.6 m off the middle door so every jamb shows its depth
// and the open leaves their thickness; a lens shift (verticals stay vertical) puts the doors at 1/6, 1/2
// and 5/6 of the 21:9 frame, the terrazzo in its foot. The gallery's screen is behind the camera: its
// morning sun lays crisp Hoa thị patches on the wall between the doors (a still's sharpened sun, see
// glsl/apertures.js `penumbra`), never in Little Rose's bay. Tre Xanh and Cầu Hàn stand open onto lit rooms
// (plaster and terrazzo in their own daylight), their emblems in the transoms and the rooms' light out on
// the corridor floor; Little Rose shut, clean, in plain daylight from above, its transom's light plain.
// In DOM order, left to right.
export const HUB = { eye: 1.1, dist: 5.0, off: 0.6, fov: 38, top: 2.78, aspect: 21 / 9, ceiling: 3.7 };
const hubHalfH = () => HUB.dist * Math.tan((HUB.fov * Math.PI) / 360);
// One door per third of the frame's width at the wall.
export const HUB_SPACING = (2 * hubHalfH() * HUB.aspect) / 3;
export const hubDoorZ = (i, n = HOME_BEATS.length) => (i - (n - 1) / 2) * HUB_SPACING;
// Little Rose's bay: no gallery sun within this distance of its door's centre (feathered over the last 0.3 m).
export const HUB_NO_SUN = 1.2;
function hub({ lime, terr, gach = null }) {
  const box = { min: [-HUB.dist - 2.4, 0, -7.5], max: [0, HUB.ceiling, 7.5] };
  const units = HOME_BEATS.map((b) => b.unit);
  const doors = units.map((unit, i) => doorSpec({ wx: 0, zc: hubDoorZ(i, units.length), unit, hinge: i < units.length / 2 ? -1 : 1 }));
  const lr = doors.find((d) => d.unit === "little-rose");
  // Morning, nearly head-on to the wall (the corridor's colour at t 0.30). The screen stands 1.2 m behind the
  // camera; at this elevation its holes land on the wall between knee and transom height, the band feathered
  // top and bottom by the screen's own extent.
  const sunSpec = STILL_SUNS.hub, L = sunFixed(sunSpec), sun = sunColor(sunSpec.color);
  const sx = box.min[0] + 1.2;
  const screen = { mode: "window", pattern: "hoaThi", corner: [sx, 2.0, box.min[2]], u: [0, 0, 1], v: [0, 1, 0], size: [box.max[2] - box.min[2], 1.5], depth: DEPTH, feather: 0.3, penumbra: 0.2 };
  const noSun = lr ? [lr.zc - HUB_NO_SUN, lr.zc + HUB_NO_SUN] : null;
  // Little Rose's bay takes none of it, wherever the ray came from (the factor reads the lit point).
  const bay = noSun ? { glsl: `float hubBayFree(vec3 P){ return smoothstep(${f4(HUB_NO_SUN - 0.3)}, ${f4(HUB_NO_SUN)}, abs(P.z - ${f4(lr.zc)})); }`, factor: "hubBayFree(P)" } : {};
  const Li = sunFixed({ az: 0, el: 61 });
  const inside = doors.flatMap(doorOpenings);
  const lights = [{ dir: L, apertures: [screen], ...bay }, { dir: Li, apertures: inside }];
  const day = mixc(tok("sky"), tok("wallSun"), 0.45), spillT = [day, day.clone().multiplyScalar(0.9), day];
  // The corridor's house (D0.6.2): yellow wash over the mint dado, broken 10 cm short of every door frame
  // (Little Rose's too), the warm ceiling and plain cream tiles; no runner, no fans.
  const U = {
    ...surfaceUniforms({ box, fillDir: [-1, 0, 0], sun, shade: houseShade(), sunT: houseSun(sun, 0.4), spillT, refs: [Math.abs(L.x), Li.y], lime, terr }),
    ...apertureUniforms(lights, [L, Li]),
    ...houseUniforms({ gach, runner: 0, doorWall: 0, half: DOOR.w / 2 + DOOR.frame, doors: doors.map((d) => d.zc), sun, k: 0.4 }),
  };
  U.uI.value = [0.9, 0.95];
  const contact = doorContact(doors);
  const shell = new Mesh(shellGeometry(boxRoom(box, { cut: { wall: "xMax", holes: doors.flatMap((d) => d.holes) } })), stillSurface(U, lights, { contact, house: true }));
  const objects = [shell];
  for (const d of doors) objects.push(...doorKit(d, { U, lights, sun, day, terr }));
  return { box, lights, U, objects, subject: [], glow: [], shafts: false, noSun };
}
// Green Bamboo's header (critique r2): its green door in the far wall of the corridor near the right corner,
// standing open onto its lit room. The gallery's screen behind the camera lays crisp Hoa thị patches on the
// far wall between the H1's plain wall and the door, at mid-height; the room's light comes out through the
// transom and lands its Đốt tre on the side wall beside the door, high, above the page's fade, and the
// doorway's light slants across the floor at the leaf's foot. The same door, light and plaster as the hub.
// Door-wall coordinates as the hub: the far wall is x = 0, the side wall z = CENTER_SIDE.
const CENTER_SIDE = 2.0, CENTER_DOOR = 1.0;
function centerDoor({ lime, terr, unit }) {
  const box = { min: [-7.5, 0, -7], max: [0, 3.3, CENTER_SIDE] };
  // Its leaf swings into the lit room (as a Saigon door does), folded back toward the room's side wall: seen
  // against the light, edge and all, and out of the way of the light leaving the doorway.
  const d = doorSpec({ wx: 0, zc: CENTER_DOOR, unit, hinge: 1, inward: true, open: 118 });
  const sunSpec = STILL_SUNS.hub, L = sunFixed(sunSpec), sun = sunColor(sunSpec.color);
  // The screen's stretch whose sun lands on the far wall at z −1.0..0.35, y 1.15..2.25 (the ray rises
  // L.y / |L.x| and drifts L.z / |L.x| per metre back toward the screen).
  const sx = -6.6, k = -sx / Math.abs(L.x), dy = L.y * k, dz = L.z * k;
  const screen = { mode: "window", pattern: "hoaThi", corner: [sx, 1.0 + dy, -1.25 + dz], u: [0, 0, 1], v: [0, 1, 0], size: [1.8, 1.4], depth: DEPTH, feather: 0.28, penumbra: 0.2 };
  // The room's light, low and raking toward the side wall: the door's own shape lands there, its doorway
  // and above it the transom's Đốt tre, drawn out 1.5× along the wall (L.x / L.z), high, above the fade.
  const Li = new Vector3(0.7, 0.3, -0.45).normalize();
  // Through its slots at this rake a 6 cm block would close them to slivers: the emblem's light is cast
  // through a thinner one (the panel the eye sees keeps its depth).
  const [transom, doorway] = doorOpenings(d), cast = { ...transom, depth: 0.012 };
  const lights = [{ dir: L, apertures: [screen] }, { dir: Li, apertures: [cast, doorway] }];
  const day = mixc(tok("sky"), tok("wallSun"), 0.45), spill = mixc(day, tok("unit-" + unit), doorLight(unit).spillTint * 0.7);
  const spillT = [spill, spill.clone().multiplyScalar(0.9), spill];
  const shade = warmShade(0.7);
  shade[1] = mixc(tok("floorShade"), tok("lime-300"), 0.72);
  const U = {
    ...surfaceUniforms({ box, fillDir: [-1, 0, 0], sun, shade, sunT: roomSun(sun, 0.4), spillT, refs: [Math.abs(L.x), 0.62], lime, terr }),
    ...apertureUniforms(lights, [L, Li]),
  };
  U.uI.value = [0.92, 1];
  const shell = new Mesh(shellGeometry(boxRoom(box, { cut: { wall: "xMax", holes: d.holes } })), stillSurface(U, lights, { contact: doorContact([d]) }));
  return { box, lights, U, objects: [shell, ...doorKit(d, { U, lights, sun, day, terr, room: [2.6, 1.4] })], subject: [transom, doorway], glow: [transom, doorway], shafts: false };
}
// Where Green Bamboo's photos will hang (P2 gallery, critique r2): a stretch of the same corridor's far wall
// with no door in it. A breeze-block screen in the side wall, out of frame, takes a low sun raking along the
// corridor and lays its Hoa thị across the plaster as a slanted band of sheared patches, low on the left
// where the frames hang and rising to the right. Wall only, down to its base (the page fades the picture
// out on every side, and a floor line would draw across that fade). The page draws the six frames in
// light over it; nothing hangs in the render.
const GALLERY = { eye: 1.2, dist: 4.2, screen: { z: CENTER_SIDE, x: [-5.0, -1.2], y: [1.5, 2.9] } };
function galleryWall({ lime, terr, gach = null }) {
  const box = { min: [-7.5, 0, -7], max: [0, 3.3, CENTER_SIDE] };
  const sunSpec = STILL_SUNS.gallery, L = sunFixed(sunSpec), sun = sunColor(sunSpec.color);
  const { z, x: [x0, x1], y: [y0, y1] } = GALLERY.screen;
  const screen = { mode: "window", pattern: "hoaThi", corner: [x0, y0, z], u: [1, 0, 0], v: [0, 1, 0], size: [x1 - x0, y1 - y0], depth: DEPTH, feather: 0.3, penumbra: 0.25 };
  const lights = [{ dir: L, apertures: [screen] }];
  // The corridor's house (D0.6.2): wash with its loang over the mint dado, no door in this stretch.
  const shade = houseShade();
  const U = {
    ...surfaceUniforms({ box, fillDir: [-1, 0, 0], sun, shade, sunT: houseSun(sun, 0.4), spillT: shade, refs: [Math.abs(L.x), 1], lime, terr }),
    ...apertureUniforms(lights, [L, new Vector3(0, 1, 0)]),
    ...houseUniforms({ gach, runner: 0, doorWall: 0, half: DOOR.w / 2 + DOOR.frame, doors: [], sun, k: 0.4 }),
  };
  U.uI.value = [0.92, 0];
  const shell = new Mesh(shellGeometry(boxRoom(box)), stillSurface(U, lights, { house: true }));
  return { box, lights, U, objects: [shell], subject: [], glow: [], shafts: false };
}
// Square on to the gallery wall from GALLERY's spot, the lens framing wall window [z0, z1] × [y0, y1].
const galleryCam = (zc, { z0, z1, y0, y1 }) => {
  const { eye, dist } = GALLERY, pos = [-dist, eye, zc];
  const lens = { l: (z0 - zc) / dist, r: (z1 - zc) / dist, t: (y1 - eye) / dist, b: (y0 - eye) / dist };
  return { pos, look: [0, eye, zc], fov: (2 * Math.atan((lens.t - lens.b) / 2) * 180) / Math.PI, lens };
};

// A level camera (verticals stay vertical) looking along `yaw` (degrees from −z toward +x), its lens slid so
// world point P lands at frame fraction (fx, fy) of a crop of `aspect`.
const levelCam = (pos, yaw, fov, aspect, P, fx, fy) => {
  const y = (yaw * Math.PI) / 180;
  return { pos, look: [pos[0] + Math.sin(y) * 10, pos[1], pos[2] - Math.cos(y) * 10], fov, lens: lensFor(pos, yaw, P, fov, aspect, fx, fy) };
};

// The hub's cameras, all from the one spot (HUB): square on to the wall (verticals stay vertical), a lens
// shift framing a window [z0, z1] × [y0, y1] of the wall.
const hubCam = ({ z0, z1, y0, y1 }) => {
  const pos = [-HUB.dist, HUB.eye, HUB.off];
  return { pos, look: [0, HUB.eye, HUB.off], fov: HUB.fov, lens: { l: (z0 - pos[2]) / HUB.dist, r: (z1 - pos[2]) / HUB.dist, t: (y1 - HUB.eye) / HUB.dist, b: (y0 - HUB.eye) / HUB.dist } };
};
const hubWide = () => { const h = 2 * hubHalfH(), w = h * HUB.aspect; return hubCam({ z0: -w / 2, z1: w / 2, y0: HUB.top - h, y1: HUB.top }); };
// A phone's door (4:5): the door 53% of the crop's height (60% with its transom), the wall and its light
// either side, the terrazzo in the bottom fifth.
const hubDoor = (zc) => { const h = 4.3, w = h * 0.8, y0 = -0.2 * h; return hubCam({ z0: zc - w / 2, z1: zc + w / 2, y0, y1: y0 + h }); };

// Lam (louver) stripes of late-morning light on a wall (Thrive Together, critique r2): a louvered window of
// six slats in the right wall, out of frame, lays six crisp gold bars across the warm back wall, rising to the
// right (a still's sharpened sun: the bars keep their edges, ≤ 3 px at 1600 w). Their gold is the render's own:
// a late-morning sun-200 toward sun-400 on lime wash, no filter on the page.
export const LAM = { slats: 6, pitch: 0.25, open: 0.46 };
function louvers({ lime, terr }) {
  const box = { min: [-8, 0, 0], max: [3, 3.2, 7] };
  const sunSpec = STILL_SUNS.louvers, L = sunFixed(sunSpec), sun = sunColor(sunSpec.color);
  const win = { mode: "window", pattern: "slats", corner: [box.max[0], 0.95, 0.7], u: [0, 0, 1], v: [0, 1, 0], size: [2.4, LAM.slats * LAM.pitch], depth: 0.03, params: [LAM.pitch, LAM.open, 0, 0], penumbra: 0.15 };
  const lights = [{ dir: L, apertures: [win] }];
  const shade = warmShade(0.75);
  const gold = mixc(tok("sun-200"), tok("sun-400"), 0.3);
  const sunT = [gold, mixc(gold, tok("lime-300"), 0.25), gold];
  const U = {
    ...surfaceUniforms({ box, fillDir: [1, 0, 0], sun, shade, sunT, spillT: shade, refs: [L.z, 1], lime, terr }),
    ...apertureUniforms(lights, [L, new Vector3(0, 1, 0)]),
  };
  const shell = new Mesh(shellGeometry(boxRoom(box, { cut: { wall: "xMax", holes: [[0.7, 3.1, 0.95, 0.95 + LAM.slats * LAM.pitch]] } })), stillSurface(U, lights));
  return { box, lights, U, objects: [shell], subject: [win], glow: [], shafts: false };
}

// The open shutters at 17:00 (Get Involved; S3's end state): the French window of sets/shutters.js, its two
// blue louvered leaves folded back into the room, the late sun laying a long gold patch across the floor.
const shutters = (o) => shuttersScene({ ...o, leaf: 104, slat: 65, plaster: true });

// The street side of the screen at 16:30 (Contact): the cast-cement face in late sun under the tamarind
// canopy's dapple, the room behind the holes in cool shade.
function streetSide({ lime, terr }) {
  const box = { min: [0, 0, -14], max: [14, 9, 14] };
  const s = { z0: -3.9, z1: -3.9 + 16 * BLOCK, y0: 0.9, y1: 3.3 };
  const sunSpec = STILL_SUNS.street, L = sunFixed(sunSpec), sun = sunColor(sunSpec.color);
  // The tamarind over the pavement: the face sits in its shade, the late sun through it in flecks.
  // A tamarind on the pavement, between the sun and the screen: its leaves throw crisp shadows over the
  // blocks (a plane parallel to the face, so they do not stretch); the wall where the H1 sits stays clear.
  // Its shadows stop short of the screen's far end (the plaster there carries the H1) and keep their leaves.
  const canopy = { mode: "occluder", pattern: "canopy", corner: [2.6, -1, -3.9], u: [0, 0, -1], v: [0, 1, 0], size: [14, 10], depth: 0, params: [2.6, 0.5, 3.1, 7.7], feather: 1.2, penumbra: 0.35 };
  const lights = [{ dir: L, apertures: [canopy] }];
  const sand = tok("faceSand");
  // Outdoors the shade takes the street's warm bounce: lime toward the cool shade.
  const shade = [mixc(tok("wallShade"), tok("lime-300"), 0.42), mixc(tok("floorShade"), tok("lime-300"), 0.5), tok("ceiling")];
  const sunT = [tint(mixc(tok("lime-50"), tok("wallSun"), 0.25), sun, 0.5), tint(mixc(tok("lime-50"), sand, 0.5), sun, 0.5).multiplyScalar(0.9), tint(tok("wallSun"), sun)];
  const U = {
    ...surfaceUniforms({ box, fillDir: [1, 0, 0], sun, shade, sunT, spillT: shade, refs: [L.x, 1], lime, terr }),
    ...apertureUniforms(lights, [L, new Vector3(0, 1, 0)]),
  };
  U.uAoK.value = 0.6;
  // A sunlit wall carries no pattern behind the text, so the text zone takes no cap (it would show as a
  // dim rectangle on an evenly lit wall).
  U.noCap = true;
  // Tall enough that its top never enters an oblique frame (no roofline across the H1).
  const facade = rect("x", 0, [-14, 14], [0, 9], 1, 0, [[s.z0, s.z1, s.y0, s.y1]]);
  const ground = rect("y", 0, [0, 14], [-14, 14], 1, 1);
  const shell = new Mesh(shellGeometry([...facade, ...ground]), stillSurface(U, lights));
  const panel = latticePanel(U, lights, {
    corner: [0, s.y0, s.z1], u: [0, 0, -1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0], pattern: "hoaThi", mode: "frontlit",
    own: { uBehindLow: { value: vec(mixc(tok("screenFace"), tok("blue-900"), 0.3)) }, uBehindHigh: { value: vec(mixc(tok("screenFace"), tok("wallShade"), 0.2)) }, uBehindY: { value: [0.5, 3.3] } },
  });
  const u = panel.material.uniforms;
  u.uFace.value.copy(vec(mixc(sand, tok("wallShade"), 0.45).multiplyScalar(0.74)));
  u.uFaceSun.value.copy(vec(tint(mixc(sand, tok("lime-50"), 0.35), sun, 0.5)));
  u.uWallC.value.copy(vec(mixc(sand, tok("screenFace"), 0.45)));
  u.uRimC.value.copy(vec(tint(mixc(sand, tok("wallSun"), 0.3), sun)));
  const aperture = { mode: "window", pattern: "hoaThi", corner: [0, s.y0, s.z1], u: [0, 0, -1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0] };
  return { box, lights, U, objects: [shell, panel], subject: [aperture], glow: [aperture], shafts: false };
}

// ---- Shots ----------------------------------------------------------------------------------------

// Cameras per crop (CSS aspect from still-shots.js). Header desk crops keep the subject right of the
// text zone (still-shots.js HEADER_TEXT); the render step checks it and shifts the lens if not.
export const SHOTS = {
  "header-our-work": { scene: (o) => sideScreen(o), crops: {
    desk: { pos: [-1.5, 1.1, 1.6], look: [-1.2, 1.15, -6.4], fov: 32 },
    mobile: { pos: [-0.6, 1.1, 0.2], look: [1.4, 1.25, -6.4], fov: 50 },
  } },
  // The far wall (critique r2, centerDoor): the H1 on its plain plaster, the patches, the door near the corner.
  "header-green-bamboo": { scene: (o) => centerDoor({ ...o, unit: "green-bamboo" }), crops: {
    desk: levelCam([-5.25, 1.1, -3.5], 112, 32, 3, [0, 1.3, CENTER_DOOR], 0.7, 0.52),
    mobile: levelCam([-3.5, 1.1, 0.5], 110, 52, 4 / 3, [0, 1.3, CENTER_DOOR], 0.36, 0.5),
  } },
  "header-thrive-together": { scene: (o) => louvers(o), crops: {
    desk: { pos: [-0.4, 1.1, 3.9], look: [-0.4, 1.2, 0], fov: 38 },
    mobile: { pos: [0.6, 1.1, 2.6], look: [1.0, 0.9, 0], fov: 52 },
  } },
  // From the side (critique r1): the open window on the right, the 17:00 sun through its leaves laid as a
  // louvered patch on the side wall at mid-height, where the page's fade does not reach.
  "header-get-involved": { scene: (o) => shutters(o), crops: {
    desk: { pos: [-3.4, 1.1, -1.6], look: [0, 1.15, 1.6], fov: 44 },
    mobile: { pos: [-3.0, 1.1, -0.8], look: [0, 1.1, 2.2], fov: 56 },
  } },
  // Turned 28° off the face (critique r2): the screen recedes toward the H1, its top, foot and near end out of
  // frame, the plain plaster past its far end for the text.
  "header-contact": { scene: (o) => streetSide(o), crops: {
    desk: levelCam([3.5, 1.1, -0.25], -118, 34, 3, [0, 2.1, 0.9], 0.594, 0.5),
    mobile: levelCam([3.2, 1.1, -0.6], -118, 50, 4 / 3, [0, 2.15, 0.9], 0.2, 0.5),
  } },
  dusk: { scene: (o) => duskScreen(o), crops: {
    // Turned 8° toward the near end (critique r2): the screen's far end and the wall past it leave the frame,
    // so the band shows only blocks and their last light at 1440 and 1920 (object-fit: cover at 4-5.5:1).
    desk: { pos: [0.0, 1.1, 0.4], look: [2.770, 2.0, -0.624], fov: 26 },
    mobile: { pos: [0.4, 1.1, 0.2], look: [2.6, 2.0, -0.8], fov: 50 },
  } },
  // Our Work's wall of doors: the three at 1/6, 1/2 and 5/6 of a 21:9 frame (6 door spacings wide), and
  // each door alone from the same wall for phones.
  "hub-doors-wide": { scene: (o) => hub(o), crops: { wide: hubWide() } },
  "door-green-bamboo": { scene: (o) => door({ ...o, unit: "green-bamboo" }), crops: { card: { pos: [-3.6, 1.1, 0.3], look: [0, 1.0, DOOR_Z], fov: 52 } } },
  "door-cau-han": { scene: (o) => door({ ...o, unit: "cau-han" }), crops: { card: { pos: [-3.6, 1.1, 0.3], look: [0, 1.0, DOOR_Z], fov: 52 } } },
  "door-little-rose": { scene: (o) => door({ ...o, unit: "little-rose" }), crops: { card: { pos: [-3.6, 1.1, 0.3], look: [0, 1.0, DOOR_Z], fov: 52 } } },
  "not-found": { scene: (o) => frontal({ ...o, missing: [7, 2] }), crops: { square: { pos: [-1.9, 1.1, 0.1], look: [2.6, 1.35, 1.55], fov: 56 } } },
  story: { scene: (o) => room(o), crops: { story: { pos: [-2.4, 1.1, 1.2], look: [0.9, 1.35, -4], fov: 40 } } },
  // 21:9 from 600 px (the frames hang over its left half, the light runs on to the right), 16:10 on phones.
  // The house's wash above its dado (D0.6.2): the frames hang on the yellow, as photos hang above a dado, and
  // the dado's painted line stays just under the crop, so no line runs through the page's bottom fade (D0.5).
  "gallery-wall": { scene: (o) => galleryWall(o), crops: {
    desk: galleryCam(-2.6, { z0: -5.6, z1: 0.4, y0: 0.95, y1: 3.25 }),
    mobile: galleryCam(-2.4, { z0: -4.1, z1: -0.7, y0: 0.95, y1: 3.075 }),
  } },
  // The passage's end (K4) for the pages without the live flight: the home corridor's own three doors, the
  // open ones open, Little Rose shut.
  "passage-corridor": { scene: (o) => corridorScene({ ...o, beats: HOME_BEATS, open: DOOR_OPEN }), crops: {
    desk: { pos: EXIT.to.toArray(), look: exitLook(EXIT.to).toArray(), fov: EXIT.fov.landscape },
    mobile: { pos: EXIT.to.toArray(), look: exitLook(EXIT.to, 14).toArray(), fov: 62 },
  } },
};

// The home corridor's walk, one still per door (still-shots.js bay-*): the live camera's reading pose for
// that door (journey.js walkPose), the door's centre put where its card leaves the frame free (desk: the
// left of the card's third, as the label beside it; mobile: the upper middle, the card below), the doors before it
// open, the ones after still shut, the sun where the walk has it. Little Rose's stays shut (setDoor).
const BAY_CROPS = { desk: { layout: "side", aspect: 16 / 10, fx: 0.58, fy: 0.52 }, mobile: { layout: "below", aspect: 3 / 4, fx: 0.6, fy: 0.42 } };
function bayShot(i) {
  const lay = corridorLayout(HOME_BEATS), n = HOME_BEATS.length, { w } = beatAt(0, n);
  const t = WALK.sun[0] + (WALK.sun[1] - WALK.sun[0]) * (WALK.beats[0] + (i + 0.5) * w);
  const crops = {};
  for (const [name, c] of Object.entries(BAY_CROPS)) {
    const p = walkPose(i + 1, c.layout, lay), y = (p.yaw * Math.PI) / 180;
    const lens = lensFor(p.pos, p.yaw, [CORRIDOR.wallX, DOOR_FOCUS_Y, lay.doors[i].zc], p.fov, c.aspect, c.fx, c.fy);
    // The live dwell's look down beside the card (journey.js dwellTilt, at its height mid-beat).
    if (c.layout === "side") { const k = Math.tan((DWELL_TILT * Math.PI) / 180); lens.t -= k; lens.b -= k; }
    crops[name] = { pos: p.pos, look: [p.pos[0] + Math.sin(y) * 10, 1.1, p.pos[2] - Math.cos(y) * 10], fov: p.fov, lens };
  }
  return {
    scene: (o) => {
      const set = corridorScene({ ...o, beats: HOME_BEATS, t });
      set.specs.forEach((_, j) => set.setDoor(j, j <= i ? DOOR_OPEN : 0));
      return set;
    },
    crops,
  };
}
for (const [name, s] of Object.entries(STILLS)) if (name.startsWith("bay-")) SHOTS[name] = bayShot(s.beat);
// Each of the hub's doors alone, 4:5, from the same spot, wall and light (phones).
for (const [name, s] of Object.entries(STILLS)) if (name.startsWith("hub-door-")) {
  SHOTS[name] = { scene: (o) => hub(o), crops: { door: hubDoor(hubDoorZ(HOME_BEATS.findIndex((b) => b.unit === s.unit))) } };
}

// S3 (sets/shutters.js) at its beats, each from the live camera's pose for its crop's frame shape: shut
// (p 0), the slats turned (0.35), the leaves open (0.75), at the exposure the live set has there.
const S3_STILLS = { "shutters-closed": 0, "shutters-louvers": SHUTTERS.slats[1], "shutters-open": SHUTTERS.leaves[1] };
for (const [name, p] of Object.entries(S3_STILLS)) {
  const crop = (bucket) => { const q = s3Pose(1, p, bucket, WIN_Z); return { pos: q.pos, look: q.look, fov: q.fov }; };
  SHOTS[name] = {
    scene: (o) => {
      const set = shuttersScene({ ...o, leaf: leafAt(p), slat: slatAt(p), t: s3Sun(p), full: true });
      set.setExposure(s3Exposure(p, { peak: 0, held: 0 }), s3Open(p), Math.max(slatAt(p) / SHUTTERS.slatDeg, leafAt(p) / SHUTTERS.leafDeg));
      return set;
    },
    crops: { desk: crop("landscape"), mobile: crop("portrait") },
  };
}

export async function buildStill(name, crop, { gl, aspect, cam: tryCam = null, sun: trySun = null }) {
  const shot = SHOTS[name];
  // Every corridor-scene shot and every door wall of the house gets the runner's textures (D0.6.2).
  const [lime, terr, gach] = await Promise.all([bakeLimeWash({ slice: false }), bakeTerrazzo({ slice: false }), bakeGachBong({ slice: false, size: 512, aniso: 8 })]);
  const set = shot.scene({ lime, terr, gach });
  if (trySun) sunFixed({ az: trySun[0], el: trySun[1], mirror: set.lights[0].dir.x < 0 && trySun[2] !== 0 }, set.lights[0].dir);
  const scene = new Scene();
  scene.add(...set.objects);
  const cam = camera(tryCam ? { pos: tryCam.slice(0, 3), look: tryCam.slice(3, 6), fov: tryCam[6] } : shot.crops[crop], aspect);
  const passes = [];
  if (set.shafts) {
    const glsl = aperturesGLSL(set.lights.slice(0, 1));
    const shafts = createShafts(gl.renderer, set.U, { box: set.box, tier: 2, ortho: gl.ortho, steps: 64, mask: { glsl, call: "lightMask0(p)" } });
    shafts.uniforms.uDensity.value = set.shafts === true ? 1.6 : set.shafts;
    passes.push(shafts);
  }
  // base: the shell in shade (no sun, no light from the rooms); lit: every light at rest. What glows in its
  // own right (the street in the holes, a doorway, the sky past a window) is in both.
  const intensities = [...set.U.uI.value];
  const setLayer = (lit) => {
    set.U.uI.value = lit ? intensities : [0, 0];
    set.U.uSunI.value = lit ? 1 : 0;
    for (const p of passes) p.enabled = lit;
  };
  // In world space: the subject for the text-zone check, the glowing openings for the light-layer split.
  // A lens of its own (the bays): the frustum the live walk uses, so verticals stay vertical.
  const lens = tryCam ? null : shot.crops[crop].lens ?? null;
  return { scene, camera: cam, lens, U: set.U, passes, setLayer, capText: !set.U.noCap, subject: set.subject.map(apertureCorners), glow: set.glow.map(apertureCorners) };
}
