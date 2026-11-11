import { ShaderMaterial } from "three";
import { APERTURE } from "../glsl/aperture.js";
import { BLOCKS } from "../glsl/blocks.js";
import { LEAF, NOISE } from "../glsl/noise.js";
import { CAPS } from "./caps.js";
import { TERRAZZO_CEMENT } from "../textures.js";
import { WALLS_GLSL } from "../glsl/walls.js";
import { WALL_SCALE } from "../walls.js";

// Room and corridor shells: lime wash, terrazzo, ceiling, all in one draw. No three.js
// lights. Shade and sun are display targets (the world-* tokens), so the wall in shade shows
// --color-world-wall-shade and a full patch --color-world-wall-sun, and the text-rect cap is
// a true 40%; the analytic aperture (open.md 3.3) decides how much patch each point gets:
//   f = max(N·L, 0) / N·L(back wall at rest) · sunMask(P) · leaf(P) · cap · intensity
//   color = mix(shade · texture · ao · fill, sun · texture, min(f, 1))  (+ glare where f > 1)
// The patch target keeps 6% headroom: the lime-wash mottle lifts it up to +9%, and a channel that
// clips at 255 while the others keep rising turns sun-200 lemon.
// The Blender plaster (walls.js, glsl/walls.js; once loaded, faded in by uWallK): walls and ceiling take its tone
// relief in place of the procedural mottle, and its normal shades the relief under the analytic sun (the sun term) and
// the screen's skylight (the shade term). The mask, the caps and the patch targets are unchanged. The floor stays
// terrazzo.
export function surfaceMaterial(U) {
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
      #include <common>
      varying vec3 vP; varying vec3 vN; varying float vKind;
      uniform vec3 uShade[3], uSunT[3], uGlare, uBoxMin, uBoxMax;
      uniform float uSunI, uNdlRef, uTime;
      uniform sampler2D uLime, uTerr;
      ${BLOCKS}
      ${APERTURE}
      ${NOISE}
      ${LEAF}
      ${CAPS}
      ${WALLS_GLSL(["Pl"])}
      float occ(float d, float r, float s){ return d < 0.003 ? 1. : 1. - s * (1. - smoothstep(0., r, d)); }
      void main(){
        int k = int(vKind + 0.5);           // 0 lime wall, 1 terrazzo floor, 2 ceiling
        vec3 N = normalize(vN);
        vec2 uv = abs(N.z) > .5 ? vP.xy : abs(N.x) > .5 ? vP.zy : vP.xz;
        vec4 lw = texture2D(uLime, uv * 0.25);
        float vertical = 1. - step(.5, abs(N.y));
        float mott = (lw.r - .5) * .09 + (lw.g - .5) * .05 + (lw.b - .5) * .04 * vertical;
        vec3 rel = k == 1 ? texture2D(uTerr, vP.xz * 0.5).rgb / ${TERRAZZO_CEMENT.toFixed(3)} * (1. + mott * .5) : vec3(1. + mott);
        vec3 Nb = N;
        if (k != 1 && uWallK > 0.) {
          vec4 w = wallTap(uWPl, uv / ${WALL_SCALE.wall.toFixed(1)}, smoothstep(.35, .65, lw.r));
          // The ceiling stays calmer; behind body text (a capped rect, caps.js) the plaster keeps a fifth of its relief,
          // so the intro's darkest pixels stay where its contrast bar was measured.
          float age = wallAge(lw.a) * (k == 2 ? .3 : 1.) * mix(.2, 1., smoothstep(.4, 1., capAt(gl_FragCoord.xy)));
          rel = mix(rel, vec3(wallRel(w.b, age)), uWallK);
          Nb = wallN(N, w.rg, uWallK * .6 * mix(.3, 1., age)); // the plaster's grit, softened: a phone stands 0.5 m from it
        }

        // Analytic contact shadow at floor, ceiling and corners: no SSAO.
        float ao = occ(vP.y - uBoxMin.y, .35, .12) * occ(uBoxMax.y - vP.y, .4, .1)
                 * occ(vP.z - uBoxMin.z, .9, .2) * occ(uBoxMax.z - vP.z, .8, .12)
                 * occ(vP.x - uBoxMin.x, .9, .2) * occ(uBoxMax.x - vP.x, .6, .12);
        // Skylight comes through the screen (+x): surfaces near it and facing it are brighter,
        // the far side and the ceiling away from it fall off. Text sits on the back wall
        // left and low, where this stays within a few percent of the target.
        float near = smoothstep(uBoxMin.x, uBoxMax.x, vP.x);
        float fill = (1. + 0.06 * N.x) * (0.92 + 0.14 * near) * (k == 2 ? 0.86 + 0.16 * near : 1.);
        // The back wall meets the screen in the back-right corner. The fill above peaks right there, so without
        // a real corner shade the last half metre of plaster read as a hard white seam against the dark screen
        // (Maxwell, 11 Nov 2026). Walls only: a deeper, wider contact shade that eases the plaster into the screen.
        float toScreen = 1. - smoothstep(0., .55, uBoxMax.x - vP.x);
        ao *= 1. - .26 * vertical * step(.5, abs(N.z)) * toScreen * toScreen;

        float ndl = max(dot(N, uL), 0.);
        float m = ndl > 0. ? sunMask(vP) : 0.;
        float f = m > 0. ? ndl / uNdlRef * m * leaf(vP, uTime) * capAt(gl_FragCoord.xy) * uSunI : 0.;
        vec3 shade = uShade[k] * rel * ao * fill * wallFill(Nb, N, vec3(.8, .6, 0.), .4);
        vec3 sun = uSunT[k] * 0.94 * rel * mix(1., ao, .5) * wallSun(Nb, N, uL);

        vec3 col = mix(mix(shade, sun, min(f, 1.)), uGlare, min(max(f - 1., 0.) * .35, .6));
        gl_FragColor = vec4(col, 1.);
        #include <colorspace_fragment>
        gl_FragColor.rgb += (h21(gl_FragCoord.xy) - .5) / 255.;
      }`,
    toneMapped: false,
  });
}
