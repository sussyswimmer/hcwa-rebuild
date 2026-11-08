import { Group, Mesh, PerspectiveCamera, PlaneGeometry, Scene, Vector3 } from "three";
import { createShafts } from "../materials/shafts.js";
import { aperturesGLSL, apertureUniforms } from "../glsl/apertures.js";
import { DEPTH } from "../glsl/blocks.js";
import { DOOR_PATTERN, DOORS, MAX_DOORS, NO_SUN, doorUniforms } from "../glsl/doors.js";
import { stillSurface } from "../materials/still-surface.js";
import { glowMaterial, paintMaterial } from "../materials/paint.js";
import { latticePanel } from "../objects/lattice.js";
import { rect, shellGeometry } from "../objects/shell.js";
import { DOOR, doorGeometries, doorLight, doorSpec } from "../objects/door.js";
import { makeSun, sunAt, sunFixed } from "../clock.js";
import { mixc, tok, vec } from "../colors.js";
import { CORRIDOR, corridorLayout } from "../journey.js";
import { roomShade, roomSun, screenColors, street, surfaceUniforms, tint } from "./common.js";
import { EXIT, exitLook } from "./places.js";

// The corridor (05-DESIGN D6.2, D6.5): open-air, the breeze-block screen on the left with the street and
// the morning sun beyond it, the lime wall on the right with one painted door per [data-beat] card, each
// in its own 6.5 m bay with a pier at its far end. Two lights: the sun through the screen (and the
// tamarind past it), and daylight from the rooms behind the doors, out through each transom in its
// unit's emblem and, where a door stands open, through the doorway. Little Rose: closed, the house's own
// Hoa thị transom, plain untinted light, and no corridor sun anywhere in its bay.
// Used by the live world (live.js) and by the still renderer (sets/stills.js), so both see one corridor.

export const DOOR_OPEN = 72; // degrees a door swings to at its beat (D5.3); live.js drives it

