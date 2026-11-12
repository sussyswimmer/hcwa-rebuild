import { CustomBlending, MinEquation, Mesh, OneFactor, PerspectiveCamera, PlaneGeometry, Scene, ShaderMaterial, Vector3 } from "three";
import { SCREEN_VERT, fullscreen } from "../renderer.js";
import { aperturesGLSL, apertureUniforms } from "../glsl/apertures.js";
import { stillSurface } from "../materials/still-surface.js";
import { glowMaterial, paintMaterial } from "../materials/paint.js";
import { createShafts } from "../materials/shafts.js";
import { createMotes } from "../materials/motes.js";
import { boxRoom, rect, shellGeometry } from "../objects/shell.js";
import { viewMaterial } from "../objects/sky.js";
import { leafAperture, shutterRig, shutterSpec } from "../objects/shutters.js";
import { STILL_SUNS, makeSun, sunAt, sunFixed } from "../clock.js";
import { EXPOSURE, mixc, tok, vec } from "../colors.js";
import { roomShade, roomSun, surfaceUniforms, tint } from "./common.js";
import { SHUTTERS } from "../journey.js";
import { WIN_Z } from "./places.js";
import { posterUniforms, wallPosters } from "../objects/wall-poster.js";
import { S3_POSTERS } from "../wall-posters-data.js";

// The shutters (05-DESIGN D6.2, D6.3; S3 in D5.3): a French window in the end wall of a lime-wash room,
// its two logo-blue louvered leaves, the late sun outside. Light enters only through the opening and, while
// the leaves are shut, only through their slat gaps: thin gold lines across the terrazzo that widen into
// bars as the slats turn, then the whole window's patch once the leaves swing in. Used by the live world
// (S3) and by the stills (Get Involved's header, the S3 fallbacks), so both see one room.
// The window is in the x = 0 wall, the room at x < 0, the window's centre at z = WIN_Z.

export { WIN_Z };
export const BOX = { min: [-6, 0, -6], max: [0, 3.2, 4.2] };
const REVEAL = 0.24; // the wall's depth at the window: jambs, head and threshold catch the light
const MARGIN = 0.06; // the frame around the leaves
// Where the motes drift: the air the beams cross between the window and the camera.
const MOTE_BOX = { min: [-4.6, 0.04, WIN_Z - 1.7], max: [-0.05, 2.5, WIN_Z + 2.9] };
const FOCUS = new Vector3(0, 1.05, WIN_Z); // the flare's centre: the middle of the opening

