import { BufferGeometry, Euler, Float32BufferAttribute, Matrix4, PlaneGeometry } from "three";

// Walls, floors and ceilings for the still sets as one merged draw (the room's shellGeometry, made
// general): axis-aligned rectangles, each cut around rectangular openings (a doorway, a transom, a
// window), the surface kind per vertex (0 lime wall, 1 terrazzo floor, 2 ceiling).

// A plane perpendicular to `axis` at `at`, spanning a = [a0, a1] and b = [b0, b1] in the two other
// axes (x-plane: z, y; y-plane: x, z; z-plane: x, y), its normal pointing to `facing` (+1 or −1).
// holes: [[a0, a1, b0, b1]], cut out of it on a grid.
export function rect(axis, at, a, b, facing, kind, holes = []) {
  const as = [...new Set([a[0], a[1], ...holes.flatMap((h) => [h[0], h[1]])])].filter((v) => v >= a[0] && v <= a[1]).sort((p, q) => p - q);
  const bs = [...new Set([b[0], b[1], ...holes.flatMap((h) => [h[2], h[3]])])].filter((v) => v >= b[0] && v <= b[1]).sort((p, q) => p - q);
  const out = [];
  for (let i = 0; i < as.length - 1; i++) for (let j = 0; j < bs.length - 1; j++) {
    const [ca, cb] = [(as[i] + as[i + 1]) / 2, (bs[j] + bs[j + 1]) / 2];
    if (holes.some((h) => ca > h[0] && ca < h[1] && cb > h[2] && cb < h[3])) continue;
    out.push({ axis, at, a: [as[i], as[i + 1]], b: [bs[j], bs[j + 1]], facing, kind });
  }
  return out;
}

const ROT = {
  // PlaneGeometry lies in x, y facing +z: turn it to face ±axis, its local x along a, y along b.
  x: (f) => (f > 0 ? [0, Math.PI / 2, 0] : [0, -Math.PI / 2, 0]),
  y: (f) => (f > 0 ? [-Math.PI / 2, 0, 0] : [Math.PI / 2, 0, 0]),
  z: (f) => (f > 0 ? [0, 0, 0] : [0, Math.PI, 0]),
};
const center = (r) => {
  const ca = (r.a[0] + r.a[1]) / 2, cb = (r.b[0] + r.b[1]) / 2;
  return r.axis === "x" ? [r.at, cb, ca] : r.axis === "y" ? [ca, r.at, cb] : [ca, cb, r.at];
};

export function shellGeometry(rects) {
  const pos = [], nor = [], kind = [], idx = [];
  const m = new Matrix4();
  for (const r of rects) {
    const g = new PlaneGeometry(r.a[1] - r.a[0], r.b[1] - r.b[0]);
    m.makeRotationFromEuler(new Euler(...ROT[r.axis](r.facing))).setPosition(...center(r));
    g.applyMatrix4(m);
    const base = pos.length / 3;
    pos.push(...g.getAttribute("position").array);
    nor.push(...g.getAttribute("normal").array);
    for (let i = 0; i < g.getAttribute("position").count; i++) kind.push(r.kind);
    idx.push(...Array.from(g.index.array, (i) => i + base));
    g.dispose();
  }
  const out = new BufferGeometry();
  out.setAttribute("position", new Float32BufferAttribute(pos, 3));
  out.setAttribute("normal", new Float32BufferAttribute(nor, 3));
  out.setAttribute("aKind", new Float32BufferAttribute(kind, 1));
  out.setIndex(idx);
  return out;
}

// A box room: floor, ceiling and four walls, one of them cut by openings.
export function boxRoom(box, { cut = null, skip = [] } = {}) {
  const [x0, y0, z0] = box.min, [x1, y1, z1] = box.max;
  const walls = {
    floor: rect("y", y0, [x0, x1], [z0, z1], 1, 1),
    ceiling: rect("y", y1, [x0, x1], [z0, z1], -1, 2),
    xMin: rect("x", x0, [z0, z1], [y0, y1], 1, 0),
    xMax: rect("x", x1, [z0, z1], [y0, y1], -1, 0),
    zMin: rect("z", z0, [x0, x1], [y0, y1], 1, 0),
    zMax: rect("z", z1, [x0, x1], [y0, y1], -1, 0),
  };
  if (cut) {
    const w = { xMin: ["x", x0, [z0, z1], 1], xMax: ["x", x1, [z0, z1], -1], zMin: ["z", z0, [x0, x1], 1], zMax: ["z", z1, [x0, x1], -1] }[cut.wall];
    walls[cut.wall] = rect(w[0], w[1], w[2], [y0, y1], w[3], 0, cut.holes);
  }
  return Object.entries(walls).filter(([k]) => !skip.includes(k)).flatMap(([, r]) => r);
}
