// PLAN-M4 4.1-4.9: About, Impact and the Get Involved subpages. No founding year, no unverified number,
// no staff names or faces, no invented partners; every form labelled and erroring in the page language;
// the placeholders that wait for HCWA stay off production.
import { test } from "node:test";
import assert from "node:assert/strict";
import { str, esc } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { routes } from "../src/routes.mjs";
import { layout } from "../src/partials/layout.mjs";
import { pages } from "../src/pages/index.mjs";
import { findBlockers } from "../src/render.mjs";
import { headerReady } from "../src/partials/lit-header.mjs";

const LANGS = ["vi", "en"];
const body = (key, lang, prod = false) => str(pages[routes[key].page](ctx(lang, key, prod)).body);
const full = (key, lang, prod = false) => {
  const c = ctx(lang, key, prod);
  return str(layout(c, pages[routes[key].page](c)));
};
const NEW = ["about", "ourStory", "team", "partners", "policies", "impact", "volunteer", "partner", "inKind"];
// Visible text only (tags and attributes out), so a year in a URL or a class name does not count.
const text = (h) => h.replace(/<script[\s\S]*?<\/script>/g, "").replace(/<[^>]+>/g, " ");

test("every new page renders in VI and EN with one H1 and its own language", () => {
  for (const key of NEW) for (const lang of LANGS) {
    const h = full(key, lang);
    assert.equal((h.match(/<h1\b/g) ?? []).length, 1, `${key} ${lang}: one H1`);
    assert.match(h, new RegExp(`<html lang="${lang}"`));
  }
});

test("no founding year, no unverified number: every year is a token and production prints none", () => {
  for (const key of ["about", "ourStory", "impact", "team", "partners"]) for (const lang of LANGS) {
    for (const prod of [false, true]) {
      const t = text(body(key, lang, prod));
      assert.doesNotMatch(t, /\b(19[89]\d|20[0-2]\d)\b/, `${key} ${lang}${prod ? " PROD" : ""}: a year`);
      assert.doesNotMatch(t, /300[.,]000|\b30 năm|\b30 years/, `${key} ${lang}: a current-site figure`);
    }
  }
  // Our story: each step's year is a token on staging and left out in production.
  for (const lang of LANGS) {
    const steps = content.about.story.steps.filter((s) => s.id !== "today").length;
    const token = esc(ctx(lang, "ourStory", false).t("yearToken"));
    assert.equal(body("ourStory", lang).split(token).length - 1, steps);
    assert.ok(!body("ourStory", lang, true).includes(token));
  }
});

test("Our story: Little Rose's step says what the shelter is, never where", () => {
  for (const lang of LANGS) {
    const c = ctx(lang, "ourStory", false);
    const step = body("ourStory", lang).match(/<li class="step" data-step="little-rose">[\s\S]*?<\/li>/)[0];
    assert.doesNotMatch(step, /icon-pin|<svg class="icon[^"]*pin|\[phường\]|\[ward\]|Phường|District|Quận|Lâm Văn Bền/);
    assert.ok(!step.includes(esc(c.t("visitCenter"))));
    assert.match(step, new RegExp(`href="${routes.littleRose[lang]}"`));
  }
});

test("Team: no names, photos, initials or silhouettes; staging slots are tokens and blocked in production", () => {
  for (const lang of LANGS) {
    const h = body("team", lang);
    assert.equal((h.match(/data-team-pending/g) ?? []).length, content.about.team.slots);
    // The dusk band's still is the house's room at 18:00, the same on every page; nothing above it is a picture.
    assert.doesNotMatch(h.split('<section class="dusk"')[0], /<img|<picture|silhouette|avatar|initials/);
    const prod = body("team", lang, true);
    assert.doesNotMatch(prod, /data-team-pending|roster__slot/);
    assert.match(prod, new RegExp(`href="${routes.volunteer[lang]}"`));
    assert.match(prod, new RegExp(`href="${routes.contact[lang]}"`));
    assert.ok(findBlockers(full("team", lang)).some((w) => /team placeholder/.test(w)), "staging carries the team blocker");
    assert.ok(!findBlockers(full("team", lang, true)).some((w) => /team placeholder/.test(w)));
  }
});

