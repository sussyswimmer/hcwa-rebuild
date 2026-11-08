// PLAN section 6 and H3/H4: the corridor is built from the page's [data-beat] cards, 0 to N doors; dwell()
// eases a beat from 0 to 1 without ever going back; the passage's timeline is one set of numbers for the
// world and the motion layer; the camera leaves the hero without the screen ever crossing a line of text
// that is still on screen.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { PerspectiveCamera } from "three";
import { CORRIDOR, EXIT_HOLE, PASSAGE, corridorLayout, dwell, readBeats, setAt, wash } from "../src/world/journey.js";
import { TIMELINE } from "../src/motion/passage.js";
import { createFlight } from "../src/world/flight.js";
import { FRAME_SHAPES, SHOTS } from "../src/world/sets/room.js";
import { applyFrame, hits, projectPoly, solveFrame } from "../src/world/frame.js";
import { corridorScene } from "../src/world/sets/corridor.js";
import { HOME_BEATS } from "../src/world/sets/stills.js";
import { EMBLEMS } from "../src/world/objects/door.js";

const card = (unit, door = "open", emblem = "hoaThi") => ({ dataset: { unit, door, emblem } });
const cards = (n) => Array.from({ length: n }, (_, i) => card(["green-bamboo", "cau-han", "unit-" + i][i % 3], "open", ["dotTre", "vomCau", "hoaThi"][i % 3]));

test("0, 1, 3 and 5 cards build 0, 1, 3 and 5 doors, one bay each, in DOM order down the corridor", () => {
  for (const n of [0, 1, 3, 5]) {
    const lay = corridorLayout(readBeats(cards(n)));
    assert.equal(lay.doors.length, n);
    assert.equal(lay.piers.length, n);
    assert.equal(lay.end, -(CORRIDOR.bay * n + CORRIDOR.tail));
    for (let i = 1; i < n; i++) assert.ok(lay.doors[i].zc < lay.doors[i - 1].zc, "each door further down the corridor");
    for (const d of lay.doors) assert.ok(d.zc < d.bay[1] && d.zc > d.bay[0], "the door stands inside its own bay");
    const scene = corridorScene({ lime: null, terr: null, beats: readBeats(cards(n)) });
    assert.equal(scene.specs.length, n);
    assert.equal(scene.U.uDoorN.value, n);
  }
});

test("removing a card removes its door", () => {
  const three = readBeats([card("green-bamboo", "open", "dotTre"), card("little-rose", "closed"), card("cau-han", "open", "vomCau")]);
  const two = readBeats([card("green-bamboo", "open", "dotTre"), card("cau-han", "open", "vomCau")]);
  assert.deepEqual(corridorScene({ beats: two }).specs.map((d) => d.unit), ["green-bamboo", "cau-han"]);
  assert.equal(corridorScene({ beats: three }).specs.length, 3);
});

test("the DOM cannot open Little Rose's door or give it a motif", () => {
  const [lr] = readBeats([card("little-rose", "open", "dotTre")]);
  assert.deepEqual(lr, { unit: "little-rose", door: "closed", emblem: "hoaThi" });
  const [odd] = readBeats([{ dataset: { unit: "cau-han", door: "ajar", emblem: "<script>" } }]);
  assert.deepEqual(odd, { unit: "cau-han", door: "open", emblem: "hoaThi" });
});

test("dwell(0) = 0, dwell(1) = 1, and it never goes back", () => {
  assert.equal(dwell(0), 0);
  assert.ok(Math.abs(dwell(1) - 1) < 1e-12);
  let prev = -1;
  for (let i = 0; i <= 2000; i++) {
    const v = dwell(i / 2000);
    assert.ok(v >= prev - 1e-12, `dwell goes back at ${i / 2000}`);
    prev = v;
  }
  assert.equal(dwell(-1), 0);
  assert.equal(dwell(2), dwell(1));
});

