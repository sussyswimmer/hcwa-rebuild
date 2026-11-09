import { DataTexture, LinearMipmapLinearFilter, LinearFilter, RepeatWrapping, RGBAFormat, UnsignedByteType } from "three";
import { tok } from "./colors.js";

// Lime wash and terrazzo, baked once at startup (05-DESIGN D6.3) so every surface pays one
// texture fetch, never per-pixel fbm. Baked in slices that yield to the main thread.
const N = 512;
export const yieldNow = () => (globalThis.scheduler?.yield ? scheduler.yield() : new Promise((r) => setTimeout(r, 0)));

export function rng(seed) {
  let s = seed >>> 0;
  return () => ((s = (s + 0x6d2b79f5) >>> 0), (((s ^ (s >>> 15)) * (s | 1)) >>> 0) / 4294967296 + 0);
}

// Tileable value noise: lattice of `period` cells across the texture.
function lattice(period, seed) {
  const r = rng(seed), v = new Float32Array(period * period);
  for (let i = 0; i < v.length; i++) v[i] = r();
  return (x, y) => {
    const fx = (x / N) * period, fy = (y / N) * period;
    const i = Math.floor(fx), j = Math.floor(fy), u = fx - i, w = fy - j;
    const s = u * u * (3 - 2 * u), t = w * w * (3 - 2 * w);
    const at = (a, b) => v[((b % period) + period) % period * period + (((a % period) + period) % period)];
    return (at(i, j) * (1 - s) + at(i + 1, j) * s) * (1 - t) + (at(i, j + 1) * (1 - s) + at(i + 1, j + 1) * s) * t;
  };
}

export function texture(data, w = N, h = N) {
  const t = new DataTexture(data, w, h, RGBAFormat, UnsignedByteType);
  t.wrapS = t.wrapT = RepeatWrapping;
  t.generateMipmaps = true;
  t.minFilter = LinearMipmapLinearFilter;
  t.magFilter = LinearFilter;
  t.needsUpdate = true;
  return t;
}

// R: broad mottling, G: fine mottling, B: vertical brush direction. A: the loang of an old lime wash
// (05-DESIGN D0.6.2, colour-light's bakeLimeWash): metre-wide patches, read by the corridor's house in the
// same fetch; the room never reads A. Covers 4 x 4 m.
export async function bakeLimeWash({ slice = true } = {}) {
  const a = lattice(6, 11), b = lattice(14, 23), c = lattice(40, 37), d = lattice(96, 41);
  const brushX = lattice(64, 53), brushY = lattice(4, 59), loang = lattice(3, 71), loang2 = lattice(7, 73);
  const data = new Uint8Array(N * N * 4);
  for (let y = 0; y < N; y++) {
    for (let x = 0; x < N; x++) {
      const i = (y * N + x) * 4;
      data[i] = 255 * (0.62 * a(x, y) + 0.38 * b(x, y));
      data[i + 1] = 255 * (0.6 * c(x, y) + 0.4 * d(x, y));
      data[i + 2] = 255 * (0.7 * brushX(x, y) + 0.3 * brushY(x, y));
      data[i + 3] = 255 * (0.7 * loang(x, y) + 0.3 * loang2(x, y));
    }
    if (slice && y % 64 === 63) await yieldNow();
  }
  return texture(data);
}

// Terrazzo (đá mài): albedo relative to the cement (1.0 = cement, stored at 0.92 so cream
// chips can sit a little above it). Chip colors come from tokens, one a quiet logo blue.
// Covers 2 x 2 m: chips of 3 to 8 mm.
export const TERRAZZO_CEMENT = 0.92;
export async function bakeTerrazzo({ slice = true } = {}) {
  const cement = tok("lime-100");
  const chipTokens = [["lime-300", 0], ["ink-500", 0.45], ["gold-700", 0.5], ["blue-700", 0.6]];
  const chips = chipTokens.map(([name, lift]) => {
    const ch = tok(name).lerp(cement, lift);
    return [ch.r / cement.r, ch.g / cement.g, ch.b / cement.b];
  });
  const weights = [0.5, 0.78, 0.92, 1];
  const r = rng(97), cloud = lattice(10, 101);
  const data = new Uint8Array(N * N * 4);
  for (let y = 0; y < N; y++) for (let x = 0; x < N; x++) {
    const i = (y * N + x) * 4, k = TERRAZZO_CEMENT * (0.97 + 0.06 * cloud(x, y)) * 255;
    data[i] = data[i + 1] = data[i + 2] = k; data[i + 3] = 255;
  }
  const count = 9000, contrast = 0.4;
  for (let n = 0; n < count; n++) {
    const cx = r() * N, cy = r() * N, rad = 0.8 + r() * r() * 2.2, pick = r();
    const tint = chips[weights.findIndex((w) => pick <= w)];
    for (let y = Math.floor(cy - rad - 1); y <= cy + rad + 1; y++) for (let x = Math.floor(cx - rad - 1); x <= cx + rad + 1; x++) {
      const cover = Math.min(1, Math.max(0, rad + 0.5 - Math.hypot(x + 0.5 - cx, y + 0.5 - cy))) * contrast;
      if (cover <= 0) continue;
      const i = (((y + N) % N) * N + ((x + N) % N)) * 4;
      for (let ch = 0; ch < 3; ch++) data[i + ch] = Math.min(255, data[i + ch] * (1 - cover) + TERRAZZO_CEMENT * 255 * tint[ch] * cover);
    }
    if (slice && n % 3000 === 2999) await yieldNow();
  }
  return texture(data);
}

// The gạch bông runner's albedo is stored relative to its cream ground at this level (gach.js), so worn
// pigment can lift toward cream and the house shader (materials/still-surface.js) divides it back out.
export const TILE_CREAM = 0.92;
