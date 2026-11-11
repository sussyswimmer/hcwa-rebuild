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
  // Plaster's A is its crack mask: the crack's core, its lips (one texel at 16 px; 8 at 1024 px), nothing beyond.
  const S = 16, wall = Uint8Array.from({ length: S * S * 4 }, (_, i) => (i % 4 === 3 ? 255 : Math.floor(i / 4) % S === 5 ? 120 : 200));
  const cracked = packWall("plaster", wall, px([128, 128, 255], S * S));
  const A = (x, y) => cracked[(y * S + x) * 4 + 3];
  assert.equal(A(5, 3), 255, "a crack is in the mask");
  assert.ok(A(4, 3) > 0 && A(6, 3) > 0, "and its lips");
  assert.equal(A(8, 3), 0, "the wall beyond is not");
});

// Every module a file pulls in before its first await: static imports only (an `import()` is a later chunk).
function staticGraph(entry) {
  const seen = new Set(), todo = [join(root, entry)];
  while (todo.length) {
    const f = todo.pop();
    if (seen.has(f) || !/\.m?js$/.test(f) || !existsSync(f)) continue;
    seen.add(f);
    const src = readFileSync(f, "utf8").replace(/\/\*[\s\S]*?\*\/|^\s*\/\/.*$/gm, "");
    for (const [, spec] of src.matchAll(/^\s*import\s+(?:[^'"]*?\sfrom\s+)?["'](\.{1,2}\/[^"']+)["']/gm)) todo.push(join(dirname(f), spec));
  }
  return [...seen].map((f) => f.slice(root.length + 1));
}

test("the maps load only after the world's first frame (never on the LCP path)", () => {
  const src = readFileSync(join(root, "src/world/live.js"), "utf8");
  const handoff = src.indexOf('root.classList.add("world-live");\n  live = true;');
  const load = src.indexOf("loadWalls({");
  assert.ok(handoff > 0 && load > handoff, "loadWalls is called after the handoff");
  // The first-paint path: main.js and the world's entry with everything they import statically. The hero posters
  // (poster.js) are the LCP and hand off to the live room, which starts on its procedural walls: they render without
  // the maps, so the handoff is exact and their bytes do not move.
  const firstPaint = [...staticGraph("src/main.js"), ...staticGraph("src/world/index.js"), "src/world/poster.js"];
  assert.ok(firstPaint.includes("src/ui/stills.js") && firstPaint.includes("src/world/tiers.js"), "the graph walk follows imports");
  for (const f of firstPaint) assert.ok(!/walls(-[a-z]+)?\.js|textures\//.test(readFileSync(join(root, f), "utf8")), `${f} does not touch the wall maps`);
  // The named stills (still.js) render with the maps (PLAN-M4 6.4) but only offline: nothing the site runs imports the
  // still entry, so it can never put the maps in front of a page (the still files themselves keep their budgets,
  // test/stills.test.mjs).
  const site = [...new Set([...staticGraph("src/main.js"), ...staticGraph("src/world/index.js"), ...staticGraph("src/world/live.js")])];
  for (const f of site) assert.ok(!/["']\.{1,2}\/(world\/)?still\.js["']/.test(readFileSync(join(root, f), "utf8")), `${f} does not import the still entry`);
});
