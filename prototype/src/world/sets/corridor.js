import { Float32BufferAttribute, Group, Mesh, PerspectiveCamera, PlaneGeometry, Scene, Vector4 } from "three";
import { createShafts } from "../materials/shafts.js";
import { aperturesGLSL, apertureUniforms } from "../glsl/apertures.js";
import { DEPTH } from "../glsl/blocks.js";
import { DOOR_PATTERN, DOORS, MAX_DOORS, NO_SUN, doorUniforms } from "../glsl/doors.js";
import { stillSurface } from "../materials/still-surface.js";
import { glowMaterial, paintMaterial } from "../materials/paint.js";
import { latticePanel } from "../objects/lattice.js";
import { rect, shellGeometry } from "../objects/shell.js";
import { DOOR, doorGeometries, doorLight, doorSpec, mergeGeometries } from "../objects/door.js";
import { makeSun, sunAt, sunFixed } from "../clock.js";
import { mixc, tok, vec } from "../colors.js";
import { CORRIDOR, corridorLayout } from "../journey.js";
import { HOUSE, houseShade, houseSun, houseUniforms, screenColors, setHouseSun, street, surfaceUniforms, tint } from "./common.js";
import { EXIT, exitLook } from "./places.js";
import { FAN, buildProps, fanAperture, fansGLSL, propsMesh } from "../objects/props.js";
import { bakeGachBong } from "../gach.js";
import { posterUniforms, wallPosters } from "../objects/wall-poster.js";
import { corridorPosters } from "../wall-posters-data.js";

// The corridor (05-DESIGN D6.2, D6.5): open-air, the breeze-block screen on the left with the street and
// the morning sun beyond it, the lime wall on the right with one painted door per [data-beat] card, each
// in its own 6.5 m bay with a pier at its far end. Two lights: the sun through the screen (and the
// tamarind past it), and daylight from the rooms behind the doors, out through each transom in its
// unit's emblem and, where a door stands open, through the doorway. Little Rose: closed, the house's own
// Hoa thị transom, plain untinted light, and no corridor sun anywhere in its bay.
// The richness pass (D0.6.2, Maxwell: "a plain white wall"): a Saigon mái ấm. Yellow lime wash with its loang
// over a mint dado to 0.90 m on every wall, broken 10 cm short of every door frame (Little Rose's too); a
// gạch bông runner on the screen side and plain cream tiles in the sunlit band by the doors; a warm ceiling;
// a fan in every bay but Little Rose's (critique r2 cut the drifting tamarind leaves: objects/props.js).
// Used by the live world (live.js) and by the still renderer (sets/stills.js), so both see one corridor.

// Degrees a door swings to at its beat (D5.3; live.js drives it). Critique r2: at 72° out into the corridor the leaf
// stood 0.9 m from the wall and the walk passed its edge, a blurred slab with full-height straight edges filling the
// frame's right; folded back flat along the wall it was still a green slab beside the lens. So an open door opens into
// its room, as a Saigon room door does: hinged on the jamb nearer the camera's approach, it turns away from the
// visitor, the lit room opening from the far jamb first, and comes to rest just past square, behind the near wall,
// where the corridor never sees it. The doorway stays clear for its light. Little Rose's stays shut and untouched
// (its hinge and leaf are as they were).
export const DOOR_OPEN = 96;

