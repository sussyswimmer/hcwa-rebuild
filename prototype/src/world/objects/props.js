import { BufferGeometry, DoubleSide, Float32BufferAttribute, Matrix4, Mesh, ShaderMaterial } from "three";
import { BLOCKS } from "../glsl/blocks.js";
import { NOISE } from "../glsl/noise.js";
import { aperturesGLSL } from "../glsl/apertures.js";
import { tok, vec } from "../colors.js";

// The corridor's life (05-DESIGN D0.6.2, ported from proposal saigon-home): a ceiling fan (quạt trần) in every bay
// but Little Rose's, turning lazily. No pots, plants or fallen flowers (D0.6 decision 1). Critique r2 cut the tamarind
// leaflets on every tier and from the stills (D0.6.2's ladder: at any size a visitor met them as a moth under the
// ceiling or a crumb on the tiles, never as a leaf); the fans, the sun shafts and the tamarinds' moving dapple carry
// the life. One merged geometry, one draw. The fans turn in the vertex shader from one uniform (uFanA): no per-frame
// JS, no allocation. Lit by the same analytic sun as the shell, so the screen's holes shade them. Little Rose's bay
// gets nothing: no fan, no fan shadow (test/little-rose.test.mjs).

export const FAN = { x: 99.75, y: 2.45, dz: -1.6, r: 0.7, hub: 0.13, blade: [0.14, 0.7, 0.0625], pitch: 0.16, omega: 2.1, ceiling: 3.1, rod: 0.6 };
export const KIND = { solid: 0, fan: 1 };

function rng(seed) {
  let s = seed >>> 0;
  return () => ((s = (s + 0x6d2b79f5) >>> 0), (((s ^ (s >>> 15)) * (s | 1)) >>> 0) / 4294967296);
}

class Builder {
  constructor() { this.pos = []; this.nor = []; this.uv = []; this.col = []; this.piv = []; this.k = []; this.idx = []; }
  add(g, m, { col, kind = KIND.solid, pivot = [0, 0, 0], seed = 0, gloss = 0 }) {
    if (m) g.applyMatrix4(m);
    const p = g.getAttribute("position"), n = g.getAttribute("normal"), u = g.getAttribute("uv"), base = this.pos.length / 3;
    for (let i = 0; i < p.count; i++) {
      this.pos.push(p.getX(i), p.getY(i), p.getZ(i));
      this.nor.push(n.getX(i), n.getY(i), n.getZ(i));
      this.uv.push(u ? u.getX(i) : 0, u ? u.getY(i) : 0);
      this.col.push(col.r, col.g, col.b);
      this.piv.push(...pivot);
      this.k.push(kind, seed, gloss, 0);
    }
    const ix = g.index ? Array.from(g.index.array) : [...Array(p.count).keys()];
    for (const i of ix) this.idx.push(i + base);
    g.dispose();
  }
  geometry() {
    const g = new BufferGeometry();
    g.setAttribute("position", new Float32BufferAttribute(this.pos, 3));
    g.setAttribute("normal", new Float32BufferAttribute(this.nor, 3));
    g.setAttribute("uv", new Float32BufferAttribute(this.uv, 2));
    g.setAttribute("aCol", new Float32BufferAttribute(this.col, 3));
    g.setAttribute("aPivot", new Float32BufferAttribute(this.piv, 3));
    g.setAttribute("aK", new Float32BufferAttribute(this.k, 4));
    g.setIndex(this.idx);
    g.computeBoundingSphere();
    return g;
  }
}

const M = () => new Matrix4();
const at = (x, y, z) => M().makeTranslation(x, y, z);

