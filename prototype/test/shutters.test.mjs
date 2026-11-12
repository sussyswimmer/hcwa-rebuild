// PLAN H7 and 05-DESIGN D5.3 S3: the shutters' timeline is one set of numbers for the world and the motion
// layer; the slats, leaves, blend and camera only move forward with the scroll; the flood is a one-shot the
// scroll arms (once per crossing, held between 0.64 and 0.72, played back 1.6× faster, none while paused);
// the light the shader casts is the leaves the mesh draws; nothing in the set or the flood runs past sun-100;
// the markup is decorative but for its skip link, in both languages, staging and production.
import { test } from "node:test";
import { readFileSync } from "node:fs";
import assert from "node:assert/strict";
import { Matrix4, Vector3 } from "three";
import { SHUTTERS, S3_VIEW, blendAt, floodAt, floodStep, leafAt, s3Exposure, s3Open, s3Pose, slatAt } from "../src/world/journey.js";
import { SHUTTER, leafAperture, shutterRig, shutterSpec, slatDuty } from "../src/world/objects/shutters.js";
import { WIN_Z, shuttersScene } from "../src/world/sets/shutters.js";
import { tok, vec } from "../src/world/colors.js";

const grid = (n) => Array.from({ length: n + 1 }, (_, i) => i / n);

test("S3: the motion layer's timeline is the world's", async () => {
  const { TIMELINE } = await import("../src/motion/shutters.js");
  for (const k of ["slats", "leaves", "flood", "unflood", "blend"]) assert.deepEqual(TIMELINE[k], SHUTTERS[k], k);
});

test("S3 (M7): the slats turn and the leaves swing over the approach, open by the pin; the look-out, then the blend into sun-100 by 0.62; never back", () => {
  // Over the approach a (the section rising into place): slats 0 → 65° over 0.12-0.50, leaves 0 → 110° over 0.50-1.
  assert.equal(slatAt(0.12), 0); assert.equal(slatAt(0.5), 65); assert.equal(slatAt(1), 65);
  assert.equal(leafAt(0.5), 0); assert.equal(leafAt(1), 110);
  // Over the stage p: the window held fully open with nothing blending (the look-out, the help heading coming up).
  for (let p = 0; p <= 0.28; p += 0.01) assert.equal(blendAt(p), 0);
  assert.equal(blendAt(0.62), 1);
  assert.ok(SHUTTERS.flood > SHUTTERS.blend[0] && SHUTTERS.flood < SHUTTERS.blend[1], "the flood lands inside the blend");
  let prev = { s: -1, l: -1, b: -1, d: -1, o: -1 };
  for (const u of grid(400)) {
    const now = { s: slatAt(u), l: leafAt(u), b: blendAt(u), d: slatDuty(slatAt(u)), o: s3Open(u) };
    for (const k of Object.keys(now)) assert.ok(now[k] >= prev[k] - 1e-12, `${k} goes back at ${u}`);
    prev = now;
  }
  // The gold lines widen into bars: a twelfth of the pitch shut, three fifths open (D5.3's duty cycle).
  assert.ok(Math.abs(slatDuty(0) - (1 - 0.055 / 0.06)) < 1e-9);
  assert.ok(slatDuty(65) > 0.6 && slatDuty(65) < 0.62);
});

