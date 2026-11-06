import { BufferGeometry, Euler, Float32BufferAttribute, Matrix4, Mesh, PerspectiveCamera, PlaneGeometry, Scene, Vector3 } from "three";
import { apertureUniforms } from "../glsl/aperture.js";
import { capUniforms } from "../materials/caps.js";
import { surfaceMaterial } from "../materials/surface.js";
import { createShafts } from "../materials/shafts.js";
import { createMotes } from "../materials/motes.js";
import { screenMesh } from "../objects/screen.js";
import { streetPlane, streetUniforms } from "../objects/sky.js";
import { bakeLimeWash, bakeTerrazzo } from "../textures.js";
import { makeSun, REST, sunAt } from "../clock.js";
import { mixc, tok, vec } from "../colors.js";

// The hero room (05-DESIGN D6.2): lime-wash room 8.6 × 3.3 × 8 m, an 18 × 8 breeze-block
// screen on the right wall above a 0.9 m parapet, terrazzo floor, the street beyond.
// Metres, Y up, the camera always at a child's eye height (1.10 m).
export const BOX = { min: [-6, 0, -4], max: [2.6, 3.3, 4] };
export const SCREEN = { sx: 2.6, z0: -4, z1: 1.4, y0: 0.9, y1: 3.3 };
// Row 1, column 9: the block the passage flies through (K1/K2, next milestone).
export const HERO_BLOCK = new Vector3(2.6, 1.05, -1.15);
const MOTE_BOX = { min: [-3.5, 0.05, -3.9], max: [2.5, 3.2, 2.0] };

// K0 per aspect bucket (D6.2 starting values; final shots are approved as stills on day 3).
export const SHOTS = {
  // Portrait: the phone stands in the back-right corner and looks into it. Below eye height the screen
  // cannot share a 390 px width with three lines of H1 anywhere else in the room (scripts searched
  // every position): here its quatrefoils blaze large overhead, the H1 sits on the back wall right
  // beside it where the throws are short and the patches crisp, and the floor closes the frame.
  portrait: { pos: [0.83, 1.1, -2.93], look: [1.68, 0.91, -3.43], fov: 49.6 },
  tablet: { pos: [-1.3, 1.1, 3.15], look: [1.2, 1.95, -3.6], fov: 54 },
  landscape: { pos: [-1.5, 1.1, 3.3], look: [0.5, 1.78, -4], fov: 46 },
  wide: { pos: [-1.5, 1.1, 3.3], look: [0.5, 1.78, -4], fov: 44 },
};

// Silhouettes the framing solver keeps off the text (world coordinates).
const corners = (s) => [new Vector3(s.sx, s.y0, s.z0), new Vector3(s.sx, s.y1, s.z0), new Vector3(s.sx, s.y1, s.z1), new Vector3(s.sx, s.y0, s.z1)];
export const FRAME_SHAPES = {
  silhouette: corners(SCREEN),
  floor: [new Vector3(BOX.min[0], 0, BOX.min[2]), new Vector3(BOX.max[0], 0, BOX.min[2]), new Vector3(BOX.max[0], 0, BOX.max[2]), new Vector3(BOX.min[0], 0, BOX.max[2])],
  heroBlock: HERO_BLOCK,
};

// Back, left, floor, ceiling, parapet, the solid wall past the screen, the wall behind the
// camera: merged into one draw, the surface kind (0 wall, 1 floor, 2 ceiling) per vertex.
function shellGeometry() {
  const [x0, y0, z0] = BOX.min, [x1, y1, z1] = BOX.max, cx = (x0 + x1) / 2, w = x1 - x0, d = z1 - z0, h = y1 - y0;
  const quads = [
    [w, h, [0, 0, 0], [cx, h / 2, z0], 0],
    [d, h, [0, Math.PI / 2, 0], [x0, h / 2, (z0 + z1) / 2], 0],
    [w, d, [-Math.PI / 2, 0, 0], [cx, y0, (z0 + z1) / 2], 1],
    [w, d, [Math.PI / 2, 0, 0], [cx, y1, (z0 + z1) / 2], 2],
    [SCREEN.z1 - SCREEN.z0, SCREEN.y0, [0, -Math.PI / 2, 0], [x1, SCREEN.y0 / 2, (SCREEN.z0 + SCREEN.z1) / 2], 0],
    [z1 - SCREEN.z1, h, [0, -Math.PI / 2, 0], [x1, h / 2, (SCREEN.z1 + z1) / 2], 0],
    [w, h, [0, Math.PI, 0], [cx, h / 2, z1], 0],
  ];
  const pos = [], nor = [], kind = [], idx = [];
  const m = new Matrix4();
  for (const [qw, qh, rot, at, k] of quads) {
    const g = new PlaneGeometry(qw, qh);
    m.makeRotationFromEuler(new Euler(...rot)).setPosition(...at);
    g.applyMatrix4(m);
    const base = pos.length / 3;
    pos.push(...g.getAttribute("position").array);
    nor.push(...g.getAttribute("normal").array);
    for (let i = 0; i < g.getAttribute("position").count; i++) kind.push(k);
    idx.push(...Array.from(g.index.array, (i) => i + base));
    g.dispose();
  }
  const out = new BufferGeometry();
  out.setAttribute("position", new Float32BufferAttribute(pos, 3));
  out.setAttribute("normal", new Float32BufferAttribute(nor, 3));
  out.setAttribute("aKind", new Float32BufferAttribute(kind, 1));
  out.setIndex(idx);
  return out;
}