// leaf, slat: degrees; t: the sun clock (S3, 0.85 → 0.95) or a fixed still sun; full: the live set and the
// S3 stills (a tamarind outside, the wall's depth at the window, no colour past sun-100); without it the
// scene is exactly Get Involved's header as rendered; plaster: its walls in a lit header's warm plaster.
export function shuttersScene({ lime, terr, leaf = 104, slat = 65, t = null, full = false, plaster = false, lite = false }) {
  const box = BOX;
  const sp = shutterSpec({ wx: 0, zc: WIN_Z, y0: 0.05, leaf, slat });
  const o = sp.opening;
  const sun = makeSun();
  if (t == null) { sunFixed(STILL_SUNS.shutters, sun.dir); sun.color.copy(tok(STILL_SUNS.shutters.color)); }
  else sunAt("shutters", t, sun);
  const L = sun.dir;

  // The live set's window patch has soft edges (its light comes in as a pool, no straight line across a frame
  // that is blending into sun-100); the header's keeps the crisp one it was rendered with.
  const win = { mode: "window", pattern: "open", corner: [0, o.y0, o.z0], u: [0, 0, 1], v: [0, 1, 0], size: [o.z1 - o.z0, o.y1 - o.y0], depth: 0.16, ...(full ? { feather: 0.09 } : {}) };
  const leaves = sp.hinges.map((h) => leafAperture(sp, h));
  // A tamarind across the street: its leaves break the late light into a moving dapple (live.js sways it).
  const tree = { mode: "occluder", pattern: "canopy", corner: [2.6, -2, WIN_Z - 9], u: [0, 0, 1], v: [0, 1, 0], size: [18, 10], depth: 0, params: [0.55, 0.74, 2.3, 5.1] };
  const lights = [{ dir: L, apertures: [win, ...leaves, ...(full ? [tree] : [])] }];
  // The late sun's gold, deeper on the full set: it is the colour the whole sequence ends in.
  const gold = full ? 0.9 : 0.55;
  const U = {
    ...surfaceUniforms({ box, fillDir: [1, 0, 0], sun: sun.color, shade: roomShade(), sunT: roomSun(sun.color, gold), spillT: roomShade(), refs: [L.y, 1], lime, terr }),
    ...apertureUniforms(lights, [L, new Vector3(0, 1, 0)]),
  };
  // Light past full runs toward the flood's own colour, sun-100: never toward white (D5.3).
  if (full) U.uGlare.value.copy(vec(tok("sun-100")));

  const holes = [[o.z0 - MARGIN, o.z1 + MARGIN, 0, o.y1 + MARGIN]];
  const rects = boxRoom(box, { cut: { wall: "xMax", holes } });
  if (full) {
    const [z0, z1, , y1] = holes[0];
    rects.push(
      ...rect("z", z0, [0, REVEAL], [0, y1], 1, 0),
      ...rect("z", z1, [0, REVEAL], [0, y1], -1, 0),
      ...rect("y", y1, [0, REVEAL], [z0, z1], -1, 0),
      ...rect("y", 0.001, [0, REVEAL], [z0, z1], 1, 1),
    );
  }
  const shell = new Mesh(shellGeometry(rects), stillSurface(U, lights));
  const paint = paintMaterial(U, lights, {
    // Lit paint lands on its token (D6.3): the late sun warms it only a little.
    uPaint: { value: vec(tok("blue-700")) }, uWood: { value: vec(tok("wood")) }, uCool: { value: vec(tok("wallShade")) },
    uSunTint: { value: vec(tint(tok("lime-50"), sun.color, 0.25)) }, uSpillTint: { value: vec(tok("lime-50")) }, uShadeK: { value: 0.8 }, uWear: { value: 1 },
  });
  // Slats are all edge at this size: worn only on stiles and rails.
  const slatPaint = paint.clone();
  slatPaint.uniforms = { ...paint.uniforms, uWear: { value: 0 } };
  const rig = shutterRig(sp, paint, slatPaint);
  // The street at 17:00 past the window. The live set and its stills (full): a Saigon street seen from the first floor
  // (objects/sky.js viewMaterial, M7): shophouses across the road behind rolling shutters, the pavement in late sun, a
  // parked Cub, a tamarind across the top of the opening, so the open leaves reveal a place, the same street as Get
  // Involved's header. Get Involved's header still: gold low, paler high.
  const outside = full ? viewMaterial({ zc: WIN_Z, trunk: -1.4, lite })
    : glowMaterial({ low: mixc(tok("sun-200"), tok("sun-400"), 0.35), high: mixc(tok("sun-100"), tok("sky"), 0.5), y0: 0.2, y1: 4, grain: 0.04,
      canopy: { color: mixc(mixc(tok("unit-green-bamboo"), tok("sun-400"), 0.5), tok("gold-700"), 0.2), amount: 0.32 } });
  const plane = new Mesh(new PlaneGeometry(14, 7), outside);
  plane.position.set(4, 1.6, WIN_Z);
  plane.rotation.set(0, -Math.PI / 2, 0);

  // ---- posable (live S3): every setter writes in place, no allocations ----
  // Late afternoon (t 0.85): the shade is the plaster in the gold that fills the room, lime-300 toward
  // sun-200, never the morning rooms' cool grey.
  if (full || plaster) U.uShade.value.forEach((v) => v.lerp(vec(mixc(tok("lime-300"), tok("sun-200"), 0.4)), full ? 0.36 : 0.55));
  const base = { shade: U.uShade.value.map((v) => v.clone()), sunT: U.uSunT.value.map((v) => v.clone()), low: outside.uniforms.uLow?.value.clone(), high: outside.uniforms.uHigh?.value.clone() };
  // The light bouncing in off the floor patch warms the shade (sun-200 at most a third of the way).
  const warm = base.shade.map((v) => v.clone().lerp(vec(tok("sun-200")), 0.34));
  const sun100 = tok("sun-100"), rest = tok("sunRoom"), lime50 = tok("lime-50"), wallSun = tok("wallSun");
  const ap = (i) => ({ U: U.uApU.value[i], S: U.uApS.value[i], P: U.uApP.value[i] });
  const leafU = [ap(1), ap(2)], scratch = {};
  const pose = (leafDeg, slatDeg) => {
    rig.set(leafDeg, slatDeg);
    sp.hinges.forEach((h, j) => {
      const a = leafAperture(sp, h, leafDeg, slatDeg, scratch);
      leafU[j].U.set(a.u[0], a.u[1], a.u[2]);
      leafU[j].S.z = a.depth;
      leafU[j].P.y = a.params[1];
    });
  };
  // A target tinted by the sun relative to the room's rest sun, at strength k (common.js tint, in place).
  const tintInto = (out, c, k) => out.set(c.x * (1 + k * (sun.color.r / rest.r - 1)), c.y * (1 + k * (sun.color.g / rest.g - 1)), c.z * (1 + k * (sun.color.b / rest.b - 1)));
  const wallV = vec(wallSun), limeV = vec(lime50);
  const setSun = (tt) => {
    sunAt("shutters", tt, sun);
    U.uRef.value[0] = Math.max(L.y, 0.05);
    tintInto(U.uSunT.value[0], wallV, gold);
    tintInto(U.uSunT.value[1], wallV, gold).multiplyScalar(0.8);
    tintInto(U.uSunT.value[2], wallV, gold);
    for (let i = 0; i < 3; i++) base.sunT[i].copy(U.uSunT.value[i]);
    U.uSunCol.value.set(sun.color.r, sun.color.g, sun.color.b);
    tintInto(paint.uniforms.uSunTint.value, limeV, 0.25);
  };
  // exposure: D5.3's number (1.22 rest → 1.55 open → 1.75 flood peak → 1.6). It is light, not a camera
  // setting, on display targets: the sun runs past full toward sun-100, the shade warms, the street pales.
  // open (0..1): how much of the window lets light in. Behind shut shutters the room is dimmer, so the first
  // gold lines read; it fills with light as they open. lit (0..1): the sun through the leaves, from faint
  // lines with the slats shut to full as they turn and the leaves swing.
  const setExposure = (e, open = 1, lit = 1) => {
    const k = Math.max(e / EXPOSURE - 1, 0); // 0 at rest, 0.27 open, 0.43 at the flood's peak
    U.uI.value[0] = (1 + 2.6 * k) * (0.55 + 0.45 * lit);
    U.uSunI.value = 1 + 0.6 * k;
    const dim = full ? 0.68 + 0.32 * open : 1; // linear: about 0.84 of the shade's sRGB when shut
    for (let i = 0; i < 3; i++) U.uShade.value[i].lerpVectors(base.shade[i], warm[i], Math.min(k * 2.2, 1)).multiplyScalar(dim);
    // M6/M7 ("the open shutters look out onto a blank cream void"): the street pales only a little as the light rises,
    // so the shophouses, the tamarind and the pavement keep their shape through the look-out; the flood pales it more
    // (k 0.27 open → 0.15, 0.43 at the flood's peak → 0.26), and the blend into sun-100 does the rest.
    if (outside.uniforms.uPale) outside.uniforms.uPale.value = Math.min(k * 0.6, 0.3);
    else {
      outside.uniforms.uLow.value.copy(base.low).lerp(sun100, Math.min(k * 0.8, 0.45));
      outside.uniforms.uHigh.value.copy(base.high).lerp(sun100, Math.min(k * 1.4, 0.8));
    }
  };
  const project = (camera, out) => out.copy(FOCUS).project(camera);

  // Wall posters (M6, wall-posters-data.js S3_POSTERS): the live set and its stills only (Get Involved's header keeps
  // its own room), on the window wall either side of the window, 5 mm off it; the slat lines and the late sun cross
  // them as they cross the wall. The paint's own sun tint, so they warm with the leaves.
  const posters = full ? wallPosters({ ...U, ...posterUniforms() }, lights, { uSunTint: paint.uniforms.uSunTint, uSpillTint: paint.uniforms.uSpillTint, uShadeK: { value: 0.74 } }, S3_POSTERS.map((p, i) => ({ id: p.id, w: p.w, at: [-0.005, p.y, WIN_Z + p.dz], seed: 41 + i * 5 }))) : null;

  return {
    box, lights, U, sun, spec: sp, rig, paint, outside, posters,
    objects: [shell, rig.group, plane, ...(posters ? [posters] : [])],
    pose, setSun, setExposure, project,
    treeIndex: full ? 3 : -1,
    // For the still renderer: what frames the shot, what glows on its own.
    subject: [win, ...leaves], glow: [win], shafts: full ? +(1.2 * (1 - 0.6 * Math.min(leaf / 110, 1))).toFixed(2) : false,
  };
}

