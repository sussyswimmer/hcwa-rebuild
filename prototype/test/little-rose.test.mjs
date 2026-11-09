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

// ---- The richness pass (05-DESIGN D0.6.4): the house adds fans, leaflets, paint and tiles, never to Little
// Rose's bay. The proposal's props check (docs/design/proposals-2/saigon-home/lr-props-check.mjs) as a test over
// three bay orders, and colour-light's red/pink pattern applied to every colour the corridor uses as light.
const ORDERS = [["green-bamboo", "little-rose", "cau-han"], ["little-rose", "green-bamboo", "cau-han"], ["cau-han", "green-bamboo", "little-rose"]];
const beatsOf = (units) => units.map((unit) => ({ unit, door: "open", emblem: unit === "cau-han" ? "vomCau" : "dotTre" }));

test("the house's props never come near Little Rose's bay, in any order, live or in a still", async () => {
  const { buildProps, FAN, KIND } = await import("../src/world/objects/props.js");
  const { corridorLayout } = await import("../src/world/journey.js");
  for (const units of ORDERS) {
    const lay = corridorLayout(beatsOf(units)), [z0, z1] = lay.doors.find((d) => d.unit === "little-rose").bay;
    const { geometry, fans } = buildProps({ lay });
    const pos = geometry.getAttribute("position"), piv = geometry.getAttribute("aPivot"), k = geometry.getAttribute("aK");
    for (let i = 0; i < pos.count; i++) {
      const kind = k.getX(i), at = `${units.join("/")}: vertex ${i}`;
      // Critique r2 cut the leaflets: every prop is a fan or its fixed canopy and rod.
      assert.ok(kind === KIND.fan || kind === KIND.solid, `${at}: a prop that is not a fan`);
      // a fan's vertex anywhere on its turn (radius about its rod)
      const reach = kind === KIND.fan ? Math.hypot(pos.getX(i) - piv.getX(i), pos.getZ(i) - piv.getZ(i)) : 0;
      const z = kind === KIND.fan ? piv.getZ(i) : pos.getZ(i);
      assert.ok(z + reach < z0 - 0.3 || z - reach > z1 + 0.3, `${at}: prop within 0.3 m of the bay`);
    }
    for (const f of fans) assert.ok(f.z + FAN.r + 0.1 < z0 || f.z - FAN.r - 0.1 > z1, `${units.join("/")}: a fan disc touches the bay`);
    assert.equal(fans.length, units.length - 1, "a fan in every bay but Little Rose's");
  }
});

test("the house's fan shadows, dado and door: Little Rose's bay is like every other, less the fan, and always shut", async () => {
  const { corridorScene } = await import("../src/world/sets/corridor.js");
  const { DOOR } = await import("../src/world/objects/door.js");
  const { HOUSE } = await import("../src/world/sets/common.js");
  const { DOOR_PATTERN } = await import("../src/world/glsl/doors.js");
  for (const units of ORDERS) {
    // The DOM asks for Little Rose open with a coloured emblem: nothing changes.
    const set = corridorScene({ beats: beatsOf(units), open: 72 });
    const i = set.specs.findIndex((d) => d.unit === "little-rose"), [z0, z1] = set.layout.doors[i].bay, U = set.U;
    for (const deg of [0, 72, 180]) { set.setDoor(i, deg); assert.equal(U.uDoors.value[i].z, 0); }
    assert.equal(U.uDoors.value[i].y, DOOR_PATTERN.open, "its transom's light is plain");
    // The fan shadows' centres are in the aperture's uv (u = start − z): none over its bay.
    for (const v of U.uFans.value) if (v.w > 0.5) { const z = set.layout.start - v.x; assert.ok(z + 0.8 < z0 || z - 0.8 > z1); }
    // One door break for every door (the dado stops 10 cm short of each frame), read from the same list.
    assert.ok(Math.abs(U.uHouse.value.z - (DOOR.w / 2 + DOOR.frame + HOUSE.doorBreak)) < 1e-9);
    assert.equal(U.uDoorN.value, units.length);
    assert.ok(U.uNoSun.value.some((v) => v.x === z0 && v.y === z1), "no corridor sun in its bay");
  }
});

