import { ShaderMaterial } from "three";
import { BLOCK, BLOCKS } from "../glsl/blocks.js";
import { NOISE } from "../glsl/noise.js";
import { aperturesGLSL } from "../glsl/apertures.js";
import { DOOR } from "../objects/door.js";

// Painted wood (doors, frames, shutters; 05-DESIGN D6.3): the paint token is the display color in full
// light, so a lit door lands on its unit token (not tone-mapped, D6.3); shade is the token darkened and
// cooled toward the room's shade. Worn edges show the wood (world-wood) on about 12% of the edge band.
// Lit by the same lights and apertures as the shell, so a leaf in a beam takes the beam.
const VERT = /* glsl */ `
varying vec3 vP; varying vec3 vN; varying vec2 vUv; varying float vDepth;
#ifdef VERTEX_PAINT
attribute vec4 aPaint; varying vec4 vPaint;
#endif
#ifdef LEAF_WEAR
attribute vec3 aLeafP; varying vec3 vL;
#endif
void main(){
  #ifdef VERTEX_PAINT
  vPaint = aPaint;
  #endif
  #ifdef LEAF_WEAR
  vL = aLeafP;
  #endif
  mat4 m = modelMatrix;
  #ifdef USE_INSTANCING
  m = m * instanceMatrix;
  #endif
  vec4 w = m * vec4(position, 1.);
  vP = w.xyz; vN = normalize(mat3(m) * normal); vUv = uv;
  vec4 v = viewMatrix * w;
  vDepth = -v.z;
  gl_Position = projectionMatrix * v;
}`;

