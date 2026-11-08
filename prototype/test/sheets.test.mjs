// The home sheets (PLAN H5-H11): block art from the shader's numbers, the partners and featured-story
// production guards, dates per locale, one sun button, Little Rose as a name only.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { str } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { findBlockers } from "../src/render.mjs";
import { generate } from "../scripts/blocks-svg.mjs";
import { KINDS, AREA_BLOCK, holes } from "../src/partials/blocks.mjs";
import { HOA_THI, BLOCK } from "../src/world/glsl/blocks.js";
import { partners } from "../src/pages/home/partners.mjs";
import { news } from "../src/pages/home/news.mjs";
import { help } from "../src/pages/home/help.mjs";
import { workSheet } from "../src/pages/home/work-sheet.mjs";
import { datedPost, programRow } from "../src/partials/components.mjs";
import { layout } from "../src/partials/layout.mjs";
import { stillReady } from "../src/lib/posters.mjs";

const svgDir = new URL("../public/svg/", import.meta.url);
const withSite = (c, site) => ({ ...c, content: { ...c.content, site: { ...c.content.site, ...site } } });
const text = (h) => h.replace(/<[^>]+>/g, "").replace(/\s+/g, " ").trim();

test("public/svg matches the block SDF numbers (run node scripts/blocks-svg.mjs)", () => {
  for (const [name, svg] of Object.entries(generate())) assert.equal(readFileSync(new URL(name, svgDir), "utf8"), svg, `${name} drifted`);
  const r = HOA_THI.r * 1000;
  assert.match(holes("hoaThi"), new RegExp(`^M${r} ${-r}A${r} ${r} `), "quatrefoil lobes use HOA_THI.r");
  assert.match(generate()["face-hoa-thi.svg"], new RegExp(`viewBox="${(-BLOCK / 2) * 1000} ${(-BLOCK / 2) * 1000} ${BLOCK * 1000} ${BLOCK * 1000}"`));
  for (const kind of Object.values(AREA_BLOCK)) assert.ok(KINDS.includes(kind), `${kind} has an SDF`);
});

test("partners: production renders the section only with 4 or more approved logos; the guard agrees", () => {
  const logo = (i, approved = true) => ({ name: `Partner ${i}`, src: `/img/partner-${i}.svg`, approved });
  for (const [n, extra] of [[0, 0], [1, 0], [3, 2], [4, 1]]) {
    const logos = [...Array.from({ length: n }, (_, i) => logo(i)), ...Array.from({ length: extra }, (_, i) => logo(100 + i, false))];
    const prod = str(partners(withSite(ctx("vi", "home", true), { partnerLogos: logos })));
    const stage = str(partners(withSite(ctx("vi", "home", false), { partnerLogos: logos })));
    if (n < 4) {
      assert.equal(prod, "", `${n} approved: no section on production`);
      assert.match(stage, /data-partners-pending/, `${n} approved: staging shows the pending strip`);
      assert.ok(findBlockers(stage, { approved: n }).some((w) => /partners/.test(w)), `${n} approved: a leaked section fails the build`);
    } else {
      assert.match(prod, /class="partner-wall"/);
      assert.equal((prod.match(/<img /g) ?? []).length, n, "only approved logos hang");
      assert.doesNotMatch(prod, /data-partners-pending/);
      assert.deepEqual(findBlockers(prod, { approved: n }), []);
    }
  }
});

test("news: the featured story placeholder is staging-only and blocked on production", () => {
  const stage = str(news(ctx("vi", "home", false)));
  const prod = str(news(ctx("vi", "home", true)));
  assert.match(stage, /data-story-placeholder/);
  assert.match(stage, new RegExp(content.ui.namesChanged.vi.replace(/[.()]/g, "\\$&")), "the names-changed note travels with the story");
  assert.doesNotMatch(prod, /data-story-placeholder|feature__/);
  assert.match(prod, /news--solo/, "the list takes the full width without the story");
  assert.ok(findBlockers(stage).includes("featured story placeholder"));
  if (stillReady("story")) assert.match(stage, /feature__wall"><picture class="feature__still">[\s\S]*?loading="lazy"/, "the lit-wall still, lazy");
  else assert.doesNotMatch(stage, /\/posters\/story/, "no story still is referenced before it exists on disk");
  assert.doesNotMatch(prod, /\/posters\/story/);
});

test("news dates read per locale (VI 25/05/2026, EN 25 May 2026)", () => {
  const post = content.news.posts[0];
  assert.equal(text(str(datedPost(ctx("vi", "home", false), post))).slice(0, 10), "25/05/2026");
  assert.equal(text(str(datedPost(ctx("en", "home", false), post))).slice(0, 11), "25 May 2026");
});

test("help: four louver slats and exactly one sun button, on Ủng hộ", () => {
  for (const lang of ["vi", "en"]) {
    const h = str(help(ctx(lang, "home", false)));
    assert.equal((h.match(/class="louver[ "]/g) ?? []).length, 4);
    assert.equal((h.match(/btn--primary/g) ?? []).length, 1);
    assert.match(h, /louver--donate[\s\S]*?btn--primary/);
  }
});

test("programs: centers are door chips with names only; Little Rose never gets a link or a location", () => {
  const tt = content.work.programs.find((p) => p.id === "thrive-together");
  for (const lang of ["vi", "en"]) {
    const row = str(programRow(ctx(lang, "home", false), tt));
    const chips = row.match(/<ul class="door-chips">[\s\S]*?<\/ul>/)[0];
    assert.doesNotMatch(chips, /<a /, "chips are not links");
    assert.match(chips, /little-rose/);
    const lr = content.work.centers.find((x) => x.id === "little-rose");
    assert.ok(!("location" in lr), "Little Rose has no location field to render");
  }
});

test("our work sheet: gapless wall of four areas, figures hidden on production while unverified", () => {
  const stage = str(workSheet(ctx("vi", "home", false)));
  const prod = str(workSheet(ctx("vi", "home", true)));
  assert.equal((stage.match(/<li class="tile /g) ?? []).length, 4);
  assert.match(stage, /class="plaques"/);
  assert.doesNotMatch(prod, /class="plaques"|data-pending/, "fewer than 2 verified figures: no plaques at all");
  assert.match(prod, /id="work"/, "the corridor's skip link lands here");
});

test("footer: dusk on every page, a link to Facebook and never an embed", () => {
  const c = ctx("en", "contact", false);
  const page = str(layout(c, { title: "x", description: "x", body: "" }));
  const foot = page.slice(page.indexOf("<footer"));
  assert.match(foot, /footer-edge/);
  assert.match(foot, /foot-contact__sun/);
  assert.match(foot, new RegExp(`href="${content.site.facebook}"`));
  assert.doesNotMatch(foot, /<iframe|<embed|fb-page|instagram/i);
  if (stillReady("dusk")) assert.match(foot, /site-footer__still" aria-hidden="true"><picture class="site-footer__dusk">[\s\S]*?loading="lazy"/, "the dusk band is decorative and lazy");
  else assert.doesNotMatch(foot, /\/posters\/dusk/);
});
