// The Blender wall maps (src/world/walls.js, PLAN 9 "M5 walls"): what ships, what it costs, and that it never sits on
// the LCP path. The bytes are the files' own, read from public/textures/.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync, statSync, existsSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const { WALL_DATA } = await import("../src/world/walls-data.js");
const { WALL_KEYS, WALL_SIZE, wallBytes, wallGpuMB, wallUrl } = await import("../src/world/walls.js");
const KB = 1024;
// The home page loads every material (room plaster and concrete, corridor wash, dado, paint, shutters plaster and paint).
const BUDGET = { 1: 300 * KB, 2: 800 * KB };
const fileBytes = (size) => WALL_DATA.names.reduce((s, n) => s + statSync(join(root, "public/textures", `${n}-${size}.webp`)).size + statSync(join(root, "public/textures", `${n}-n-${size}.webp`)).size, 0);

test("every material has its albedo and normal map at 512 px (Tier 1) and 1024 px (Tier 2)", () => {
  assert.deepEqual([...WALL_DATA.names].sort(), Object.keys(WALL_KEYS).sort());
  assert.deepEqual(WALL_SIZE, { 1: 512, 2: 1024 });
  for (const n of WALL_DATA.names) for (const s of [512, 1024]) for (const normal of [false, true]) {
    const f = join(root, "public", wallUrl(n, s, normal).replace(/^\//, ""));
    assert.ok(existsSync(f), `${f} missing`);
    const b = readFileSync(f);
    assert.equal(b.toString("ascii", 0, 4) + b.toString("ascii", 8, 12), "RIFFWEBP", `${f} is a WebP`);
  }
});

test("Tier 1 loads at most 300 KB of wall maps, Tier 2 at most 800 KB", () => {
  for (const t of [1, 2]) {
    const real = fileBytes(WALL_SIZE[t]);
    assert.equal(real, wallBytes(t), `walls-data.js is stale for Tier ${t}: run scripts/encode-walls.py`);
    assert.ok(real <= BUDGET[t], `Tier ${t}: ${(real / KB).toFixed(1)} KB > ${BUDGET[t] / KB} KB`);
  }
});

test("GPU memory of the packed maps: one RGBA texture a material with mips", () => {
  assert.ok(wallGpuMB(1) <= 7, `Tier 1 ${wallGpuMB(1)} MB`);
  assert.ok(wallGpuMB(2) <= 27, `Tier 2 ${wallGpuMB(2)} MB`);
});

test("the maps' means are linear and plausible (the shader's relief divides by them)", () => {
  for (const n of WALL_DATA.names) {
    const m = WALL_DATA.mean[n];
    assert.equal(m.length, 3);
    for (const v of m) assert.ok(v > 0.05 && v < 0.95, `${n}: ${m}`);
  }
});

test("packing: normal xy, tone relief around 1.0, chips only on the dado and the paint", async () => {
  const { packWall } = await import("../src/world/walls-pack.js");
  const px = (rgb, n = 4) => Uint8Array.from({ length: n * 4 }, (_, i) => (i % 4 === 3 ? 255 : rgb[i % 4]));
  const flat = packWall("plaster", px([200, 200, 200]), px([128, 128, 255]));
  assert.deepEqual([...flat.slice(0, 4)], [128, 128, 128, 0], "a flat map is the token itself, facing out");
  const dado = packWall("dado", Uint8Array.from([127, 183, 154, 255, 228, 220, 200, 255]), px([128, 128, 255], 2));
  assert.equal(dado[3], 0, "mint paint is not a chip");
  assert.ok(dado[7] > 200, "cream under the paint is a chip");
  const paint = packWall("paint", Uint8Array.from([210, 210, 210, 255, 122, 92, 64, 255]), px([128, 128, 255], 2));
  assert.equal(paint[3], 0);
  assert.ok(paint[7] > 200, "wood under the paint is a chip");
  assert.equal(packWall("concrete", Uint8Array.from([100, 90, 80, 255]), px([128, 128, 255], 1))[3], 0, "concrete has no chips");
});

test("the maps load only after the world's first frame (never on the LCP path)", () => {
  const src = readFileSync(join(root, "src/world/live.js"), "utf8");
  const handoff = src.indexOf('root.classList.add("world-live");\n  live = true;');
  const load = src.indexOf("loadWalls({");
  assert.ok(handoff > 0 && load > handoff, "loadWalls is called after the handoff");
  for (const f of ["src/main.js", "src/world/index.js", "src/world/poster.js", "src/world/still.js"]) {
    assert.ok(!/walls\.js|textures\//.test(readFileSync(join(root, f), "utf8")), `${f} does not touch the wall maps`);
  }
});
