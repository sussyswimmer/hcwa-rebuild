// The world's pure parts (05-DESIGN D6): the framing solver keeps the silhouette off text,
// the live gates step down in order, buckets and the sun clock hold their numbers.
import { test } from "node:test";
import assert from "node:assert/strict";
import { PerspectiveCamera } from "three";
import { PAD, clearAt, edgePads, projectPoly, solveFrame, toFrame } from "../src/world/frame.js";
import { capUniforms, setCaps } from "../src/world/materials/caps.js";
import { dprFor } from "../src/world/renderer.js";
import { POSTER_FRAMES } from "../src/world/poster-frames.js";
import { createGate } from "../src/world/tiers.js";
import { BUCKETS, bucketFor } from "../src/world/buckets.js";
import { FRAME_SHAPES, SHOTS } from "../src/world/sets/room.js";
import { makeSun, REST, sunAt } from "../src/world/clock.js";

const shot = (name, W, H) => {
  const s = SHOTS[name], cam = new PerspectiveCamera(s.fov, W / H, 0.05, 60);
  cam.position.set(...s.pos);
  cam.lookAt(...s.look);
  cam.updateProjectionMatrix();
  cam.updateMatrixWorld();
  return cam;
};
const inside = (p, r) => p.x > r.x - PAD && p.x < r.x + r.w + PAD && p.y > r.y - PAD && p.y < r.y + r.h + PAD;

test("the solver moves the screen silhouette off every text rect", () => {
  const [W, H] = [1440, 900];
  // A wide H1 that the unshifted landscape shot puts under the screen.
  const rects = [{ x: 60, y: 260, w: 1100, h: 350, kind: "display" }, { x: 60, y: 650, w: 480, h: 90, kind: "body" }];
  const cam = shot("landscape", W, H);
  const r = solveFrame({ camera: cam, width: W, height: H, rects, ...FRAME_SHAPES, stage: null });
  assert.ok(r.ok, "a text-safe framing exists");
  const scr = projectPoly(FRAME_SHAPES.silhouette, cam, W, H).map((p) => ({ x: p.x + r.sx, y: p.y + r.sy }));
  // Sample the silhouette's edges: no point may fall inside a padded text rect.
  for (let i = 0; i < scr.length; i++) for (let t = 0; t <= 1; t += 0.05) {
    const a = scr[i], b = scr[(i + 1) % scr.length], p = { x: a.x + (b.x - a.x) * t, y: a.y + (b.y - a.y) * t };
    for (const rect of rects) assert.ok(!inside(p, rect), `silhouette edge at ${Math.round(p.x)},${Math.round(p.y)} inside a text rect`);
  }
});

test("a still's shift is reused only while it keeps the text clear", () => {
  const [W, H] = [1440, 900];
  const rects = [{ x: 60, y: 260, w: 1100, h: 350, kind: "display" }, { x: 60, y: 650, w: 480, h: 90, kind: "body" }];
  const r = solveFrame({ camera: shot("landscape", W, H), width: W, height: H, rects, ...FRAME_SHAPES, stage: null });
  assert.ok(clearAt(r, r.sx, r.sy), "the solved shift is clear");
  assert.ok(!clearAt(r, 0, 0), "no shift puts the screen on the H1");
  assert.ok(!clearAt({ ...r, ok: false }, r.sx, r.sy), "nothing is clear in a frame that found no framing");
});

test("every bucket has the shift its still was framed with", () => {
  for (const b of BUCKETS) {
    const f = POSTER_FRAMES[b.name];
    assert.ok(f, `${b.name}: npm run posters writes src/world/poster-frames.js`);
    assert.deepEqual(f.view, b.view, `${b.name}: the still was framed for this bucket's view`);
    assert.ok(Number.isFinite(f.sx) && Number.isFinite(f.sy));
  }
});

test("header rects are given up only when nothing else fits, and say so", () => {
  const [W, H] = [390, 844];
  const rects = [
    { x: 137, y: 8, w: 237, h: 44, kind: "body", pinned: true },
    { x: 16, y: 359, w: 328, h: 139, kind: "display" },
    { x: 16, y: 515, w: 333, h: 78, kind: "body" },
  ];
  const r = solveFrame({ camera: shot("portrait", W, H), width: W, height: H, rects, ...FRAME_SHAPES, stage: null });
  assert.ok(r.ok);
  assert.equal(typeof r.headerClear, "boolean");
});

