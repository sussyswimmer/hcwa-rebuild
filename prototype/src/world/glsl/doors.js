import { Vector4 } from "three";
import { BLOCK, DEPTH, MAX_DOORS } from "./blocks.js";
import { DOOR } from "../objects/door.js";

// The corridor's doors as one light (05-DESIGN D6.5): daylight from the rooms behind the door wall
// (x = uDoorWall.x, facing −x) comes out through each transom, shaped by its unit's emblem, and, where a
// door stands open, through the doorway past the leaf. One test for any number of doors: the ray toward
// the light meets the wall once, and the door nearest that spot decides. Little Rose's transom (pattern 0) casts
// no light on the floor at all: no motif, no pool. Needs BLOCKS before it.
export { MAX_DOORS };
export const DOOR_PATTERN = { open: 0, dotTre: 1, vomCau: 2, hoaThi: 3 };

const f = (n) => n.toFixed(4);
// How much larger than its transom the emblem's light lands on the floor (critique r2).
export const EMBLEM = 1.5;

export const DOORS = /* glsl */ `
uniform vec4 uDoors[${MAX_DOORS}];  // zc, pattern id, leaf angle (rad), hinge side (+1 the +z jamb, −1 the −z; ±2: into the room)
uniform int uDoorN;
uniform vec4 uDoorWall;             // wx, transom y0, 0, 0
float doorPat(float id, vec2 c){
  if (id < 0.5) return -1.;
  if (id < 1.5) return sdDotTre(c);
  if (id < 2.5) return sdVomCau(c);
  return sdHoaThi(c);
}
float doorsMask(vec3 P, vec3 L){
  float wx = uDoorWall.x;
  if (P.x > wx - 0.001 || L.x < 1e-4) return 0.;
  float t0 = (wx - P.x) / L.x;
  float qz = P.z + L.z * t0;
  int k = -1; float bd = 1e9;
  for (int i = 0; i < ${MAX_DOORS}; i++) {
    if (i >= uDoorN) break;
    float dz = abs(qz - uDoors[i].x);
    if (dz < bd) { bd = dz; k = i; }
  }
  if (k < 0) return 0.;
  vec4 D = uDoors[k];
  float w = 0.0025 + 0.0093 * t0 * 0.6;
  // Transom: three blocks, two face tests through the block's depth. Its emblem lands on the plain threshold
  // (critique r1): drawn crisper than the doorway's soft spill, so the slots and the arch read on the floor.
  // Critique r2: at 1440 the Đốt tre pool was three pairs of 6 px dashes. The light is cast as through a transom
  // ${EMBLEM}× the size about its own centre (the pool lands where it did, ${EMBLEM}× larger, its shape and its crisp
  // penumbra kept): the slots and the arch read as the unit's light, not as marks on the tiles.
  float wt = 0.0015 + 0.0035 * t0;
  float tr = 1.;
  for (int s = 0; s < 2; s++) {
    float t = (wx + float(s) * ${f(DEPTH)} - P.x) / L.x;
    vec3 R = P + L * t;
    vec2 uv = vec2(R.z - D.x, R.y - uDoorWall.y - ${f(0.5 * BLOCK)}) / ${f(EMBLEM)} + vec2(${f(1.5 * BLOCK)}, ${f(0.5 * BLOCK)});
    float rect = sdBox2(uv - vec2(${f(1.5 * BLOCK)}, ${f(0.5 * BLOCK)}), vec2(${f(1.5 * BLOCK)}, ${f(0.5 * BLOCK)}));
    // Little Rose's (pattern 0): its transom glows with plain daylight on the wall, and casts nothing on its
    // threshold, no pool of any kind (critique r1): its floor has only the corridor's untinted shade.
    if (D.y < 0.5) tr = 0.;
    else tr *= smoothstep(wt, -wt, max(rect, doorPat(D.y, mod(uv, ${f(BLOCK)}) - ${f(0.5 * BLOCK)})));
  }
  // Doorway, only while the door stands open, through the 14 cm reveal, past the leaf.
  float dw = 0.;
  if (D.z > 0.01) {
    dw = 1.;
    for (int s = 0; s < 2; s++) {
      float t = (wx + float(s) * ${f(DOOR.reveal)} - P.x) / L.x;
      vec3 R = P + L * t;
      float rect = sdBox2(vec2(R.z - D.x, R.y - ${f(DOOR.h / 2)}), vec2(${f(DOOR.w / 2)}, ${f(DOOR.h / 2)}));
      dw *= smoothstep(w, -w, rect);
    }
    // The leaf swings about its hinge jamb, out into the corridor or (|w| 2) into the room.
    float h = D.w < 0. ? -1. : 1., sw = abs(D.w) > 1.5 ? -1. : 1.;
    vec3 C = vec3(wx + 0.03, 0., D.x + h * ${f(DOOR.w / 2)});
    vec3 U = vec3(-sw * sin(D.z), 0., -h * cos(D.z)), N = vec3(h * cos(D.z), 0., -sw * sin(D.z));
    float ln = dot(L, N);
    if (abs(ln) > 1e-4) {
      float t = dot(C - P, N) / ln;
      if (t > 0.) {
        vec3 R = P + L * t;
        vec2 uv = vec2(dot(R - C, U), R.y);
        float leaf = sdBox2(uv - vec2(${f(DOOR.w / 2)}, ${f(DOOR.h / 2)}), vec2(${f(DOOR.w / 2)}, ${f(DOOR.h / 2)}));
        dw *= smoothstep(-w, w, leaf);
      }
    }
  }
  return max(tr, dw);
}
`;

// No corridor sun in the bays listed (Little Rose: plain daylight only, D6.5): 0 inside, 1 outside,
// a 0.6 m soft edge at each end of the bay.
export const NO_SUN = /* glsl */ `
uniform vec4 uNoSun[2]; // z0, z1 of a bay (z0 < z1); an empty range when unused
float bayFree(vec3 P){
  float m = 1.;
  for (int i = 0; i < 2; i++) {
    vec4 r = uNoSun[i];
    if (r.y <= r.x) continue;
    m *= 1. - smoothstep(-0.3, 0.3, min(P.z - r.x, r.y - P.z));
  }
  return m;
}
`;

export function doorUniforms() {
  return {
    uDoors: { value: Array.from({ length: MAX_DOORS }, () => new Vector4()) },
    uDoorN: { value: 0 },
    uDoorWall: { value: new Vector4() },
    uNoSun: { value: [new Vector4(), new Vector4()] },
  };
}
