import { Vector2, Vector4 } from "three";
import { PAD } from "../frame.js";

// Patch intensity inside text rects is capped per kind of text with a 24 px feather (05-DESIGN
// D6.6): a cap on the light, never a lift of the wall (no haze). Display text (the H1) takes the
// light uncapped: ink-900 on a patch is 13.1:1 against 9.8:1 on the shade, and the concept puts the
// patches across it. Body text (intro, nav) keeps 40%, a pattern behind 17-20 px text hurts reading.
// Buttons are opaque and skip it. Rects are document pixels at the rest pose, which is also the
// canvas's own space (it scrolls with the hero).
export const MAX_CAPS = 8;
export const CAP_LEVEL = { display: 1, body: 0.4 };

export const CAPS = /* glsl */ `
uniform vec4 uCaps[${MAX_CAPS}];
uniform float uCapLv[${MAX_CAPS}];
uniform int uCapN;
uniform float uCapFeather;
uniform vec2 uCapShift; // the text's scroll since the rest pose (device px, y up): the canvas is fixed
float capAt(vec2 p){
  p -= uCapShift;
  float m = 1.;
  for (int i = 0; i < ${MAX_CAPS}; i++) {
    if (i >= uCapN) break;
    vec4 r = uCaps[i];
    vec2 d = max(max(r.xy - p, p - r.zw), 0.);
    m = min(m, mix(1., uCapLv[i], 1. - smoothstep(0., uCapFeather, length(d))));
  }
  return m;
}
`;

export const capUniforms = () => ({
  uCaps: { value: Array.from({ length: MAX_CAPS }, () => new Vector4()) },
  uCapLv: { value: new Array(MAX_CAPS).fill(1) },
  uCapN: { value: 0 },
  uCapFeather: { value: 24 },
  uCapShift: { value: new Vector2() },
});

// rects: CSS px at rest ({ x, y, w, h, kind }); h: canvas height in CSS px.
// Only rects whose kind is capped take a slot; the largest win when there are too many. Header text
// (pinned) is left out: its bar carries an 85% scrim, which washes any pattern behind it.
export function setCaps(U, rects, { h, dpr }) {
  const list = rects.filter((r) => !r.pinned && (CAP_LEVEL[r.kind] ?? 1) < 1).sort((a, b) => b.w * b.h - a.w * a.h).slice(0, MAX_CAPS);
  list.forEach((r, i) => {
    U.uCaps.value[i].set((r.x - PAD) * dpr, (h - (r.y + r.h + PAD)) * dpr, (r.x + r.w + PAD) * dpr, (h - (r.y - PAD)) * dpr);
    U.uCapLv.value[i] = CAP_LEVEL[r.kind];
  });
  U.uCapN.value = list.length;
  U.uCapFeather.value = 24 * dpr;
}