// Traces of children's lives (05-DESIGN D6.8, staging only until HCWA approves): the chalk lò cò on the Tre Xanh
// bay's plain band, on the side of its door away from Little Rose, its whole length kept LO_CO.clear from Little
// Rose's bay (else none). Never in a still. Its squares are 0.34 m: 6 rows and a half-round home, 2.21 m.
export const LO_CO = { unit: "green-bamboo", len: 6 * 0.34 + 0.17, gap: 0.05, clear: 1.5 };
export function loCoSpot(lay, C = CORRIDOR) {
  const d = lay.doors.find((x) => x.unit === LO_CO.unit), lr = lay.doors.find((x) => x.unit === "little-rose")?.bay;
  if (!d) return null;
  const zs = Math.round(d.zc / HOUSE.tile) * HOUSE.tile, near = HOUSE.threshold + LO_CO.gap;
  const x = C.wallX - (C.wallX - C.screenX - HOUSE.runner) / 2;
  // +z: from the camera's side toward the door (first square farthest from it); −z: past the door.
  for (const [z0, z1, from, dir] of [[zs + near, zs + near + LO_CO.len, zs + near + LO_CO.len, -1], [zs - near - LO_CO.len, zs - near, zs - near - LO_CO.len, 1]]) {
    if (z0 < d.bay[0] || z1 > d.bay[1]) continue;
    if (lr && !(z1 + LO_CO.clear <= lr[0] || z0 - LO_CO.clear >= lr[1])) continue;
    return { z0, z1, from, dir, x };
  }
  return null;
}

