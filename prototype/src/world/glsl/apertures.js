import { Vector3, Vector4 } from "three";
import { BLOCK } from "./blocks.js";

// The analytic sun of aperture.js (05-DESIGN D6.1) for sets with more than one opening: any number of
// rectangles in any plane, each with a hole pattern, so a transom, a doorway, a louvered window, a
// shutter leaf or a canopy throw light with the same maths as the screen. For a point P, each light
// traces toward its source and asks every rectangle on the way: two tests at the faces of a `depth`
// thick panel (thinning at grazing angles) and a penumbra that grows with distance at the sun's size.
//   window:   light passes only through the rectangle's holes (a wall with an opening). A light
//             with several windows takes the brightest: it enters through any of them.
//   occluder: light passes around the rectangle and through its holes (a leaf, a canopy).
// Rectangles are given by a corner C and two unit axes U, V; uv is metres from C along them. A window
// with `feather` (metres) fades out at its edges instead of cutting: a long screen whose light thins out
// past the frame, not a projected slide. `penumbra` scales the sun's size for that aperture: a still that
// must read the pattern on a wall several metres from its screen (the hub's corridor) keeps it crisp
// (D6.4) where the true sun would blur it; the live sets keep 1.

// Hole patterns: signed distance in metres, negative where light passes. p: per-aperture params.
const PATTERNS = {
  hoaThi: "return sdHoaThi(mod(uv, B) - 0.5 * B);",
  // Only the block at p.xy (column, row) lets light through: the last ray of the day.
  hoaThiOne: "vec2 id = floor(uv / B); return all(equal(id, p.xy)) ? sdHoaThi(mod(uv, B) - 0.5 * B) : 1.;",
  // The block at p.xy is gone: a square opening (the 404).
  hoaThiMissing: "vec2 c = mod(uv, B) - 0.5 * B; float d = sdHoaThi(c); return all(equal(floor(uv / B), p.xy)) ? min(d, sdBox2(c, vec2(0.5 * B - 0.004))) : d;",
  dotTre: "return sdDotTre(mod(uv, B) - 0.5 * B);",
  vomCau: "return sdVomCau(mod(uv, B) - 0.5 * B);",
  open: "return -1.;",
  solid: "return 1.;",
  // Louver gaps along V: p.x pitch, p.y open fraction.
  slats: "float h = 0.5 * p.x; return abs(mod(uv.y, p.x) - h) - h * p.y;",
  // Tamarind leaves: value noise at p.x per metre, light where it falls under p.y, shifted by p.zw.
  // Octaves turned against each other so the value-noise grid never shows as square leaves.
  canopy: "vec2 q = mat2(.8, -.6, .6, .8) * uv * p.x; float n = 0.62 * fbm(q + p.zw) + 0.38 * vn(mat2(.5, .87, -.87, .5) * q * 3.7 + p.wz); return (n - p.y) * 0.5 / p.x;",
};

export const MAX_APERTURES = 8;

