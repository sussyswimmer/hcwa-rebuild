import { BufferGeometry, DoubleSide, Float32BufferAttribute, Matrix4, Mesh, PlaneGeometry, ShaderMaterial } from "three";
import { BLOCKS } from "../glsl/blocks.js";
import { NOISE } from "../glsl/noise.js";
import { aperturesGLSL } from "../glsl/apertures.js";
import { mixc, tok, vec } from "../colors.js";

// The corridor's life (05-DESIGN D0.6.2, ported from proposal saigon-home, fans and leaflets only): a ceiling
// fan (quạt trần) in every bay but Little Rose's, turning lazily, and tamarind leaflets (lá me) drifting down
// through the sun from the screen's top course. No pots, plants or fallen flowers (D0.6 decision 1).
// One merged geometry, one draw. Everything moves in the vertex shader from two uniforms (uTime, uFanA): no
// per-frame JS, no allocation. Lit by the same analytic sun as the shell, so the screen's holes shade them.
// Little Rose's bay gets nothing: no fan, no fan shadow, no leaflet spawn and no drift path within LEAFLET.clear
// of it (test/little-rose.test.mjs).

export const FAN = { x: 99.75, y: 2.45, dz: -1.6, r: 0.7, hub: 0.13, blade: [0.14, 0.7, 0.0625], pitch: 0.16, omega: 2.1, ceiling: 3.1, rod: 0.6 };
// Critique r1 (fix round 1): at 34 × 13 mm and 32 in the air they read as crumbs. Now drawn at twice that, a
// tapered tamarind leaflet with a midrib, in jade warmed by the sun (it separates from the yellow wash), glinting
// only when its face is to the sun; Tier 2 only. Each door's beat gets two, released where their whole drift
// passes `near` metres ahead of the camera's reading spot (journey.js VIEW.d before the door), so they read as
// leaves (10–20 px) and never as specks down the corridor. An 18 s cycle: 15 s drifting down and across, 3 s at
// rest on the tiles, the last of it fading.
export const LEAFLET = { size: [0.068, 0.026], cycle: 18, drift: 15, clear: 0.8, sway: 0.3, across: [1.1, 0.7], top: [2.75, 0.25], read: 4.4, near: [1.7, 2.6], perBeat: 2 };
export const KIND = { solid: 0, fan: 1, leaflet: 2, rest: 3 };

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

// Where leaflets may be released along z: the lead-in and every bay but Little Rose's, each run kept
// LEAFLET.clear + LEAFLET.sway away from its bay so neither the spawn nor the drift path comes near it.
export function leafletRuns(lay) {
  const lr = lay.doors.find((d) => d.unit === "little-rose")?.bay ?? null;
  const pad = LEAFLET.clear + LEAFLET.sway;
  const raw = [[0.4, lay.start - 0.6], ...lay.doors.filter((d) => d.unit !== "little-rose").map((d) => [d.bay[0] + 0.5, d.bay[1] - 0.5])];
  const out = [];
  for (const [z0, z1] of raw) {
    if (!lr) { out.push([z0, z1]); continue; }
    const cut0 = lr[0] - pad, cut1 = lr[1] + pad;
    if (z1 <= cut0 || z0 >= cut1) { out.push([z0, z1]); continue; }
    if (z0 < cut0) out.push([z0, cut0]);
    if (z1 > cut1) out.push([cut1, z1]);
  }
  return out.filter(([a, b]) => b - a > 0.3);
}

