// Shading with the Blender wall maps (walls.js; packed by walls-pack.js: rg normal, b tone relief / 2, a chip mask).
// The world keeps its display-target model: the maps never set a colour, they set relief around the house tokens.
//   tone   wallRel(): the albedo's luminance over its mean, darker freely, lighter by half and never past +5%, so a
//          sun patch keeps its headroom (surface.js: a channel clipping at 255 turns sun-200 lemon)
//   sun    wallSun(): how much more or less a texel faces the light than its wall, on the light's own term, so the
//          cracks, chips and brush streaks catch a grazing sun; bounded, so a patch never glares from relief alone
//   shade  wallFill(): the same against the sky's fill, softer
// Walls are axis-aligned: the tangent basis follows the surface's own uv (x-facing: z, y; z-facing: x, y; floors and
// ceilings: x, z). On Tier 2 (uWallMix) a second sample, turned 34° at 0.73 the scale, is blended in where `w` (the
// lime texture's broad mottle) says, so no crack repeats on the 2 m grid. uWallK fades all of it in (0: procedural).
// wallAge(): where the wall has aged (the lime texture's metre-wide loang, A): cracks, stains and chips read at full
// strength there and at a third elsewhere, so one crack is not seen every 2 m.
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
float wallRel(float r, float k){ float d = (r - 1.) * k; return 1. + min(d, min(.5 * d, .05)); }
float wallSun(vec3 Nb, vec3 N, vec3 L){ float g = dot(N, L); return clamp(1. + .8 * (dot(Nb, L) - g) / max(g, .25), .55, 1.05); }
float wallFill(vec3 Nb, vec3 N, vec3 D, float k){ return clamp(1. + k * dot(Nb - N, D), .7, 1.04); }
`;