// gach: the baked runner ({ field, border }, gach.js; null in tests). fanShadows: the blades in the sun's mask (Tier 2
// and the stills; never on a phone). traces: the chalk lò cò (live corridor only, when the page carries data-traces).
export function corridorScene({ lime, terr, gach = null, beats, open = 0, t = 0.25, fanShadows = true, fanAngle = 0.6, traces = false }) {
  if (beats.length > MAX_DOORS) throw new Error(`corridor: ${beats.length} doors > ${MAX_DOORS}`);
  const lay = corridorLayout(beats), C = CORRIDOR, s = lay.screen;
  const box = { min: [C.screenX, 0, lay.end], max: [C.wallX, C.ceiling, lay.start] };
  const sun = makeSun();
  sunAt("corridor", t, sun);
  const Li = sunFixed({ az: 0, el: 57 });

  const screen = { mode: "window", pattern: "hoaThi", corner: [s.sx, s.y0, s.z0], u: [0, 0, 1], v: [0, 1, 0], size: [s.z1 - s.z0, s.y1 - s.y0], depth: DEPTH };
  // Tamarinds along the street: the patches come and go as in the room.
  const trees = { mode: "occluder", pattern: "canopy", corner: [s.sx - 3.2, -2, lay.end - 6], u: [0, 0, 1], v: [0, 1, 0], size: [lay.start - lay.end + 12, 9], depth: 0, params: [0.9, 0.56, 1.3, 4.1] };
  // The fans: never in Little Rose's bay (objects/props.js).
  const props = buildProps({ lay });
  const shadows = fanShadows && props.fans.length > 0;
  const lights = [
    { dir: sun.dir, apertures: shadows ? [screen, trees, fanAperture({ C, lay })] : [screen, trees], glsl: NO_SUN + (shadows ? fansGLSL(props.fans.length) : ""), factor: "bayFree(P)" },
    { dir: Li, glsl: DOORS, mask: "doorsMask(P, L)" },
  ];

  // Daylight from the rooms: untinted, the same for every door (Little Rose's never carries a color).
  const day = mixc(tok("sky"), tok("wallSun"), 0.45);
  // On the floor the rooms' light is the day itself (it was 86% of it): the emblem pools on the plain thresholds
  // reach a lit-to-shade ratio of 1.6 or more (D0.6.2's signature check, critique r1).
  const spillT = [day, day.clone(), day];
  const U = {
    ...surfaceUniforms({ box, fillDir: [-1, 0, 0], sun: sun.color, shade: houseShade(), sunT: houseSun(sun.color), spillT, refs: [Math.abs(sun.dir.x), Li.y], lime, terr }),
    ...apertureUniforms(lights, [sun.dir, Li]),
    ...doorUniforms(),
    // The runner from the screen wall; the door wall's breaks read uDoors (glsl/doors.js).
    ...houseUniforms({ gach, runnerX: C.screenX, doorWall: C.wallX, half: DOOR.w / 2 + DOOR.frame, sun: sun.color }),
    // Fan shadows (fansGLSL): centres in the fan aperture's uv (along −z from the start, across from the screen).
    uFans: { value: Array.from({ length: Math.max(props.fans.length, 1) }, (_, i) => props.fans[i] ? new Vector4(lay.start - props.fans[i].z, FAN.x - C.screenX, props.fans[i].seed, shadows ? 1 : 0) : new Vector4()) },
    uFanA: { value: fanAngle },
  };
  U.uI.value = [1, 1]; // the rooms' daylight at full strength: the emblem pools read on the thresholds (critique r1)
  const loCo = traces ? loCoSpot(lay, C) : null;
  if (loCo) U.uTrace.value.set(loCo.from, loCo.dir, loCo.x, 1);

  // ---- doors from the beats ----
  const specs = lay.doors.map((d) => ({ ...doorSpec({ wx: C.wallX, zc: d.zc, unit: d.unit, open: 0, ...(d.unit === "little-rose" ? { hinge: -1 } : { hinge: 1, inward: true }) }), beat: d }));
  U.uDoorN.value = specs.length;
  U.uDoorWall.value.set(C.wallX, specs[0]?.transom.y0 ?? 2.3, 0, 0);
  let lr = 0;
  specs.forEach((d, i) => {
    const light = doorLight(d.unit);
    // w: the hinge side (±1), doubled when the leaf swings into the room (glsl/doors.js).
    U.uDoors.value[i].set(d.zc, DOOR_PATTERN[light.transomPattern] ?? DOOR_PATTERN.hoaThi, 0, d.hinge * (d.swing < 0 ? 2 : 1));
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
  const shell = new Mesh(shellGeometry(rects), stillSurface(U, lights, { house: true, nearFade: true }));

  // Faces the corridor (+x): laid from the screen's +z end along −z, on the light's own block grid.
  const lattice = latticePanel(U, lights, { corner: [s.sx, s.y0, s.z1], u: [0, 0, -1], v: [0, 1, 0], size: screen.size, pattern: "hoaThi", light: 0, own: street() });
  screenColors(lattice.material.uniforms);

  // The fans: one draw, before the shell (props.js).
  const propMesh = propsMesh(U, lights, props, tint(tok("lime-50"), sun.color, 0.6));
  const objects = [shell, lattice, propMesh];
  // Every door frame, and Little Rose's leaf (it never moves: setDoor keeps it at 0), as one draw: the paint
  // and its wear per vertex (paint.js vertexPaint).
  const fixed = [];
  const paintOf = (geo, c, wear) => {
    const n = geo.getAttribute("position").count, a = new Float32Array(n * 4);
    for (let k = 0; k < n; k++) a.set([c.r, c.g, c.b, wear], k * 4);
    geo.setAttribute("aPaint", new Float32BufferAttribute(a, 4));
    return geo;
  };
  const paintOwn = () => ({
    // The chips show an older, lighter coat (paint.js oldCoat): uWood is the tiles' cream it is mixed with.
    uWood: { value: vec(tok("tile-cream")) }, uCool: { value: vec(tok("wallShade")) },
    uSunTint: { value: vec(tint(tok("lime-50"), sun.color)) }, uSpillTint: { value: vec(mixc(tok("lime-50"), day, 0.5)) },
    uShadeK: { value: 0.58 },
  });
  const leaves = [], glows = [];
  for (const d of specs) {
    const unitColor = tok("unit-" + d.unit), isLR = d.unit === "little-rose", wear = isLR ? 0.3 : 0.6;
    const g = doorGeometries(d);
    fixed.push(paintOf(g.frame, unitColor, wear));
    const leaf = new Group();
    leaf.position.set(...g.hinge);
    if (isLR) { fixed.push(paintOf(g.leaf.clone().translate(...g.hinge), unitColor, wear)); g.leaf.dispose(); }
    else {
      const paint = paintMaterial(U, lights, { ...paintOwn(), uPaint: { value: vec(unitColor) }, uWear: { value: wear } }, { leafWear: true, nearFade: true, oldCoat: true });
      leaf.add(new Mesh(g.leaf, paint));
      objects.push(leaf);
    }
    leaves.push(leaf);
    // What shows through an open doorway: the lit room, nothing in it modeled (critique r1: not a flat slab). Its
    // light warms from sun-100 under the head to sun-200 at the threshold, the reveal keeps it off the jambs, and
    // the room's own screen lays a faint Hoa thị dapple on its back wall; a few percent of the unit (D6.5 ≤ 15%).
    // Little Rose has none.
    if (!isLR) {
      const glow = new Mesh(new PlaneGeometry(DOOR.w + 0.4, DOOR.h + 0.3), glowMaterial({ low: mixc(tok("sun-200"), unitColor, 0.08), high: mixc(tok("sun-100"), unitColor, 0.05), y0: 0, y1: DOOR.h, dapple: { zc: d.zc, half: DOOR.w / 2, amount: 0.07 } }));
      glow.rotation.y = -Math.PI / 2;
      // 1.05 m back: the leaf, open into the room (0.98 m), stands in front of it.
      glow.position.set(C.wallX + 1.05, DOOR.h / 2, d.zc);
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
  if (fixed.length) objects.push(new Mesh(mergeGeometries(fixed), paintMaterial(U, lights, paintOwn(), { vertexPaint: true, leafWear: true, nearFade: true, oldCoat: true })));

  // Wall posters (M6, objects/wall-poster.js): on the door wall above the dado, 5 mm off it, lit as the wall is. Where
  // they hang and the Little Rose rule are wall-posters-data.js's; all of them are one mesh, one draw (corridorPosters drops any that would come within half
  // a bay and 1.5 m of Little Rose's door). Drawn once the atlas is in (posterUniforms().uPosterK).
  const posterOwn = { uSunTint: { value: vec(tint(tok("lime-50"), sun.color, 0.6)) }, uSpillTint: { value: vec(mixc(tok("lime-50"), day, 0.5)) }, uShadeK: { value: 0.62 } };
  const posters = wallPosters({ ...U, ...posterUniforms() }, lights, posterOwn, corridorPosters(lay).map((p, i) => (p.wall === "end"
    ? { id: p.id, w: p.w, at: [p.x, p.y, p.z + 0.005], rotY: 0, seed: 3 + i * 7 }
    : { id: p.id, w: p.w, at: [C.wallX - 0.005, p.y, p.z], seed: 3 + i * 7 })));
  if (posters) objects.push(posters);

  // A door at its angle: the leaf, the light past it, the lit room behind it. Little Rose stays shut.
  const setDoor = (i, deg) => {
    const d = specs[i];
    if (!d) return;
    const a = d.unit === "little-rose" ? 0 : (Math.min(Math.max(deg, 0), DOOR_OPEN) * Math.PI) / 180;
    leaves[i].rotation.y = d.hinge * d.swing * a;
    U.uDoors.value[i].z = a;
    if (glows[i]) glows[i].visible = a > 0.01;
  };
  specs.forEach((_, i) => setDoor(i, open));

  // The sun along the corridor's clock (D6.4: t 0.25 → 0.45, nearly head-on to the door wall).
  const setSun = (tt, intensity = 1) => {
    sunAt("corridor", tt, sun);
    const k = houseSun(sun.color);
    U.uSunT.value.forEach((v, i) => v.set(k[i].r, k[i].g, k[i].b));
    setHouseSun(U, sun.color);
    const p = tint(tok("lime-50"), sun.color, 0.6);
    propMesh.material.uniforms.uSunTint.value.set(p.r, p.g, p.b);
    posterOwn.uSunTint.value.set(p.r, p.g, p.b);
    U.uSunCol.value.set(sun.color.r, sun.color.g, sun.color.b);
    U.uRef.value[0] = Math.max(Math.abs(sun.dir.x), 0.05);
    U.uI.value[0] = intensity;
    U.uSunI.value = intensity;
  };

  // The life in it: tm in seconds of the props clock (live.js: Tier 2 the clock and the scroll, Tier 1 the
  // scroll alone, frozen while paused). One uniform write, no allocation.
  const animate = (tm) => { U.uFanA.value = (fanAngle + tm * FAN.omega) % (Math.PI * 2); };
  const lrIndex = lay.doors.findIndex((d) => d.unit === "little-rose");
  // Tier 2 → 1 at runtime (the live gate): the fans stop casting (their test stays compiled, every fan off).
  const setFanShadows = (on) => { for (const v of U.uFans.value) if (v.x || v.y) v.w = on && shadows ? 1 : 0; };

  return {
    box, lights, U, objects, sun, layout: lay, specs, setDoor, setSun, animate, setFanShadows, lr: lrIndex, props, posters,
    // For the still renderer: what frames the shot, what glows on its own.
    subject: [screen], glow: [screen], shafts: 1.4,
  };
}

export { EXIT, exitLook };

// The live corridor (live.js): built from the page's beats once the room is live, sharing the room's
// baked textures. Shafts through the screen on both tiers (they gate like the room's).
// The runner bakes here, in idle slices after the hero is live (never on the LCP path): 256 px a pair with
// anisotropy 2 on Tier 1, 512 and 4 on Tier 2. Fan shadows on Tier 2 only (the blade test in every sun lookup
// took the phone proxy +80%); phones turn the fans and cast nothing.
export async function buildCorridor({ gl, tier, beats, textures, traces = false, onSlice = null }) {
  const gach = await bakeGachBong({ worker: true, size: tier >= 2 ? 512 : 256, aniso: tier >= 2 ? 4 : 2, rows: tier >= 2 ? 16 : 8, onSlice });
  const set = corridorScene({ ...textures, gach, beats, fanShadows: tier >= 2, traces });
  const scene = new Scene();
  scene.add(...set.objects);
  // The shafts leave the fans out: the blade test in every step of the march was most of Tier 2's added
  // cost (SwiftShader proxy at 1440: +45% with it, +6% without); the surfaces keep the blades' shadows.
  const shaftLight = { ...set.lights[0], apertures: set.lights[0].apertures.filter((a) => a.pattern !== "fans") };
  const shafts = createShafts(gl.renderer, set.U, { box: set.box, tier, ortho: gl.ortho, mask: { glsl: aperturesGLSL([shaftLight]), call: "lightMask0(p)" } });
  shafts.uniforms.uDensity.value = 1.4;
  const camera = new PerspectiveCamera(EXIT.fov.landscape, 1, 0.05, 60);
  // The tamarinds' dapple (the screen's light is aperture 0, the trees 1).
  const treeP = set.U.uApP.value[1], treeAt = treeP.clone();
  return {
    ...set, scene, camera, shafts, bakedInWorker: gach.worker,
    setTier: (t) => { shafts.setTier(t); set.setFanShadows(t >= 2); },
    // The tamarinds outside sway on the props clock (live.js: with the scroll on phones): two slow sines.
    sway(time) { treeP.z = treeAt.z + 0.2 * Math.sin(time * 0.31 + 0.6) + 0.06 * Math.sin(time * 1.07); treeP.w = treeAt.w + 0.1 * Math.sin(time * 0.23 + 2.1); },
    compile: async () => {
      await gl.renderer.compileAsync(scene, camera);
      for (const s of shafts.scenes) await gl.renderer.compileAsync(s, gl.ortho);
    },
    dispose() {
      scene.traverse((o) => { if (o.isMesh) { o.geometry.dispose(); o.material.dispose(); } });
      gach.field.dispose(); gach.border.dispose();
      shafts.dispose();
    },
  };
}
