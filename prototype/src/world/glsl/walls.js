// Shading with the Blender wall maps (walls.js; packed by walls-pack.js: rg normal, b tone relief / 2, a chips or cracks).
// The world keeps its display-target model: the maps never set a colour, they set relief around the house tokens.
//   tone   wallRel(): the albedo's luminance over its mean, darker freely, lighter by half and never past +5%, so a
//          sun patch keeps its headroom (surface.js: a channel clipping at 255 turns sun-200 lemon)
//   sun    wallSun(): how much more or less a texel faces the light than its wall, on the light's own term, so the
//          cracks, chips and brush streaks catch a grazing sun; bounded, so a patch never glares from relief alone
//   shade  wallFill(): the same against the sky's fill, softer
// Walls are axis-aligned: the tangent basis follows the surface's own uv (x-facing: z, y; z-facing: x, y; floors and
// ceilings: x, z). On Tier 2 (uWallMix) a second sample, turned 34° at 0.73 the scale, is blended in where `w` (the
// lime texture's broad mottle) says, so no crack repeats on the 2 m grid. uWallK fades all of it in (0: procedural).
// wallAge(): where the wall has aged (the lime texture's metre-wide loang, A, 4 m a tile): stains and chips read at
// full strength there and at a third elsewhere; cracks only where it is well aged (below).
// keys: the materials a shader samples (walls.js WALL_KEYS values).
export const WALLS_GLSL = (keys) => /* glsl */ `
uniform float uWallK, uWallMix;
${keys.map((k) => `uniform sampler2D uW${k};`).join("\n")}
vec4 wallTap(sampler2D t, vec2 uv, float w){
  vec4 a = texture2D(t, uv);
  vec4 s = vec4(a.rg * 2. - 1., a.b * 2., a.a);
  if (uWallMix > 0.) {
    vec4 b = texture2D(t, mat2(.829, .559, -.559, .829) * uv * 1.37 + vec2(.31, .17));
    vec2 n2 = b.rg * 2. - 1.;
    s = mix(s, vec4(.829 * n2.x + .559 * n2.y, -.559 * n2.x + .829 * n2.y, b.b * 2., b.a), w * uWallMix);
  }
  return s;
}
// The relief normal on an axis-aligned surface (N: its normal), at strength k.
vec3 wallN(vec3 N, vec2 n, float k){
  vec3 T = abs(N.x) > .5 ? vec3(0., 0., 1.) : vec3(1., 0., 0.);
  vec3 B = abs(N.y) > .5 ? vec3(0., 0., 1.) : vec3(0., 1., 0.);
  n *= k;
  return normalize(N * sqrt(max(1. - dot(n, n), .04)) + T * n.x + B * n.y);
}
// Any basis (a panel's u, v; a door's derivative frame).
vec3 wallNB(vec3 N, vec3 T, vec3 B, vec2 n, float k){ n *= k; return normalize(N * sqrt(max(1. - dot(n, n), .04)) + T * n.x + B * n.y); }
float wallAge(float a){ return mix(.3, 1., smoothstep(.3, .72, a)); }
// Cracks. A map has few, and a 2 m map repeats, so a crack seen in every repeat reads as a stamp. They come in only
// where a gate says (0..1), and the gate is non-periodic (wallCrackGate()).
// wallRelC(): the tone relief split at 10% darker: above it the mottle, laps and stains (k), below it the cracks and
// deep pits (kc). wallRel(): a constant k (paint, blocks), its cracks in only from k 0.82 up.
float wallRelC(float r, float k, float kc){ float d = r - 1., m = max(d, -.1); d = m * k + (d - m) * kc; return 1. + min(d, min(.5 * d, .05)); }
float wallCrack(float k){ return smoothstep(.82, 1., k); }
float wallRel(float r, float k){ return wallRelC(r, k, k * wallCrack(k)); }
// Plaster and lime wash carry a crack mask in A (walls-pack.js: the crack's core and the 8 texels of lips its normal
// gives it): tone and relief under the mask take the gate g, so a crack left out leaves no trace, not even its lit lip.
float wallRelM(vec4 w, float k, float g){ float d = w.b - 1.; d *= k * mix(1., g, w.a); return 1. + min(d, min(.5 * d, .05)); }
vec2 wallCrackN(vec4 w, float g){ return w.rg * mix(1., g, w.a); }
// The walls' crack gate (surface.js, still-surface.js): well aged (wallCrack(age)) and, per repeat of the map, a
// random draw (a value noise whose lattice points sit on each 2 m tile's middle, so a crack, which keeps inside its
// tile, takes its own tile's draw, and the draw never cuts across a crack): about one repeat in three keeps its cracks.
// The Tier 2 second sample draws on its own tiles. wuv: the map's uv; w: wallTap's blend.
float wallH(vec2 p){ return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }
float wallVN(vec2 p){ vec2 i = floor(p), f = fract(p); f = f * f * (3. - 2. * f); return mix(mix(wallH(i), wallH(i + vec2(1., 0.)), f.x), mix(wallH(i + vec2(0., 1.)), wallH(i + 1.), f.x), f.y); }
float wallCrackGate(vec2 wuv, float w, float age){
  float d = mix(wallVN(wuv - .5), wallVN(mat2(.829, .559, -.559, .829) * wuv * 1.37 + vec2(.31, .17) - .5 + 17.), w * uWallMix);
  return wallCrack(age) * smoothstep(.55, .8, d);
}
float wallSun(vec3 Nb, vec3 N, vec3 L){ float g = dot(N, L); return clamp(1. + .8 * (dot(Nb, L) - g) / max(g, .25), .55, 1.05); }
float wallFill(vec3 Nb, vec3 N, vec3 D, float k){ return clamp(1. + k * dot(Nb - N, D), .7, 1.04); }
`;