// lights: [{ apertures: [{ mode, pattern }], glsl?, mask?, factor? }] in index order across all lights.
// Writes `float lightMask<i>(vec3 P)` per light. Needs BLOCKS and NOISE before it.
//   glsl:   extra functions the light needs (declared before its mask)
//   mask:   a GLSL expression in P and L that replaces the apertures (the corridor's row of doors: one
//           test whatever the number of doors, glsl/doors.js)
//   factor: a GLSL expression in P multiplied in (Little Rose's bay takes no corridor sun)
export function aperturesGLSL(lights) {
  let k = 0;
  const fns = [], masks = [];
  for (const [li, light] of lights.entries()) {
    if (light.glsl) fns.push(light.glsl);
    if (light.mask) {
      masks.push(`float lightMask${li}(vec3 P){ vec3 L = uLt[${li}]; return (${light.mask})${light.factor ? ` * (${light.factor})` : ""}; }`);
      continue;
    }
    const windows = [], occluders = [];
    for (const a of light.apertures) {
      const i = k++;
      fns.push(`float hole${i}(vec2 uv, vec4 p){ ${PATTERNS[a.pattern]} }
float ap${i}(vec3 P, vec3 L){
  vec3 C = uApC[${i}], U = uApU[${i}], V = uApV[${i}], N = cross(U, V);
  float ln = dot(L, N), pn = dot(P - C, N), side = sign(ln);
  if (abs(ln) < 1e-4 || pn * side > -0.001) return 1.;
  vec2 size = uApS[${i}].xy;
  float m = 1.;
  for (int f = 0; f < 2; f++) {
    float t = (float(f) * uApS[${i}].z * side - pn) / ln;
    vec3 Q = P + L * t;
    vec2 uv = vec2(dot(Q - C, U), dot(Q - C, V));
    float w = 0.0025 + 0.0093 * max(t, 0.) * ${a.penumbra != null ? (0.6 * a.penumbra).toFixed(4) : "0.6"};
    float rect = sdBox2(uv - 0.5 * size, 0.5 * size);
    float h = hole${i}(uv, uApP[${i}]);
    ${!a.feather ? `m *= smoothstep(w, -w, ${a.mode === "occluder" ? "min(-rect, h)" : "max(rect, h)"});`
      : a.mode === "occluder" ? `m *= mix(1., smoothstep(w, -w, h), smoothstep(uApS[${i}].w, -uApS[${i}].w, rect));`
      : `m *= smoothstep(w, -w, h) * smoothstep(uApS[${i}].w, -uApS[${i}].w, rect);`}
  }
  return m;
}`);
      (a.mode === "occluder" ? occluders : windows).push(`ap${i}(P, L)`);
    }
    const win = windows.length ? windows.reduce((acc, w) => `max(${acc}, ${w})`) : "1.";
    masks.push(`float lightMask${li}(vec3 P){ vec3 L = uLt[${li}]; return ${[win, ...occluders, ...(light.factor ? [`(${light.factor})`] : [])].join(" * ")}; }`);
  }
  return /* glsl */ `
#define B ${BLOCK.toFixed(4)}
uniform vec3 uApC[${MAX_APERTURES}], uApU[${MAX_APERTURES}], uApV[${MAX_APERTURES}];
uniform vec4 uApS[${MAX_APERTURES}], uApP[${MAX_APERTURES}];
uniform vec3 uLt[2];
${fns.join("\n")}
${masks.join("\n")}
#undef B
`;
}

// Uniform values for the apertures in the same order: { corner, u, v, size: [w, h], depth, params }.
export function apertureUniforms(lights, dirs) {
  const fill = (f) => Array.from({ length: MAX_APERTURES }, f);
  const U = {
    uApC: { value: fill(() => new Vector3()) },
    uApU: { value: fill(() => new Vector3(1, 0, 0)) },
    uApV: { value: fill(() => new Vector3(0, 1, 0)) },
    uApS: { value: fill(() => new Vector4()) },
    uApP: { value: fill(() => new Vector4()) },
    uLt: { value: dirs },
  };
  let k = 0;
  for (const light of lights) for (const a of light.apertures ?? []) {
    const i = k++;
    U.uApC.value[i].set(...a.corner);
    U.uApU.value[i].set(...a.u).normalize();
    U.uApV.value[i].set(...a.v).normalize();
    U.uApS.value[i].set(a.size[0], a.size[1], a.depth ?? 0, a.feather ?? 0);
    U.uApP.value[i].set(...(a.params ?? [0, 0, 0, 0]));
  }
  if (k > MAX_APERTURES) throw new Error(`apertures: ${k} > ${MAX_APERTURES}`);
  return U;
}

// The four corners of an aperture in world space (for framing and the light-layer split).
export function apertureCorners(a) {
  const C = new Vector3(...a.corner), U = new Vector3(...a.u).normalize(), V = new Vector3(...a.v).normalize();
  const [w, h] = a.size;
  return [C.clone(), C.clone().addScaledVector(U, w), C.clone().addScaledVector(U, w).addScaledVector(V, h), C.clone().addScaledVector(V, h)];
}
