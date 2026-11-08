import { BoxGeometry, Group, Mesh, PlaneGeometry } from "three";
import { mergeGeometries } from "three/addons/utils/BufferGeometryUtils.js";
import { BLOCK } from "../glsl/blocks.js";

// A unit's door in a wall at x = wx that faces −x (05-DESIGN D6.3, D6.5): frame (two jambs and a
// head), a leaf 0.95 × 2.2 × 0.04 m with four raised panels a face, a transom of three blocks of the
// unit's emblem above it, and behind an open door one glowing quad (nothing inside is modeled: only
// light). The leaf swings out into the corridor about its hinge at the +z jamb.
// Little Rose is fixed by its unit, whatever the caller asks: closed, Hoa thị transom (D6.5).
export const DOOR = { w: 0.95, h: 2.2, t: 0.04, frame: 0.06, reveal: 0.14 };
export const EMBLEMS = { "green-bamboo": "dotTre", "cau-han": "vomCau", "little-rose": "hoaThi" };

// What light a unit's door lets out (D6.5). Little Rose: plain, untinted, motif-free daylight from its
// transom (the opening's light, not the block's pattern) and no corridor sun anywhere in its bay. The
// others: their emblem through the transom and at most 15% of their color in the doorway's spill.
export function doorLight(unit) {
  const lr = unit === "little-rose";
  return { transomPattern: lr ? "open" : EMBLEMS[unit] ?? "hoaThi", spillTint: lr ? 0 : 0.12, corridorSun: !lr };
}

// hinge: +1 hinges the leaf on the +z jamb (the stills look at the wall square on), −1 on the −z jamb (the
// corridor walks along −z, so its leaves open away from the visitor and show the lit doorway).
// inward: the leaf swings into the room behind (a still whose light must leave the doorway unobstructed).
export function doorSpec({ wx, zc, unit, open = 72, hinge = 1, inward = false }) {
  const lr = unit === "little-rose";
  const half = DOOR.w / 2, top = DOOR.h + DOOR.frame;
  const transom = { z0: zc - 1.5 * BLOCK, z1: zc + 1.5 * BLOCK, y0: top + 0.04, y1: top + 0.04 + BLOCK };
  return {
    wx, zc, unit, hinge, swing: inward ? -1 : 1,
    open: lr ? 0 : open,
    emblem: EMBLEMS[unit] ?? "hoaThi",
    // Openings cut in the wall: the doorway with its frame, and the transom.
    holes: [[zc - half - DOOR.frame, zc + half + DOOR.frame, 0, top], [transom.z0, transom.z1, transom.y0, transom.y1]],
    doorway: { z0: zc - half, z1: zc + half, y0: 0, y1: DOOR.h },
    transom,
  };
}

// Meshes: frame and leaf take the paint material, the doorway its glow (only when open).
export function doorMeshes(spec, { paint, frame = paint, panel = paint, glow }) {
  const { wx, zc } = spec, half = DOOR.w / 2, f = DOOR.frame, R = DOOR.reveal;
  const g = new Group();
  const box = (w, h, d, x, y, z, mat) => { const m = new Mesh(new BoxGeometry(w, h, d), mat); m.position.set(x, y, z); g.add(m); return m; };
  // Jambs and head sit in the opening and stand 2 cm proud of the wall.
  box(R, DOOR.h + f, f, wx + R / 2 - 0.02, (DOOR.h + f) / 2, zc - half - f / 2, frame);
  box(R, DOOR.h + f, f, wx + R / 2 - 0.02, (DOOR.h + f) / 2, zc + half + f / 2, frame);
  box(R, f, DOOR.w + 2 * f, wx + R / 2 - 0.02, DOOR.h + f / 2, zc, frame);
  const h = spec.hinge ?? 1;
  const leaf = new Group();
  leaf.position.set(wx + 0.03, 0, zc + h * half);
  leaf.rotation.y = (h * (spec.swing ?? 1) * spec.open * Math.PI) / 180;
  const slab = new Mesh(new BoxGeometry(DOOR.t, DOOR.h, DOOR.w), paint);
  slab.position.set(0, DOOR.h / 2, -h * half);
  leaf.add(slab);
  for (const side of [-1, 1]) for (const [y, ph] of [[0.12 + 0.42, 0.84], [1.12 + 0.48, 0.96]]) for (const dz of [-0.24, 0.24]) {
    const p = new Mesh(new BoxGeometry(0.012, ph, 0.34), panel);
    p.position.set(side * (DOOR.t / 2 + 0.004), y, -h * half + dz);
    leaf.add(p);
  }
  g.add(leaf);
  if (spec.open > 0 && glow) {
    const q = new Mesh(new PlaneGeometry(DOOR.w + 0.4, DOOR.h + 0.3), glow);
    q.rotation.y = -Math.PI / 2;
    q.position.set(wx + 0.9, DOOR.h / 2, zc);
    g.add(q);
  }
  return g;
}

// The same door as three merged geometries for the live corridor, two draws a door: the frame in world
// space, the leaf (slab and its raised panels) in hinge space, so turning one group swings it.
export function doorGeometries(spec) {
  const { wx, zc } = spec, half = DOOR.w / 2, f = DOOR.frame, R = DOOR.reveal;
  const box = (w, h, d, x, y, z) => new BoxGeometry(w, h, d).translate(x, y, z);
  const frame = mergeGeometries([
    box(R, DOOR.h + f, f, wx + R / 2 - 0.02, (DOOR.h + f) / 2, zc - half - f / 2),
    box(R, DOOR.h + f, f, wx + R / 2 - 0.02, (DOOR.h + f) / 2, zc + half + f / 2),
    box(R, f, DOOR.w + 2 * f, wx + R / 2 - 0.02, DOOR.h + f / 2, zc),
  ]);
  const hs = spec.hinge ?? 1;
  const parts = [box(DOOR.t, DOOR.h, DOOR.w, 0, DOOR.h / 2, -hs * half)];
  for (const side of [-1, 1]) for (const [y, h] of [[0.12 + 0.42, 0.84], [1.12 + 0.48, 0.96]]) for (const dz of [-0.24, 0.24]) {
    parts.push(box(0.012, h, 0.34, side * (DOOR.t / 2 + 0.004), y, -hs * half + dz));
  }
  // Turn the hinge group by hinge × angle to open it.
  return { frame, leaf: mergeGeometries(parts), hinge: [wx + 0.03, 0, zc + hs * half], side: hs };
}

// The leaf as seen by the light (an occluder with no holes), for the doorway's spill.
export function leafAperture(spec) {
  const a = (spec.open * Math.PI) / 180, half = DOOR.w / 2, h = spec.hinge ?? 1, s = spec.swing ?? 1;
  return {
    mode: "occluder", pattern: "solid",
    corner: [spec.wx + 0.03, 0, spec.zc + h * half],
    u: [-s * Math.sin(a), 0, -h * Math.cos(a)], v: [0, 1, 0],
    size: [DOOR.w, DOOR.h], depth: DOOR.t,
  };
}