test("Partners: no invented names; the pending strip on staging only; the one funder name is the program's own", () => {
  const known = new Set(content.work.programs.flatMap((p) => p.partners));
  for (const lang of LANGS) {
    const h = body("partners", lang);
    assert.match(h, /data-partners-pending/);
    assert.doesNotMatch(h, /\sdata-partners="/, "no logo wall below four approved logos");
    for (const name of h.matchAll(/class="funders__name">([^<]+)</g)) for (const n of name[1].split(", ")) assert.ok(known.has(n), n);
    const prod = body("partners", lang, true);
    assert.doesNotMatch(prod, /data-partners-pending|funders__name/);
    assert.doesNotMatch(body("partner", lang, true), /data-partners-pending/);
  }
});

test("Policies: three sections with ids in the page's language, in production too, no PDF link yet", () => {
  for (const lang of LANGS) for (const prod of [false, true]) {
    const h = body("policies", lang, prod);
    for (const s of content.about.policies.sections) assert.match(h, new RegExp(`<section class="policy container" id="${s.id[lang]}"`));
    assert.doesNotMatch(h, /href="[^"]+\.pdf"/);
  }
});

test("Impact: figures only through the verified rule, never counting up; the reports id in production", () => {
  for (const lang of LANGS) {
    const h = body("impact", lang);
    const pending = content.impact.filter((f) => f.scope === "organization" && !f.verified).length;
    assert.equal((h.match(/class="chip-pending" data-pending/g) ?? []).length, pending);
    assert.doesNotMatch(h, /data-count|countup|data-to=/i);
    assert.match(h, /data-story-placeholder/);
    const prod = body("impact", lang, true);
    assert.doesNotMatch(prod, /data-pending|class="counts|years__table|data-story-placeholder/, "production shows no unverified figure");
    assert.match(prod, new RegExp(`id="${routes.reports[lang].split("#")[1]}"`));
  }
});

test("Get Involved forms: every label, help and error in the page language, consent linked to privacy", () => {
  const forms = { volunteer: ["v-name", "v-email", "v-interest", "v-consent"], partner: ["p-name", "p-org", "p-email", "p-way", "p-message", "p-consent"] };
  for (const [key, ids] of Object.entries(forms)) for (const lang of LANGS) {
    const c = ctx(lang, key, false);
    const h = body(key, lang);
    for (const id of ids) assert.match(h, new RegExp(`id="${id}-error" data-error="[^"]+"`), `${key} ${lang} ${id}`);
    assert.ok(h.includes(esc(c.t("f_name"))) && h.includes(esc(c.t("f_send"))));
    assert.match(h, new RegExp(`href="${routes.privacy[lang].replace(/[#/]/g, "\\$&")}"`));
    // No string of the other language's form vocabulary.
    const other = ctx(lang === "vi" ? "en" : "vi", key, false);
    for (const k of ["f_name", "f_send", "e_email", "f_interest", "f_organization"]) assert.ok(!h.includes(esc(other.t(k))), `${key} ${lang}: ${k} in the wrong language`);
    assert.match(h, /data-form novalidate/);
  }
});

test("In-kind and the bakery: orders through the office, no photos, no place but the office", () => {
  const { site } = content;
  for (const lang of LANGS) {
    const h = body("inKind", lang);
    const id = routes.bakery[lang].split("#")[1];
    const bakery = h.match(new RegExp(`<section class="bakery container" id="${id}"[\\s\\S]*?</section>`))[0];
    assert.doesNotMatch(bakery, /<img|<picture/);
    assert.deepEqual([...bakery.matchAll(/href="tel:([^"]+)"/g)].map((m) => m[1]), [site.phone.tel], "the office phone only");
    assert.match(bakery, new RegExp(`href="${routes.littleRose[lang]}"`));
    assert.doesNotMatch(bakery, /\[phường\]|\[ward\]|Lâm Văn Bền|Tân Hưng/);
    assert.match(body("inKind", lang, true), new RegExp(`id="${id}"`));
  }
});

// Fix round 1: a page whose header still the walls stream has not shipped opens on the sun band (About in
// morning lime, Impact in midday sun), never on the lit header's flat shade fill, and swaps back by itself.
test("Who we are and Impact: the lit header once their still ships, the sun band until then", () => {
  for (const [key, still, time] of [["about", "header-about", "morning"], ["impact", "header-impact", "sun"]]) {
    for (const lang of LANGS) {
      const c = ctx(lang, key, false);
      const out = pages[routes[key].page](c);
      const h = str(out.body);
      if (headerReady(still)) {
        assert.match(h, new RegExp(`data-still="${still}"`));
        assert.equal(out.lit, true);
      } else {
        assert.doesNotMatch(h, /class="lit-head\b|data-still=/, `${key} ${lang}: no shade fill`);
        assert.match(h, new RegExp(`class="sun-head sun-head--page sun-head--${time}"`));
        assert.equal(out.lit, false, "no sun-pending head script for a page without a still");
      }
    }
  }
});

test("Our story: the order of the steps is flagged for HCWA; Vietnamese names in English titles carry lang", () => {
  for (const lang of LANGS) {
    assert.match(body("ourStory", lang), /<p class="steps__order container" data-draft>\[/);
    assert.doesNotMatch(body("ourStory", lang, true), /steps__order/, "production leaves the note out");
  }
  assert.match(body("ourStory", "en"), /The <span lang="vi">Cầu Muối<\/span> club/);
  assert.doesNotMatch(body("ourStory", "vi"), /<span lang="vi">Cầu Muối/, "no lang span on the Vietnamese page");
});