export async function buildRoom({ gl, tier, slice = true, shaftSteps = null }) {
  const [lime, terr] = await Promise.all([bakeLimeWash({ slice }), bakeTerrazzo({ slice })]);
  const sun = makeSun();
  sunAt("room", REST.room, sun);

  // Display targets for each surface in shade and in full sun. The floor's sun is the wall's,
  // a step darker (terrazzo under lime wash); the ceiling never faces the sun.
  const shade = [tok("wallShade"), tok("floorShade"), tok("ceiling")].map(vec);
  const sunTarget = [tok("wallSun"), tok("wallSun").multiplyScalar(0.8), tok("wallSun")].map(vec);
  const restColor = tok("sunRoom"), ndlRef = Math.max(sun.dir.z, 0.05);

  const L = sun.dir;
  const U = {
    ...apertureUniforms(SCREEN, L),
    ...capUniforms(),
    ...streetUniforms(),
    uShade: { value: shade },
    uSunT: { value: sunTarget.map((v) => v.clone()) },
    uNdlRef: { value: ndlRef },
    uGlare: { value: vec(tok("sky")) },
    uSunCol: { value: new Vector3() },
    uSunI: { value: 1 },
    uRimI: { value: 1 },
    uBoxMin: { value: new Vector3(...BOX.min) },
    uBoxMax: { value: new Vector3(...BOX.max) },
    uLime: { value: lime },
    uTerr: { value: terr },
    uFace: { value: tok("screenFace") },
    uGlow: { value: tok("sky") },
    uRim: { value: mixc(tok("wallSun"), tok("sky"), 0.3) },
    uWall: { value: mixc(tok("screenFace"), tok("sky"), 0.38) },
    uTime: { value: 0 },
    uLeafAmt: { value: 0 }, // the canopy, faded in by live.js after the handoff
    uWind: { value: new Vector3() },
    uStir: { value: 0 },
    uMotesA: { value: 1 },
    uPx: { value: 1 },
    // Motes add in display space (as in the proof), so their color is the sRGB value: sun-200 toward
    // the street light, warm enough to show on the cool wall and on a patch alike.
    uMoteCol: { value: vec(mixc(tok("sky"), tok("sun-200"), 0.6).convertLinearToSRGB()) },
  };

  const scene = new Scene();
  const shell = new Mesh(shellGeometry(), surfaceMaterial(U));
  let screen = screenMesh(U, SCREEN, tier);
  const street = streetPlane(U, { x: 9, z: -1 });
  const motes = createMotes(U, MOTE_BOX);
  scene.add(shell, screen, street, motes.points);
  const shafts = createShafts(gl.renderer, U, { box: BOX, tier, ortho: gl.ortho, steps: shaftSteps });
  const camera = new PerspectiveCamera(46, 1, 0.05, 60);

  const setTier = (t) => {
    if ((t >= 2) !== screen.isInstancedMesh) {
      scene.remove(screen);
      screen.geometry.dispose();
      screen.material.dispose();
      screen = screenMesh(U, SCREEN, t);
      scene.add(screen);
    }
    street.visible = t >= 2; // Tier 1 draws the street inside the screen shader
    motes.setTier(t);
    shafts.setTier(t);
  };
  setTier(tier);

  // nudge: pointer sun in degrees; intensity: the cloud (1 → 0.82). rims: the sunlit hole
  // walls (the poster's shaded base keeps them lit, so the light layer is the room only).
  const setSun = ({ t = REST.room, nudge = null, intensity = 1, rims = intensity } = {}) => {
    sunAt("room", t, sun, nudge);
    // The patch target follows the sun's color along the clock (it is the token at rest).
    const kr = sun.color.r / restColor.r, kg = sun.color.g / restColor.g, kb = sun.color.b / restColor.b;
    for (let i = 0; i < 3; i++) U.uSunT.value[i].set(sunTarget[i].x * kr, sunTarget[i].y * kg, sunTarget[i].z * kb);
    U.uSunCol.value.set(sun.color.r, sun.color.g, sun.color.b);
    U.uSunI.value = intensity;
    U.uRimI.value = rims;
  };
  setSun();

  // The bucket's shot on a frame of W × H (the poster's pixels; the live canvas shows the
  // same frame scaled into the hero box, see frame.js).
  const setShot = (bucket, W, H, s = SHOTS[bucket.name]) => {
    camera.position.set(...s.pos);
    camera.fov = s.fov;
    camera.aspect = W / H;
    camera.lookAt(...s.look);
    camera.clearViewOffset();
    camera.updateProjectionMatrix();
    camera.updateMatrixWorld();
  };

  return {
    scene, camera, U, shafts, motes, setTier, setSun, setShot,
    compile: async () => {
      await gl.renderer.compileAsync(scene, camera);
      for (const s of shafts.scenes) await gl.renderer.compileAsync(s, gl.ortho);
    },
    dispose() {
      scene.traverse((o) => { if (o.isMesh || o.isPoints) { o.geometry.dispose(); o.material.dispose(); } });
      shafts.dispose();
      lime.dispose();
      terr.dispose();
    },
  };
}