// The corridor's props for a layout (journey.js corridorLayout). C: CORRIDOR. leaflets: at most how many drift
// (two a beat; 0 on Tier 1, sets/corridor.js LEAFLETS), or "rest" for the stills (a few lying on the tiles, none
// in the air).
export function buildProps({ C, lay, leaflets = 32 }) {
  const b = new Builder(), r = rng(29);
  const bays = lay.doors.filter((d) => d.unit !== "little-rose");
  const fans = bays.map((d) => ({ z: d.zc + FAN.dz, seed: r() * 6.283 }));
  for (const f of fans) fan(b, FAN.x, f.z, f.seed);
  const leaf = mixc(tok("tile-jade"), tok("sun-400"), 0.25);
  const runs = leafletRuns(lay);
  if (leaflets === "rest") {
    // Stills: two lying on the tiles in each open bay and one in the lead-in, on the plain band and the
    // runner's sunny side, turned any way (≤ 6 in any frame).
    const spots = [];
    for (const [a, c] of runs) for (let i = 0; i < (c - a > 3 ? 2 : 1); i++) spots.push(a + (c - a) * (0.25 + 0.5 * r()));
    for (const z of spots) {
      const x = C.screenX + 1.2 + 0.9 * r();
      const g = new PlaneGeometry(...LEAFLET.size).rotateX(-Math.PI / 2).rotateY(r() * Math.PI);
      b.add(g, at(x, 0.003, z), { col: leaf.clone().multiplyScalar(0.85 + 0.3 * r()), kind: KIND.rest, pivot: [x, 0, z], seed: r() });
    }
  } else if (leaflets > 0) {
    // Two a beat (at most `leaflets` in all), each released inside its door's near window where that window is
    // in a run (never in or near Little Rose's bay, whose own beat has none).
    let left = leaflets;
    for (const d of lay.doors) {
      const w0 = d.zc + LEAFLET.read - LEAFLET.near[1], w1 = d.zc + LEAFLET.read - LEAFLET.near[0];
      const spans = runs.map(([a, c]) => [Math.max(a, w0), Math.min(c, w1)]).filter(([a, c]) => c - a > 0.05);
      for (let k = 0; k < LEAFLET.perBeat && spans.length && left > 0; k++, left--) {
        const [a, c] = spans[k % spans.length], z = a + (c - a) * ((k + 0.2 + 0.6 * r()) / LEAFLET.perBeat);
        const x = C.screenX + 0.05 + 0.2 * r(), y = LEAFLET.top[0] + LEAFLET.top[1] * r();
        const g = new PlaneGeometry(...LEAFLET.size).rotateX(-Math.PI / 2);
        // Seeds half a cycle apart: one of the two is always in the air while the beat is read.
        b.add(g, null, { col: leaf.clone().multiplyScalar(0.9 + 0.2 * r()), kind: KIND.leaflet, pivot: [x, y, z], seed: (k * 0.5 + 0.3 * r()) % 1 });
      }
    }
  }
  return { geometry: b.geometry(), fans, runs };
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
  const L = LEAFLET, rest = (L.cycle - L.drift) / L.cycle;
  return new ShaderMaterial({
    uniforms: { ...U, ...own },
    side: DoubleSide,
    vertexShader: /* glsl */ `
attribute vec3 aCol, aPivot; attribute vec4 aK;
uniform float uTime, uFanA, uLeafFade;
varying vec3 vP, vN, vCol; varying vec2 vUv; varying vec4 vK; varying float vFade;
vec3 rotY(vec3 v, float a){ float c = cos(a), s = sin(a); return vec3(c * v.x + s * v.z, v.y, -s * v.x + c * v.z); }
vec3 rotX(vec3 v, float a){ float c = cos(a), s = sin(a); return vec3(v.x, c * v.y - s * v.z, s * v.y + c * v.z); }
void main(){
vec3 p = position, n = normal; float kind = aK.x; vFade = 1.;
if (kind > .5 && kind < 1.5) {
float a = uFanA + aK.y;
p = aPivot + rotY(p - aPivot, a); n = rotY(n, a);
} else if (kind > 1.5 && kind < 2.5) {
float ph = fract(uTime / ${L.cycle.toFixed(1)} + aK.y), f = min(ph / ${(1 - rest).toFixed(4)}, 1.), sd = aK.y * 61.;
float fall = f * (.65 + .35 * f);
vec3 c = aPivot + vec3((${L.across[0].toFixed(2)} + ${L.across[1].toFixed(2)} * fract(aK.y * 7.3)) * f + .06 * sin(f * 23. + sd) * (1. - f),
(.006 - aPivot.y) * fall,
${(L.sway * 0.9).toFixed(3)} * sin(f * 7. + sd) * (1. - .3 * f));
float air = 1. - step(1., f);
float a1 = (f * 38. + sd) * air + sd, a2 = f * 27. + sd * .7;
p = c + rotY(rotX(position * uLeafFade, a2 * air), a1); n = rotY(rotX(normal, a2 * air), a1);
vFade = smoothstep(0., .02, ph) * (1. - smoothstep(1. - ${(rest * 0.45).toFixed(4)}, 1., ph)) * uLeafFade;
}
vec4 w = modelMatrix * vec4(p, 1.);
vP = w.xyz; vN = normalize(mat3(modelMatrix) * n); vCol = aCol; vUv = uv; vK = aK;
gl_Position = projectionMatrix * viewMatrix * w;
}`,
    fragmentShader: /* glsl */ `
varying vec3 vP, vN, vCol; varying vec2 vUv; varying vec4 vK; varying float vFade;
uniform vec3 uCool, uSunTint; uniform float uShadeK, uRef[2], uI[2];
${BLOCKS}
${NOISE}
${aperturesGLSL(lights)}
void main(){
float kind = vK.x;
vec2 q = vUv * 2. - 1.;
vec3 albedo = vCol;
if (kind > 1.5) {
// A tamarind leaflet: a blunt base, tapering to its tip (+x), a darker midrib along it.
float hw = sqrt(max(1. - q.x * q.x, 0.)) * (1. - .5 * max(q.x, 0.));
if (abs(q.y) > hw || h21(gl_FragCoord.xy) > vFade) discard;
albedo *= 1. - .24 * (1. - smoothstep(.07, .16, abs(q.y))) * step(q.x, .82);
}
vec3 N = normalize(vN);
if (!gl_FrontFacing) N = -N;
float ndl = dot(N, uLt[0]), thin = step(1.5, kind);
float n0 = mix(max(ndl, 0.), abs(ndl), thin);
float f0 = n0 > 0. && uI[0] > 0. ? min(n0 / uRef[0], 1.4) * lightMask0(vP) * uI[0] : 0.;
float sky = .8 + .2 * N.y;
vec3 shade = mix(albedo * uShadeK, uCool * uShadeK, .12) * sky;
vec3 col = mix(shade, albedo * uSunTint, min(f0, 1.));
vec3 V = normalize(cameraPosition - vP), R = reflect(-uLt[0], N);
// A leaflet glints only while the face it shows is to the sun (never its shaded underside).
float gloss = kind < 1.5 ? vK.z : .8 * step(0., ndl);
col += uSunTint * pow(max(dot(R, V), 0.), 24.) * gloss * min(f0, 1.) * .5;
gl_FragColor = vec4(col, 1.);
#include <colorspace_fragment>
}`,
    toneMapped: false,
  });
}

// sun: the lit tint (sets/corridor.js setSun keeps it on the clock).
export function propsMesh(U, lights, props, sun) {
  const mat = propsMaterial(U, lights, {
    uTime: { value: 0 }, uLeafFade: { value: 1 }, uShadeK: { value: 0.62 }, uCool: { value: vec(tok("ceilingWarm")) }, uSunTint: { value: vec(sun) },
  });
  const mesh = new Mesh(props.geometry, mat);
  // Drawn before the shell, so the plaster and tiles behind a blade or a leaflet are rejected by depth.
  mesh.renderOrder = -1;
  mesh.frustumCulled = false; // leaflets and blades move in the vertex shader
  return mesh;
}
