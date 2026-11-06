import { AdditiveBlending, BufferGeometry, Float32BufferAttribute, Points, ShaderMaterial, Vector3 } from "three";
import { APERTURE } from "../glsl/aperture.js";
import { BLOCKS } from "../glsl/blocks.js";
import { LEAF, NOISE } from "../glsl/noise.js";

// Dust motes (05-DESIGN D6.3): visible only inside a beam (the vertex shader asks sunMask),
// drifting 1 to 3 cm/s on two sines; scroll velocity adds wind and a little sparkle. Big and warm
// enough (4-7 px, sun-200 toward sky, 55-90%) to read against a light wall, not only in the air.
// Allocated for Tier 2 (420); Tier 1 draws the first 160.
export const MOTES = { 1: 160, 2: 420 };

export function createMotes(U, { min, max }) {
  const n = MOTES[2], seed = new Float32Array(n * 4);
  let s = 7;
  const r = () => ((s = (s * 16807) % 2147483647) / 2147483647);
  for (let i = 0; i < seed.length; i++) seed[i] = r();
  const g = new BufferGeometry();
  g.setAttribute("position", new Float32BufferAttribute(new Float32Array(n * 3), 3));
  g.setAttribute("aSeed", new Float32BufferAttribute(seed, 4));
  const lo = new Vector3(...min), size = new Vector3(...max).sub(lo);
  const mat = new ShaderMaterial({
    uniforms: { ...U, uBoxMin: { value: lo }, uBoxSize: { value: size } },
    vertexShader: /* glsl */ `
      attribute vec4 aSeed;
      uniform vec3 uBoxMin, uBoxSize, uWind;
      uniform float uTime, uPx, uMotesA, uStir, uSunI;
      varying float vA;
      ${BLOCKS}
      ${APERTURE}
      ${NOISE}
      ${LEAF}
      void main(){
        float ph = aSeed.w * 6.2831;
        vec3 p = uBoxMin + aSeed.xyz * uBoxSize + uWind
          + vec3(sin(uTime * .21 + ph) * .06, sin(uTime * .17 + ph * 1.7) * .05 + uTime * .006, cos(uTime * .19 + ph * .7) * .06);
        p = uBoxMin + mod(p - uBoxMin, uBoxSize);
        vec4 mv = modelViewMatrix * vec4(p, 1.);
        float m = sunMask(p);
        vA = m > 0. ? m * leaf(p, uTime) * uMotesA * uSunI * (0.725 + 0.175 * sin(uTime * 2.3 + ph * 3.)) * (1. + 0.4 * min(abs(uStir), 1.)) : 0.;
        gl_PointSize = (4.2 * (3. / -mv.z) + 1.2) * uPx;
        gl_Position = vA < 0.004 ? vec4(2., 2., 2., 1.) : projectionMatrix * mv;
      }`,
    fragmentShader: /* glsl */ `
      uniform vec3 uMoteCol; varying float vA;
      void main(){ vec2 d = gl_PointCoord - .5; gl_FragColor = vec4(uMoteCol * vA * exp(-dot(d, d) * 18.), 1.); }`,
    blending: AdditiveBlending, transparent: true, depthWrite: false, toneMapped: false,
  });
  const points = new Points(g, mat);
  points.frustumCulled = false;
  points.renderOrder = 5;
  return {
    points,
    setTier(t) { g.setDrawRange(0, MOTES[t >= 2 ? 2 : 1]); },
    get count() { return g.drawRange.count; },
    // Wind wraps inside the box, so it can integrate forever without losing precision.
    wrap(w) { w.set(((w.x % size.x) + size.x) % size.x, ((w.y % size.y) + size.y) % size.y, ((w.z % size.z) + size.z) % size.z); },
    dispose() { g.dispose(); mat.dispose(); },
  };
}
