import { ClampToEdgeWrapping } from "three";
import { tok } from "./colors.js";
import { texture, yieldNow } from "./textures.js";

// The corridor's gạch bông runner as textures (05-DESIGN D0.6.2). Its own module, so it rides in the
// corridor's lazy chunk, never with the hero. The pixels come from gach-bake.js: in a module worker when the
// page can start one (a 4× CPU trace put the main-thread bake's first slice at 32 ms, over D0.6.2's 16 ms
// rule), else in slices that yield to the main thread; the stills call it in one go.

// The border's v spans exactly one tile across the runner: clamped, so its outer edge never samples the field side.
function tileTexture(w, h, data, aniso, clampV = false) {
  const t = texture(data, w, h);
  t.anisotropy = aniso;
  if (clampV) t.wrapT = ClampToEdgeWrapping;
  return t;
}
export const gachPigments = () => {
  const lin = (c) => [c.r, c.g, c.b], cream = lin(tok("tile-cream"));
  return Object.fromEntries(["cream", "ochre", "indigo", "jade"].map((k) => { const c = lin(tok("tile-" + k)); return [k, c.map((v, i) => v / cream[i])]; }));
};
// Both textures' pixels from gach-worker.js, as transferable buffers.
function inWorker(size, P) {
  return new Promise((ok, no) => {
    let w;
    try { w = new Worker(new URL("./gach-worker.js", import.meta.url), { type: "module" }); } catch (e) { no(e); return; }
    const done = () => w.terminate();
    w.onmessage = ({ data }) => { done(); ok(data); };
    w.onerror = (e) => { done(); no(e); };
    w.postMessage({ size, P });
  });
}
// size: 512 on Tier 2 and the stills (a tile 256 px), 256 on Tier 1 (a near tile is under 100 px on a
// phone). worker: bake in a module worker when one can start (the live corridor); otherwise rows a slice that
// yields to the main thread (8 on Tier 1, 16 on Tier 2), or all at once with slice false (the stills).
// onSlice(ms): main-thread time per slice, for the 16 ms rule (0 when the worker baked).
export async function bakeGachBong({ slice = true, worker = false, aniso = 4, size = 512, rows = 16, onSlice = null } = {}) {
  const P = gachPigments(), W = size, H2 = size / 2;
  if (worker && typeof Worker === "function") {
    try {
      const [field, border] = await inWorker(size, P);
      onSlice?.(0);
      return { field: tileTexture(W, W, field, aniso), border: tileTexture(W, H2, border, aniso, true), worker: true };
    } catch { /* no module worker here: bake on the main thread, sliced */ }
  }
  // The maths itself only loads here when no worker baked it (the worker carries its own copy).
  const { gachBaker } = await import("./gach-bake.js");
  const bake = async (kind, w, h, tx, ty) => {
    let t0 = performance.now();
    const fill = gachBaker(kind, w, h, tx, ty, P);
    for (let y = 0; y < h; y += rows) {
      fill(y, Math.min(h, y + rows));
      onSlice?.(performance.now() - t0);
      if (slice) await yieldNow();
      t0 = performance.now();
    }
    return fill.data;
  };
  const field = await bake("field", W, W, 2, 2);
  const border = await bake("border", W, H2, 2, 1);
  return { field: tileTexture(W, W, field, aniso), border: tileTexture(W, H2, border, aniso, true), worker: false };
}
