import { ShaderMaterial } from "three";
import { BLOCKS } from "../glsl/blocks.js";
import { NOISE } from "../glsl/noise.js";
import { aperturesGLSL } from "../glsl/apertures.js";
import { CAPS } from "./caps.js";
import { TERRAZZO_CEMENT, TILE_CREAM } from "../textures.js";
import { MAX_DOORS } from "../glsl/blocks.js";

// Shells of the still sets (05-DESIGN D6.10): the room surface model of surface.js (display targets in
// shade and in full light, one texture fetch, analytic AO, caps inside text rects) with up to two
// lights through any apertures (glsl/apertures.js): light 0 is the sun, light 1 the daylight inside a
// room behind a door. Kinds per vertex: 0 lime wall, 1 terrazzo floor, 2 ceiling.
//   f_i = max(N·L_i, 0) / ref_i · mask_i(P) · cap · I_i
//   color = mix(mix(shade, sun, f0) + glare, spill, f1)
// contact: GLSL defining `float contact(vec3 P)`, the soft occlusion of what stands against the shell (a
// door frame proud of the wall, an open leaf on the floor), multiplied into the AO.
// house (05-DESIGN D0.6.2, from proposal saigon-home): the corridor's Saigon mái ấm, with the targets of
// sets/common.js houseUniforms. Walls (kind 0): yellow lime wash with its loang (the lime texture's A) above a
// mint oil-paint dado to uHouse.x on every wall, a uHouse.y painted line on its top; on the door wall
// (x = uHouse.w, facing −x) the dado stops uHouse.z from every door's centre (10 cm short of its frame),
// whatever the unit. Floor (kind 1): a gạch bông runner of uGachP.w from x = uGachP.x (border row, field,
// border row; 0: none), then plain cream tiles with hairline joints, per-tile tone; wear (the texture's A)
// lifts pigment toward cream, most along the runner's centre. Before every door (uGachP.y > 0, critique r1) the
// runner stops for a plain cream threshold uGachP.y either side of the tile joint nearest the door's centre,
// across its full width, its border chain turning the corner: each bay has its own rug, and the emblem light
// out of each transom lands on plain cream. Little Rose's threshold is the same. uTrace (staging only, D6.8):
// the chalk lò cò on a bay's plain band. Ceiling (kind 2): its warm target, warmed toward the yellow wash within
// 0.5 m of the long walls (their bounce) and aged with the wash's loang.
export const HOUSE_GLSL = (doors) => /* glsl */ `
uniform sampler2D uGach, uGachB;
uniform vec4 uGachP, uHouse, uWearP, uTrace;
uniform vec3 uDadoShade, uDadoSun, uDadoLine, uDadoLineSun, uChalk;
${doors ? "" : `uniform vec4 uDoors[${MAX_DOORS}];\nuniform int uDoorN;`}
// The lò cò (hopscotch) in chalk: rows of 0.34 m squares, single, single, single, double, single, double, and a
// half-round home; p: across from the band's centre, along the hop from its first square. No numbers, no names.
float loCo(vec2 p){
  const float S = .34;
  float d = 1e3;
  for (int r = 0; r < 6; r++) {
    float c = (float(r) + .5) * S;
    if (r == 3 || r == 5) { d = min(d, abs(sdBox2(vec2(p.x - S * .5, p.y - c), vec2(S * .5)))); d = min(d, abs(sdBox2(vec2(p.x + S * .5, p.y - c), vec2(S * .5)))); }
    else d = min(d, abs(sdBox2(vec2(p.x, p.y - c), vec2(S * .5))));
  }
  if (p.y > 6. * S) d = min(d, abs(length(vec2(p.x, p.y - 6. * S)) - S * .5));
  float aa = max(fwidth(p.y), fwidth(p.x));
  float stroke = 1. - smoothstep(.011 - aa, .011 + aa, d);
  // Chalk on cement: the stroke breaks where the tile's grain is low, and is a little worn all over.
  return stroke * smoothstep(.22, .55, vn(p * 46.)) * (.7 + .3 * vn(p * 9. + 3.1));
}
`;
export function stillSurface(U, lights, { contact = null, house = false } = {}) {
  // The corridor's light 1 already declares the doors (glsl/doors.js); a still's house declares them itself.
  const doorsDeclared = lights.some((l) => l.glsl?.includes("uDoors["));
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
      ${house ? HOUSE_GLSL(doorsDeclared) : ""}
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
        vec3 shT = uShade[k], suT = uSunT[k], spT = uSpillT[k];
        ${house ? `
        if (k == 1) {
          float s = vP.x - uGachP.x, T = uGachP.z, R = uGachP.w;
          float tone = 1. + (h21(floor(vec2(s, vP.z) / T) + 17.31) - .5) * .05;
          // ez: how far into its bay's rug (< 0: on a door's threshold); a long way when there are no thresholds.
          float ez = 1e3;
          if (uGachP.y > 0. && R > 0.) for (int i = 0; i < ${MAX_DOORS}; i++) { if (i >= uDoorN) break; ez = min(ez, abs(vP.z - floor(uDoors[i].x / T + .5) * T) - uGachP.y); }
          if (s >= 0. && s < R && ez >= 0.) {
            float e = min(s, R - s);
            // The border chain along each long edge, and across the rug's ends where it meets a threshold: mitred
            // in the corner tiles, so the chain turns the corner.
            vec4 g = min(e, ez) < T ? (ez < e ? texture2D(uGachB, vec2(s / (2. * T), 1. - ez / T)) : texture2D(uGachB, vec2(vP.z / (2. * T), 1. - e / T))) : texture2D(uGach, vec2(s - T, vP.z) / (2. * T));
            float d = (s - .5 * R) / uWearP.y;
            float lift = min(uWearP.z * g.a * (.3 + .7 * exp(-.5 * d * d)) * (.55 + .9 * lw.r), uWearP.z);
            rel = mix(g.rgb / ${TILE_CREAM.toFixed(3)}, vec3(1.), lift);
          } else {
            vec2 q = abs(fract(vec2(s, vP.z) / T + .5) - .5) * T;
            float fw = max(fwidth(s), fwidth(vP.z));
            rel = vec3(1. - .11 * (1. - smoothstep(.0007, .0007 + 1.5 * fw, min(q.x, q.y))));
            if (uTrace.w > .5) rel = mix(rel, uChalk, .85 * loCo(vec2(vP.x - uTrace.z, (vP.z - uTrace.x) * uTrace.y)));
          }
          rel *= tone * (1. + mott * .3);
        } else if (k == 0) {
          // 3 mm soft edges, never thinner than a pixel (a far line must not crawl).
          float ay = max(.0015, fwidth(vP.y)), az = max(.0015, fwidth(vP.z));
          float dado = smoothstep(uHouse.x + ay, uHouse.x - ay, vP.y);
          if (N.x < -.5 && abs(vP.x - uHouse.w) < .02) {
            float dz = 1e3;
            for (int i = 0; i < ${MAX_DOORS}; i++) { if (i >= uDoorN) break; dz = min(dz, abs(vP.z - uDoors[i].x)); }
            dado *= smoothstep(uHouse.z - az, uHouse.z + az, dz);
          }
          float line = smoothstep(uHouse.x - uHouse.y - ay, uHouse.x - uHouse.y + ay, vP.y);
          vec3 dS = mix(uDadoShade, uDadoLine, line), dU = mix(uDadoSun, uDadoLineSun, line);
          spT = mix(spT, spT * dU / max(suT, vec3(.001)), dado);
          shT = mix(shT, dS, dado);
          suT = mix(suT, dU, dado);
          rel = mix(rel * (1. + (lw.a - .5) * uWearP.w), vec3(1. + mott * .3), dado);
        } else {
          // The ceiling: the yellow walls' bounce within 0.5 m of them (up to 30%), and the wash's loang.
          float dw = min(vP.x - uBoxMin.x, uBoxMax.x - vP.x);
          shT = mix(shT, uShade[0], .3 * (1. - smoothstep(0., .5, dw)));
          rel *= 1. + (lw.a - .5) * uWearP.w;
        }` : ""}
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
        vec3 shade = shT * rel * ao * fill;
        vec3 sun = suT * 0.94 * rel * mix(1., ao, .5);
        vec3 col = mix(mix(shade, sun, min(f0, 1.)), uGlare, min(max(f0 - 1., 0.) * .35, .6));
        col = mix(col, spT * 0.94 * rel * mix(1., ao, .5), min(f1, 1.));
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
