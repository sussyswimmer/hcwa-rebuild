// PLAN-M4 4.11: the Little Rose Warm Shelter page, safeguarding first (05-DESIGN D6.5, 0.6.4, section 8;
// CLAUDE.md). Words and an abstract illustration only: no door, no still, no picture, no place, no phone of
// its own, never "visit", and the privacy note on the page. VI and EN, staging and production.
import { test } from "node:test";
import { readFileSync } from "node:fs";
import assert from "node:assert/strict";
import { str, esc } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { routes } from "../src/routes.mjs";
import { layout } from "../src/partials/layout.mjs";
import { pages } from "../src/pages/index.mjs";
import { LITTLE_ROSE_DOOR, findBlockers } from "../src/render.mjs";

const CASES = ["vi", "en"].flatMap((lang) => [false, true].map((prod) => [lang, prod]));
const render = (lang, prod) => {
  const c = ctx(lang, "littleRose", prod);
  const page = pages[routes.littleRose.page](c);
  return { c, body: str(page.body), full: str(layout(c, page)) };
};

test("the route is its own template, not the door-page center()", () => {
  assert.equal(routes.littleRose.page, "little-rose");
  const x = content.work.centers.find((u) => u.id === "little-rose");
  for (const lang of ["vi", "en"]) assert.equal(x.path[lang], routes.littleRose[lang]);
});

