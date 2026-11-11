// 08 task 1.6: PROD=1 must leave out unverified figures and refuse to ship anything unapproved.
import { test } from "node:test";
import assert from "node:assert/strict";
import { spawnSync } from "node:child_process";
import { existsSync, mkdtempSync, readFileSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { fileURLToPath } from "node:url";
import { renderAll, findBlockers, littleRoseDoorLeak, tracesBlocked } from "../src/render.mjs";
import { pageRoutes } from "../src/routes.mjs";
import { visibleFigures } from "../src/lib/content.mjs";

const tmp = (p) => mkdtempSync(join(tmpdir(), p));

test("each blocker category is detected on its own; approved HTML passes", () => {
  assert.deepEqual(findBlockers('<p>Approved text</p><a href="/x?a=[1]" title="[x]">Link</a>'), [], "brackets in attributes are not text");
  assert.deepEqual(findBlockers("<p data-draft>x</p>"), ["draft text (data-draft)"]);
  assert.deepEqual(findBlockers('<span data-pending>N</span>'), ["unverified figure"]);
  assert.deepEqual(findBlockers('<dd data-donation-placeholder>x</dd>'), ["donation placeholder"]);
  assert.deepEqual(findBlockers("<p>children supported since [year]</p>"), ["bracket placeholder token in text"]);
  assert.ok(findBlockers("<p>[DRAFT: x]</p>").includes("[DRAFT marker"));
  assert.deepEqual(findBlockers("<li class=\"roster__slot\" data-team-pending>x</li>"), ["team placeholder (people not yet agreed to be listed)"]);
});

test("unverified figures never render on production", () => {
  const figs = [{ verified: true }, { verified: false }];
  assert.equal(visibleFigures(figs, true).length, 1);
  assert.equal(visibleFigures(figs, false).length, 2);
});

test("PROD render fails per category, drops unverified figures and the components page", () => {
  const out = tmp("hcwa-prod-");
  let message = "";
  assert.throws(() => renderAll(out, true), (e) => ((message = e.message), /PROD build blocked/.test(e.message)));
  // "unverified figure" is covered by the category test above: production renders no pending figure at
  // all now (the donate page's gift amounts are staging-only, PLAN P5).
  for (const why of ["draft text", "donation placeholder", "bracket placeholder token"]) {
    assert.match(message, new RegExp(why), `expected a "${why}" problem`);
  }
  assert.doesNotMatch(message, /unverified figure/, "a pending figure reached a PROD page");
  const donate = readFileSync(join(out, "chung-tay", "ung-ho", "index.html"), "utf8");
  assert.doesNotMatch(donate, /class="gifts\b|data-pending/, "gift examples with [amount] tokens are staging only");
  assert.match(donate, /data-donation-placeholder/, "the payment panel still trips the donation guard");
  const home = readFileSync(join(out, "index.html"), "utf8");
  assert.doesNotMatch(home, /data-pending/, "pending chips leaked into a PROD page");
  assert.doesNotMatch(home, /figures--strip/, "strip should hide with < 2 verified figures");
  assert.doesNotMatch(home, /proto-note/, "prototype note leaked into a PROD page");
  assert.equal(existsSync(join(out, "components", "index.html")), false, "components page is prototype-only");
});

test("prototype render succeeds and shows pending figures", () => {
  const files = renderAll(tmp("hcwa-proto-"), false);
  assert.equal(files.length, pageRoutes.length * 2, "every built route in VI and EN");
  assert.ok(files.length >= 46, "PLAN-M4: 23 page pairs");
});

test("renderer refuses to clean a folder it did not create", () => {
  const out = tmp("hcwa-foreign-");
  writeFileSync(join(out, "keep-me.txt"), "not ours");
  assert.throws(() => renderAll(out, false), /Refusing to render/);
  assert.equal(readFileSync(join(out, "keep-me.txt"), "utf8"), "not ours");
});

test("the acceptance command PROD=1 npm run build exits non-zero", () => {
  const root = fileURLToPath(new URL("..", import.meta.url));
  const vite = join(root, "node_modules", "vite", "bin", "vite.js");
  const r = spawnSync(process.execPath, [vite, "build", "--outDir", tmp("hcwa-dist-")], { cwd: root, env: { ...process.env, PROD: "1" }, encoding: "utf8" });
  assert.notEqual(r.status, 0);
  assert.match(r.stdout + r.stderr, /PROD build blocked/);
});

// PLAN section 6: HCWA safeguarding sign-off for the world (content/draft/world.json).
test("the Little Rose door blocks PROD in every form until HCWA approves it", () => {
  const pending = { littleRoseDoor: { approved: false } };
  const approved = { littleRoseDoor: { approved: true } };
  const doors = [
    '<li class="bay" id="center-little-rose" data-beat data-unit="little-rose" data-door="closed">',
    '<li class="door" data-areas="protection" data-unit="little-rose" data-door="closed">',
    '<li class="place" data-unit="little-rose" data-door="closed">',
    '<li class="unit" data-unit="little-rose" data-door="closed">',
    '<img src="/posters/door-little-rose-card-still-800.jpg" alt="">',
    '<source srcset="/posters/bay-little-rose-desk-still-960.avif 960w">',
    '<source srcset="/posters/hub-door-little-rose-door-still-480.avif 480w">',
    // The hub panorama shows all three doors in one picture, Little Rose's among them.
    '<img src="/posters/hub-doors-wide-wide-still-1600.jpg" alt="">',
  ];
  for (const h of doors) {
    assert.ok(findBlockers(h, { approved: 0, world: pending }).some((w) => /Little Rose door/.test(w)), h);
    assert.ok(!littleRoseDoorLeak(h, approved), `approved door still blocked: ${h}`);
  }
  assert.ok(littleRoseDoorLeak(doors[0], {}), "a missing world.json counts as not approved");
  // Its name, its unit colour and a form option are not the door.
  for (const h of ['<li class="door-chip" style="--unit:var(--color-unit-little-rose)">', '<option value="little-rose">', '<a href="#center-little-rose">']) {
    assert.ok(!littleRoseDoorLeak(h, pending), h);
  }
});

test("every page that shows the Little Rose door is caught by the guard", () => {
  const out = tmp("hcwa-lr-");
  renderAll(out, false);
  const page = (...p) => readFileSync(join(out, ...p, "index.html"), "utf8");
  const pending = { littleRoseDoor: { approved: false } };
  for (const p of [[], ["en"], ["hoat-dong"], ["hoat-dong", "du-an", "cung-vuon-len"], ["lien-he"], ["en", "contact"]]) {
    assert.ok(littleRoseDoorLeak(page(...p), pending), `/${p.join("/")} shows the door but the guard misses it`);
  }
});

test("traces block PROD while enabled and unapproved", () => {
  assert.equal(tracesBlocked({ traces: { enabled: true, approved: false } }), true);
  assert.equal(tracesBlocked({ traces: { enabled: true } }), true);
  assert.equal(tracesBlocked({ traces: { enabled: false, approved: false } }), false);
  assert.equal(tracesBlocked({ traces: { enabled: true, approved: true } }), false);
});

test("PROD render names the world guards while world.json is unapproved", () => {
  const world = JSON.parse(readFileSync(fileURLToPath(new URL("../../content/draft/world.json", import.meta.url)), "utf8"));
  let message = "";
  assert.throws(() => renderAll(tmp("hcwa-prod-world-"), true), (e) => ((message = e.message), true));
  if (world.littleRoseDoor?.approved !== true) assert.match(message, /\/: Little Rose door without HCWA safeguarding sign-off/);
  if (tracesBlocked(world)) assert.match(message, /traces enabled without HCWA safeguarding approval/);
});