test("the passage: the wash covers the plateau, the swap happens behind it, the text layer uses the same numbers", () => {
  assert.equal(wash(0), 0);
  assert.equal(wash(1), 0);
  for (let p = PASSAGE.plateau[0]; p <= PASSAGE.plateau[1]; p += 0.01) assert.equal(wash(p), 1, `wash at ${p}`);
  let swap = null;
  for (let p = 0; p <= 1; p += 0.001) if (setAt(p) === "corridor" && swap == null) swap = p;
  assert.ok(swap > PASSAGE.plateau[0] && swap < PASSAGE.plateau[1], "the room becomes the corridor only behind a full wash");
  assert.deepEqual(TIMELINE.plateau, PASSAGE.plateau);
  assert.deepEqual(TIMELINE.read, PASSAGE.read);
  assert.deepEqual(TIMELINE.approach, PASSAGE.approach);
});

test("the corridor screen has a hole exactly where the camera comes out", () => {
  const { screen } = corridorLayout(readBeats(cards(3)));
  const col = (EXIT_HOLE.z - screen.z0) / 0.3 - 0.5, row = (EXIT_HOLE.y - screen.y0) / 0.3 - 0.5;
  assert.ok(Math.abs(col - Math.round(col)) < 1e-9 && Math.abs(row - Math.round(row)) < 1e-9);
  assert.ok(screen.z0 <= -(CORRIDOR.bay * 3) && screen.z1 >= EXIT_HOLE.z);
});

test("the stills' corridor has the home page's centers, in order, with their emblems", () => {
  const work = JSON.parse(readFileSync(new URL("../../content/draft/work.json", import.meta.url), "utf8"));
  assert.deepEqual(HOME_BEATS.map((b) => b.unit), work.centers.map((c) => c.id));
  for (const b of HOME_BEATS) assert.equal(b.emblem, EMBLEMS[b.unit]);
});

// The hero scrolling out over the fixed canvas: for every row of the flight table, the screen silhouette
// (and the floor, under body text) stays off every line of hero text still below the opaque header.
test("the flight keeps the screen off the hero text while it scrolls out, and never goes back", () => {
  for (const [bucket, [W, H], rects] of [
    ["landscape", [1440, 900], [{ x: 60, y: 260, w: 830, h: 345, kind: "display" }, { x: 60, y: 640, w: 480, h: 96, kind: "body" }, { x: 600, y: 700, w: 180, h: 44, kind: "display" }]],
    ["portrait", [390, 844], [{ x: 16, y: 403, w: 328, h: 139, kind: "display" }, { x: 16, y: 559, w: 333, h: 78, kind: "body" }, { x: 23, y: 795, w: 184, h: 22, kind: "display" }]],
  ]) {
    const s = SHOTS[bucket], cam = new PerspectiveCamera(s.fov, W / H, 0.05, 60);
    cam.position.set(...s.pos);
    cam.lookAt(...s.look);
    cam.updateProjectionMatrix();
    cam.updateMatrixWorld();
    const view = { w: W, h: H }, top = 64;
    // The rest frame as the live world sets it: the framing solver's lens shift (frame.js).
    const rest = solveFrame({ camera: cam, width: W, height: H, rects, ...FRAME_SHAPES, stage: null });
    assert.ok(rest.ok, `${bucket}: a text-safe rest frame`);
    applyFrame(cam, { x: 0, y: 0, w: W, h: H }, view, rest);
    const flight = createFlight();
    flight.setRest(cam, bucket, view);
    flight.solve(cam, { heroH: H, approach: 0.28 * 1.8 * H, rects, shapes: FRAME_SHAPES, view, top });
    let prev = -1;
    for (const row of flight.table.rows) {
      assert.ok(row.s >= prev - 1e-9, `${bucket}: the camera goes back at ${row.y}`);
      prev = row.s;
      assert.ok(row.lift <= row.y + 1e-9, `${bucket}: the image is carried faster than the page at ${row.y}`);
      if (row.y >= H) continue;
      flight.room(cam, row.s, row.lift);
      const scr = projectPoly(FRAME_SHAPES.silhouette, cam, W, H), flo = projectPoly(FRAME_SHAPES.floor, cam, W, H);
      const edge = row.y > 80 ? top : 0;
      for (const r of rects) {
        const y0 = Math.max(r.y - row.y, edge), y1 = Math.min(r.y - row.y + r.h, H);
        if (y1 <= y0) continue;
        const v = { x: r.x, y: y0, w: r.w, h: y1 - y0 };
        assert.ok(!hits(scr, 0, 0, v), `${bucket}: the screen crosses text at scroll ${row.y}`);
        if (r.kind === "body") assert.ok(!hits(flo, 0, 0, v), `${bucket}: body text over the floor at scroll ${row.y}`);
      }
    }
    assert.ok(flight.table.rows.at(-1).s > 1.9, `${bucket}: the camera reaches the hole before the wash`);
  }
});

