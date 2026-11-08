// PLAN section 5 and D6.7: every named still (inner-page lit headers, the dusk band, door cards, the 404,
// the story slot) is on disk for each crop, layer and width, inside its budget, and the markup pages get
// from lib/posters.mjs is art-directed per crop, decorative, and eager only for a lit header's base.
import { test } from "node:test";
import assert from "node:assert/strict";
import { existsSync, statSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { STILLS, budgetKB, cropsOf, stillFile } from "../src/world/still-shots.js";
import { SHOTS } from "../src/world/sets/stills.js";
import { litStill, missingStills, still as stillHtml, stillPicture } from "../src/lib/posters.mjs";
import { str } from "../src/lib/html.mjs";

const still = (...a) => str(stillHtml(...a));

const pub = fileURLToPath(new URL("../public", import.meta.url));
const size = (p) => statSync(pub + p).size;
const EXTS = { base: ["avif", "webp", "jpg"], lightroom: ["avif", "webp"], lightscreen: ["avif", "webp"], still: ["avif", "webp", "jpg"] };

test("every still has a scene with a camera per crop", () => {
  assert.deepEqual(Object.keys(SHOTS).sort(), Object.keys(STILLS).sort());
  for (const name of Object.keys(STILLS)) assert.deepEqual(Object.keys(SHOTS[name].crops).sort(), cropsOf(name).map((c) => c.name).sort(), name);
});

test("every still is rendered: each crop, layer, width and format", () => {
  for (const name of Object.keys(STILLS)) assert.deepEqual(missingStills(name), [], `${name}: npm run posters -- --still ${name}`);
});

test("each file is inside its budget (AVIF; WebP and JPEG at most twice)", () => {
  for (const [name, s] of Object.entries(STILLS)) for (const c of cropsOf(name)) for (const layer of s.layers) for (const w of c.widths) {
    // A lit header's room light may take what its base leaves of the base's share (encode-posters.py): the
    // layers together keep the header's budget (next test).
    const spare = s.kind === "header" && layer === "lightroom" ? Math.max(0, budgetKB(name, c.name, "base", w) - size(stillFile(name, c.name, "base", w, "avif")) / 1024 - 0.5) : 0;
    const kb = budgetKB(name, c.name, layer, w) + spare;
    for (const ext of EXTS[layer]) {
      const p = stillFile(name, c.name, layer, w, ext);
      assert.ok(existsSync(pub + p), p);
      assert.ok(size(p) <= kb * 1024 * (ext === "avif" ? 1 : 2), `${p}: ${(size(p) / 1024).toFixed(1)} KB > ${kb * (ext === "avif" ? 1 : 2)} KB`);
    }
  }
});

test("a lit header's layers together: 70 KB at 1600 w, 22 KB at 480 w (AVIF)", () => {
  for (const [name, s] of Object.entries(STILLS)) {
    if (s.kind !== "header") continue;
    for (const c of cropsOf(name)) for (const w of c.widths) {
      const total = s.layers.reduce((sum, l) => sum + size(stillFile(name, c.name, l, w, "avif")), 0);
      const cap = w >= 1600 ? 70 : w <= 480 ? 22 : (70 * w) / 1600 + 4;
      assert.ok(total <= cap * 1024, `${name} ${c.name} ${w}: ${(total / 1024).toFixed(1)} KB > ${cap} KB`);
    }
  }
});

test("litStill: decorative, base eager, light lazy, one <source> per crop and format", () => {
  const m = str(litStill("header-our-work"));
  assert.match(m, /^<div class="lit lit--still" aria-hidden="true" data-still="header-our-work">/);
  const base = m.match(/<picture class="lit__base">[\s\S]*?<\/picture>/)[0];
  assert.match(base, /fetchpriority="high"/);
  assert.doesNotMatch(base, /loading="lazy"/);
  for (const cls of ["lit__light", "lit__screen"]) assert.match(m.match(new RegExp(`<picture class="${cls}">[\\s\\S]*?</picture>`))[0], /loading="lazy"/);
  // desk (3:1, media) then mobile (4:3, no media): AVIF, WebP, JPEG each.
  assert.equal((base.match(/<source /g) ?? []).length, 6);
  assert.match(base, /<source type="image\/avif" media="\(min-width: 600px\)" srcset="\/posters\/header-our-work-desk-base-960\.avif 960w, \/posters\/header-our-work-desk-base-1600\.avif 1600w" sizes="100vw" width="1600" height="533">/);
  assert.match(base, /<img src="\/posters\/header-our-work-mobile-base-960\.jpg" alt="" width="960" height="720"/);
  assert.doesNotMatch(m, /data:image\/gif/, "no placeholder source once the files exist");
});

test("a header whose opening is out of frame has no screen layer", () => {
  assert.doesNotMatch(str(litStill("header-thrive-together")), /lit__screen/);
  assert.throws(() => stillPicture("header-thrive-together", "lightscreen"), /no lightscreen layer/);
});

test("plain stills are lazy and decorative unless asked; unknown names fail loudly", () => {
  const m = still("dusk");
  assert.match(m, /loading="lazy"/);
  assert.match(m, /alt=""/);
  assert.match(m, /media="\(min-width: 600px\)"/);
  assert.match(still("not-found", { eager: true }), /fetchpriority="high"/);
  assert.match(still("door-green-bamboo", { sizes: "(min-width: 1024px) 400px, 100vw" }), /sizes="\(min-width: 1024px\) 400px, 100vw"/);
  assert.throws(() => still("header-our-work"), /no still layer/);
  assert.throws(() => litStill("header-nowhere"), /no still named/);
});

test("lit headers carry their motion: louver reveal (Get Involved), 0.12× drift (center, program)", () => {
  assert.match(str(litStill("header-get-involved")), /class="lit lit--still lit--louver"/);
  for (const name of ["header-green-bamboo", "header-thrive-together"]) assert.match(str(litStill(name)), /class="lit lit--still lit--drift"/);
  assert.match(str(litStill("header-our-work", { cls: "page-head__still" })), /class="lit lit--still page-head__still"/);
});
