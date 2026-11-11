// Get Involved, Donate, Contact, 404, components and the View Transition types (PLAN P4-P8, D5.5): the
// payment panel holds placeholders only, Little Rose never gets an address, forms speak one language and
// submit nowhere, and the type script runs in <head> before first render.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { str, esc } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { getInvolved, donate, contact, notFound } from "../src/pages/involve.mjs";
import { components } from "../src/pages/components.mjs";
import { help } from "../src/pages/home/help.mjs";
import { layout } from "../src/partials/layout.mjs";
import { findBlockers } from "../src/render.mjs";

const LANGS = ["vi", "en"];
const body = (fn, lang, key, prod = false) => str(fn(ctx(lang, key, prod)).body);
const full = (fn, lang, key, prod = false) => { const c = ctx(lang, key, prod); return str(layout(c, fn(c))); };
const motionCss = readFileSync(new URL("../src/styles/motion.css", import.meta.url), "utf8");
const pagesCss = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8");

test("donate: every payment value is the pending placeholder; copy buttons disabled; the QR frame is empty", () => {
  for (const lang of LANGS) {
    const h = body(donate, lang, "donate");
    const slip = h.match(/<section class="slip"[\s\S]*?<section class="tell"/)[0];
    const fields = content.involve.bankFields.length + content.involve.abroadFields.length;
    assert.equal((slip.match(/<span class="pending-value" data-donation-placeholder>/g) ?? []).length, fields, "one placeholder per bank field");
    assert.doesNotMatch(slip.replace(/<[^>]+>/g, " "), /\d{5,}/, "no account-like number anywhere in the slip");
    assert.doesNotMatch(slip, /<img|<svg(?![^>]*class="icon)/, "no imagery near payment details (icons only)");
    const copies = slip.match(/<button class="copy-btn"[^>]*>/g) ?? [];
    assert.ok(copies.length > 0 && copies.every((b) => /\sdisabled/.test(b) && /aria-describedby="pay-note"/.test(b) && /data-copy=""/.test(b)), "copy buttons disabled, empty, described by the pending note");
    assert.match(slip, /class="slip__qr"[^>]*data-donation-placeholder/);
    assert.match(slip, /role="tablist"[^>]*hidden/, "without JS the panels stack and the tab list stays hidden");
    assert.ok(!/data-pending/.test(body(donate, lang, "donate", true)), "gift amounts are staging only");
    assert.ok(findBlockers(full(donate, lang, "donate", true)).includes("donation placeholder"), "PROD still refuses the page");
  }
});

test("forms submit nowhere and speak one language: labels, errors and states from ui.json in the page language", () => {
  for (const [fn, key] of [[contact, "contact"], [donate, "donate"]]) {
    for (const lang of LANGS) {
      const h = body(fn, lang, key);
      const other = lang === "vi" ? "en" : "vi";
      for (const form of h.match(/<form class="form[\s\S]*?<\/form>/g)) {
        assert.doesNotMatch(form.match(/<form[^>]*>/)[0], /\saction=|\smethod=/, "no live submission");
        assert.match(form, /type="checkbox"[^>]*required/, "a consent line on every form");
        for (const k of ["e_name", "e_email", "e_consent"]) {
          assert.ok(form.includes(`data-error="${content.ui[k][lang]}"`), `${key} ${lang}: ${k} in the page language`);
          assert.ok(!form.includes(content.ui[k][other]), `${key} ${lang}: no ${other} ${k}`);
        }
        assert.ok(form.includes(content.ui.f_send[lang]) && form.includes(content.ui.f_sendAnother[lang]));
      }
    }
  }
  const h = body(contact, "en", "contact");
  assert.equal((h.match(/<input type="radio" [^>]*name="topic"/g) ?? []).length, 5, "five topics as one radio group");
  assert.match(h, /<fieldset class="field field--choices" data-field aria-describedby="topic-error">/);
});

test("contact: Little Rose has no address or direct line, only the office and the privacy note", () => {
  const lr = content.work.centers.find((x) => x.id === "little-rose");
  for (const lang of LANGS) {
    const h = body(contact, lang, "contact");
    const unit = h.match(/<li class="unit" data-unit="little-rose"[\s\S]*?<\/li>/)[0];
    assert.ok(!unit.includes(esc(content.involve.contactPage.centerPending[lang])), "no address slot for Little Rose");
    assert.ok(unit.includes(esc(content.work.littleRose.privacyNote[lang])), "the privacy note says why");
    assert.ok(unit.includes(esc(content.involve.contactPage.littleRose[lang])), "everything goes through the office");
    assert.doesNotMatch(unit, /<a\s/, "no link to a visit");
    if (lr.location?.[lang]) assert.ok(!unit.includes(esc(lr.location[lang])), "no location");
    assert.doesNotMatch(h, /<iframe|maps\/embed/, "map is a link, never an embed");
  }
});

test("get involved: four louvers as h2 slats, one sun button; home shares the same slats with no rules", () => {
  for (const lang of LANGS) {
    const h = body(getInvolved, lang, "getInvolved");
    assert.equal((h.match(/<li class="louver/g) ?? []).length, content.involve.ways.length);
    assert.equal((h.match(/<h2 class="louver__title">/g) ?? []).length, content.involve.ways.length);
    // M6 fix: the header's ways bar carries each way's button (Ủng hộ the one sun button); the slats below carry the
    // names and the detail, every title a link, and repeat no button.
    const louverList = h.match(/<ul class="louvers[\s\S]*?<\/ul>/)[0];
    assert.equal((louverList.match(/class="btn/g) ?? []).length, 0, "no button repeated in the slats");
    assert.equal((louverList.match(/<h2 class="louver__title"><a /g) ?? []).length, content.involve.ways.length, "every slat title is a link");
    assert.equal((h.match(/btn--primary/g) ?? []).length, 1, "the ways bar's Ủng hộ, nothing else");
    assert.equal((h.match(/<details class="ask">/g) ?? []).length, content.involve.faq.length);
    const home = str(help(ctx(lang, "home", false)));
    assert.equal((home.match(/<h3 class="louver__title">/g) ?? []).length, content.involve.ways.length);
  }
  const louverCss = pagesCss.match(/\.louvers? \{[^}]*\}|\.louver \{[\s\S]*?\n\}/g).join("\n");
  assert.doesNotMatch(louverCss, /border/, "slats are separated by light, never a rule (D0.5)");
});

test("404: the missing brick, the search and three ways on, nothing animated", () => {
  for (const lang of LANGS) {
    const h = body(notFound, lang, "notFound");
    assert.match(h, /<h1 class="gap__title"/);
    assert.match(h, /class="gap__still" aria-hidden="true"/);
    assert.match(h, /role="search"/);
    assert.equal((h.match(/<ul class="gap__links">[\s\S]*?<\/ul>/)[0].match(/<li>/g) ?? []).length, 3);
    assert.doesNotMatch(h, /data-reveal/, "static page");
  }
});

test("components: every measured contrast pair passes; strings come from kit.json in both languages", () => {
  for (const lang of LANGS) {
    const h = body(components, lang, "components");
    assert.doesNotMatch(h, /pair--fail/);
    assert.ok((h.match(/<tr class="pair/g) ?? []).length > 60);
    assert.doesNotMatch(h, /class="ph\b|center-card|story-card/, "no Phase 1 kit on the kit page");
  }
});

test("view transitions: types chosen before first render, back and lang styled, off when paused", () => {
  const page = full(contact, "vi", "contact");
  const head = page.match(/<head>[\s\S]*?<\/head>/)[0];
  const inline = head.indexOf("pagereveal"), module = head.indexOf('<script type="module"'), motion = head.indexOf("data-motion");
  assert.ok(inline > 0 && inline < module, "a classic script in <head>, before the module");
  assert.ok(motion > 0 && motion < inline, "after head-inline.js has set data-motion");
  assert.match(head, /vt\.types\.add\("lang"\)/);
  assert.match(head, /vt\.types\.add\("back"\)/);
  assert.match(head, /skipTransition\(\)/);
  assert.equal((page.match(/data-lang-switch/g) ?? []).length, 3, "header, menu sheet and footer switches mark the lang type");
  assert.match(motionCss, /:active-view-transition-type\(back\)::view-transition-old\(root\)/);
  assert.match(motionCss, /:active-view-transition-type\(lang\)::view-transition-new\(root\)[^}]*180ms/);
  assert.match(motionCss, /@media \(prefers-reduced-motion: no-preference\) \{\s*@view-transition/);
});
