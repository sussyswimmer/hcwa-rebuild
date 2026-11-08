// Our Work hub, a center and a program (PLAN P1-P3): the no-JS filter, Little Rose on every page it
// appears, staging-only placeholders, the lit headers and the two shapes (doors for places, slats for
// time-bound work).
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { str, esc } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { ourWork, center, program } from "../src/pages/work.mjs";
import { layout } from "../src/partials/layout.mjs";
import { findBlockers } from "../src/render.mjs";
import { workSheet } from "../src/pages/home/work-sheet.mjs";

const css = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8");
const page = (fn, lang, key, prod = false) => str(fn(ctx(lang, key, prod)).body);
const LANGS = ["vi", "en"];

test("hub filters work without JS: every area and status chip lands on a :target the CSS knows", () => {
  for (const lang of LANGS) {
    const h = page(ourWork, lang, "ourWork");
    for (const a of content.work.focusAreas) {
      assert.match(h, new RegExp(`<span class="filters__anchor" id="f-${a.id}"></span>`));
      assert.match(h, new RegExp(`href="\\?area=${a.id}#f-${a.id}" data-area="${a.id}"`));
      assert.ok(css.includes(`:has(#f-${a.id}:target) [data-areas]:not([data-areas~="${a.id}"])`), `pages.css hides other areas for ${a.id}`);
      assert.ok(css.includes(`:has(#f-${a.id}:target) [data-show-for~="${a.id}"]`), `pages.css shows the empty note for ${a.id}`);
    }
    for (const s of ["active", "completed"]) {
      assert.match(h, new RegExp(`id="f--${s}"`));
      assert.ok(css.includes(`:has(#f--${s}:target) .program-row:not([data-status="${s}"])`));
    }
    // An area no center serves gets the centers' empty note (policy today).
    const unserved = content.work.focusAreas.map((a) => a.id).filter((a) => !content.work.centers.some((x) => x.areas.includes(a)));
    for (const a of unserved) assert.match(h, new RegExp(`data-show-for="[^"]*\\b${a}\\b`));
  }
});

test("home's focus tiles land on the hub's filter, filtered without JS", () => {
  for (const lang of LANGS) {
    const c = ctx(lang, "home", false);
    const sheet = str(workSheet(c));
    for (const a of content.work.focusAreas) assert.ok(sheet.includes(`href="${c.url("ourWork")}?area=${a.id}#f-${a.id}"`), a.id);
  }
});

test("centers are doors, programs are slats: two shapes that cannot be confused", () => {
  for (const lang of LANGS) {
    const h = page(ourWork, lang, "ourWork");
    assert.equal((h.match(/<li class="door"/g) ?? []).length, content.work.centers.length);
    assert.equal((h.match(/<li class="program-row"/g) ?? []).length, content.work.programs.length);
    assert.doesNotMatch(h, /class="ph\b|center-card/, "no Phase 1 placeholder boxes or center cards");
    for (const fn of [center, program]) assert.doesNotMatch(page(fn, lang, fn === center ? "greenBamboo" : "thriveTogether"), /class="ph\b|report-row__cover/);
  }
});

test("Little Rose: door closed, never 'visit', never a place, its privacy note on every page that names it", () => {
  const lr = content.work.centers.find((x) => x.id === "little-rose");
  for (const lang of LANGS) {
    const c = ctx(lang, "ourWork", false);
    const door = page(ourWork, lang, "ourWork").match(/<li class="door"[^>]*data-unit="little-rose"[\s\S]*?<\/li>/)[0];
    assert.match(door, /data-door="closed"/);
    assert.ok(!door.includes(esc(c.t("visitCenter"))), "never 'visit'");
    assert.ok(door.includes(esc(c.t("aboutShelter"))) && door.includes(esc(c.t("bakery"))));
    assert.ok(door.includes(esc(c.tx(content.work.littleRose.privacyNote))));
    assert.doesNotMatch(door, /door__where|icon door__pin/, "no location line");
    const place = page(program, lang, "thriveTogether").match(/<li class="place"[^>]*data-unit="little-rose"[\s\S]*?<\/li>/)[0];
    assert.match(place, /data-door="closed"/);
    assert.doesNotMatch(place, /<a /, "the name only, no link");
    assert.ok(page(program, lang, "thriveTogether").includes(esc(c.tx(content.work.littleRose.privacyNote))));
    assert.ok(!("location" in lr));
  }
});

test("staging-only placeholders stay off production: gallery, figures, since tokens", () => {
  for (const lang of LANGS) {
    const stage = page(center, lang, "greenBamboo");
    const prod = page(center, lang, "greenBamboo", true);
    assert.match(stage, /class="hang__wall"/);
    assert.match(stage, /data-pending/);
    assert.doesNotMatch(prod, /hang__wall|data-pending|data-lightbox/, "no empty frames, no unverified figures");
    const progProd = page(program, lang, "thriveTogether", true);
    assert.doesNotMatch(progProd, /data-pending|class="doc"/);
    assert.match(progProd, /class="docs__empty"/, "production shows the empty documents state");
    // Whatever is still draft on production is caught by the guard, never shipped; so is the Little Rose
    // door until HCWA's safeguarding sign-off (world.json).
    for (const h of [prod, progProd, page(ourWork, lang, "ourWork", true)]) assert.ok(findBlockers(h).every((w) => /draft|DRAFT|bracket|Little Rose door/.test(w)), findBlockers(h).join());
  }
});

test("each page opens on its lit header and asks the head script for the sun-in", () => {
  const pages = [[ourWork, "ourWork", "header-our-work"], [center, "greenBamboo", "header-green-bamboo"], [program, "thriveTogether", "header-thrive-together"]];
  for (const [fn, key, still] of pages) for (const lang of LANGS) {
    const c = ctx(lang, key, false);
    const out = str(layout(c, fn(c)));
    assert.match(out, /<html lang="(vi|en)" data-lit>/);
    assert.match(out, new RegExp(`class="lit-head[^"]*"[\\s\\S]*?data-still="${still}"`));
    assert.equal((out.match(/<h1\b/g) ?? []).length, 1);
    assert.match(out, /<section class="dusk"/, "the day ends at dusk");
  }
  assert.match(page(center, "vi", "greenBamboo"), /lit-head--unit" style="--unit:var\(--color-unit-green-bamboo\)"/);
});
