// The gạch bông bake as pure maths (no three.js), so a module worker can run it (gach-worker.js) and the
// still renderer can call it directly. gach.js turns its bytes into textures.
// ---- Gạch bông: the corridor's cement-tile runner (05-DESIGN D0.6.2, ported from proposal saigon-home) ----
// 20 cm encaustic cement tiles in the tile-* pigments. Stored like the terrazzo as albedo relative to the
// cream ground (1.0 = cream, kept at TILE_CREAM), so the shell's shade and sun targets light it like any
// surface. field: 2 × 2 tiles; border: 2 tiles along the runner × 1 across. A holds the wear (quintic value
// noise, 3 octaves, and a 1 px speckle at 3%): the house shader lifts pigment toward cream by up to 18%
// with it, most along the runner's centre line (foot traffic), so the 0.4 m bake never repeats as a pattern.
// Per-tile tone (±2.5%) is the shader's too, by tile id. No brick anywhere in the corridor.
export const TILE_CREAM = 0.92; // = textures.js TILE_CREAM (test/world.test.mjs keeps them equal)
export const GACH = {
  field: { disc: 0.27, discLobe: 0.105, lobe: 0.085, leaf: [0.33, 0.1, 0.036] }, // tile units, centre 0
  border: { loz: 0.22, core: 0.08, link: 0.07, bar: 0.012, line: [0.39, 0.42] },
  grout: { px: 1.2, dark: 0.86 }, // 1.2 px at 256 px a tile, ≤ 14% darker
};
// Pigment ids (index into P): 0 cream, 1 ochre, 2 indigo, 3 jade. Plain maths, no allocation per sample.
const quat = (x, y, r) => {
  const a = x - r, b = x + r, c = y - r, d = y + r, r2 = r * r;
  return a * a + y * y < r2 || b * b + y * y < r2 || x * x + c * c < r2 || x * x + d * d < r2;
};
// x, y in −0.5..0.5 of a field tile. An indigo disc with a cream Hoa thị where four tiles meet, an ochre
// quatrefoil in the middle, pointed jade leaves toward each edge's midpoint (a pair across every joint).
function fieldTile(x, y) {
  const F = GACH.field;
  const cx = x - (x < 0 ? -0.5 : 0.5), cy = y - (y < 0 ? -0.5 : 0.5);
  if (cx * cx + cy * cy < F.disc * F.disc) return quat(cx, cy, F.discLobe) ? 0 : 2;
  if (quat(x, y, F.lobe)) return 1;
  const c = F.leaf[0], a = F.leaf[1], w = F.leaf[2], ax = x < 0 ? -x : x, ay = y < 0 ? -y : y;
  let t = (ax - c) / a;
  if (t > -1 && t < 1 && Math.abs(y) < w * (1 - t * t)) return 3;
  t = (ay - c) / a;
  if (t > -1 && t < 1 && Math.abs(x) < w * (1 - t * t)) return 3;
  return 0;
}
// a along the runner, b across (−0.5 at the field, +0.5 at the runner's outer edge). A chain of indigo
// lozenges with ochre cores, linked at the joints, between two jade lines.
function borderTile(a, b) {
  const B = GACH.border, aa = Math.abs(a), ab = Math.abs(b);
  if (ab > B.line[0] && ab < B.line[1]) return 3;
  const l = aa + ab;
  if (l < B.core) return 1;
  if (l < B.loz) return 2;
  if (0.5 - aa + ab < B.link) return 2;
  if (ab < B.bar) return 2;
  return 0;
}
function rng(seed) {
  let s = seed >>> 0;
  return () => ((s = (s + 0x6d2b79f5) >>> 0), (((s ^ (s >>> 15)) * (s | 1)) >>> 0) / 4294967296);
}
// Tileable gradient noise over a W × H texture, quintic fade, roughly 0..1 (no value-noise squares at 2× zoom).
function quintic(periodX, periodY, seed, W, H) {
  const r = rng(seed), gx = new Float32Array(periodX * periodY), gy = new Float32Array(periodX * periodY);
  for (let i = 0; i < gx.length; i++) { const a = r() * Math.PI * 2; gx[i] = Math.cos(a); gy[i] = Math.sin(a); }
  const q = (t) => t * t * t * (t * (t * 6 - 15) + 10);
  return (x, y) => {
    const fx = (x / W) * periodX, fy = (y / H) * periodY;
    const i = Math.floor(fx), j = Math.floor(fy), u = fx - i, v = fy - j, s = q(u), t = q(v);
    const dot = (a, b, dx, dy) => { const k = (((b % periodY) + periodY) % periodY) * periodX + (((a % periodX) + periodX) % periodX); return gx[k] * dx + gy[k] * dy; };
    const n = (dot(i, j, u, v) * (1 - s) + dot(i + 1, j, u - 1, v) * s) * (1 - t) + (dot(i, j + 1, u, v - 1) * (1 - s) + dot(i + 1, j + 1, u - 1, v - 1) * s) * t;
    return 0.5 + 0.7 * n;
  };
}
// The bake itself, pure maths into a Uint8Array (a module worker could run it unchanged): W × H pixels of
// tilesX × tilesY tiles of `kind`, 2 × 2 samples a pixel. P: pigments relative to the cream. Returns
// rows(y0, y1), which fills those rows of `data`: the noise tables are set up once, the slices only sample.
export function gachBaker(kind, W, H, tilesX, tilesY, P, data = new Uint8Array(W * H * 4)) {
  const fn = kind === "field" ? fieldTile : borderTile, tw = W / tilesX, th = H / tilesY;
  const pig = [P.cream, P.ochre, P.indigo, P.jade];
  const g = GACH.grout.px / 2 / 256; // grout half-width in tile units
  const o1 = quintic(5 * tilesX, 5 * tilesY, kind === "field" ? 211 : 307, W, H), o2 = quintic(11 * tilesX, 11 * tilesY, 223, W, H), o3 = quintic(23 * tilesX, 23 * tilesY, 227, W, H);
  // 1 px speckle, the same pixels however the bake is sliced.
  const speckAt = (x, y) => { let h = Math.imul(x * 73856093 ^ y * 19349663 ^ (kind === "field" ? 83492791 : 2971215073), 0x9e3779b1); h ^= h >>> 15; return ((h >>> 0) % 1000) < 30; };
  const S = [0.25, 0.75];
  const rows = (y0, y1) => { for (let py = y0; py < y1; py++) for (let px = 0; px < W; px++) {
    let cr = 0, cg = 0, cb = 0, grout = 0;
    for (let k = 0; k < 4; k++) {
      const u = ((px + S[k & 1]) % tw) / tw - 0.5, v = ((py + S[k >> 1]) % th) / th - 0.5;
      const c = pig[fn(u, v)];
      cr += c[0]; cg += c[1]; cb += c[2];
      if (0.5 - Math.abs(u) < g || 0.5 - Math.abs(v) < g) grout++;
    }
    const k = 1 - (1 - GACH.grout.dark) * (grout / 4);
    const n = 0.55 * o1(px, py) + 0.3 * o2(px, py) + 0.15 * o3(px, py);
    const speck = speckAt(px, py);
    const wear = speck ? 1 : Math.min(1, Math.max(0, (n - 0.42) / 0.36));
    const i = (py * W + px) * 4, s = TILE_CREAM * 255 * k * (speck ? 0.97 : 1);
    data[i] = Math.min(255, s * (cr / 4));
    data[i + 1] = Math.min(255, s * (cg / 4));
    data[i + 2] = Math.min(255, s * (cb / 4));
    data[i + 3] = 255 * wear;
  } };
  rows.data = data;
  return rows;
}
export const gachPixels = (kind, W, H, tilesX, tilesY, P) => { const r = gachBaker(kind, W, H, tilesX, tilesY, P); r(0, H); return r.data; };
