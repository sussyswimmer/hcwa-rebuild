// Wall posters (M6; src/world/wall-posters-data.js): original, never propaganda, never near Little Rose, in budget.
import { test } from "node:test";
import assert from "node:assert/strict";
import { createHash } from "node:crypto";
import { readFileSync, statSync } from "node:fs";
import { color as TOK } from "../src/world/tokens.js";
import { ATLAS, CORRIDOR_POSTERS, FORBIDDEN_INKS, LR_CLEAR, PAPER, S3_POSTERS, WALL_POSTERS, atlasSizeFor, cellUv, corridorPosters, posterGpuMB } from "../src/world/wall-posters-data.js";
import { posterSvg } from "../scripts/wall-posters.mjs";
import { corridorLayout, readBeats } from "../src/world/journey.js";

const file = (p) => new URL(`../${p}`, import.meta.url);
const report = JSON.parse(readFileSync(file("src/world/wall-posters-report.json"), "utf8"));

test("the set: unique ids, no lettering, token inks only, never Little Rose's, brick or red", () => {
  const ids = WALL_POSTERS.map((p) => p.id);
  assert.equal(new Set(ids).size, ids.length);
  for (const p of WALL_POSTERS) {
    // No lettering (M6 fix): a word drawn into a texture is not HTML and has no English.
    assert.equal(p.word, null, `${p.id}: no word`);
    for (const ink of [...p.inks, PAPER]) {
      assert.ok(TOK[ink], `${p.id}: ${ink} is a token`);
      assert.ok(!FORBIDDEN_INKS.includes(ink) && !/pink|rose|red|brick|error/.test(ink), `${p.id}: ${ink} is forbidden`);
    }
  }
  assert.equal(WALL_POSTERS.filter((p) => p.atlas != null).length, ATLAS.cols * ATLAS.rows, "six in the atlas");
});

const hexes = (svg) => [...svg.matchAll(/(?:fill|stroke)="(#[0-9A-Fa-f]{6})"/g)].map((m) => m[1].toUpperCase());
const texts = (svg) => [...svg.matchAll(/<text\b[^>]*>([^<]*)<\/text>/g)].map((m) => m[1]);

test("the art: no image, no link, no text at all, every colour one of its inks", () => {
  for (const p of WALL_POSTERS) {
    const svg = posterSvg(p.id);
    assert.doesNotMatch(svg, /<image|<foreignObject|<use\b|href=|xlink|<script/i, `${p.id}: nothing from outside`);
    assert.deepEqual(texts(svg), [], `${p.id}: no lettering`);
    assert.doesNotMatch(svg, /<text\b|font-family/, `${p.id}: no text element`);
    const allowed = new Set([...p.inks, PAPER].map((k) => TOK[k].toUpperCase()));
    for (const h of hexes(svg)) assert.ok(allowed.has(h), `${p.id}: ${h} is not one of its inks`);
  }
});

// A star: a closed outline of 8-12 points whose distance from its centre alternates between two radii, the inner
// under 0.6 of the outer. No poster may draw one (no stars of any kind).
function stars(svg) {
  const found = [];
  const polys = [...svg.matchAll(/<polygon[^>]*points="([^"]+)"/g)].map((m) => m[1].trim().split(/[\s,]+/).map(Number));
  for (const [, d] of svg.matchAll(/<path[^>]*\sd="([^"]+)"/g)) {
    for (const sub of d.split(/(?=M)/)) {
      if (/[CcQqAaSsTtHhVv]/.test(sub) || !/[Zz]/.test(sub)) continue;
      polys.push((sub.match(/-?\d*\.?\d+/g) ?? []).map(Number));
    }
  }
  for (const flat of polys) {
    const pts = [];
    for (let i = 0; i + 1 < flat.length; i += 2) pts.push([flat[i], flat[i + 1]]);
    if (pts.length < 8 || pts.length > 12) continue;
    const cx = pts.reduce((s, p) => s + p[0], 0) / pts.length, cy = pts.reduce((s, p) => s + p[1], 0) / pts.length;
    const r = pts.map(([x, y]) => Math.hypot(x - cx, y - cy));
    const even = r.filter((_, i) => i % 2 === 0), odd = r.filter((_, i) => i % 2 === 1);
    const lo = Math.min(Math.max(...even), Math.max(...odd)), hi = Math.max(Math.min(...even), Math.min(...odd));
    if (pts.length % 2 === 0 && lo / hi < 0.6) found.push(pts.length);
  }
  return found;
}

test("no stars: no outline of 8-12 points alternating between two radii", () => {
  // The scan itself finds a five-pointed star.
  const star = Array.from({ length: 10 }, (_, i) => { const a = (i * Math.PI) / 5, r = i % 2 ? 20 : 50; return `${(100 + r * Math.sin(a)).toFixed(1)} ${(100 - r * Math.cos(a)).toFixed(1)}`; });
  assert.equal(stars(`<path d="M${star.join(" L")} Z"/>`).length, 1);
  for (const p of WALL_POSTERS) assert.deepEqual(stars(posterSvg(p.id)), [], p.id);
});

test("the prints: no pink at all, red under 2%, the report matches the files", () => {
  for (const p of WALL_POSTERS) {
    const r = report.prints[p.id];
    assert.ok(r, `${p.id} printed`);
    assert.equal(r.pink, 0, `${p.id}: pink ${r.pink}`);
    assert.ok(r.red <= 0.02, `${p.id}: red ${r.red}`);
    const sha = createHash("sha256").update(readFileSync(file(`art/prints/${p.id}.webp`))).digest("hex").slice(0, 16);
    assert.equal(sha, r.sha, `${p.id}: the print changed since the report (run scripts/encode-wall-posters.py)`);
  }
});