// A blade in the XY plane along +x from r0 to r1, its tip rounded: a strip of quads and a fan of 5 for the tip
// (built by hand: three's ShapeGeometry would bring its triangulator into the world's chunk).
function bladeGeometry() {
  const [r0, r1, hw] = FAN.blade, c = r1 - hw, pos = [], idx = [];
  const ring = [[r0, -hw * 0.7], [c, -hw]];
  for (let k = 1; k < 6; k++) { const a = -Math.PI / 2 + (k * Math.PI) / 6; ring.push([c + hw * Math.cos(a), hw * Math.sin(a)]); }
  ring.push([c, hw], [r0, hw * 0.7]);
  for (const [x, y] of ring) pos.push(x, y, 0);
  // fan from the root's middle
  pos.push(r0, 0, 0);
  const m = ring.length;
  for (let i = 0; i < m - 1; i++) idx.push(m, i, i + 1);
  idx.push(m, m - 1, 0);
  const g = new BufferGeometry();
  g.setAttribute("position", new Float32BufferAttribute(pos, 3));
  g.setAttribute("normal", new Float32BufferAttribute(Array.from({ length: m + 1 }, () => [0, 0, 1]).flat(), 3));
  g.setIndex(idx);
  return g;
}

// A profile [[r, y], …] turned about +y in seg steps (three's Lathe and Cylinder geometries, by hand: the
// world's chunk carries neither).
function lathe(profile, seg) {
  const pos = [], idx = [], n = profile.length;
  for (let i = 0; i <= seg; i++) {
    const a = (i / seg) * Math.PI * 2, c = Math.cos(a), s = Math.sin(a);
    for (const [r, y] of profile) pos.push(r * s, y, r * c);
  }
  for (let i = 0; i < seg; i++) for (let j = 0; j < n - 1; j++) {
    const a = i * n + j, b = a + n;
    idx.push(a, b, a + 1, b, b + 1, a + 1);
  }
  const g = new BufferGeometry();
  g.setAttribute("position", new Float32BufferAttribute(pos, 3));
  g.setIndex(idx);
  g.computeVertexNormals();
  return g;
}
const tube = (r0, r1, h, seg) => lathe([[r0, -h / 2], [r1, h / 2]], seg);

// A ceiling fan hung at (x, z): canopy, a 0.6 m downrod, a 16-segment motor housing in jade, three pitched
// cream blades on jade arms, turning about the rod (kind fan, pivot on its axis).
function fan(b, x, z, seed) {
  const body = tok("tile-jade"), cream = tok("tile-cream"), C = FAN.ceiling, pivot = [x, FAN.y, z];
  b.add(lathe([[0, -0.025], [0.085, -0.025], [0.075, 0.025], [0, 0.025]], 16), at(x, C - 0.025, z), { col: body, gloss: 0.5 });
  const top = C - 0.05, foot = FAN.y + 0.075;
  b.add(tube(0.013, 0.013, top - foot, 6), at(x, (top + foot) / 2, z), { col: body });
  const motor = lathe([[0, -0.07], [0.07, -0.066], [0.125, -0.035], [0.135, 0.005], [0.115, 0.05], [0.05, 0.075], [0, 0.08]], 16);
  b.add(motor, at(x, FAN.y, z), { col: body, kind: KIND.fan, pivot, seed, gloss: 0.7 });
  for (let k = 0; k < 3; k++) {
    const turn = M().makeRotationY((k * 2 * Math.PI) / 3);
    const blade = bladeGeometry();
    b.add(blade, at(x, FAN.y - 0.01, z).multiply(turn).multiply(M().makeRotationX(-Math.PI / 2 + FAN.pitch)), { col: cream, kind: KIND.fan, pivot, seed, gloss: 0.25 });
    const arm = tube(0.012, 0.012, 0.12, 5).rotateZ(Math.PI / 2).translate(0.13, -0.004, 0);
    b.add(arm, at(x, FAN.y - 0.01, z).multiply(turn), { col: body, kind: KIND.fan, pivot, seed });
  }
}

// The corridor's props for a layout (journey.js corridorLayout). C: CORRIDOR.
export function buildProps({ lay }) {
  const b = new Builder(), r = rng(29);
  const bays = lay.doors.filter((d) => d.unit !== "little-rose");
  const fans = bays.map((d) => ({ z: d.zc + FAN.dz, seed: r() * 6.283 }));
  for (const f of fans) fan(b, FAN.x, f.z, f.seed);
  return { geometry: b.geometry(), fans };
}