test("toFrame inverts object-fit: cover anchored to the bottom", () => {
  // A 1440 × 900 poster covering a 1440 × 816 box at y 84 loses 84 px at the top (the text's edge, the
  // bottom, never moves): 100 px into the box is 184 px into the poster.
  const [r] = toFrame([{ x: 60, y: 84 + 100, w: 10, h: 10 }], { x: 0, y: 84, w: 1440, h: 816 }, [1440, 900]);
  assert.deepEqual([r.x, r.y], [60, 184]);
  // A taller box scales the poster up and crops its sides evenly.
  const [t] = toFrame([{ x: 0, y: 0, w: 10, h: 10 }], { x: 0, y: 0, w: 390, h: 910 }, [390, 844]);
  assert.ok(Math.abs(t.x - (390 * 910 / 844 - 390) / 2 / (910 / 844)) < 1e-9 && t.y === 0);
});

test("the portrait H1 gets an 8 px top edge, nothing else does", () => {
  const rects = [{ x: 0, y: 0, w: 1, h: 1, kind: "display" }, { x: 0, y: 0, w: 1, h: 1, kind: "body" }, { x: 0, y: 0, w: 1, h: 1, kind: "display", pinned: true }];
  assert.deepEqual(edgePads(rects, "portrait").map((r) => r.padTop), [8, undefined, undefined]);
  assert.deepEqual(edgePads(rects, "landscape").map((r) => r.padTop), [undefined, undefined, undefined]);
});

test("the H1 takes the light uncapped; body text is capped at 40%", () => {
  const U = capUniforms();
  setCaps(U, [{ x: 10, y: 10, w: 300, h: 120, kind: "display" }, { x: 10, y: 150, w: 300, h: 60, kind: "body" }, { x: 10, y: 220, w: 300, h: 48, kind: "opaque" }], { h: 900, dpr: 1 });
  assert.equal(U.uCapN.value, 1);
  assert.equal(U.uCapLv.value[0], 0.4);
});

test("Tier 2 stays inside its memory budget: 3 M pixels, supersampled when it has no MSAA", () => {
  const was = globalThis.devicePixelRatio;
  try {
    globalThis.devicePixelRatio = 1;
    assert.equal(dprFor(1440, 900, 2, false, false), 1.5);
    assert.equal(dprFor(1440, 900, 2, false, true), 1);
    globalThis.devicePixelRatio = 2;
    const d = dprFor(1440, 900, 2, false, false);
    assert.ok(1440 * 900 * d * d <= 3e6 + 1e4, `${d}`);
    assert.equal(dprFor(390, 844, 1, false, false), 1.25);
  } finally { globalThis.devicePixelRatio = was; }
});

test("live gates step down Tier 2 → Tier 1 → lite → off, and stop on a pass", () => {
  const gl = { getExtension: () => null };
  const steps = [];
  const gate = createGate(gl, { tier: 2, onStep: (s) => steps.push(s.state) });
  let now = 1000; // rAF timestamps are never 0
  const run = (ms, n = 90) => { for (let i = 0; i < n; i++) { now += ms; gate.end(now); } };
  gate.end(now); // first frame only sets the clock
  run(25); run(25); run(40);
  assert.deepEqual(steps, ["t1", "lite", "off"]);
  assert.equal(gate.measuring, false);

  const ok = [];
  const g2 = createGate(gl, { tier: 1, onStep: (s) => ok.push(s) });
  let t = 1000;
  g2.end(t);
  for (let i = 0; i < 90; i++) g2.end((t += 16));
  assert.equal(ok.length, 1);
  assert.equal(ok[0].final, true);
  assert.equal(g2.measuring, false);
});

