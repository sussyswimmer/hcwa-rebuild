import { Vector3 } from "three";
import { BLOCK, DEPTH } from "./blocks.js";

// The analytic sun (05-DESIGN D6.1), from open/proof-hero.html. For a point P, trace toward
// the sun to the screen plane and ask the block SDF whether that spot is a hole. Testing
// both faces of the 10 cm block thins the patches at grazing angles; the penumbra grows
// with distance at the sun's angular size. No shadow maps.
//
// The screen is a plane x = uSX facing the room; the street is on the side the sun is on
// (sign of uL.x), so the same chunk serves the room (street at +x) and the corridor (−x).
export const APERTURE = /* glsl */ `
uniform vec3 uL;
uniform float uSX, uB, uD, uZ0, uZ1, uY0, uY1;
#ifndef HOLE
#define HOLE sdHoaThi
#endif
vec2 blockLocal(vec2 zy){ return mod(zy - vec2(uZ0, uY0), uB) - 0.5 * uB; }
bool onScreen(vec3 Q){ return Q.z >= uZ0 && Q.z <= uZ1 && Q.y >= uY0 && Q.y <= uY1; }
float apert(vec3 P, float x){
  float t = (x - P.x) / uL.x;
  vec3 Q = P + uL * t;
  if (!onScreen(Q)) return 0.0;
  float w = 0.0025 + 0.0093 * max(t, 0.0) * 0.6;
  return smoothstep(w, -w, HOLE(blockLocal(Q.zy)));
}
float sunMask(vec3 P){
  float side = sign(uL.x);
  if ((P.x - uSX) * side > -0.001) return 0.0;
  return apert(P, uSX) * apert(P, uSX + side * uD);
}
`;

// One uniform set per screen; uL is shared by reference so the clock moves every material.
export function apertureUniforms({ sx, z0, z1, y0, y1 }, L = new Vector3(1, 0, 0)) {
  return {
    uL: { value: L },
    uSX: { value: sx },
    uB: { value: BLOCK },
    uD: { value: DEPTH },
    uZ0: { value: z0 },
    uZ1: { value: z1 },
    uY0: { value: y0 },
    uY1: { value: y1 },
  };
}
