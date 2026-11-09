// The WordPress export (docs/plan/11-WP-PORT.md 1.1, 1.3, 3.4): the generated files the theme, hcwa-core
// and the tests read are what scripts/export-wp.mjs makes from the prototype now, the production guards
// are render.mjs's own, and every still that shows the Little Rose door is listed as showing it.
// Needs no dist: the committed files are compared with a fresh export in memory (classes.json, which is
// read from the built CSS, is checked only when the bundle has been built).
import { test } from "node:test";
import assert from "node:assert/strict";
import { existsSync, readFileSync, readdirSync } from "node:fs";
import { join } from "node:path";
import { spawnSync } from "node:child_process";
import { BLOCKERS, LITTLE_ROSE_DOOR, MIN_PARTNER_LOGOS } from "../src/render.mjs";
import { headInline } from "../src/lib/head-inline.mjs";
import { str } from "../src/lib/html.mjs";
import { STILLS } from "../src/world/still-shots.js";
import { DIST, GENERATED, REPO, bundleCss, cssClasses, generate, littleRoseDoorStills, readManifest } from "../scripts/export-wp.mjs";

const read = (p) => readFileSync(join(REPO, p), "utf8");
const gen = (p) => JSON.parse(readFileSync(join(GENERATED, p), "utf8"));

// The scenes that contain the Little Rose door (1.3): the home corridor's three doors, the hub's
// three-door wall and Little Rose's own card.
const SHOWS_DOOR = ["bay-cau-han", "bay-green-bamboo", "bay-little-rose", "door-little-rose", "hub-door-cau-han", "hub-door-green-bamboo", "hub-door-little-rose", "hub-doors-wide", "passage-corridor"];

test("every still that shows the Little Rose door is listed, and only those", async () => {
  const fresh = await littleRoseDoorStills();
  assert.deepEqual(Object.keys(fresh).filter((n) => fresh[n]).sort(), SHOWS_DOOR);
  const committed = gen("stills.json").stills;
  assert.deepEqual(Object.keys(committed).filter((n) => committed[n].showsLittleRoseDoor).sort(), SHOWS_DOOR);
  for (const name of ["door-green-bamboo", "door-cau-han", "gallery-wall", ...Object.keys(STILLS).filter((n) => n.startsWith("header-"))]) {
    assert.equal(committed[name].showsLittleRoseDoor, false, name);
  }
  // Every still the prototype declares is in stills.json (a name missing there reads as showing the door).
  assert.deepEqual(Object.keys(committed).sort(), Object.keys(STILLS).sort());
});

test("the guards are render.mjs's own patterns, exported once for Python and once for PHP", () => {
  const g = gen("guards.json");
  assert.deepEqual(g.blockers.map((b) => b.source), BLOCKERS.map(([re]) => String(re.source)));
  assert.deepEqual(g.blockers.map((b) => b.why), BLOCKERS.map(([, why]) => why));
  assert.equal(g.littleRoseDoor.source, String(LITTLE_ROSE_DOOR.source));
  assert.equal(g.minPartnerLogos, MIN_PARTNER_LOGOS);
  assert.equal(g.footnote.source, String(/^\[\d+\]$/.source));
  const php = read("wp/plugins/hcwa-core/includes/prototype-data.php");
  assert.equal(php.match(/'pattern' => /g)?.length, BLOCKERS.length, "one PHP pattern per blocker");
});

