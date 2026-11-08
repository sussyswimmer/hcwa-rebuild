import { ShaderMaterial } from "three";
import { BLOCKS } from "../glsl/blocks.js";
import { NOISE } from "../glsl/noise.js";
import { aperturesGLSL } from "../glsl/apertures.js";
import { CAPS } from "./caps.js";
import { TERRAZZO_CEMENT } from "../textures.js";

// Shells of the still sets (05-DESIGN D6.10): the room surface model of surface.js (display targets in
// shade and in full light, one texture fetch, analytic AO, caps inside text rects) with up to two
// lights through any apertures (glsl/apertures.js): light 0 is the sun, light 1 the daylight inside a
// room behind a door. Kinds per vertex: 0 lime wall, 1 terrazzo floor, 2 ceiling.
//   f_i = max(N·L_i, 0) / ref_i · mask_i(P) · cap · I_i
//   color = mix(mix(shade, sun, f0) + glare, spill, f1)
// contact: GLSL defining `float contact(vec3 P)`, the soft occlusion of what stands against the shell (a
// door frame proud of the wall, an open leaf on the floor), multiplied into the AO.
export function stillSurface(U, lights, { contact = null } = {}) {
  return new ShaderMaterial({
    uniforms: U,
    vertexShader: /* glsl */ `
      attribute float aKind;
      varying vec3 vP; varying vec3 vN; varying float vKind;
      void main(){
        vec4 w = modelMatrix * vec4(position, 1.);
        vP = w.xyz; vN = normalize(mat3(modelMatrix) * normal); vKind = aKind;
        gl_Position = projectionMatrix * viewMatrix * w;
      }`,
    fragmentShader: /* glsl */ `
      varying vec3 vP; varying vec3 vN; varying float vKind;
      uniform vec3 uShade[3], uSunT[3], uSpillT[3], uGlare, uBoxMin, uBoxMax, uFillDir;
      uniform float uRef[2], uI[2], uAoK;
      uniform sampler2D uLime, uTerr;
      ${BLOCKS}
      ${NOISE}
      ${aperturesGLSL(lights)}
      ${CAPS}
      ${contact ?? "float contact(vec3 P){ return 1.; }"}
      // A surface skips the term of the plane it lies in (facing it, within 3 mm). Distance alone also
      // skipped the last 3 mm of the neighbouring wall, and every corner drew an unshaded bright seam.
      float occ(float d, float r, float s, float self){ return self > .5 && d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);
        vec3 N = normalize(vN);
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${TERRAZZO_CEMENT.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);
        vec3 an = abs(N);
        float ao = occ(vP.y - uBoxMin.y, .35, .12 * uAoK, an.y) * occ(uBoxMax.y - vP.y, .4, .1 * uAoK, an.y)
                 * occ(vP.z - uBoxMin.z, .9, .2 * uAoK, an.z) * occ(uBoxMax.z - vP.z, .9, .2 * uAoK, an.z)
                 * occ(vP.x - uBoxMin.x, .9, .2 * uAoK, an.x) * occ(uBoxMax.x - vP.x, .9, .2 * uAoK, an.x) * contact(vP);
        // Skylight from the open side: surfaces facing it and near it a little brighter.
        vec3 c = 0.5 * (uBoxMin + uBoxMax), half_ = max(0.5 * (uBoxMax - uBoxMin), vec3(0.01));
        float near = clamp(dot((vP - c) / half_, uFillDir) * 0.5 + 0.5, 0., 1.);
        float fill = (1. + 0.06 * dot(N, uFillDir)) * (0.92 + 0.14 * near) * (k == 2 ? 0.86 + 0.16 * near : 1.);
        float cap = capAt(gl_FragCoord.xy);
        float n0 = max(dot(N, uLt[0]), 0.), n1 = max(dot(N, uLt[1]), 0.);
        float f0 = n0 > 0. && uI[0] > 0. ? n0 / uRef[0] * lightMask0(vP) * cap * uI[0] : 0.;
        ${lights.length > 1 ? "float f1 = n1 > 0. && uI[1] > 0. ? n1 / uRef[1] * lightMask1(vP) * cap * uI[1] : 0.;" : "float f1 = 0.;"}
        vec3 shade = uShade[k] * rel * ao * fill;
        vec3 sun = uSunT[k] * 0.94 * rel * mix(1., ao, .5);
        vec3 col = mix(mix(shade, sun, min(f0, 1.)), uGlare, min(max(f0 - 1., 0.) * .35, .6));
        col = mix(col, uSpillT[k] * 0.94 * rel * mix(1., ao, .5), min(f1, 1.));
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        gl_FragColor.rgb += (h21(gl_FragCoord.xy) - .5) / 255.;
      }`,
    toneMapped: false,
  });
}

// A room seen through an open doorway (05-DESIGN D6.5, critique r2): its own daylight, nothing in it but
// plaster and terrazzo. The back wall takes the light whole (sun-100 at `back`), the side walls warm down
// toward the doorway, the floor is a pool of the same light fading toward the door, the corners soft.
// No lights of its own: what reads as depth is the falloff and the corners. Kinds per vertex as the shell.
export function litRoom({ box, back, side, floor, ceiling, terr }) {
  return new ShaderMaterial({
    uniforms: {
      uBack: { value: back }, uSide: { value: side }, uFloor: { value: floor }, uCeil: { value: ceiling }, uTerr: { value: terr },
      uMin: { value: box.min.slice() }, uMax: { value: box.max.slice() },
    },
    vertexShader: /* glsl */ `
      attribute float aKind; varying vec3 vP; varying vec3 vN; varying float vKind;
      void main(){ vec4 w = modelMatrix * vec4(position, 1.); vP = w.xyz; vN = normalize(mat3(modelMatrix) * normal); vKind = aKind; gl_Position = projectionMatrix * viewMatrix * w; }`,
    fragmentShader: /* glsl */ `
      varying vec3 vP; varying vec3 vN; varying float vKind;
      uniform vec3 uBack, uSide, uFloor, uCeil; uniform float uMin[3], uMax[3]; uniform sampler2D uTerr;
      ${NOISE}
      float occ(float d, float r){ return d < 0.003 ? 1. : 0.8 + 0.2 * smoothstep(0., r, d); }
      void main(){
        int k = int(vKind + 0.5);
        vec3 N = normalize(vN);
        // 0 at the doorway, 1 at the back wall.
        float deep = clamp((vP.x - uMin[0]) / (uMax[0] - uMin[0]), 0., 1.);
        vec3 col = k == 1 ? uFloor * texture2D(uTerr, vP.xz * 0.5).rgb / ${TERRAZZO_CEMENT.toFixed(3)} * (0.86 + 0.14 * deep)
                 : k == 2 ? uCeil * (0.9 + 0.1 * deep)
                 : N.x < -0.5 ? uBack * (0.97 + 0.03 * smoothstep(0., 2.4, vP.y))
                 : uSide * (0.8 + 0.2 * deep);
        float ao = occ(vP.y - uMin[1], .3) * occ(uMax[1] - vP.y, .4) * occ(vP.z - uMin[2], .3) * occ(uMax[2] - vP.z, .3) * occ(uMax[0] - vP.x, .35);
        gl_FragColor = vec4(col * ao * (1. + (vn(vP.zy * 6. + vP.xx * 3.) - .5) * .02), 1.);
        #include <colorspace_fragment>
        gl_FragColor.rgb += (h21(gl_FragCoord.xy) - .5) / 255.;
      }`,
    toneMapped: false,
  });
}