test("a gap in rAF (hidden tab, pause) never counts as a slow frame", () => {
  const steps = [];
  const gate = createGate({ getExtension: () => null }, { tier: 2, onStep: (s) => steps.push(s.state) });
  let t = 1000;
  gate.end(t);
  for (let i = 0; i < 89; i++) { if (i % 10 === 0) { gate.gap(); t += 5000; } gate.end((t += 16)); }
  for (let i = 0; i < 20; i++) gate.end((t += 16));
  assert.deepEqual(steps, ["t2"]);
});

test("buckets match the poster media queries and cover every aspect", () => {
  assert.equal(bucketFor(390, 844).name, "portrait");
  assert.equal(bucketFor(768, 1024).name, "tablet");
  assert.equal(bucketFor(1440, 900).name, "landscape");
  assert.equal(bucketFor(1920, 1080).name, "wide");
  assert.equal(bucketFor(3440, 1440).name, "wide");
  for (const b of BUCKETS) assert.equal(bucketFor(...b.view).name, b.name);
});

test("the room's rest sun is 40° azimuth, 24° elevation (05-DESIGN D6.4)", () => {
  const s = sunAt("room", REST.room, makeSun());
  assert.ok(Math.abs(Math.asin(s.dir.y) * 180 / Math.PI - 24) < 1e-6);
  assert.ok(Math.abs(Math.atan2(s.dir.z, s.dir.x) * 180 / Math.PI - 40) < 1e-6);
  // The corridor mirrors: its street is on the −x side.
  assert.ok(sunAt("corridor", 0.3, makeSun()).dir.x < 0);
});

// The richness pass's runner (05-DESIGN D0.6.2): the bake's cream level is the shader's, pigment stays within
// its share of each tile, and the corridor stays within its draw budget.
test("gạch bông: one cream level, field pigment ≤ 38%, border ≤ 30%, no brick", async () => {
  const { TILE_CREAM } = await import("../src/world/textures.js");
  const bake = await import("../src/world/gach-bake.js");
  const { gachPigments } = await import("../src/world/gach.js");
  assert.equal(bake.TILE_CREAM, TILE_CREAM);
  const P = gachPigments();
  assert.deepEqual(Object.keys(P).sort(), ["cream", "indigo", "jade", "ochre"]);
  for (const [kind, w, h, tx, ty, max] of [["field", 256, 256, 2, 2, 0.38], ["border", 256, 128, 2, 1, 0.3]]) {
    const d = bake.gachPixels(kind, w, h, tx, ty, P), c = TILE_CREAM * 255;
    let pig = 0;
    for (let i = 0; i < w * h; i++) if (Math.abs(d[i * 4] - c) + Math.abs(d[i * 4 + 1] - c) + Math.abs(d[i * 4 + 2] - c) > 60) pig++;
    assert.ok(pig / (w * h) <= max, `${kind}: ${(100 * pig / (w * h)).toFixed(1)}% pigment`);
    assert.ok(pig / (w * h) > 0.1, `${kind}: has its pattern`);
  }
});

test("the corridor's meshes: frames and Little Rose's leaf are one draw, the props one more", async () => {
  const { corridorScene } = await import("../src/world/sets/corridor.js");
  const { readBeats } = await import("../src/world/journey.js");
  const beats = readBeats(["green-bamboo", "little-rose", "cau-han"].map((unit) => ({ dataset: { unit, door: "open", emblem: "dotTre" } })));
  const set = corridorScene({ beats, open: 72 });
  const meshes = [];
  for (const o of set.objects) o.traverse((m) => m.isMesh && meshes.push(m));
  const painted = meshes.filter((m) => m.geometry.getAttribute("aPaint"));
  assert.equal(painted.length, 1, "every frame and Little Rose's fixed leaf in one mesh");
  assert.equal(meshes.filter((m) => m.geometry.getAttribute("aK")).length, 1, "fans and leaflets in one mesh");
  // shell, screen, props, frames, 2 swinging leaves, 2 doorway glows, 3 transoms
  assert.equal(meshes.length, 11);
  const tris = meshes.reduce((s, m) => s + (m.geometry.index ? m.geometry.index.count : m.geometry.getAttribute("position").count) / 3, 0);
  assert.ok(tris <= 2500, `${tris} triangles`);
});