for (const [lang, prod] of CASES) {
  const label = `${lang}${prod ? " PROD" : ""}`;
  test(`${label}: no door in any form, no still, no picture`, () => {
    const { body, full } = render(lang, prod);
    assert.doesNotMatch(full, /data-door/, "no data-door anywhere on the page");
    assert.doesNotMatch(full, LITTLE_ROSE_DOOR, "nothing the PROD door guard would catch");
    assert.doesNotMatch(full, /door-little-rose-|bay-little-rose-|hub-door-little-rose|hub-doors-wide/);
    assert.doesNotMatch(body, /<img|<picture|\/posters\//, "no image of any kind in the page body");
    assert.ok(!findBlockers(full).some((w) => /Little Rose door/.test(w)));
  });

  test(`${label}: no location, no contact person, no phone of its own, never "visit"`, () => {
    const { c, body, full } = render(lang, prod);
    const x = content.work.centers.find((u) => u.id === "little-rose");
    assert.equal(x.location, undefined, "work.json carries no location for Little Rose");
    assert.doesNotMatch(body, /door__where|icon door__pin|lit-head__facts|plate__facts|\[phường\]|\[ward\]|Lâm Văn Bền|Tân Hưng|google\.com\/maps/);
    assert.ok(!full.includes(esc(c.t("visitCenter"))), "never the visit label");
    assert.doesNotMatch(body.replace(/<[^>]+>/g, " "), /\bvisit\b|đến thăm/i, "never the word visit");
    const tels = new Set([...full.matchAll(/href="tel:([^"]+)"/g)].map((m) => m[1]));
    assert.deepEqual([...tels], [content.site.phone.tel], "only the office phone");
    assert.doesNotMatch(body, /contactPerson|Chủ nhiệm|Người liên hệ|share|facebook\.com\/TheLittleRose/i);
    assert.doesNotMatch(body, /class="(related|hang|counts)\b/, "no news, gallery or figures");
  });

  test(`${label}: the privacy note is on the page, and the ways to help go through HCWA`, () => {
    const { c, body } = render(lang, prod);
    assert.ok(body.includes(esc(c.tx(content.work.littleRose.privacyNote))));
    for (const key of ["donate", "bakery", "inKind", "contact"]) assert.ok(body.includes(`href="${c.url(key)}"`), key);
    assert.doesNotMatch(body, /\?center=little-rose/, "no account of its own is implied");
  });
}

// M6 "Khu vườn có tường bao": the illustration is a paper-cut garden in inline SVG. Each rule below is a
// safeguarding rule from the art direction, held for both languages and for staging and production.
const sceneOf = (body) => body.match(/<div class="lr-garden__scene"[\s\S]*?<span class="lr-layer lr-grain"><\/span><\/div>/)?.[0];
const ALLOWED = new Set(["lime-50", "lime-100", "sun-100", "sun-200", "sun-300", "tile-cream", "tile-ochre", "tile-jade", "tile-indigo", "ink-700", "ink-900", "gold-700"]);

for (const [lang, prod] of CASES) {
  const label = `${lang}${prod ? " PROD" : ""}`;
  test(`${label}: the garden is one decorative inline SVG picture, with no file, image or words in it`, () => {
    const { body } = render(lang, prod);
    const scene = sceneOf(body);
    assert.ok(scene, "the garden scene is on the page");
    assert.match(scene, /^<div class="lr-garden__scene" aria-hidden="true">/);
    assert.match(scene, /<svg class="lr-scene[^"]*"[^>]*aria-hidden="true"/);
    assert.doesNotMatch(scene, /<image|<img|<foreignObject|<picture|<video|<iframe/i);
    for (const [, ref] of scene.matchAll(/(?:xlink:)?href="([^"]*)"/g)) assert.match(ref, /^#lr-/, `only local references (${ref})`);
    for (const [, ref] of scene.matchAll(/url\(([^)]*)\)/g)) assert.match(ref, /^#lr-[\w-]+$/, `only local paint servers and filters (${ref})`);
    assert.doesNotMatch(scene, /<text[\s>]|<tspan/, "no words inside the picture");
  });

  test(`${label}: nothing in the garden names a person, a way in, a place or the unit's pink`, () => {
    const scene = sceneOf(render(lang, prod).body);
    const names = [...scene.matchAll(/\s(?:class|id)="([^"]+)"/g)].map((m) => m[1]);
    assert.ok(names.length > 10);
    for (const n of names) assert.doesNotMatch(n, /door|gate|person|girl|face|hand|child|rose|pink|little-rose|unit|window-open|street|sign|number/i, n);
  });

  test(`${label}: every colour in the garden is a scene token (no hex, rgb or hsl; no pink, red or brick)`, () => {
    const scene = sceneOf(render(lang, prod).body);
    assert.doesNotMatch(scene, /#[0-9a-f]{3,8}\b(?![\w-])|rgba?\(|hsla?\(|oklch\(|unit-little-rose|tile-brick|--color-error|magenta|crimson/i);
    const paints = [...scene.matchAll(/(?:^|[\s;"])(fill|stroke|stop-color|flood-color)\s*[:=]\s*"?([^;"]+)/g)].map((m) => m[2].trim());
    assert.ok(paints.length > 40, `${paints.length} paints`);
    for (const p of paints) {
      if (/^(none|currentColor|url\(#lr-[\w-]+\))$/.test(p)) continue;
      const t = p.match(/^var\(--color-([\w-]+)\)$/)?.[1];
      assert.ok(t && ALLOWED.has(t), `paint ${p} is a scene token`);
    }
  });
}

test("the story's steps are the five things the shelter provides, from content only, in both languages", () => {
  const offers = content.work.littleRose.page.offers;
  for (const lang of ["vi", "en"]) {
    const { c, body } = render(lang, false);
    const steps = [...body.matchAll(/<li class="lr-step" data-step="(\d)"><span class="lr-step__text"( data-draft)?>([^<]*)<\/span><\/li>/g)];
    assert.equal(steps.length, offers.length, "one step per offer");
    steps.forEach((m, i) => {
      assert.equal(m[1], String(i + 1));
      assert.equal(m[3], esc(c.tx(offers[i])), "the step text is the content");
      assert.equal(Boolean(m[2]), Boolean(offers[i].draft), "draft wording is marked");
    });
    // The rest frame is the plain list: visible without JS (no reveal on it).
    assert.match(body, /<ul class="does offers__list lr-steps">/);
    assert.doesNotMatch(body.match(/<ul class="does offers__list lr-steps">/)[0], /data-reveal/);
    // The caption says it is an illustration, in the page's language, marked as draft wording.
    const note = content.work.littleRose.page.artNote;
    assert.ok(body.includes(`<figcaption class="lr-garden__note" data-draft>${esc(note[lang])}</figcaption>`));
  }
});

test("no band, rule or pink paint at the head of the page; the production build still waits on the drafts", () => {
  const { body } = render("vi", false);
  assert.doesNotMatch(body, /sun-head--unit|--unit:var\(--color-unit-little-rose\)|lr-art/);
  const css = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8");
  const lr = css.slice(css.indexOf("Little Rose: \"Khu vườn"), css.indexOf("@media (prefers-reduced-motion: reduce) { .lr-sheet"));
  assert.ok(lr.length > 1000, "the garden's CSS block");
  assert.doesNotMatch(lr, /unit-little-rose|tile-brick|--color-error|#[0-9a-f]{3,8}\b/i);
  assert.doesNotMatch(lr.replace(/\/\*[\s\S]*?\*\//g, ""), /\.lr-(?:step__text|head__title|head__intro)[^{]*\{[^}]*(?:transform|translate|scale)/, "the words never move");
  // PROD: the page's own drafts are still caught by the existing guard.
  const prod = render("vi", true);
  assert.ok(findBlockers(prod.full).includes("draft text (data-draft)"));
});