test("no brick in the corridor, and every colour it uses as light is daylight or leans green/blue, never red or pink", async () => {
  const { readFileSync } = await import("node:fs");
  for (const f of ["../src/world/sets/corridor.js", "../src/world/objects/props.js", "../src/world/gach-bake.js", "../src/world/gach.js", "../src/world/sets/common.js"]) {
    assert.doesNotMatch(readFileSync(new URL(f, import.meta.url), "utf8"), /tile-brick|brick-lit|["']brick["']/, `${f} references brick`);
  }
  const { gachPigments } = await import("../src/world/gach.js");
  assert.deepEqual(Object.keys(gachPigments()).sort(), ["cream", "indigo", "jade", "ochre"]);
  const { corridorScene } = await import("../src/world/sets/corridor.js");
  const set = corridorScene({ beats: beatsOf(ORDERS[0]), open: 72 });
  // Daylight: warm white as displayed (sRGB), its green within 15% of its red and its blue under its green
  // (never pink: b > g, never red: a low green).
  const srgb = (v) => (v <= 0.0031308 ? 12.92 * v : 1.055 * v ** (1 / 2.4) - 0.055);
  const daylight = (c) => { const [r, g, b] = c.toArray().map(srgb); return g <= r + 1e-9 && g >= 0.85 * r && b <= g + 1e-9; };
  const light = (c, what) => assert.ok(daylight(c) || c.y >= c.x, `${what} leans red or pink: ${c.toArray().map((v) => v.toFixed(3))}`);
  for (const tt of [0.25, 0.35, 0.45]) {
    set.setSun(tt);
    light(set.U.uSunCol.value, `the sun at ${tt}`);
    set.U.uSpillT.value.forEach((c, k) => light(c, `the rooms' daylight (kind ${k})`));
    const props = set.objects.find((o) => o.material?.uniforms?.uSunTint && o.geometry?.getAttribute("aK"));
    light(props.material.uniforms.uSunTint.value, "the fans' glint");
  }
  // Little Rose's transom: the rooms' plain daylight behind it, never its unit's colour.
  const { mixc, tok, vec } = await import("../src/world/colors.js");
  const day = vec(mixc(tok("sky"), tok("wallSun"), 0.45));
  const transoms = set.objects.filter((o) => o.material?.uniforms?.uBehindLow && o.material.uniforms.uBehindY?.value?.[0] === 2);
  const lrZ = set.specs.find((d) => d.unit === "little-rose").zc;
  const lr = transoms.find((o) => Math.abs(o.material.uniforms.uO?.value?.z ?? NaN) >= 0 && Math.abs((o.material.uniforms.uO.value.z + o.material.uniforms.uU.value.z * 0.45) - lrZ) < 0.5);
  assert.ok(lr, "Little Rose's transom panel");
  assert.ok(lr.material.uniforms.uBehindLow.value.distanceTo(day) < 1e-6, "plain daylight behind it");
  light(lr.material.uniforms.uBehindLow.value, "Little Rose's transom light");
  light(lr.material.uniforms.uBehindHigh.value, "Little Rose's transom sky");
});

test("the house's door walls in the stills: Little Rose's door breaks the dado like the others, no fan, no runner", async () => {
  const { SHOTS, HOME_BEATS, hubDoorZ } = await import("../src/world/sets/stills.js");
  const hub = SHOTS["hub-doors-wide"].scene({ lime: null, terr: null });
  const lr = HOME_BEATS.findIndex((b) => b.unit === "little-rose");
  assert.equal(hub.U.uDoorN.value, HOME_BEATS.length);
  assert.ok(hub.U.uDoors.value.slice(0, HOME_BEATS.length).some((v) => Math.abs(v.x - hubDoorZ(lr)) < 1e-9), "its door is in the dado's break list");
  assert.equal(hub.U.uGachP.value.w, 0, "plain tiles, no runner");
  assert.equal(hub.U.uFans, undefined, "no fans");
  const card = SHOTS["door-little-rose"].scene({ lime: null, terr: null });
  assert.equal(card.U.uDoorN.value, 1);
  // The walk's stills of its bay: no fan in it (checked above), its door shut.
  const bay = SHOTS["bay-little-rose"].scene({ lime: null, terr: null });
  const i = bay.specs.findIndex((d) => d.unit === "little-rose");
  assert.equal(bay.U.uDoors.value[i].z, 0);
});

// ---- Critique r1, fix round 1; critique r2 ----
// Critique r2: the leaflets are cut on every tier and from the stills (D0.6.2's ladder), so none can ever be seen
// near Little Rose's door. And no door leaf passes the lens: every open leaf opens into its room on the near jamb
// (sets/corridor.js DOOR_OPEN), so over the whole walk, at 390 × 844 and 1440 × 900, in three bay orders, no
// point of any leaf inside the camera's frame is nearer than 1.5 m, and none is a full-height slab across the frame.
test("no leaflets anywhere; no door leaf in the walk camera's near field, at 390 and 1440, in any order", async () => {
  const { CORRIDOR, DOOR_FOCUS_Y, VIEW, WALK, WALK_OUT, corridorLayout, doorAngle, lensFor, walkPose, walkT, walkLeave, walkPitch } = await import("../src/world/journey.js");
  const { corridorScene } = await import("../src/world/sets/corridor.js");
  const { DOOR } = await import("../src/world/objects/door.js");
  const { PerspectiveCamera, Vector3 } = await import("three");
  const { readFileSync } = await import("node:fs");
  for (const f of ["../src/world/objects/props.js", "../src/world/sets/corridor.js", "../src/world/live.js", "../src/world/sets/stills.js"]) {
    assert.doesNotMatch(readFileSync(new URL(f, import.meta.url), "utf8"), /leaflets?:|LEAFLET|leafFade/, `${f} still draws leaflets`);
  }
  const FRAMINGS = [["side", 1440, 900, { x0: 0, x1: 940, y0: 64, y1: 900 }], ["below", 390, 844, { x0: 0, x1: 390, y0: 60, y1: 844 * 0.55 }]];
  for (const units of ORDERS) {
    const beats = beatsOf(units), lay = corridorLayout(beats), n = beats.length;
    const set = corridorScene({ beats });
    for (const [name, W, H, free] of FRAMINGS) {
      const v = VIEW[name], p1 = walkPose(1, name, lay);
      const fx = (free.x0 + v.at[0] * (free.x1 - free.x0)) / W, fy = (free.y0 + v.at[1] * (free.y1 - free.y0)) / H;
      const lens = lensFor(p1.pos, p1.yaw, [CORRIDOR.wallX, DOOR_FOCUS_Y, lay.doors[0].zc], p1.fov, W / H, fx, fy);
      let nearest = Infinity;
      for (let s = 0; s <= 400; s++) {
        const q = s / 400, out = q > WALK.release ? (q - WALK.release) / (1 - WALK.release) * 0.4 : 0;
        const p = walkPose(walkT(q, n) + walkLeave(out), name, lay), cam = new PerspectiveCamera(p.fov, W / H, 0.05, 60), y = (p.yaw * Math.PI) / 180;
        const pt = (walkPitch(q) * Math.PI) / 180, c10 = Math.cos(pt) * 10;
        cam.position.set(...p.pos);
        cam.lookAt(p.pos[0] + Math.sin(y) * c10, 1.1 - Math.sin(pt) * 10, p.pos[2] - Math.cos(y) * c10);
        cam.updateMatrixWorld();
        cam.projectionMatrix.makePerspective(lens.l * cam.near, lens.r * cam.near, lens.t * cam.near, lens.b * cam.near, cam.near, cam.far);
        const eye = new Vector3(...p.pos);
        set.specs.forEach((d, i) => {
          if (d.unit === "little-rose") return;
          assert.ok(d.hinge === 1 && d.swing === -1, `${d.unit}: hinged on the near jamb, opening into its room`);
          set.setDoor(i, doorAngle(i, q, n));
          const a = (doorAngle(i, q, n) * Math.PI) / 180;
          // The leaf's centre plane (objects/door.js: from its hinge along (−swing·sin a, −hinge·cos a)), sampled.
          const hx = CORRIDOR.wallX + 0.03, hz = d.zc + d.hinge * (DOOR.w / 2);
          let inFrame = 0;
          for (let r = 0; r <= 8; r++) for (let h = 0; h <= 8; h++) {
            const sAlong = (r / 8) * DOOR.w, P = new Vector3(hx - d.swing * Math.sin(a) * sAlong, (h / 8) * DOOR.h, hz - d.hinge * Math.cos(a) * sAlong);
            const c = P.clone().project(cam);
            if (!(c.z < 1 && Math.abs(c.x) <= 1 && Math.abs(c.y) <= 1)) continue;
            inFrame++;
            nearest = Math.min(nearest, P.distanceTo(eye));
            assert.ok(P.distanceTo(eye) >= 1.5, `${units.join("/")} ${name} q ${q.toFixed(3)}: door ${i}'s leaf ${P.distanceTo(eye).toFixed(2)} m from the lens`);
          }
        });
      }
      assert.ok(nearest > 1.5 && Number.isFinite(nearest), `${units.join("/")} ${name}: nearest leaf in frame ${nearest.toFixed(2)} m`);
    }
  }
  assert.ok(WALK_OUT.hem >= 0.24 && WALK_OUT.hemPhone >= 0.16, "the hem is at least D0.5's 24svh / 16svh");
});

// The thresholds (critique r1): before every door, Little Rose's too, the runner gives way to the same plain cream,
// one uniform for all of them, so its bay is never marked by a difference; the stills' runner has the same.
test("every door has the same plain threshold, Little Rose's included, live and in the stills", async () => {
  const { corridorScene } = await import("../src/world/sets/corridor.js");
  const { HOUSE } = await import("../src/world/sets/common.js");
  const { SHOTS } = await import("../src/world/sets/stills.js");
  for (const units of ORDERS) {
    const set = corridorScene({ beats: beatsOf(units), open: 72 });
    assert.equal(set.U.uGachP.value.y, HOUSE.threshold);
    assert.equal(Math.round(HOUSE.threshold / HOUSE.tile), 3, "six tiles, 1.2 m");
    assert.ok(Math.abs(HOUSE.threshold - 3 * HOUSE.tile) < 1e-9);
    assert.equal(set.U.uDoorN.value, units.length, "every door, Little Rose's in the list");
  }
  // Its threshold takes no pool of any kind: its plain transom casts nothing on the floor (glsl/doors.js).
  const { DOORS } = await import("../src/world/glsl/doors.js");
  assert.match(DOORS, /if \(D\.y < 0\.5\) tr = 0\.;/, "Little Rose's transom casts no pool");
  for (const name of ["bay-green-bamboo", "bay-little-rose", "bay-cau-han", "passage-corridor"]) {
    const s = SHOTS[name].scene({ lime: null, terr: null });
    assert.equal(s.U.uGachP.value.y, HOUSE.threshold, name);
    assert.equal(s.U.uTrace.value.w, 0, `${name}: no traces in any still`);
  }
});

// Traces (D6.8, staging only): the chalk lò cò on the Tre Xanh bay's plain band, never in or near Little Rose's bay
// (its whole length at least 1.5 m from it, or none), never in a still, and only when the page asks for it.
test("the chalk lò cò stays in the Tre Xanh bay, far from Little Rose's, and only when traces are on", async () => {
  const { LO_CO, corridorScene, loCoSpot } = await import("../src/world/sets/corridor.js");
  const { CORRIDOR, corridorLayout } = await import("../src/world/journey.js");
  for (const units of [...ORDERS, ["green-bamboo", "cau-han", "little-rose"], ["cau-han", "little-rose", "green-bamboo"]]) {
    const lay = corridorLayout(beatsOf(units)), spot = loCoSpot(lay, CORRIDOR);
    const lr = lay.doors.find((d) => d.unit === "little-rose").bay, tx = lay.doors.find((d) => d.unit === "green-bamboo");
    if (spot) {
      assert.ok(spot.z1 + LO_CO.clear <= lr[0] || spot.z0 - LO_CO.clear >= lr[1], `${units.join("/")}: the lò cò within ${LO_CO.clear} m of Little Rose's bay`);
      assert.ok(spot.z0 >= tx.bay[0] && spot.z1 <= tx.bay[1], `${units.join("/")}: the lò cò in the Tre Xanh bay`);
      assert.ok(spot.x > CORRIDOR.screenX + 1.6 && spot.x < CORRIDOR.wallX, "on the plain band");
    }
    assert.equal(corridorScene({ beats: beatsOf(units) }).U.uTrace.value.w, 0, "off unless asked");
    const on = corridorScene({ beats: beatsOf(units), traces: true }).U.uTrace.value;
    assert.equal(on.w, spot ? 1 : 0);
  }
  // The page asks only in staging while HCWA has not approved traces (pages/home/corridor.mjs).
  const { ctx } = await import("../src/lib/ctx.mjs");
  const { corridor } = await import("../src/pages/home/corridor.mjs");
  const { str } = await import("../src/lib/html.mjs");
  for (const lang of ["vi", "en"]) {
    const c = ctx(lang, "home", true);
    if (c.content.world?.traces?.approved !== true) assert.doesNotMatch(str(corridor(c)), /data-traces/, "never on a PROD page before approval");
  }
});
