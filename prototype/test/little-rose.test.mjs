// 05-DESIGN D6.5, PLAN section 6: Little Rose Warm Shelter's door never opens, whatever the caller asks;
// its transom is the house's Hoa thị block and lets out plain, untinted, motif-free daylight; no corridor
// sun reaches its bay; its stills carry no page, no location, no header of their own.
import { test } from "node:test";
import assert from "node:assert/strict";
import { doorLight, doorSpec, EMBLEMS } from "../src/world/objects/door.js";
import { STILLS } from "../src/world/still-shots.js";

test("the Little Rose door stays closed at any requested angle", () => {
  for (const open of [0, 28, 72, 110]) assert.equal(doorSpec({ wx: 0, zc: 0, unit: "little-rose", open }).open, 0);
  assert.equal(doorSpec({ wx: 0, zc: 0, unit: "green-bamboo", open: 72 }).open, 72);
});

test("its transom is the house block, its light plain and untinted, no corridor sun in its bay", () => {
  assert.equal(doorSpec({ wx: 0, zc: 0, unit: "little-rose" }).emblem, "hoaThi");
  assert.deepEqual(doorLight("little-rose"), { transomPattern: "open", spillTint: 0, corridorSun: false });
});

test("open doors carry their emblem and at most 15% of their color", () => {
  for (const unit of ["green-bamboo", "cau-han"]) {
    const l = doorLight(unit);
    assert.equal(l.transomPattern, EMBLEMS[unit]);
    assert.ok(l.spillTint <= 0.15, unit);
  }
});

test("Little Rose has a card still, its frame of the home walk and its door on the hub's wall only: no page header, no location", () => {
  const lr = Object.entries(STILLS).filter(([name, s]) => name.includes("little-rose") || s.unit === "little-rose");
  assert.deepEqual(lr.map(([name]) => name).sort(), ["bay-little-rose", "door-little-rose", "hub-door-little-rose"]);
  const kinds = { "door-little-rose": "card", "bay-little-rose": "frame", "hub-door-little-rose": "door" };
  for (const [name, s] of lr) {
    assert.equal(s.kind, kinds[name]);
    assert.notEqual(s.kind, "header");
    assert.ok(s.page === undefined || s.page === "home" || s.page === "our-work", `${name}: no page of its own`);
    assert.doesNotMatch(JSON.stringify(s), /location|địa chỉ|address|quận|district/i);
  }
});

test("the hub's wall: Little Rose shut, clean, its transom's light plain, and no corridor sun lands near its door", async () => {
  const { SHOTS, HUB_NO_SUN, hubDoorZ, HOME_BEATS } = await import("../src/world/sets/stills.js");
  const set = SHOTS["hub-doors-wide"].scene({ lime: null, terr: null });
  const [sun, rooms] = set.lights;
  const lr = HOME_BEATS.findIndex((b) => b.unit === "little-rose"), zc = hubDoorZ(lr);
  // The gallery's sun is cut out of its whole bay, whatever stretch of the screen the ray came through.
  assert.ok(HUB_NO_SUN >= 1.2, "no sun within 1.2 m of its door");
  assert.deepEqual(set.noSun.map((v) => +v.toFixed(3)), [+(zc - HUB_NO_SUN).toFixed(3), +(zc + HUB_NO_SUN).toFixed(3)]);
  assert.equal(sun.factor, "hubBayFree(P)");
  assert.match(sun.glsl, new RegExp(`abs\\(P\\.z - ${zc.toFixed(4).replace(/[.-]/g, "\\$&")}\\)`));
  // Its transom lets out plain light, and it has no doorway and no leaf in the light: shut.
  const near = rooms.apertures.filter((a) => a.corner[2] < zc + 0.6 && a.corner[2] + a.size[0] > zc - 0.6);
  assert.equal(near.length, 1, "only its transom");
  assert.equal(near[0].pattern, "open");
  // Its leaf is shut and clean: no wear, no lit room behind it.
  const { tok } = await import("../src/world/colors.js");
  const rose = tok("unit-little-rose"), meshes = [];
  for (const o of set.objects) o.traverse?.((m) => m.isMesh && meshes.push(m));
  const pink = meshes.map((m) => m.material.uniforms).filter((u) => u?.uPaint && Math.abs(u.uPaint.value.x - rose.r) < 1e-6 && Math.abs(u.uPaint.value.y - rose.g) < 1e-6);
  assert.ok(pink.length >= 3, "its leaf, panels and frame");
  assert.ok(pink.every((u) => u.uWear.value === 0), "Little Rose's paint carries no wear");
});