test("S3 (M7): the help section rises the whole stage, so its heading arrives as the leaves finish; the live world reads the approach", () => {
  const css = readFileSync(new URL("../src/styles/stages.css", import.meta.url), "utf8");
  assert.match(css, /html\.stages \.shutters \{ --s3: 100svh; --lead: 14svh; --rise: var\(--s3\);/);
  assert.match(css, /html\.stages \.shutters \{ --s3: 90svh; --lead: 12svh; --rise: var\(--s3\); \}/);
  // The stills fallback keys its beats on the same approach (--a) and its blend on the stage (--p).
  assert.match(css, /\.shutters__view--louvers \{ opacity: clamp\(0, \(var\(--a, 0\) - 0\.12\) \/ 0\.3, 1\); \}/);
  assert.match(css, /\.shutters__view--open \{ opacity: clamp\(0, \(var\(--a, 0\) - 0\.55\) \/ 0\.4, 1\); \}/);
  assert.match(css, /--b: clamp\(0, \(var\(--p, 0\) - 0\.28\) \/ 0\.34, 1\)/);
  const live = readFileSync(new URL("../src/world/live.js", import.meta.url), "utf8");
  assert.match(live, /const slat = slatAt\(a\), leaf = leafAt\(a\) \/ SHUTTERS\.leafDeg;/);
  assert.match(live, /const e = s3Exposure\(a, fl\), open = s3Open\(a, slat\);/);
});

test("S3: the flood fires once per crossing, holds between 0.22 and 0.30, plays back 1.6× faster, and not at all paused", () => {
  const s = { on: false, v: 0 }, dt = 1 / 60;
  const run = (p, secs, paused = false) => { for (let t = 0; t < secs; t += dt) floodStep(s, p, dt, paused); };
  run(0.29, 2);
  assert.deepEqual(s, { on: false, v: 0 }, "not before 0.30");
  let frames = 0;
  while (s.v < 1) { floodStep(s, 0.4, dt); frames++; }
  assert.ok(Math.abs(frames * dt - 0.9) < 2 * dt, `forward in 900 ms (${(frames * dt).toFixed(3)} s)`);
  run(0.24, 2);
  assert.deepEqual(s, { on: true, v: 1 }, "held above 0.22: a visitor hovering at the threshold never sets it flickering");
  run(0.4, 2);
  assert.equal(s.v, 1, "it does not fire again while still past it");
  frames = 0;
  while (s.v > 0) { floodStep(s, 0.2, dt); frames++; }
  assert.ok(Math.abs(frames * dt - 0.9 / 1.6) < 2 * dt, `back in 563 ms (${(frames * dt).toFixed(3)} s)`);
  // Paused (D6.10: "no flood"): straight to where it would settle, nothing in between.
  floodStep(s, 0.9, dt, true);
  assert.deepEqual(s, { on: true, v: 1 });
  floodStep(s, 0.1, dt, true);
  assert.deepEqual(s, { on: false, v: 0 });
});

test("S3: exposure 1.22 → 1.55 as the leaves open, about 1.75 at the flood's peak, 1.6 after it", () => {
  const f = { peak: 0, held: 0 };
  assert.ok(Math.abs(s3Exposure(0, floodAt(0, f)) - 1.22) < 1e-9);
  assert.ok(Math.abs(s3Exposure(1, floodAt(0, f)) - 1.55) < 1e-9, "open by the pin");
  const peak = Math.max(...grid(100).map((v) => s3Exposure(1, floodAt(v, f))));
  assert.ok(peak > 1.72 && peak <= 1.75 + 1e-9, `peak ${peak}`);
  assert.ok(Math.abs(s3Exposure(1, floodAt(1, f)) - 1.6) < 1e-9);
  assert.equal(floodAt(1, f).peak, 0);
});

test("S3: the camera only comes closer, turns at most 40° per 60svh, never changes its fov and never rolls", () => {
  // The approach spans at least 100svh of scroll (the section rising) and the stage 90svh (phones; M7).
  for (const bucket of Object.keys(S3_VIEW)) {
    let prev = null;
    const yaw = (q) => Math.atan2(q.look[2] - q.pos[2], q.look[0] - q.pos[0]);
    const dist = (q) => Math.hypot(q.pos[0], q.pos[2] - WIN_Z);
    for (const u of grid(400)) {
      const a = Math.min(u * 2, 1), p = Math.max(u * 2 - 1, 0);
      const q = s3Pose(a, p, bucket, WIN_Z);
      assert.equal(q.pos[1], 1.1, "eye height");
      assert.equal(q.fov, S3_VIEW[bucket].fov);
      if (prev) {
        assert.ok(dist(q) <= dist(prev) + 1e-9, `${bucket} backs away at ${u}`);
        const svh = (u <= 0.5 ? 100 : 90) / 200; // svh of scroll per grid step
        const deg = (Math.abs(yaw(q) - yaw(prev)) * 180) / Math.PI;
        assert.ok(deg <= (40 / 60) * svh + 1e-6, `${bucket} turns ${deg.toFixed(3)}° in ${svh}svh at ${u}`);
      }
      prev = { pos: [...q.pos], look: [...q.look] };
    }
  }
});

test("the light the leaves cast is the leaves the mesh draws, at any angle", () => {
  const sp = shutterSpec({ wx: 0, zc: WIN_Z, leaf: 0, slat: 0 });
  const rig = shutterRig(sp, null);
  const m = new Matrix4(), at = new Vector3();
  for (const leaf of [0, 37, 90, 110]) for (const slat of [0, 65]) {
    rig.set(leaf, slat);
    sp.hinges.forEach((h, j) => {
      const a = leafAperture(sp, h, leaf, slat);
      // The far stile (instance 1 of 5 per leaf) sits at the free edge, along the aperture's u.
      rig.frames.getMatrixAt(j * 5 + 1, m);
      at.setFromMatrixPosition(m);
      const want = new Vector3(...a.corner).addScaledVector(new Vector3(...a.u), SHUTTER.w - SHUTTER.stile / 2).setY(sp.y0 + SHUTTER.h / 2);
      assert.ok(at.distanceTo(want) < 1e-6, `leaf ${j} at ${leaf}°/${slat}°: mesh ${at.toArray()} vs light ${want.toArray()}`);
      // Into the room (x < 0) as they open, never out through the wall.
      if (leaf > 0) assert.ok(at.x < 0.02, "swings into the room");
      assert.ok(Math.abs(a.params[1] - slatDuty(slat)) < 1e-12);
    });
  }
});

test("nothing in the set or the flood runs past sun-100: the glare, the street, the flood's own colour", () => {
  const set = shuttersScene({ lime: null, terr: null, leaf: 0, slat: 0, t: SHUTTERS.sun[0], full: true });
  const sun100 = vec(tok("sun-100"));
  assert.deepEqual(set.U.uGlare.value.toArray(), sun100.toArray(), "light past full runs toward sun-100");
  // The street (M7, objects/sky.js viewMaterial): its highest light is sun-100, and the late light only ever pales it
  // toward that (uPale, at most 0.3); the set's MIN cap holds every pixel to sun-100 anyway (buildShutters).
  const V = set.outside.uniforms;
  assert.deepEqual(V.uSkyHi.value.toArray(), sun100.toArray());
  for (const e of [1.22, 1.55, 1.75, 1.6]) {
    set.setExposure(e, 1);
    assert.ok(V.uPale.value >= 0 && V.uPale.value <= 0.3, `pale ${V.uPale.value} at ${e}`);
  }
  set.setExposure(1.55, 1);
  assert.ok(V.uPale.value < 0.2, "open, the street keeps its shape (the M6 'blank cream view')");
  // The wash and the flare are one colour: --color-world-wash, which equals sun-100 (tokens.css).
  assert.equal(tok("wash").getHexString(), tok("sun-100").getHexString());
});

test("the street outside S3 (M7): drawn by ray from the camera, no people, no lettering; the same street as Get Involved's header", () => {
  const set = shuttersScene({ lime: null, terr: null, leaf: 110, slat: 65, t: SHUTTERS.sun[1], full: true });
  const fs = set.outside.fragmentShader;
  assert.match(fs, /vec3 ro = cameraPosition, rd = normalize\(vP - ro\);/, "a ray through the window: real depth and parallax");
  for (const part of ["vec3 front(", "float cub(", "float canopy(", "// Two overhead wires"]) assert.ok(fs.includes(part), part);
  // Shophouse washes, jade louvers and the Cub are the house's tokens (Get Involved's header street uses the same).
  const gi = readFileSync(new URL("../src/partials/involve-window.mjs", import.meta.url), "utf8");
  for (const t of ["world-vang-sun", "tile-jade", "tile-jade-lit", "tile-cream", "tile-ochre"]) assert.ok(gi.includes(t), `GI street uses ${t}`);
  const sky = readFileSync(new URL("../src/world/objects/sky.js", import.meta.url), "utf8");
  for (const t of ["world-vang-sun", "tile-jade", "tile-jade-lit", "tile-cream", "tile-ochre-lit"]) assert.ok(sky.includes(`c("${t}")`), `S3 street uses ${t}`);
  assert.match(sky, /No people, no signage, no text\./);
});

test("H7 markup: a skip link to the help section, a decorative stage, the three beats' stills, in VI and EN", async () => {
  const { ctx } = await import("../src/lib/ctx.mjs");
  const { shutters } = await import("../src/pages/home/shutters.mjs");
  const { home } = await import("../src/pages/home.mjs");
  const { str } = await import("../src/lib/html.mjs");
  for (const lang of ["vi", "en"]) for (const prod of [false, true]) {
    const c = ctx(lang, "home", prod), m = str(shutters(c));
    assert.match(m, /^\s*<section class="shutters" id="shutters" data-world="shutters" data-stage>/);
    assert.match(m, new RegExp(`<a class="shutters__skip" href="#help">${c.t("skipShutters")}</a>`));
    assert.match(m, /<div class="shutters__track" data-stage-track>\s*<div class="shutters__frame" aria-hidden="true">/);
    for (const name of ["shutters-closed", "shutters-louvers", "shutters-open"]) assert.match(m, new RegExp(`/posters/${name}-mobile-still-800\\.avif`), name);
    assert.equal((m.match(/loading="lazy"/g) ?? []).length, 3, "every still lazy");
    assert.doesNotMatch(m, /fetchpriority/);
    // The only text is the skip link's.
    const text = m.replace(/<a class="shutters__skip"[^>]*>[^<]*<\/a>/, "").replace(/<[^>]+>/g, "").trim();
    assert.equal(text, "");
    // Between the news and the help section it opens onto (PLAN section 3 order).
    const page = str(home(c).body);
    assert.ok(page.indexOf('id="news"') < page.indexOf('id="shutters"') && page.indexOf('id="shutters"') < page.indexOf('id="help"'));
  }
});