export function corridorScene({ lime, terr, beats, open = 0, t = 0.25 }) {
  if (beats.length > MAX_DOORS) throw new Error(`corridor: ${beats.length} doors > ${MAX_DOORS}`);
  const lay = corridorLayout(beats), C = CORRIDOR, s = lay.screen;
  const box = { min: [C.screenX, 0, lay.end], max: [C.wallX, C.ceiling, lay.start] };
  const sun = makeSun();
  sunAt("corridor", t, sun);
  const Li = sunFixed({ az: 0, el: 57 });

  const screen = { mode: "window", pattern: "hoaThi", corner: [s.sx, s.y0, s.z0], u: [0, 0, 1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0], depth: DEPTH };
  // Tamarinds along the street: the patches come and go as in the room.
  const trees = { mode: "occluder", pattern: "canopy", corner: [s.sx - 3.2, -2, lay.end - 6], u: [0, 0, 1], v: [0, 1, 0], size: [lay.start - lay.end + 12, 9], depth: 0, params: [0.9, 0.56, 1.3, 4.1] };
  const lights = [
    { dir: sun.dir, apertures: [screen, trees], glsl: NO_SUN, factor: "bayFree(P)" },
    { dir: Li, glsl: DOORS, mask: "doorsMask(P, L)" },
  ];

  // Daylight from the rooms: untinted, the same for every door (Little Rose's never carries a color).
  const day = mixc(tok("sky"), tok("wallSun"), 0.45);
  const spillT = [day, day.clone().multiplyScalar(0.86), day];
  const U = {
    ...surfaceUniforms({ box, fillDir: [-1, 0, 0], sun: sun.color, shade: roomShade(), sunT: roomSun(sun.color, 0.5), spillT, refs: [Math.abs(sun.dir.x), Li.y], lime, terr }),
    ...apertureUniforms(lights, [sun.dir, Li]),
    ...doorUniforms(),
  };
  U.uI.value = [1, 0.9];

  // ---- doors from the beats ----
  const specs = lay.doors.map((d) => ({ ...doorSpec({ wx: C.wallX, zc: d.zc, unit: d.unit, open: 0, hinge: -1 }), beat: d }));
  U.uDoorN.value = specs.length;
  U.uDoorWall.value.set(C.wallX, specs[0]?.transom.y0 ?? 2.3, 0, 0);
  let lr = 0;
  specs.forEach((d, i) => {
    const light = doorLight(d.unit);
    U.uDoors.value[i].set(d.zc, DOOR_PATTERN[light.transomPattern] ?? DOOR_PATTERN.hoaThi, 0, d.hinge);
    if (!light.corridorSun && lr < 2) U.uNoSun.value[lr++].set(d.beat.bay[0], d.beat.bay[1], 0, 0);
  });

  // ---- shell: floor, ceiling, the door wall cut by doorways and transoms, the screen wall around the
  // screen (parapet, beam, solid ends), both ends, and a shallow pier closing each bay ----
  const ends = [lay.end, lay.start], X = [C.screenX, C.wallX], H = C.ceiling, P = C.pier, half = 0.12;
  const rects = [
    ...rect("y", 0, X, ends, 1, 1),
    ...rect("y", H, X, ends, -1, 2),
    ...rect("x", C.wallX, ends, [0, H], -1, 0, specs.flatMap((d) => d.holes)),
    ...rect("x", C.screenX, ends, [0, H], 1, 0, [[s.z0, s.z1, s.y0, s.y1]]),
    ...rect("z", lay.start, X, [0, H], -1, 0),
    ...rect("z", lay.end, X, [0, H], 1, 0),
    ...lay.piers.flatMap((z) => [
      ...rect("z", z + half, [C.wallX - P, C.wallX], [0, H], 1, 0),
      ...rect("z", z - half, [C.wallX - P, C.wallX], [0, H], -1, 0),
      ...rect("x", C.wallX - P, [z - half, z + half], [0, H], -1, 0),
    ]),
  ];
  const shell = new Mesh(shellGeometry(rects), stillSurface(U, lights));

  // Faces the corridor (+x): laid from the screen's +z end along −z, on the light's own block grid.
  const lattice = latticePanel(U, lights, { corner: [s.sx, s.y0, s.z1], u: [0, 0, -1], v: [0, 1, 0], size: screen.size, pattern: "hoaThi", light: 0, own: street() });
  screenColors(lattice.material.uniforms);

  const objects = [shell, lattice];
  const leaves = [], glows = [];
  for (const d of specs) {
    const unitColor = tok("unit-" + d.unit), isLR = d.unit === "little-rose";
    const paint = paintMaterial(U, lights, {
      uPaint: { value: vec(unitColor) }, uWood: { value: vec(tok("wood")) }, uCool: { value: vec(tok("wallShade")) },
      uSunTint: { value: vec(tint(tok("lime-50"), sun.color)) }, uSpillTint: { value: vec(mixc(tok("lime-50"), day, 0.5)) },
      uShadeK: { value: 0.58 }, uWear: { value: isLR ? 0.3 : 0.6 },
    });
    const g = doorGeometries(d);
    const leaf = new Group();
    leaf.position.set(...g.hinge);
    leaf.add(new Mesh(g.leaf, paint));
    objects.push(new Mesh(g.frame, paint), leaf);
    leaves.push(leaf);
    // What shows through an open doorway: the lit room, nothing in it modeled. Little Rose has none.
    if (!isLR) {
      const glow = new Mesh(new PlaneGeometry(DOOR.w + 0.4, DOOR.h + 0.3), glowMaterial({ low: mixc(tok("sun-200"), unitColor, 0.08), high: mixc(tok("sun-100"), unitColor, 0.05), y0: 0, y1: 2.4 }));
      glow.rotation.y = -Math.PI / 2;
      glow.position.set(C.wallX + 0.9, DOOR.h / 2, d.zc);
      glow.visible = false;
      objects.push(glow);
      glows.push(glow);
    } else glows.push(null);
    // The transom: three blocks of its emblem, backlit by the room (the light's own pattern: doors.js).
    const tr = latticePanel(U, lights, {
      corner: [C.wallX, d.transom.y0, d.transom.z0], u: [0, 0, 1], v: [0, 1, 0], size: [d.transom.z1 - d.transom.z0, d.transom.y1 - d.transom.y0], pattern: d.emblem, light: 1,
      own: { uBehindLow: { value: vec(isLR ? day : mixc(tok("sun-100"), unitColor, 0.06)) }, uBehindHigh: { value: vec(isLR ? tok("sky") : tok("sun-100")) }, uBehindY: { value: [2, 3] } },
    });
    const u = tr.material.uniforms;
    u.uFace.value.copy(vec(mixc(tok("wallShade"), tok("screenFace"), 0.7)));
    u.uWallC.value.copy(vec(mixc(tok("wallShade"), tok("screenFace"), 0.35)));
    u.uRimC.value.copy(vec(day));
    u.uHalo.value = 0.18;
    objects.push(tr);
  }

  // A door at its angle: the leaf, the light past it, the lit room behind it. Little Rose stays shut.
  const setDoor = (i, deg) => {
    const d = specs[i];
    if (!d) return;
    const a = d.unit === "little-rose" ? 0 : (Math.min(Math.max(deg, 0), DOOR_OPEN) * Math.PI) / 180;
    leaves[i].rotation.y = d.hinge * a;
    U.uDoors.value[i].z = a;
    if (glows[i]) glows[i].visible = a > 0.01;
  };
  specs.forEach((_, i) => setDoor(i, open));

  // The sun along the corridor's clock (D6.4: t 0.25 → 0.45, nearly head-on to the door wall).
  const setSun = (tt, intensity = 1) => {
    sunAt("corridor", tt, sun);
    const k = roomSun(sun.color, 0.5);
    U.uSunT.value.forEach((v, i) => v.set(k[i].r, k[i].g, k[i].b));
    U.uSunCol.value.set(sun.color.r, sun.color.g, sun.color.b);
    U.uRef.value[0] = Math.max(Math.abs(sun.dir.x), 0.05);
    U.uI.value[0] = intensity;
    U.uSunI.value = intensity;
  };

  return {
    box, lights, U, objects, sun, layout: lay, specs, setDoor, setSun,
    // For the still renderer: what frames the shot, what glows on its own.
    subject: [screen], glow: [screen], shafts: 1.4,
  };
}

