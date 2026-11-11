// One GPU texture per wall material (walls.js), packed from the two shipped WebPs (albedo, normal):
//   R, G  the tangent-space normal's x and y (OpenGL, +Y = +v); the shader rebuilds z
//   B     the albedo's tone relief: its linear luminance over the map's mean, 0..2 in 0..255 (1.0 = the token itself)
//   A     the dado and the paint: what shows through a chip (the plaster under the mint paint, the wood under a door's
//         paint). Plaster, lime wash and concrete: where the cracks and their relief are (crackMask), so the shader can
//         leave a crack out of a stretch of wall whole, its dark core and the lit and shaded lips its normal gives it.
// Only the relief of tone comes from the map, so the house tokens keep the hue and the contrast bars hold; a chip
// takes its own token in the shader. Four bytes a texel instead of eight for two textures (the GPU memory budget).
// Pure: used by walls-worker.js and, where no worker starts, by walls.js on the main thread.
const LIN = new Float32Array(256);
for (let i = 0; i < 256; i++) { const c = i / 255; LIN[i] = c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4; }
const smooth = (a, b, x) => { const t = Math.min(Math.max((x - a) / (b - a), 0), 1); return t * t * (3 - 2 * t); };
// Chip masks from the albedo's own colour (bytes): the dado's chips are cream on mint (red rises past green), the
// door's are brown wood on neutral grey (red well above blue).
const MASK = {
  dado: (r, g) => smooth(-6, 10, r - g),
  paint: (r, g, b) => smooth(18, 40, r - b),
};

// The crack mask: 1 on a crack's core (its albedo 6-16% under the map's mean luminance and darker) and falling off
// linearly over R texels around it, R = 8 at 1024 px (the normal's lips around a crack reach about 8 texels: measured
// on the bake, the normal's deviation is back to the wall's own 8 texels out). The map is seamless, so it wraps.
function crackMask(rel, size) {
  const n = size * size, R = Math.max(1, Math.round((8 * size) / 1024)), step = 1 / (R + 1);
  let a = new Float32Array(n), b = new Float32Array(n);
  for (let i = 0; i < n; i++) a[i] = smooth(0.06, 0.16, 1 - rel[i]);
  for (let k = 0; k < R; k++) {
    for (let y = 0; y < size; y++) {
      const row = y * size, up = ((y + size - 1) % size) * size, dn = ((y + 1) % size) * size;
      for (let x = 0; x < size; x++) {
        const l = (x + size - 1) % size, r = (x + 1) % size;
        const m = Math.max(a[row + l], a[row + r], a[up + x], a[dn + x]) - step;
        b[row + x] = Math.max(a[row + x], m);
      }
    }
    [a, b] = [b, a];
  }
  return a;
}

// albedo, normal: RGBA bytes (Uint8ClampedArray or Uint8Array) of the same size, row 0 = v 0.
export function packWall(name, albedo, normal) {
  const n = albedo.length / 4, out = new Uint8Array(n * 4), lum = new Float32Array(n), mask = MASK[name];
  let sum = 0;
  for (let i = 0; i < n; i++) {
    const j = i * 4, l = 0.2126 * LIN[albedo[j]] + 0.7152 * LIN[albedo[j + 1]] + 0.0722 * LIN[albedo[j + 2]];
    lum[i] = l;
    sum += l;
  }
  const mean = sum / n || 1;
  for (let i = 0; i < n; i++) lum[i] /= mean;
  const size = Math.round(Math.sqrt(n));
  const cracks = mask || size * size !== n ? null : crackMask(lum, size);
  for (let i = 0; i < n; i++) {
    const j = i * 4;
    out[j] = normal[j];
    out[j + 1] = normal[j + 1];
    out[j + 2] = Math.min(255, Math.round(lum[i] * 127.5));
    out[j + 3] = mask ? Math.round(255 * mask(albedo[j], albedo[j + 1], albedo[j + 2])) : cracks ? Math.round(255 * cracks[i]) : 0;
  }
  return out;
}

// The pixels of a decoded image, bottom row first (the bitmap was decoded flipped), through a 2D canvas.
export function pixels(bitmap) {
  const { width: w, height: h } = bitmap;
  const c = typeof OffscreenCanvas === "function" ? new OffscreenCanvas(w, h) : Object.assign(document.createElement("canvas"), { width: w, height: h });
  const g = c.getContext("2d", { willReadFrequently: true, colorSpace: "srgb" });
  g.drawImage(bitmap, 0, 0);
  return g.getImageData(0, 0, w, h, { colorSpace: "srgb" }).data;
}

export async function decode(url, signal) {
  const r = await fetch(url, { signal, priority: "low" });
  if (!r.ok) throw new Error(`${url}: ${r.status}`);
  return createImageBitmap(await r.blob(), { imageOrientation: "flipY", premultiplyAlpha: "none", colorSpaceConversion: "none" });
}

// Both maps of one material, fetched, decoded and packed: { name, size, data }.
export async function packFrom(name, size, urls, signal) {
  const [a, nm] = await Promise.all([decode(urls[0], signal), decode(urls[1], signal)]);
  if (a.width !== size || nm.width !== size) throw new Error(`${name}: expected ${size} px`);
  const data = packWall(name, pixels(a), pixels(nm));
  a.close?.(); nm.close?.();
  return { name, size, data };
}
