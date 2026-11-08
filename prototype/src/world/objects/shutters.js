import { BoxGeometry, Group, InstancedMesh, Matrix4, Vector3 } from "three";

// Louvered shutters (cửa lá sách; 05-DESIGN D6.3, open.md 3.3): two leaves 0.8 × 2.0 m in a window in
// a wall at x = wx that faces −x, hinged at the jambs and swinging into the room. Each leaf is two
// stiles and three rails around two panels of 14 slats (0.68 × 0.055 × 0.012 m at a 0.06 m pitch);
// all 56 slats are one InstancedMesh, the 10 stiles and rails another, so the whole pair is two draws
// whatever its angles (S3 turns them every frame). Slats turn about their long axis; light passes the
// gaps with a duty cycle of 1 − cos(slat) · 0.055 / 0.06 (D5.3 S3), the same number the light uses.
export const SHUTTER = { w: 0.8, h: 2.0, stile: 0.06, rail: 0.07, depth: 0.035, slat: [0.68, 0.055, 0.012], pitch: 0.06, perPanel: 14 };
export const slatDuty = (deg) => 1 - Math.cos((deg * Math.PI) / 180) * (SHUTTER.slat[1] / SHUTTER.pitch);

// zc: the window's centre; leaf: degrees each leaf is open (0 closed, 110 folded back toward the wall);
// slat: degrees the slats are turned; y0: the sill.
export function shutterSpec({ wx, zc, y0 = 0.05, leaf = 110, slat = 65 }) {
  const hinges = [
    { z: zc - SHUTTER.w, dir: 1, angle: -leaf },  // left leaf: free edge at +z, swings to −x
    { z: zc + SHUTTER.w, dir: -1, angle: leaf },  // right leaf: free edge at −z
  ];
  return { wx, zc, y0, leaf, slat, hinges, opening: { z0: zc - SHUTTER.w, z1: zc + SHUTTER.w, y0, y1: y0 + SHUTTER.h } };
}

// One leaf as the light sees it: an occluder whose holes are the slat gaps. Into `out` (live: per frame).
export function leafAperture(spec, h, leafDeg = spec.leaf, slatDeg = spec.slat, out = {}) {
  // The left leaf (dir 1) turns by −leaf, the right by +leaf: both into the room. Local free-edge
  // direction (0, 0, dir) turned about y by that angle.
  const a = (-h.dir * leafDeg * Math.PI) / 180;
  out.mode = "occluder"; out.pattern = "slats";
  out.corner = [spec.wx + 0.02, spec.y0, h.z];
  out.u = [h.dir * Math.sin(a), 0, h.dir * Math.cos(a)];
  out.v = [0, 1, 0];
  out.size = [SHUTTER.w, SHUTTER.h];
  out.depth = SHUTTER.slat[1] * Math.sin((slatDeg * Math.PI) / 180) + 0.004;
  out.params = [SHUTTER.pitch, slatDuty(slatDeg), 0, 0];
  return out;
}
export const leafApertures = (spec) => spec.hinges.map((h) => leafAperture(spec, h));

// The pair, posable: set(leafDeg, slatDeg) writes every instance matrix (66 multiplies, no allocations).
export function shutterRig(spec, paint, slatPaint = paint) {
  const [sl, sw, st] = SHUTTER.slat;
  const slats = new InstancedMesh(new BoxGeometry(st, sw, sl), slatPaint, 2 * 2 * SHUTTER.perPanel);
  const frames = new InstancedMesh(new BoxGeometry(1, 1, 1), paint, 2 * 5);
  // Each leaf's stiles and rails in its own frame (hinge at the origin, free edge along dir · z).
  const inner = SHUTTER.w - 2 * SHUTTER.stile, panelH = SHUTTER.perPanel * SHUTTER.pitch;
  const local = spec.hinges.map((h) => {
    const zMid = (h.dir * SHUTTER.w) / 2;
    const box = (w, hh, y, z) => new Matrix4().makeScale(SHUTTER.depth, hh, w).setPosition(0, y, z);
    const parts = [
      box(SHUTTER.stile, SHUTTER.h, SHUTTER.h / 2, (h.dir * SHUTTER.stile) / 2),
      box(SHUTTER.stile, SHUTTER.h, SHUTTER.h / 2, h.dir * (SHUTTER.w - SHUTTER.stile / 2)),
      ...[SHUTTER.rail / 2, SHUTTER.rail + panelH + SHUTTER.rail / 2, SHUTTER.h - SHUTTER.rail / 2].map((y) => box(inner, SHUTTER.rail, y, zMid)),
    ];
    const ys = [SHUTTER.rail, 2 * SHUTTER.rail + panelH].flatMap((base) => Array.from({ length: SHUTTER.perPanel }, (_, i) => base + (i + 0.5) * SHUTTER.pitch));
    return { parts, slats: ys.map((y) => new Vector3(0, y, zMid)) };
  });
  const leafM = new Matrix4(), rot = new Matrix4(), m = new Matrix4();
  const set = (leafDeg, slatDeg) => {
    rot.makeRotationZ((slatDeg * Math.PI) / 180);
    let k = 0, f = 0;
    spec.hinges.forEach((h, j) => {
      const a = (-h.dir * leafDeg * Math.PI) / 180;
      leafM.makeRotationY(a).setPosition(spec.wx + 0.02, spec.y0, h.z);
      for (const p of local[j].parts) frames.setMatrixAt(f++, m.multiplyMatrices(leafM, p));
      for (const at of local[j].slats) {
        m.copy(rot).setPosition(at);
        slats.setMatrixAt(k++, m.premultiply(leafM));
      }
    });
    slats.instanceMatrix.needsUpdate = true;
    frames.instanceMatrix.needsUpdate = true;
  };
  set(spec.leaf, spec.slat);
  // The pair never leaves its window: one bounding sphere for any angle, so neither is culled.
  for (const mesh of [slats, frames]) {
    mesh.computeBoundingSphere();
    mesh.boundingSphere.center.set(spec.wx, spec.y0 + SHUTTER.h / 2, spec.zc);
    mesh.boundingSphere.radius = 2.4;
  }
  const group = new Group().add(frames, slats);
  return { group, frames, slats, set };
}

export const shutterMeshes = (spec, paint, slatPaint = paint) => shutterRig(spec, paint, slatPaint).group;