test("the atlases stay in budget and are the ones the report measured", () => {
  for (const [size, kb] of Object.entries(ATLAS.budgetKB)) {
    const path = file(`public/textures/wall-posters-${size}.webp`), bytes = statSync(path).size;
    assert.ok(bytes <= kb * 1024, `${size} px: ${(bytes / 1024).toFixed(1)} KB > ${kb} KB`);
    assert.equal(createHash("sha256").update(readFileSync(path)).digest("hex").slice(0, 16), report.atlases[size].sha, `${size} px atlas stale`);
  }
  // Tier 1 and phones pay the 512 atlas once, after the world is live (live.js); Tier 2 the 1024.
  assert.ok(ATLAS.budgetKB[1024] <= 100 && ATLAS.budgetKB[512] <= 34);
  // Cells never overlap and stay inside the atlas.
  for (const size of [512, 1024, 2048]) for (let i = 0; i < 6; i++) { const [u0, v0, u1, v1] = cellUv(i, size); assert.ok(u0 >= 0 && v0 >= 0 && u1 <= 1 && v1 <= 1 && u1 > u0 && v1 > v0); }
});

const UNITS = ["green-bamboo", "little-rose", "cau-han"];
const perms = (a) => (a.length <= 1 ? [a] : a.flatMap((x, i) => perms([...a.slice(0, i), ...a.slice(i + 1)]).map((p) => [x, ...p])));

test("the atlas sizes live: 512 on phones and Tier 1, 1024 on Tier 2 where memory allows, 2048 only for the stills; counted in GPU memory", () => {
  assert.equal(atlasSizeFor({ tier: 1, width: 1440 }), 512);
  assert.equal(atlasSizeFor({ tier: 2, width: 390 }), 512);
  assert.equal(atlasSizeFor({ tier: 2, width: 1440 }), 1024);
  assert.equal(posterGpuMB(512), 1.3);
  assert.equal(posterGpuMB(1024), 5.3);
  const live = readFileSync(file("src/world/live.js"), "utf8");
  assert.match(live, /gl\.track\("posters", posterGpuMB\(r\.size\)\)/);
  assert.match(live, /Math\.min\(atlasSizeFor\(\{ tier, width: innerWidth \}\), planNow\(\)\.atlas\)/);
});

test("Little Rose: no poster within half a bay and 1.5 m of its door, in any order of the bays", () => {
  assert.equal(LR_CLEAR, 4.75);
  for (const order of perms(UNITS)) {
    const lay = corridorLayout(readBeats(order.map((unit) => ({ dataset: { unit } }))));
    const lr = lay.doors.find((d) => d.unit === "little-rose");
    for (const p of corridorPosters(lay)) {
      // A door-wall print's nearest edge along the corridor; an end-wall print lies across it, at the end wall's z.
      assert.ok(Math.abs(p.z - lr.zc) - (p.wall === "end" ? 0 : p.w / 2) > LR_CLEAR, `${order.join(",")}: ${p.id} at z ${p.z.toFixed(2)} is ${(Math.abs(p.z - lr.zc) - p.w / 2).toFixed(2)} m from Little Rose's door`);
      // On the door wall, above the dado, clear of every door frame.
      for (const d of lay.doors) assert.ok(Math.abs(p.z - d.zc) - (p.wall === "end" ? 0 : p.w / 2) > 0.6, `${p.id} clear of ${d.unit}'s door`);
      if (p.wall === "end") assert.ok(p.x - p.w / 2 > 98.9 + 0.1 && p.x + p.w / 2 < 101.3 - 0.1, `${p.id} within the end wall`);
      assert.ok(p.y - p.h / 2 > 0.9 + 0.2, `${p.id} above the dado`);
    }
    // The home order hangs all three.
    if (order.join() === UNITS.join()) assert.equal(corridorPosters(lay).length, CORRIDOR_POSTERS.length);
  }
  // Every hung poster is in the atlas.
  for (const p of [...CORRIDOR_POSTERS, ...S3_POSTERS]) assert.notEqual(WALL_POSTERS.find((x) => x.id === p.id)?.atlas ?? null, null, p.id);
});

test("the sets hang them: the corridor's door wall and S3's window wall, never the hero's rest frame", () => {
  const corridor = readFileSync(file("src/world/sets/corridor.js"), "utf8"), s3 = readFileSync(file("src/world/sets/shutters.js"), "utf8");
  assert.match(corridor, /wallPosters\(\{ \.\.\.U, \.\.\.posterUniforms\(\) \}, lights, posterOwn, corridorPosters\(lay\)\.map/, "one mesh for the corridor's set");
  assert.match(s3, /const posters = full \? wallPosters\(.*S3_POSTERS\.map/, "one mesh for S3's set; Get Involved's header room (not full) has none");
  // The hero's H1 covers its back wall in every bucket (PLAN.md section 9, M6): no poster there.
  assert.doesNotMatch(readFileSync(file("src/world/sets/room.js"), "utf8"), /wallPoster|wall-poster/);
  // Loaded only after the first frame: live.js asks for the atlas in idle time, poster.js (the LCP) never.
  assert.doesNotMatch(readFileSync(file("src/world/poster.js"), "utf8"), /wall-poster/);
  assert.match(readFileSync(file("src/world/live.js"), "utf8"), /idle\(\)\.then\(async \(\) => \{\n    if \(dead\) return;\n    const r = await loadPosterAtlas/);
});
