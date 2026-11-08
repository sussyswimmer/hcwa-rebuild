// Cheap hash and value noise, ported from docs/design/concepts/open/proof-hero.html.
// Per-pixel fbm is only used where a baked texture cannot reach (the street canopy).
export const NOISE = /* glsl */ `
float h21(vec2 p){ p = fract(p * vec2(123.34, 456.21)); p += dot(p, p + 45.32); return fract(p.x * p.y); }
float vn(vec2 p){ vec2 i = floor(p), f = fract(p); f = f * f * (3. - 2. * f);
  return mix(mix(h21(i), h21(i + vec2(1, 0)), f.x), mix(h21(i + vec2(0, 1)), h21(i + vec2(1, 1)), f.x), f.y); }
float fbm(vec2 p){ float s = 0., a = .5; for (int i = 0; i < 4; i++) { s += a * vn(p); p *= 2.03; a *= .5; } return s; }
vec2 h22(vec2 p){ return fract(sin(vec2(dot(p, vec2(127.1, 311.7)), dot(p, vec2(269.5, 183.3)))) * 43758.5453); }
`;

// Display transform shared by passes that write outside three's tone-mapping includes
// (shafts into a render target). Same curve as THREE.NeutralToneMapping.
export const DISPLAY = /* glsl */ `
vec3 neutralTM(vec3 c, float exposure){
  c *= exposure;
  float x = min(c.r, min(c.g, c.b));
  float off = x < 0.08 ? x - 6.25 * x * x : 0.04;
  c -= off;
  float peak = max(c.r, max(c.g, c.b));
  if (peak < 0.76) return c;
  float d = 0.24;
  float np = 1. - d * d / (peak + d - 0.76);
  c *= np / peak;
  float g = 1. - 1. / (0.15 * (peak - np) + 1.);
  return mix(c, vec3(np), g);
}
vec3 toSRGB(vec3 c){ c = max(c, 0.); return mix(c * 12.92, 1.055 * pow(c, vec3(1. / 2.4)) - 0.055, step(0.0031308, c)); }
`;

// The tamarind canopy beyond the screen moving the light (open.md S1): value noise on the screen
// plane, read where the ray toward the sun crosses it, so a whole patch dims and brightens together
// and its beam with it. Leaves cross a patch in about 1.5-2 s, between 42% light and a little glare
// (1.1) where they part. uLeafAmt fades the canopy in after the handoff (live.js): the stills and the
// first live frame are the clear light, so the handoff is exact and the light arrives at full
// strength. `time` stops while paused and under reduced motion, so does the canopy. Needs NOISE and
// APERTURE.
export const LEAF = /* glsl */ `
uniform float uLeafAmt;
float leaf(vec3 P, float time){
  if (uLeafAmt <= 0.) return 1.;
  vec2 q = (P + uL * ((uSX - P.x) / uL.x)).zy;
  // Two layers of leaves drifting apart: a phone frame that holds only a few blocks' patches still
  // spans several cells, so its light never sits on one plateau for seconds.
  float n = 0.5 * vn(q * 1.2 + vec2(time * 0.55, sin(time * 0.6) * 0.5)) + 0.5 * vn(q * 3.6 + vec2(-time * 0.9, time * 0.4 + 7.3));
  return mix(1., 0.42 + 0.68 * smoothstep(0.36, 0.64, n), uLeafAmt);
}
`;

// Half a display level of noise on the output (sRGB): long soft gradients (the halation round a hole, the
// wash clearing over the scene, the quatrefoil vignette) otherwise step into visible rings at 8 bits.
// Interleaved gradient noise: no texture, no dependency.
export const DITHER = /* glsl */ `
float ign(vec2 p){ return fract(52.9829189 * fract(dot(p, vec2(0.06711056, 0.00583715)))); }
#define DITHER_OUT gl_FragColor.rgb += (ign(gl_FragCoord.xy) - .5) / 255.
`;