export { EXIT, exitLook };

// The live corridor (live.js): built from the page's beats once the room is live, sharing the room's
// baked textures. Shafts through the screen on both tiers (they gate like the room's).
export async function buildCorridor({ gl, tier, beats, textures }) {
  const set = corridorScene({ ...textures, beats });
  const scene = new Scene();
  scene.add(...set.objects);
  const shafts = createShafts(gl.renderer, set.U, { box: set.box, tier, ortho: gl.ortho, mask: { glsl: aperturesGLSL(set.lights.slice(0, 1)), call: "lightMask0(p)" } });
  shafts.uniforms.uDensity.value = 1.4;
  const camera = new PerspectiveCamera(EXIT.fov.landscape, 1, 0.05, 60);
  // The tamarinds' dapple (the screen's light is aperture 0, the trees 1).
  const treeP = set.U.uApP.value[1], treeAt = treeP.clone();
  return {
    ...set, scene, camera, shafts,
    setTier: (t) => shafts.setTier(t),
    // The leaves sway while the visitor reads (live.js, Tier 2 only): two slow sines, never a drift.
    sway(time) { treeP.z = treeAt.z + 0.2 * Math.sin(time * 0.31 + 0.6) + 0.06 * Math.sin(time * 1.07); treeP.w = treeAt.w + 0.1 * Math.sin(time * 0.23 + 2.1); },
    compile: async () => {
      await gl.renderer.compileAsync(scene, camera);
      for (const s of shafts.scenes) await gl.renderer.compileAsync(s, gl.ortho);
    },
    dispose() {
      scene.traverse((o) => { if (o.isMesh) { o.geometry.dispose(); o.material.dispose(); } });
      shafts.dispose();
    },
  };
}