test("the home corridor's Little Rose bay: closed door, no location, never 'visit', its privacy note", async () => {
  const { ctx } = await import("../src/lib/ctx.mjs");
  const { corridor } = await import("../src/pages/home/corridor.mjs");
  const { str, esc } = await import("../src/lib/html.mjs");
  for (const lang of ["vi", "en"]) for (const prod of [false, true]) {
    const c = ctx(lang, "home", prod);
    const lrBay = str(corridor(c)).match(/<li class="bay"[^>]*data-unit="little-rose"[\s\S]*?<\/li>/)[0];
    assert.match(lrBay, /data-door="closed"/);
    assert.match(lrBay, /data-emblem="hoaThi"/);
    assert.doesNotMatch(lrBay, /bay__where/, "no location for Little Rose");
    assert.ok(!lrBay.includes(esc(c.t("visitCenter"))), "Little Rose is never 'visit'");
    assert.ok(lrBay.includes(esc(c.t("aboutShelter"))) && lrBay.includes(esc(c.tx(c.content.work.littleRose.privacyNote))));
  }
});

// The live corridor (sets/corridor.js, glsl/doors.js): the same rules whatever the page's attributes say.
test("the live corridor: Little Rose stays shut at any angle, plain light, no corridor sun in its bay", async () => {
  const { corridorScene, DOOR_OPEN } = await import("../src/world/sets/corridor.js");
  const { readBeats } = await import("../src/world/journey.js");
  const { DOOR_PATTERN } = await import("../src/world/glsl/doors.js");
  const beats = readBeats([
    { dataset: { unit: "green-bamboo", door: "open", emblem: "dotTre" } },
    { dataset: { unit: "little-rose", door: "open", emblem: "vomCau" } },
    { dataset: { unit: "cau-han", door: "open", emblem: "vomCau" } },
  ]);
  const set = corridorScene({ beats, open: DOOR_OPEN });
  const lr = set.specs.findIndex((d) => d.unit === "little-rose");
  for (const deg of [0, 30, 72, 180]) {
    set.setDoor(lr, deg);
    assert.equal(set.U.uDoors.value[lr].z, 0, `asked ${deg}°`);
  }
  assert.equal(set.U.uDoors.value[lr].y, DOOR_PATTERN.open, "its transom lets out plain light, no motif");
  assert.equal(set.specs[lr].emblem, "hoaThi");
  const [z0, z1] = set.layout.doors[lr].bay;
  assert.ok(set.U.uNoSun.value.some((v) => v.x === z0 && v.y === z1), "no corridor sun in its bay");
  for (const i of [0, 2]) assert.ok(set.U.uDoors.value[i].z > 1, "the open doors stand open");
  assert.equal(set.U.uNoSun.value.filter((v) => v.y > v.x).length, 1, "the other bays keep their sun");
});

// The walk and its stills (PLAN H4): the camera reads every door, Little Rose's stays shut in every still
// of the walk, and its card and index entry carry no location and never "visit".
test("the walk's stills: Little Rose stays shut, the doors before each still open, the ones after shut", async () => {
  const { SHOTS } = await import("../src/world/sets/stills.js");
  const { STILLS } = await import("../src/world/still-shots.js");
  const bays = Object.keys(STILLS).filter((k) => k.startsWith("bay-"));
  for (const name of bays) {
    const set = SHOTS[name].scene({ lime: null, terr: null }), i = STILLS[name].beat;
    set.specs.forEach((d, j) => {
      const open = set.U.uDoors.value[j].z > 1;
      if (d.unit === "little-rose") assert.equal(set.U.uDoors.value[j].z, 0, `${name}: Little Rose shut`);
      else assert.equal(open, j <= i, `${name}: door ${j}`);
    });
    assert.doesNotMatch(JSON.stringify(STILLS[name]), /location|địa chỉ|address|quận|district/i);
  }
  assert.equal(STILLS["bay-little-rose"].kind, "frame");
  assert.equal(STILLS["bay-little-rose"].page, "home");
});

test("the corridor's index and stage: Little Rose by name only, no location, never 'visit'", async () => {
  const { ctx } = await import("../src/lib/ctx.mjs");
  const { corridor } = await import("../src/pages/home/corridor.mjs");
  const { str, esc } = await import("../src/lib/html.mjs");
  for (const lang of ["vi", "en"]) {
    const c = ctx(lang, "home", true), m = str(corridor(c));
    const lr = c.content.work.centers.find((x) => x.id === "little-rose");
    const entry = m.match(/<li><a href="#center-little-rose"[\s\S]*?<\/li>/)[0];
    assert.ok(entry.includes(esc(c.tx(lr.shortName))));
    assert.ok(!entry.includes(esc(c.t("visitCenter"))));
    assert.match(m.match(/<li class="bay"[^>]*data-unit="little-rose"[^>]*>/)[0], /--spill:var\(--color-sun-100\)/, "its light in the panel is plain daylight");
  }
});