// The live S3 set (live.js): built in idle time after the corridor, sharing the room's baked textures.
// Shafts and motes read the same light (the window, the leaves' slat gaps, the tamarind).
export async function buildShutters({ gl, tier, textures }) {
  // Tier 1 draws the street's lighter noise (sky.js viewMaterial lite); the stills and Tier 2 the full one.
  const set = shuttersScene({ ...textures, leaf: 0, slat: 0, t: SHUTTERS.sun[0], full: true, lite: tier < 2 });
  const scene = new Scene();
  scene.add(...set.objects);
  // Shafts and motes see the window and the leaves only: the tamarind's fbm at every step of every shaft
  // ray would cost more than the dapple is worth in the air (fixing-motion-performance). The tree is the
  // last aperture, so the first three keep their uniform slots.
  const mask = { glsl: aperturesGLSL([{ apertures: set.lights[0].apertures.slice(0, 3) }]), call: "lightMask0(p)" };
  Object.assign(set.U, {
    uPx: { value: 1 }, uMotesA: { value: 1 }, uStir: { value: 0 },
    // Display-space additive, like the room's (motes.js): sun-200 toward the late gold.
    uMoteCol: { value: vec(mixc(tok("sun-200"), tok("sunShuttersA"), 0.45).convertLinearToSRGB()) },
  });
  const motes = createMotes(set.U, MOTE_BOX, { mask });
  scene.add(motes.points);
  const shafts = createShafts(gl.renderer, set.U, { box: set.box, tier, ortho: gl.ortho, mask });
  shafts.uniforms.uDensity.value = 1;
  const camera = new PerspectiveCamera(44, 1, 0.05, 60);
  const treeP = set.U.uApP.value[set.treeIndex], treeAt = treeP.clone();
  // The ceiling of the light (D5.3: the brightest frame is sun-100, never white): after the scene and its
  // shafts, every pixel is held to sun-100 per channel (a MIN blend), so screen-blended beams and motes over
  // the window's glare cannot run past it, flood or not.
  const cap = new ShaderMaterial({
    uniforms: { uC: { value: tok("sun-100") } }, vertexShader: SCREEN_VERT,
    fragmentShader: /* glsl */ `uniform vec3 uC; void main(){ gl_FragColor = vec4(uC, 1.);
      #include <colorspace_fragment>
      }`,
    blending: CustomBlending, blendEquation: MinEquation, blendSrc: OneFactor, blendDst: OneFactor,
    depthTest: false, depthWrite: false, toneMapped: false,
  });
  const capScene = new Scene().add(new Mesh(fullscreen(), cap));
  capScene.children[0].frustumCulled = false;
  const capPass = { scenes: [capScene], render() { gl.renderer.render(capScene, gl.ortho); }, dispose() { cap.dispose(); capScene.children[0].geometry.dispose(); } };
  let swaying = tier >= 2;
  const setTier = (t) => { shafts.setTier(t); motes.setTier(t); swaying = t >= 2; if (!swaying) set.outside.uniforms.uSway.value = 0; };
  setTier(tier);
  return {
    ...set, scene, camera, shafts, motes, setTier, passes: [shafts, capPass],
    // The tamarind sways (two slow sines, never a drift that repeats); time stops while paused.
    // The tamarind outside the window sways on Tier 2 only: 0.6° at 0.15 Hz about its trunk.
    sway(time) {
      treeP.z = treeAt.z + 0.22 * Math.sin(time * 0.37) + 0.07 * Math.sin(time * 1.13); treeP.w = treeAt.w + 0.12 * Math.sin(time * 0.29 + 1.7);
      if (swaying) set.outside.uniforms.uSway.value = 0.0105 * Math.sin(time * 2 * Math.PI * 0.15);
      set.outside.uniforms.uTime.value = time; // the dapple on the street drifts with the world's clock (stops while paused)
    },
    compile: async () => {
      await gl.renderer.compileAsync(scene, camera);
      for (const s of [...shafts.scenes, capScene]) await gl.renderer.compileAsync(s, gl.ortho);
    },
    dispose() {
      scene.traverse((o) => { if (o.isMesh || o.isPoints) { o.geometry.dispose(); o.material.dispose(); } });
      shafts.dispose();
      capPass.dispose();
    },
  };
}