// The fans as one occluder for the sun (glsl/apertures.js pattern "fans", Tier 2 and the stills): their
// centres in the aperture's uv (metres along −z from the corridor's start, metres from the screen), a phase
// each; uFanA turns them all. The same blades as the meshes (FAN.blade), so the shadow is the fan's own.
export const fansGLSL = (n) => /* glsl */ `
uniform vec4 uFans[${Math.max(n, 1)}];
uniform float uFanA;
float fanBlades(vec2 uv){
float d = 1e3;
for (int i = 0; i < ${Math.max(n, 1)}; i++) {
vec4 F = uFans[i];
if (F.w < .5) continue;
vec2 q = uv - F.xy;
float r = length(q);
if (r > ${(FAN.r + 0.05).toFixed(3)}) continue;
float a = atan(q.y, q.x) + uFanA + F.z - 1.5707963;
float s = 2.0943951, k = a - s * floor(a / s + .5);
vec2 bq = r * vec2(cos(k), sin(k));
d = min(d, sdBox2(bq - vec2(${((FAN.blade[0] + FAN.blade[1]) / 2).toFixed(3)}, 0.), vec2(${((FAN.blade[1] - FAN.blade[0]) / 2).toFixed(3)}, ${FAN.blade[2].toFixed(4)})));
d = min(d, r - ${FAN.hub.toFixed(3)});
}
return d;
}
`;

export function fanAperture({ C, lay }) {
  return { mode: "occluder", pattern: "fans", corner: [C.screenX, FAN.y, lay.start], u: [0, 0, -1], v: [1, 0, 0], size: [lay.start - lay.end, C.wallX - C.screenX], depth: 0, thin: true };
}

export function propsMaterial(U, lights, own) {
  return new ShaderMaterial({
    uniforms: { ...U, ...own },
    side: DoubleSide,
    vertexShader: /* glsl */ `
attribute vec3 aCol, aPivot; attribute vec4 aK;
uniform float uFanA;
varying vec3 vP, vN, vCol; varying vec4 vK;
vec3 rotY(vec3 v, float a){ float c = cos(a), s = sin(a); return vec3(c * v.x + s * v.z, v.y, -s * v.x + c * v.z); }
void main(){
vec3 p = position, n = normal;
if (aK.x > .5) { float a = uFanA + aK.y; p = aPivot + rotY(p - aPivot, a); n = rotY(n, a); }
vec4 w = modelMatrix * vec4(p, 1.);
vP = w.xyz; vN = normalize(mat3(modelMatrix) * n); vCol = aCol; vK = aK;
gl_Position = projectionMatrix * viewMatrix * w;
}`,
    fragmentShader: /* glsl */ `
varying vec3 vP, vN, vCol; varying vec4 vK;
uniform vec3 uCool, uSunTint; uniform float uShadeK, uRef[2], uI[2];
${BLOCKS}
${NOISE}
${aperturesGLSL(lights)}
void main(){
vec3 N = normalize(vN);
if (!gl_FrontFacing) N = -N;
float ndl = dot(N, uLt[0]);
float f0 = ndl > 0. && uI[0] > 0. ? min(ndl / uRef[0], 1.4) * lightMask0(vP) * uI[0] : 0.;
float sky = .8 + .2 * N.y;
vec3 shade = mix(vCol * uShadeK, uCool * uShadeK, .12) * sky;
vec3 col = mix(shade, vCol * uSunTint, min(f0, 1.));
vec3 V = normalize(cameraPosition - vP), R = reflect(-uLt[0], N);
col += uSunTint * pow(max(dot(R, V), 0.), 24.) * vK.z * min(f0, 1.) * .5;
gl_FragColor = vec4(col, 1.);
#include <colorspace_fragment>
}`,
    toneMapped: false,
  });
}

// sun: the lit tint (sets/corridor.js setSun keeps it on the clock).
export function propsMesh(U, lights, props, sun) {
  const mat = propsMaterial(U, lights, {
    uShadeK: { value: 0.62 }, uCool: { value: vec(tok("ceilingWarm")) }, uSunTint: { value: vec(sun) },
  });
  const mesh = new Mesh(props.geometry, mat);
  // Drawn before the shell, so the plaster behind a blade is rejected by depth.
  mesh.renderOrder = -1;
  mesh.frustumCulled = false; // the blades move in the vertex shader
  return mesh;
}