test("the PHP guard patterns compile and match like the JS ones", { skip: spawnSync("php", ["-v"]).status !== 0 && "no php" }, () => {
  const samples = ['<span data-draft>x</span>', "<p>[DRAFT: a]</p>", "<p>Năm [năm]</p>", "<p>[1]</p>", '<li data-unit="little-rose" class="a" data-door="closed">', "posters/hub-doors-wide-wide-still-1600.avif", "<p>plain</p>"];
  const code = `define('ABSPATH', '/'); $d = include '${join(REPO, "wp/plugins/hcwa-core/includes/prototype-data.php")}';
    $s = json_decode(stream_get_contents(STDIN), true); $out = array();
    foreach ($s as $t) { $row = array(); foreach ($d['guards']['blockers'] as $b) $row[] = preg_match($b['pattern'], $t); $row[] = preg_match($d['guards']['little_rose_door'], $t); $out[] = $row; }
    echo json_encode($out);`;
  const run = spawnSync("php", ["-r", code], { input: JSON.stringify(samples), encoding: "utf8" });
  assert.equal(run.status, 0, run.stderr);
  const want = samples.map((t) => [...BLOCKERS.map(([re]) => (re.test(t) ? 1 : 0)), LITTLE_ROSE_DOOR.test(t) ? 1 : 0]);
  assert.deepEqual(JSON.parse(run.stdout), want);
});

test("head-inline and pageReveal are the prototype's own scripts, within the budget", () => {
  for (const [variant, prod] of [["staging", false], ["production", true]]) {
    const js = readFileSync(join(GENERATED, `head-inline.${variant}.js`), "utf8");
    assert.equal(`<script>${js}</script>`, str(headInline(prod)), variant);
    assert.ok(Buffer.byteLength(js) <= 1536, `${variant} ${Buffer.byteLength(js)} B`);
  }
  assert.ok(readFileSync(join(GENERATED, "head-inline.production.js"), "utf8").includes("var proto = false;"));
  assert.ok(readFileSync(join(GENERATED, "page-reveal.js"), "utf8").startsWith("(function pageReveal() {"));
  // Every inline script the theme prints has its CSP hash recorded (1.2); wp/tests checks them against PHP.
  assert.deepEqual(Object.keys(gen("sources.json").csp).sort(), ["head-inline.production.js", "head-inline.staging.js", "page-reveal.js", "wp-bars announce", "wp-bars en"]);
  for (const v of Object.values(gen("sources.json").csp)) assert.match(v, /^'sha256-[A-Za-z0-9+/]{43}='$/);
});

test("the committed generated files are what the export makes now", async () => {
  const fresh = await generate({ dist: null });
  for (const [path, text] of fresh) {
    if (path.endsWith("/sources.json")) continue;
    assert.ok(existsSync(join(REPO, path)), `${path} is missing: run npm run export:wp`);
    assert.equal(read(path), text, `${path} is stale: run npm run export:wp`);
  }
  const sources = gen("sources.json"), now = JSON.parse(fresh.get("wp/themes/hcwa-2026/assets/generated/sources.json"));
  assert.deepEqual(sources.inputs, now.inputs, "sources.json inputs changed: run npm run export:wp");
  assert.equal(sources.version, now.version);
  // Nothing in generated/ that the export does not write.
  const files = (dir) => readdirSync(dir, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? files(join(dir, e.name)) : [join(dir, e.name)]));
  const known = new Set([...fresh.keys(), "wp/themes/hcwa-2026/assets/generated/classes.json"]);
  for (const f of files(GENERATED)) assert.ok(known.has(f.slice(REPO.length + 1)), `${f} is not written by the export`);
});

test("the bundle: relative URLs only, classes.json from its CSS", { skip: !readManifest() && "no assets/dist (run npm run export:wp)" }, () => {
  const assets = join(DIST, "assets");
  for (const f of readdirSync(assets).filter((n) => /\.(js|css)$/.test(n))) {
    const text = readFileSync(join(assets, f), "utf8");
    assert.doesNotMatch(text, /["'`(]\/(?:assets|svg|fonts|posters|img)\//, `${f} has a root-absolute asset URL`);
  }
  for (const f of ["_headers", "_redirects", "robots.txt"]) assert.ok(!existsSync(join(DIST, f)), `${f} left in dist`);
  const classes = gen("classes.json");
  assert.deepEqual(classes.classes, cssClasses(bundleCss().map((c) => c.text)));
  for (const c of classes.unstyled) assert.ok(!classes.classes.includes(c), c);
});