test("the statement: one plain sentence for screen readers, word spans for the light, the stage hooks", async () => {
  const { splitWords } = await import("../src/lib/split-words.mjs");
  const { ctx } = await import("../src/lib/ctx.mjs");
  const { passage } = await import("../src/pages/home/passage.mjs");
  const { str } = await import("../src/lib/html.mjs");
  const w = splitWords("Hội chăm sóc <trẻ em> & gia đình");
  assert.equal(w.count, 8);
  assert.match(str(w.words), /<span class="w" style="--w:3">&lt;trẻ <\/span>/);
  assert.equal(str(w.words).replace(/<[^>]+>/g, ""), "Hội chăm sóc &lt;trẻ em&gt; &amp; gia đình");
  for (const lang of ["vi", "en"]) {
    const c = ctx(lang, "home", false);
    const m = str(passage(c));
    const text = c.tx(c.content.site.whatWeDo.summary);
    assert.match(m, /<section class="passage" id="passage" data-world="passage" data-stage aria-labelledby="what-title">/);
    assert.ok(m.includes(`<span class="sr-only">${text}</span>`), `${lang}: the plain sentence`);
    assert.match(m, /<span class="statement__words" aria-hidden="true">/);
    assert.equal((m.match(/class="w"/g) ?? []).length, text.split(/\s+/).length);
    assert.match(m, new RegExp(`style="--n:${text.split(/\s+/).length}"`));
    assert.doesNotMatch(m, /data-text-safe/, "the statement is never framed around: the wash and its halo carry it");
  }
});

// ---- The corridor walk (PLAN H4) ----
test("the walk: the motion layer's card timeline is the world's", async () => {
  const { TIMELINE: CARDS } = await import("../src/motion/stages.js");
  const { WALK } = await import("../src/world/journey.js");
  for (const k of ["head", "beats", "cardIn", "cardOut", "release"]) assert.deepEqual(CARDS[k], WALK[k], k);
});

test("the walk never goes back, starts at the stage's pose and reads each door at the middle of its beat", async () => {
  const { walkT, beatAt } = await import("../src/world/journey.js");
  const { beatMiddle, cardAt } = await import("../src/motion/stages.js");
  for (const n of [0, 1, 3, 5]) {
    assert.equal(walkT(0, n), 0);
    let prev = -1, jump = 0;
    for (let k = 0; k <= 4000; k++) {
      const t = walkT(k / 4000, n);
      assert.ok(Number.isFinite(t) && t >= prev - 1e-9, `n ${n}: back at ${k / 4000}`);
      if (prev >= 0) jump = Math.max(jump, t - prev);
      prev = t;
    }
    // No jump: the fastest step of the camera is a small part of a bay at this resolution.
    assert.ok(jump < 0.02, `n ${n}: the camera jumps ${jump}`);
    for (let i = 0; i < n; i++) {
      const q = beatMiddle(i, n);
      assert.ok(Math.abs(walkT(q, n) - (i + 1)) < 0.06, `n ${n}: door ${i} is read at the middle of its beat`);
      assert.equal(cardAt(q, n), i, `n ${n}: card ${i} is shown while its door is read`);
      assert.equal(beatAt(q, n).i, i);
    }
    if (n) assert.ok(walkT(1, n) > n && walkT(1, n) < n + 0.5, "the walk comes to rest just past the last door");
  }
});

