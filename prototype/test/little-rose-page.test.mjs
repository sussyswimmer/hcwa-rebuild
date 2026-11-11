// PLAN-M4 4.11: the Little Rose Warm Shelter page, safeguarding first (05-DESIGN D6.5, 0.6.4, section 8;
// CLAUDE.md). Words and an abstract illustration only: no door, no still, no picture, no place, no phone of
// its own, never "visit", and the privacy note on the page. VI and EN, staging and production.
import { test } from "node:test";
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

test("the illustration is the house's block and plain daylight only: no pink light, no figure", () => {
  const { body } = render("vi", false);
  const art = body.match(/<div class="sun-head__art"[\s\S]*?<\/div>/)[0];
  assert.match(art, /aria-hidden="true"/);
  assert.match(art, /class="lr-art__block"/);
  assert.doesNotMatch(art, /little-rose|unit|pink|rose/i);
});
