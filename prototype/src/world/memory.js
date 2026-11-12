// GPU memory (05-DESIGN D6.7; M7, the lead's call of 12 Nov 2026): everything the world keeps on the GPU counts
// against one budget of 48 MB, on every tier and at every viewport: the drawing buffers, the shafts' target, the baked
// lime wash and terrazzo, the gạch bông runner, the Blender wall maps and the poster atlas. Until M6 only the buffers
// were counted, and Tier 2 at 1440 held 65 MB (33.4 buffers + 26.7 wall maps + 5.3 atlas), more with the rest.
// The textures are chosen against what is left once the canvas is paid for (planTextures): the 1024 px wall maps, the
// 1024 px atlas and the 512 px runner are Tier 2 luxuries taken only where they fit; the floor is the Tier 1 set.
export const BUDGET_MB = 48;
const MB = 2 ** 20;
const mb = (bytes) => bytes / MB;

// Bytes a canvas pixel costs: depth, resolve and display (12), 4x MSAA about 32 more (tiers.js chooses MSAA).
export const BYTES = { base: 12, msaa: 32 };
// An RGBA8 texture, with its mip chain (x 4/3) unless it has none.
export const textureMB = (w, h, mips = true) => mb(w * h * 4 * (mips ? 4 / 3 : 1));
export const buffersMB = (cw, ch, msaa = false) => mb(cw * ch * (BYTES.base + (msaa ? BYTES.msaa : 0)));
// The shafts' raymarch target (materials/shafts.js): one, shared by every set, ½ the canvas on Tier 2, ¼ on Tier 1.
export const shaftScale = (tier) => (tier >= 2 ? 2 : 4);
export const shaftMB = (cw, ch, tier) => { const s = shaftScale(tier); return mb(Math.max(1, Math.ceil(cw / s)) * Math.max(1, Math.ceil(ch / s)) * 4); };
// The room's baked lime wash and terrazzo (the room's bake, 512 px each, every tier).
export const BAKED_MB = 2 * textureMB(512, 512);
// The runner (gach.js): a field size × size and a border size × size/2.
export const gachMB = (size) => textureMB(size, size) + textureMB(size, size / 2);
// The Blender wall maps: five packed materials, one RGBA texture each.
export const WALL_COUNT = 5;
export const wallsMB = (size) => WALL_COUNT * textureMB(size, size);
// The poster atlas (wall-posters-data.js posterGpuMB, kept in step by test/world.test.mjs).
export const atlasMB = (size) => textureMB(size, size);

// Drawing-buffer pixels the canvas may hold so it, its shaft target (1 B a canvas pixel on Tier 2, ¼ B on Tier 1) and
// the least textures (baked, walls 512, atlas 512, runner 256) stay inside the budget: (48 − 11.2 MB) / 13 B ≈ 2.97 M,
// without MSAA, on either tier (a 2560 × 1440 window at DPR 1 is 3.7 M). With MSAA a pixel costs 45 B, so it can afford
// only about 0.86 M.
export const FLOOR = { walls: 512, atlas: 512, gach: 256 };
export const floorTexturesMB = () => BAKED_MB + wallsMB(FLOOR.walls) + atlasMB(FLOOR.atlas) + gachMB(FLOOR.gach);
export const MAX_PIXELS = 2.9e6;
export const MSAA_PIXELS = 0.85e6;

// What the textures can be, given the canvas already paid for (cw × ch drawing-buffer pixels). Each step up is taken
// only if the whole still fits, in the order the picture misses most: the wall maps (desktop-class Tier 2 only: 600 px
// and wider), then the atlas (the same), then the runner (Tier 2). Returns { walls, atlas, gach, mb } (mb: the total).
export function planTextures({ tier, width, cw, ch, msaa = false, budget = BUDGET_MB }) {
  const plan = { ...FLOOR };
  const fixed = buffersMB(cw, ch, msaa) + shaftMB(cw, ch, tier) + BAKED_MB;
  let used = fixed + wallsMB(plan.walls) + atlasMB(plan.atlas) + gachMB(plan.gach);
  const up = (key, size, cost) => { const extra = cost(size) - cost(plan[key]); if (used + extra <= budget) { plan[key] = size; used += extra; } };
  if (tier >= 2 && width >= 600) { up("walls", 1024, wallsMB); up("atlas", 1024, atlasMB); }
  if (tier >= 2) up("gach", 512, gachMB);
  return { ...plan, mb: +used.toFixed(1), textures: +texturesMB(plan).toFixed(2) };
}
// What a plan's textures hold on the GPU (the baked pair included).
export const texturesMB = (plan) => BAKED_MB + wallsMB(plan.walls) + atlasMB(plan.atlas) + gachMB(plan.gach);

// The most drawing-buffer pixels a canvas may have once its textures are paid for (M7 fix round: the budget holds after
// a resize too). The textures are loaded once, at the plan; a window that grows afterwards gets a canvas this size or
// smaller (renderer.js dprFor: a lower DPR, under 1 if need be), never more memory. Per canvas pixel: the buffers (12 B,
// 44 B with MSAA) and the shafts' target (4 B per shaft texel, ½ × ½ the canvas on Tier 2, ¼ × ¼ on Tier 1); the 0.99
// leaves room for the shaft target's rounding up.
export function canvasCap({ tier, msaa = false, textures = floorTexturesMB(), budget = BUDGET_MB }) {
  const s = shaftScale(tier), perPixel = BYTES.base + (msaa ? BYTES.msaa : 0) + 4 / (s * s);
  const pixels = Math.floor((0.99 * (budget - textures) * MB) / perPixel);
  return Math.max(0, Math.min(pixels, msaa ? MSAA_PIXELS : MAX_PIXELS));
}