test("cards: the stage header first, one card per beat, never two, gaps between doors, the last stays", async () => {
  const { cardAt, doorAt, TIMELINE } = await import("../src/motion/stages.js");
  const n = 3, w = (TIMELINE.beats[1] - TIMELINE.beats[0]) / n;
  assert.equal(cardAt(0, n), -1);
  assert.equal(cardAt(TIMELINE.beats[0] + 0.1 * w, n), -1, "the header holds until the first card comes in");
  assert.equal(cardAt(TIMELINE.beats[0] + 0.99 * w, n), null, "between doors the panel is empty");
  assert.equal(cardAt(TIMELINE.release - 1e-3, n), n - 1, "the last card stays until the stage lets go");
  assert.equal(cardAt(1, n), null, "the frame lets go with nothing on it");
  for (let k = 0; k <= 1000; k++) { const d = doorAt(k / 1000, n); assert.ok(d >= 0 && d < n); }
  assert.equal(cardAt(0.5, 0), -1, "no centers: the header only");
});

test("doors swing open at their beat, 0 → 72°, and stay open", async () => {
  const { doorAngle, WALK, DOOR_DEG } = await import("../src/world/journey.js");
  const n = 3, w = (WALK.beats[1] - WALK.beats[0]) / n, at = (i, u) => WALK.beats[0] + (i + u) * w;
  for (let i = 0; i < n; i++) {
    assert.equal(doorAngle(i, at(i, 0.05), n), 0, `door ${i} shut before its beat`);
    assert.equal(doorAngle(i, at(i, WALK.swing[1]), n), DOOR_DEG, `door ${i} open by 0.45 of its beat`);
    assert.equal(doorAngle(i, 1, n), DOOR_DEG, `door ${i} stays open`);
    let prev = 0;
    for (let u = 0; u <= 1; u += 0.01) { const a = doorAngle(i, at(i, u), n); assert.ok(a >= prev - 1e-9); prev = a; }
  }
  assert.equal(DOOR_DEG, 72);
});

test("the lens puts the door where the card leaves the frame free, at any aspect", async () => {
  const { lensFor } = await import("../src/world/journey.js");
  for (const [fov, aspect, fx, fy, yaw] of [[50, 1.6, 0.52, 0.5, 15], [78, 390 / 844, 0.64, 0.27, 2], [62, 0.75, 0.6, 0.42, 10]]) {
    const C = [99.5, 1.1, 1.2], P = [101.3, 1.3, -3.25];
    const b = lensFor(C, yaw, P, fov, aspect, fx, fy);
    const cam = new PerspectiveCamera(fov, aspect, 0.05, 60);
    cam.position.set(...C);
    const y = (yaw * Math.PI) / 180;
    cam.lookAt(C[0] + Math.sin(y) * 10, 1.1, C[2] - Math.cos(y) * 10);
    cam.updateMatrixWorld();
    cam.projectionMatrix.makePerspective(b.l * cam.near, b.r * cam.near, b.t * cam.near, b.b * cam.near, cam.near, cam.far);
    const v = new (cam.position.constructor)(...P).project(cam);
    assert.ok(Math.abs((v.x + 1) / 2 - fx) < 1e-6 && Math.abs((1 - v.y) / 2 - fy) < 1e-6, `${fov}/${aspect.toFixed(2)}`);
    assert.ok(Math.abs((b.r - b.l) / (b.t - b.b) - aspect) < 1e-9, "the frustum keeps the frame's aspect");
  }
});

