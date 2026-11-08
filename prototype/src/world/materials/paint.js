import { ShaderMaterial } from "three";
import { BLOCKS } from "../glsl/blocks.js";
import { NOISE } from "../glsl/noise.js";
import { aperturesGLSL } from "../glsl/apertures.js";

// Painted wood (doors, frames, shutters; 05-DESIGN D6.3): the paint token is the display color in full
// light, so a lit door lands on its unit token (not tone-mapped, D6.3); shade is the token darkened and
// cooled toward the room's shade. Worn edges show the wood (world-wood) on about 12% of the edge band.
// Lit by the same lights and apertures as the shell, so a leaf in a beam takes the beam.
const VERT = /* glsl */ `
varying vec3 vP; varying vec3 vN; varying vec2 vUv;
void main(){
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal); vUv = uv;
  gl_Position = projectionMatrix * viewMatrix * w;
}`;

// ao: GLSL `float paintAo(vec3 P)`, contact shade where the paint meets something (a leaf at its hinge, a
// shut leaf in its frame). uGrad (own): the daylight down a face, that much brighter at the head than at
// the foot (a shut door lit only from above).
export function paintMaterial(U, lights, own, { ao = null } = {}) {
  return new ShaderMaterial({
    uniforms: { ...U, uGrad: { value: 0 }, ...own },
    vertexShader: VERT,
    fragmentShader: /* glsl */ `
      varying vec3 vP; varying vec3 vN; varying vec2 vUv;
      uniform vec3 uPaint, uWood, uCool, uSpillTint, uSunTint;
      uniform float uShadeK, uWear, uGrad, uRef[2], uI[2];
      ${BLOCKS}
      ${NOISE}
      ${aperturesGLSL(lights)}
      ${ao ?? "float paintAo(vec3 P){ return 1.; }"}
      void main(){
        vec3 N = normalize(vN);
        // Edge band in face UV, broken up by noise: chipped paint at corners and edges only.
        vec2 e = min(vUv, 1. - vUv);
        float edge = 1. - smoothstep(0.015, 0.05, min(e.x, e.y));
        float chip = smoothstep(0.62, 0.7, vn(vP.xy * 38. + vP.zz * 21.)) * edge * uWear;
        vec3 albedo = mix(uPaint, uWood, chip);
        float sky = 0.82 + 0.18 * N.y;
        vec3 shade = mix(albedo * uShadeK, uCool * uShadeK * 1.08, 0.18) * sky;
        float n0 = max(dot(N, uLt[0]), 0.), n1 = max(dot(N, uLt[1]), 0.);
        float f0 = n0 > 0. && uI[0] > 0. ? n0 / uRef[0] * lightMask0(vP) * uI[0] : 0.;
        ${lights.length > 1 ? "float f1 = n1 > 0. && uI[1] > 0. ? n1 / uRef[1] * lightMask1(vP) * uI[1] : 0.;" : "float f1 = 0.;"}
        vec3 col = mix(shade, albedo * uSunTint, min(f0, 1.));
        col = mix(col, albedo * uSpillTint, min(f1, 1.) * 0.85);
        col *= (1. + uGrad * (smoothstep(0., 2.2, vP.y) - .5)) * paintAo(vP);
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }`,
    toneMapped: false,
  });
}

// Light seen directly (a doorway's lit room, the sky past a window): a vertical gradient in world y
// with a little value noise, drawn as the display color.
// canopy: the tamarind leaves of sky.js STREET over a street seen through a window ({ color, amount }).
export function glowMaterial({ low, high, y0 = 0, y1 = 2.4, grain = 0.02, canopy = null }) {
  return new ShaderMaterial({
    uniforms: { uLow: { value: low }, uHigh: { value: high }, uY: { value: [y0, y1] }, uGrain: { value: grain }, uCanopyC: { value: canopy?.color ?? high }, uCanopyAmt: { value: canopy?.amount ?? 0 } },
    vertexShader: /* glsl */ `varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,
    fragmentShader: /* glsl */ `
      varying vec3 vP; uniform vec3 uLow, uHigh, uCanopyC; uniform float uY[2], uGrain, uCanopyAmt;
      ${NOISE}
      void main(){
        float t = smoothstep(uY[0], uY[1], vP.y);
        vec3 col = mix(uLow, uHigh, t) * (1. + (vn(vP.zy * 3. + vP.xx) - .5) * uGrain);
        float tree = smoothstep(.54, .66, fbm(vP.zy * vec2(.55, .6) + 3.1) + (clamp(vP.y / 5., 0., 1.) - .45) * .9);
        col = mix(col, uCanopyC, tree * uCanopyAmt);
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }`,
    toneMapped: false,
  });
}
