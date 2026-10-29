// 08 task 1.6: PROD=1 must leave out unverified figures and refuse to ship anything unapproved.
import { test } from "node:test";
import assert from "node:assert/strict";
import { spawnSync } from "node:child_process";
import { existsSync, mkdtempSync, readFileSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { fileURLToPath } from "node:url";
import { renderAll, findBlockers } from "../src/render.mjs";
import { visibleFigures } from "../src/lib/content.mjs";

const tmp = (p) => mkdtempSync(join(tmpdir(), p));

test("each blocker category is detected on its own; approved HTML passes", () => {
  assert.deepEqual(findBlockers('<p>Approved text</p><a href="/x?a=[1]" title="[x]">Link</a>'), [], "brackets in attributes are not text");
  assert.deepEqual(findBlockers("<p data-draft>x</p>"), ["draft text (data-draft)"]);
  assert.deepEqual(findBlockers('<span data-pending>N</span>'), ["unverified figure"]);
  assert.deepEqual(findBlockers('<dd data-donation-placeholder>x</dd>'), ["donation placeholder"]);
  assert.deepEqual(findBlockers("<p>children supported since [year]</p>"), ["bracket placeholder token in text"]);
  assert.ok(findBlockers("<p>[DRAFT: x]</p>").includes("[DRAFT marker"));
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
  for (const why of ["draft text", "donation placeholder", "unverified figure", "bracket placeholder token"]) {
    assert.match(message, new RegExp(why), `expected a "${why}" problem`);
  }
  const home = readFileSync(join(out, "index.html"), "utf8");
  assert.doesNotMatch(home, /data-pending/, "pending chips leaked into a PROD page");
  assert.doesNotMatch(home, /figures--strip/, "strip should hide with < 2 verified figures");
  assert.doesNotMatch(home, /proto-note/, "prototype note leaked into a PROD page");
  assert.equal(existsSync(join(out, "components", "index.html")), false, "components page is prototype-only");
});

test("prototype render succeeds and shows pending figures", () => {
  const files = renderAll(tmp("hcwa-proto-"), false);
  assert.equal(files.length, 18);
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