test("the corridor's markup: one card per center in order, the door index, the stage hooks, the tab order", async () => {
  const { ctx } = await import("../src/lib/ctx.mjs");
  const { corridor } = await import("../src/pages/home/corridor.mjs");
  const { str } = await import("../src/lib/html.mjs");
  const { STILLS } = await import("../src/world/still-shots.js");
  for (const lang of ["vi", "en"]) for (const prod of [false, true]) {
    const c = ctx(lang, "home", prod), m = str(corridor(c)), ids = c.content.work.centers.map((x) => x.id);
    assert.match(m, /<section class="bays-section" id="centers" data-world="corridor" data-stage style="--n:3"/);
    assert.match(m, /<div class="bays__track" data-stage-track>/);
    assert.deepEqual([...m.matchAll(/<li class="bay" id="center-([\w-]+)" data-beat/g)].map((x) => x[1]), ids);
    assert.deepEqual([...m.matchAll(/<a href="#center-([\w-]+)" data-to="(\d+)"/g)].map((x) => [x[1], +x[2]]), ids.map((id, i) => [id, i]));
    assert.match(m, new RegExp(`<nav class="bays__rail" aria-label="${c.t("centersRail")}">`));
    // Skip link, then the index, then the cards' links: the order the stage shows them in.
    const focus = [...m.matchAll(/<a [^>]*?(?:class="([^"]+)"[^>]*)?href="([^"]+)"/g)].map((x) => x[2]);
    assert.equal(focus[0], "#work");
    assert.deepEqual(focus.slice(1, 1 + ids.length), ids.map((id) => "#center-" + id));
    for (const id of ids) assert.ok(STILLS["bay-" + id], `a bay still for ${id}`);
    assert.deepEqual(ids.map((id) => STILLS["bay-" + id].beat), ids.map((_, i) => i), "each bay still is rendered at its own beat");
  }
});

// The card follows the camera (critique r1): sampled along the stage, the card on the panel is always the
// door nearest the camera's look target (the reading spot VIEW.d ahead), and while Little Rose's door is in
// the middle 60% of the frame no other center's card is shown.
test("cards follow the camera: the card shown is the door the camera looks at, never another over Little Rose", async () => {
  const { cardAt, paneAt, TIMELINE } = await import("../src/motion/stages.js");
  const { VIEW, walkPose, walkT } = await import("../src/world/journey.js");
  const { Vector3 } = await import("three");
  const lay = corridorLayout(HOME_BEATS), n = HOME_BEATS.length, lr = HOME_BEATS.findIndex((b) => b.unit === "little-rose");
  // The two stage layouts as live.js sets them: 1440 × 900 with the card panel on the right, 390 × 844 with it below.
  for (const [name, W, H, free] of [["side", 1440, 900, { x0: 0, x1: 940, y0: 64, y1: 900 }], ["below", 390, 844, { x0: 0, x1: 390, y0: 60, y1: 844 * 0.56 }]]) {
    const v = VIEW[name], p1 = walkPose(1, name, lay);
    const fx = (free.x0 + v.at[0] * (free.x1 - free.x0)) / W, fy = (free.y0 + v.at[1] * (free.y1 - free.y0)) / H;
    const { lensFor, CORRIDOR, DOOR_FOCUS_Y } = await import("../src/world/journey.js");
    const lens = lensFor(p1.pos, p1.yaw, [CORRIDOR.wallX, DOOR_FOCUS_Y, lay.doors[0].zc], p1.fov, W / H, fx, fy);
    const seen = (q, i) => {
      const p = walkPose(walkT(q, n), name, lay), cam = new PerspectiveCamera(p.fov, W / H, 0.05, 60), y = (p.yaw * Math.PI) / 180;
      cam.position.set(...p.pos);
      cam.lookAt(p.pos[0] + Math.sin(y) * 10, 1.1, p.pos[2] - Math.cos(y) * 10);
      cam.updateMatrixWorld();
      cam.projectionMatrix.makePerspective(lens.l * cam.near, lens.r * cam.near, lens.t * cam.near, lens.b * cam.near, cam.near, cam.far);
      const c = new Vector3(CORRIDOR.wallX, DOOR_FOCUS_Y, lay.doors[i].zc).project(cam);
      return c.z < 1 ? (c.x + 1) / 2 : Infinity;
    };
    for (let k = 0; k <= 500; k++) {
      const q = k / 500, card = cardAt(q, n);
      const look = walkPose(walkT(q, n), name, lay).pos[2] - v.d;
      const near = lay.doors.reduce((best, d, i) => (Math.abs(d.zc - look) < Math.abs(lay.doors[best].zc - look) ? i : best), 0);
      if (card != null && card >= 0) assert.equal(HOME_BEATS[card].unit, HOME_BEATS[near].unit, `${name} q ${q}: card ${HOME_BEATS[card].unit} over the door the camera looks at (${HOME_BEATS[near].unit})`);
      // Little Rose's door filling the frame: the nearest door ahead of the camera, in the middle 60%.
      const camZ = look + v.d, ahead = lay.doors.findIndex((d) => d.zc < camZ), x = seen(q, lr);
      if (ahead === lr && x > 0.2 && x < 0.8 && card != null && card >= 0) assert.equal(card, lr, `${name} q ${q}: ${HOME_BEATS[card].unit}'s card while Little Rose's door is in the middle of the frame`);
    }
    // Little Rose's card holds until its door has slid out of the middle of the frame.
    const w = (TIMELINE.beats[1] - TIMELINE.beats[0]) / n, out = TIMELINE.beats[0] + (lr + TIMELINE.cardOut) * w;
    assert.ok(seen(out - 1e-4, lr) >= 0.8, `${name}: Little Rose's card goes while its door is still at ${seen(out - 1e-4, lr).toFixed(2)}`);
  }
  // The pane fades over the last 0.05 and nothing is on it when the frame lets go.
  assert.equal(paneAt(TIMELINE.release), 1);
  assert.equal(paneAt(1), 0);
  for (let q = TIMELINE.release; q <= 1; q += 0.01) assert.ok(cardAt(q, n) == null, `no card on the fading pane at ${q}`);
});

// Critique r2: the last card holds while its door is the subject, then the camera walks on past it and the
// frame washes to the plaster below (live.js, renderer.js color), so the link under the stage never stands on
// the corridor.
test("the way out: the last card holds to 0.95, the camera only starts to leave there, the track has no tail", async () => {
  const { WALK, WALK_OUT, walkLeave, walkT } = await import("../src/world/journey.js");
  const { TIMELINE, cardAt } = await import("../src/motion/stages.js");
  const n = 3;
  assert.equal(TIMELINE.release, 0.95);
  assert.equal(cardAt(0.949, n), n - 1, "the last card is still read at 0.949");
  assert.equal(cardAt(0.951, n), null);
  assert.equal(walkLeave(0), 0, "no leaving before release");
  assert.ok(walkLeave(1) >= 0.3 && walkLeave(1) <= 0.6, "it walks on about half a bay");
  for (let x = 0; x < 1; x += 0.05) assert.ok(walkLeave(x + 0.05) >= walkLeave(x), "never back");
  // Under the last card the camera has not moved more than its reading drift.
  assert.ok(walkT(WALK.release, n) - walkT(WALK.beats[0] + (n - 1 + 0.5) * ((WALK.beats[1] - WALK.beats[0]) / n), n) < 0.2);
  assert.ok(WALK_OUT.wash > 0 && WALK_OUT.wash <= 0.25, "the wash is done within the plaster's first quarter screen");
  const { readFileSync } = await import("node:fs");
  const css = readFileSync(new URL("../src/styles/stages.css", import.meta.url), "utf8");
  assert.match(css, /\.bays__track \{ height: calc\(100svh \+ var\(--n\) \* 50svh\); \}/, "no 10svh tail on the track");
});
