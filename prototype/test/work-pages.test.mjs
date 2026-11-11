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
import { routes } from "../src/routes.mjs";
import { headerReady } from "../src/partials/lit-header.mjs";

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

test("hub area chips carry their area's gạch tile from the page's one sprite, before every program row (final m3)", () => {
  const PIG = { education: "ochre", protection: "indigo", family: "jade", policy: "brick" };
  for (const lang of LANGS) {
    const h = page(ourWork, lang, "ourWork");
    assert.equal((h.match(/class="gach-sprite"/g) ?? []).length, 1, "one sprite per page");
    const nav = h.match(/<nav class="filters[\s\S]*?<\/nav>/)[0];
    assert.match(nav, /class="gach-sprite"/, "the filters bring the sprite (never a row a filter may hide)");
    assert.ok(h.indexOf('class="gach-sprite"') < h.indexOf('class="program-row"'));
    for (const a of content.work.focusAreas) {
      const chip = nav.match(new RegExp(`<a class="chip" href="\\?area=${a.id}#f-${a.id}"[\\s\\S]*?</a>`))[0];
      assert.match(chip, new RegExp(`<svg class="gach-strip chip__tile" viewBox="0 0 200 200" aria-hidden="true" focusable="false"><use href="#gach-[a-zA-Z]+" class="pig--${a.id}"/></svg>`));
      assert.ok(css.includes(`.pig--${a.id})`) && css.includes(`var(--color-tile-${PIG[a.id]})`), `pages.css paints ${a.id} in ${PIG[a.id]}`);
    }
    assert.doesNotMatch(nav, /block-art|chip__icon/, "no more navy screen blocks in the area filter");
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

// PLAN-M4 4.10 and 4.12: the generalised center and program templates.
test("Cầu Hàn: its own header, unit and emblem; no health status anywhere on it; no figures or news sections", () => {
  for (const lang of LANGS) {
    const h = page(center, lang, "cauHan");
    // Its lit header once the walls stream ships header-cau-han; the morning sun band with its facts until
    // then, never the lit header's flat shade fill (fix round 1).
    if (headerReady("header-cau-han")) assert.match(h, /data-still="header-cau-han"/);
    else {
      assert.doesNotMatch(h, /data-still=/, "no shade-fill header while the still is pending");
      assert.match(h, /class="sun-head sun-head--page sun-head--morning[^"]*sun-head--unit sun-head--art"/);
      assert.match(h, /class="block-art block-art--vomCau" aria-hidden|<div class="sun-head__art" aria-hidden="true"><span class="block-art block-art--vomCau">/, "its own block as the picture, decoration only");
      assert.match(h, /class="sun-head__meta">[\s\S]*lit-head__facts/, "the facts stay above the title");
      assert.match(h, /class="day day--center day--center-sun"/, "the day starts on the band's lime");
    }
    assert.match(h, /--unit:var\(--color-unit-cau-han\)/);
    assert.doesNotMatch(h, /class="related\b|class="counts\b/, "no scoped figures, no tagged news");
    // Who it serves names the families the project is for (work.json); nothing else on the page may point
    // at any child's or family's health.
    const rest = h.replace(new RegExp(esc(ctx(lang, "cauHan", false).tx(content.work.centers.find((x) => x.id === "cau-han").serves)).replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), "g"), "");
    assert.doesNotMatch(rest, /HIV|AIDS|nhiễm|infect|positive|dương tính/i);
    assert.equal(page(center, lang, "greenBamboo").includes('data-still="header-green-bamboo"'), true, "Green Bamboo unchanged");
  }
});

test("programs: Charity Class and the completed pilot use the program template without Little Rose's door", () => {
  for (const lang of LANGS) {
    const cc = page(program, lang, "charityClass");
    assert.match(cc, /data-still="header-thrive-together"/);
    assert.doesNotMatch(cc, /data-unit="little-rose"/, "Charity Class does not run at Little Rose");
    assert.doesNotMatch(cc, /lit-head__funder/, "no funder named");
    const dp = page(program, lang, "disabilityPilot");
    assert.match(dp, /status--completed/);
    assert.doesNotMatch(dp, /class="where\b/, "no centers, no 'where it runs'");
    assert.doesNotMatch(dp, new RegExp(`href="${routes.partner[lang]}"`), "a completed project takes no new partners");
    const urls = content.work.programPages["disability-pilot"].news;
    assert.equal((dp.match(/<li class="dated">/g) ?? []).length, urls.length, "its two real posts only");
    // Thrive Together keeps its Little Rose chip (name only) and partner CTA.
    const tt = page(program, lang, "thriveTogether");
    assert.match(tt, /data-unit="little-rose" data-door="closed"/);
    assert.match(tt, new RegExp(`href="${routes.partner[lang]}"`));
  }
});
