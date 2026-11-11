import { DataTexture, LinearFilter, LinearMipmapLinearFilter, NoColorSpace, RGBAFormat, RepeatWrapping, UnsignedByteType } from "three";
import { WALL_DATA } from "./walls-data.js";
import { packFrom } from "./walls-pack.js";

// The Blender wall materials (Maxwell, 11 Nov 2026: "the walls look too perfect, like play-doh; they need character:
// scratches, a real wall"). blender/walls.py bakes seamless maps with Cycles; scripts/encode-walls.py writes them to
// public/textures/ as WebP, 512 px for Tier 1 and 1024 px for Tier 2, an albedo and a tangent-space normal each:
//   plaster   the hero room's and the shutters room's walls and ceilings, the corridor's ceiling
//   limewash  the corridor's yellow wash above the dado
//   dado      the corridor's mint oil-paint dado (its chips show the plaster)
//   paint     doors, frames and shutters: neutral paint over wood, tinted per unit in the shader
//   concrete  the breeze blocks (the room's screen, the corridor's screen, the transoms)
// They never touch the LCP path: live.js asks for them only after the world's first frame (the hero is live), and
// until they arrive, or if they never do, every surface keeps its procedural look. A worker fetches, decodes and packs
// each material into one RGBA texture (walls-pack.js: normal xy, tone relief, chip mask). Every material of every set
// spreads the same uniform objects (wallUniforms()), so one load reaches the room, the corridor and the shutters;
// uWallK fades them in over WALL_FADE ms (a pop on a held frame would read as a glitch). The named stills (still.js:
// inner headers, doors, bays) render with them at full strength (stillWalls(), Tier 2 maps), offline. The hero posters
// (poster.js) do not: they are the LCP and hand off to the live room, which starts on its procedural walls.
// glsl/walls.js holds the shading.
export const WALL_KEYS = { plaster: "Pl", limewash: "Lw", dado: "Da", paint: "Pt", concrete: "Co" };
// Map size per tier. Tier 2 at 1024 px costs ~28 MB of GPU memory (5 × 4 MB + mips); 512 px is ~7 MB (PLAN 9, M5).
export const WALL_SIZE = { 1: 512, 2: 1024 };
export const WALL_FADE = 700;
// Real scale: metres one map covers on a wall, on paint, on a breeze block (each block takes its own corner of it).
export const WALL_SCALE = { wall: 2, paint: 1, block: 1 };

const shared = { uWallK: { value: 0 }, uWallMix: { value: 0 } };
for (const k of Object.values(WALL_KEYS)) shared[`uW${k}`] = { value: null };
// The same uniform objects for every material of every set (each set spreads them into its U).
export const wallUniforms = () => shared;

// public/textures/ under the page's base; a relative base (the WordPress bundle) resolves against this chunk (assets/).
// (The chunk's URL goes through a variable: Vite would read new URL(`…${x}`, import.meta.url) as a glob and inline src/.)
const env = import.meta.env ?? {}, chunk = import.meta.url;
export function wallUrl(name, size, normal = false) {
  const file = `textures/${name}${normal ? "-n" : ""}-${size}.webp`;
  const base = env.BASE_URL ?? "/";
  return base.startsWith("/") ? base + file : new URL(`../${file}`, chunk).href;
}
const sizeFor = (tier) => WALL_SIZE[tier >= 2 ? 2 : 1];
// Bytes the page downloads for a tier (every material; the budget test holds the same sums).
export const wallBytes = (tier) => WALL_DATA.names.reduce((s, n) => s + WALL_DATA.bytes[sizeFor(tier)][n].reduce((a, b) => a + b, 0), 0);
// GPU bytes once uploaded: RGBA8 with its mip chain.
export const wallGpuMB = (tier) => +((WALL_DATA.names.length * sizeFor(tier) ** 2 * 4 * 4) / 3 / 2 ** 20).toFixed(1);

function texture({ data, size }, aniso) {
  const t = new DataTexture(data, size, size, RGBAFormat, UnsignedByteType);
  t.wrapS = t.wrapT = RepeatWrapping;
  t.minFilter = LinearMipmapLinearFilter;
  t.magFilter = LinearFilter;
  t.generateMipmaps = true;
  t.anisotropy = aniso;
  t.colorSpace = NoColorSpace; // data, not colour: the relief is already linear
  t.needsUpdate = true;
  return t;
}

// One material packed in a module worker when the page can start one, else on the main thread (decode is still off it).
function packed(name, size, signal) {
  const urls = [wallUrl(name, size), wallUrl(name, size, true)];
  if (typeof Worker !== "function" || typeof OffscreenCanvas !== "function") return packFrom(name, size, urls, signal);
  return new Promise((ok, no) => {
    let w;
    try { w = new Worker(new URL("./walls-worker.js", import.meta.url), { type: "module" }); } catch { ok(packFrom(name, size, urls, signal)); return; }
    const done = () => { w.terminate(); signal?.removeEventListener("abort", stop); };
    const stop = () => { done(); no(new Error("walls: aborted")); };
    signal?.addEventListener("abort", stop, { once: true });
    w.onmessage = ({ data }) => { done(); data.error ? no(new Error(data.error)) : ok(data); };
    w.onerror = (e) => { done(); e.preventDefault?.(); ok(packFrom(name, size, urls, signal)); };
    w.postMessage({ name, size, urls });
  });
}

// Fetches, packs and uploads every material for the tier, one upload a slice, then hands them to the shared uniforms.
// Throws on any failure (nothing is handed over); the caller keeps the procedural look.
export async function loadWalls({ renderer, tier, yieldNow, signal }) {
  const size = sizeFor(tier), aniso = tier >= 2 ? 4 : 2;
  const all = await Promise.all(WALL_DATA.names.map((n) => packed(n, size, signal)));
  const made = [];
  try {
    for (const p of all) {
      if (signal?.aborted) throw new Error("walls: aborted");
      const t = texture(p, aniso);
      renderer.initTexture(t);
      made.push([`uW${WALL_KEYS[p.name]}`, t]);
      await yieldNow();
    }
  } catch (e) {
    for (const [, t] of made) t.dispose();
    throw e;
  }
  for (const [k, t] of made) {
    shared[k].value = t;
    t.image.data = null; // uploaded: the GPU holds it, the page need not
  }
  shared.uWallMix.value = tier >= 2 ? 1 : 0;
  return { size, kb: Math.round(wallBytes(tier) / 1024), gpuMB: wallGpuMB(tier) };
}

// The named stills (still.js, offline): the tier's maps at full strength, at once (nothing fades in a render).
export async function stillWalls({ renderer, tier }) {
  const r = await loadWalls({ renderer, tier, yieldNow: () => Promise.resolve() });
  shared.uWallK.value = 1;
  return r;
}

// The live gate stepping Tier 2 down: the second, rotated sample goes with Tier 2. The maps stay (already paid for).
export const setWallTier = (tier) => { shared.uWallMix.value = tier >= 2 && shared.uWPl.value ? 1 : 0; };
export const wallsReady = () => Boolean(shared.uWPl.value);

export function disposeWalls() {
  for (const k of Object.keys(shared)) if (shared[k].value?.isTexture) { shared[k].value.dispose(); shared[k].value = null; }
  shared.uWallK.value = 0;
  shared.uWallMix.value = 0;
}