// ao: GLSL `float paintAo(vec3 P)`, contact shade where the paint meets something (a leaf at its hinge, a
// shut leaf in its frame). uGrad (own): the daylight down a face, that much brighter at the head than at
// the foot (a shut door lit only from above).
// vertexPaint (05-DESIGN D0.6.2, from proposal saigon-home): the paint and its wear come from an aPaint
// attribute (rgb, wear), so the corridor's door frames and Little Rose's fixed leaf are one draw.
// leafWear (critique r1: the wear read as dirt or mould, speckled over the leaves' faces): a door leaf (its aLeafP,
// objects/door.js, in hinge space) is worn only where a door wears, within 4 cm of its edges, in the 25 cm kick
// zone at its foot and a 10 cm disc round the handle; elsewhere its face takes at most 0.1. Frames (aLeafP.y −1)
// keep the chipped edge band. The same rule for every door, Little Rose's at its 0.3.
// oldCoat (the corridor, critique r2: the kick zone read as mud or rust at 1440): what a chip shows is an older,
// lighter coat of the same paint (mix(paint, uWood, 0.45), uWood the tiles' cream), not bare wood, and a leaf's
// kick zone fades up its foot's 12.5 cm (it was 25).
// nearFade (the corridor, critique r2): a backstop for what passes the lens: paint nearer the camera than 1.4 m along
// its view axis dithers away (gone by 0.7 m), so no leaf or frame is ever a blurred slab with straight edges across the
// frame. Discarded, never blended: no transparency sort.
export function paintMaterial(U, lights, own, { ao = null, vertexPaint = false, leafWear = false, nearFade = false, oldCoat = false } = {}) {
  return new ShaderMaterial({
    defines: { ...(vertexPaint ? { VERTEX_PAINT: "" } : {}), ...(leafWear ? { LEAF_WEAR: "" } : {}), ...(nearFade ? { NEAR_FADE: "" } : {}), ...(oldCoat ? { OLD_COAT: "" } : {}) },
    uniforms: { ...U, uGrad: { value: 0 }, ...own },
    vertexShader: VERT,
    fragmentShader: /* glsl */ `
      varying vec3 vP; varying vec3 vN; varying vec2 vUv; varying float vDepth;
      #ifdef LEAF_WEAR
      varying vec3 vL;
      #endif
      #ifdef VERTEX_PAINT
      varying vec4 vPaint;
      #define uPaint vPaint.rgb
      #define uWear vPaint.a
      #else
      uniform vec3 uPaint;
      uniform float uWear;
      #endif
      uniform vec3 uWood, uCool, uSpillTint, uSunTint;
      uniform float uShadeK, uGrad, uRef[2], uI[2];
      ${BLOCKS}
      ${NOISE}
      ${aperturesGLSL(lights)}
      ${ao ?? "float paintAo(vec3 P){ return 1.; }"}
      void main(){
        #ifdef NEAR_FADE
        if (h21(gl_FragCoord.xy) > smoothstep(.7, 1.4, vDepth)) discard;
        #endif
        vec3 N = normalize(vN);
        // Edge band in face UV, broken up by noise: chipped paint at corners and edges only.
        vec2 e = min(vUv, 1. - vUv);
        float wear = (1. - smoothstep(0.015, 0.05, min(e.x, e.y))) * uWear;
        vec2 grain = vP.xy * 38. + vP.zz * 21.;
        #ifdef LEAF_WEAR
        if (vL.y > -.5) {
          float az = abs(vL.z), d = min(min(vL.y, ${DOOR.h.toFixed(3)} - vL.y), min(az, ${DOOR.w.toFixed(3)} - az));
          #ifdef OLD_COAT
          float m = max(1. - smoothstep(.03, .045, d), 1. - smoothstep(.01, .125, vL.y)); // edges; scuffs fading up the kick zone
          #else
          float m = max(1. - smoothstep(.03, .045, d), 1. - smoothstep(.02, .25, vL.y)); // edges; scuffs fading up the kick zone
          #endif
          m = max(m, 1. - smoothstep(.085, .105, length(vec2(vL.y - 1.02, az - ${(DOOR.w - 0.08).toFixed(3)}))));
          wear = mix(min(uWear, .1), uWear, m);
          grain = vL.zy * 38. + vL.xx * 21.; // the chips stay on the leaf as it swings
        }
        #endif
        float chip = smoothstep(0.62, 0.7, vn(grain)) * wear;
        #ifdef OLD_COAT
        vec3 albedo = mix(uPaint, mix(uPaint, uWood, .45), chip);
        #else
        vec3 albedo = mix(uPaint, uWood, chip);
        #endif
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
// dapple: a doorway's room ({ zc, half, amount }, a plane facing −x): the reveal's shade along the jambs and the
// room's own screen's Hoa thị sun, slanted down its back wall, `amount` either way (critique r1: not a flat slab).
export function glowMaterial({ low, high, y0 = 0, y1 = 2.4, grain = 0.02, canopy = null, dapple = null }) {
  return new ShaderMaterial({
    uniforms: { uLow: { value: low }, uHigh: { value: high }, uY: { value: [y0, y1] }, uGrain: { value: grain }, uCanopyC: { value: canopy?.color ?? high }, uCanopyAmt: { value: canopy?.amount ?? 0 }, uDapple: { value: dapple ? [dapple.zc, dapple.half, dapple.amount, 1] : [0, 0, 0, 0] } },
    vertexShader: /* glsl */ `varying vec3 vP; void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; gl_Position = projectionMatrix * viewMatrix * w; }`,
    fragmentShader: /* glsl */ `
      varying vec3 vP; uniform vec3 uLow, uHigh, uCanopyC; uniform float uY[2], uGrain, uCanopyAmt, uDapple[4];
      ${NOISE}
      ${BLOCKS}
      void main(){
        float t = smoothstep(uY[0], uY[1], vP.y);
        vec3 col = mix(uLow, uHigh, t) * (1. + (vn(vP.zy * 3. + vP.xx) - .5) * uGrain);
        if (uDapple[3] > .5) {
          float a = uDapple[1] - abs(vP.z - uDapple[0]), k = uDapple[2];
          // The reveal keeps the room's light off the jambs and the head; deeper at the floor's line.
          col *= 1. - k * 1.4 * (1. - smoothstep(0., .14, a)) - k * (1. - smoothstep(0., .1, vP.y));
          // The room's own screen: its holes' sun slanted down the back wall, soft (it is metres away).
          vec2 c = vec2(vP.z - uDapple[0] + vP.y * .42, vP.y * 1.15 - .3);
          float h = sdHoaThi(mod(c, ${BLOCK.toFixed(2)}) - ${(BLOCK / 2).toFixed(3)});
          col *= 1. + k * (1. - smoothstep(-.012, .03, h)) * smoothstep(.3, 1.1, vP.y);
        }
        float tree = smoothstep(.54, .66, fbm(vP.zy * vec2(.55, .6) + 3.1) + (clamp(vP.y / 5., 0., 1.) - .45) * .9);
        col = mix(col, uCanopyC, tree * uCanopyAmt);
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
      }`,
    toneMapped: false,
  });
}
