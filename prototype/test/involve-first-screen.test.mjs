// M6 "Cửa mở": Get Involved's first screen says come in: a welcoming H1 from content, the sentence naming the four
// ways and one link down to them (M7 fix round: the ways, each with its own button, are listed once below), the
// window drawn in SVG (no photo, no person, no lettering), and no shade or grey left on the page.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { str, esc } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { getInvolved } from "../src/pages/involve.mjs";

const LANGS = ["vi", "en"];
const ROUTE = { donate: "donate", volunteer: "volunteer", partner: "partner", "in-kind": "inKind" };
const render = (lang, prod = false) => { const c = ctx(lang, "getInvolved", prod); return { c, page: getInvolved(c), body: str(getInvolved(c).body) }; };
const head = (body) => body.match(/<header class="gi-head">[\s\S]*?<\/header>/)[0];

for (const lang of LANGS) {
  // M7 fix round (Maxwell 12 Nov: the four ways were listed twice in a row): the header names the ways in its intro and
  // links down to them; the ways themselves, each with its one button, are listed once under it (involve-pages.test).
  test(`${lang}: the header welcomes, names the four ways and links down to them`, () => {
    const { c, body } = render(lang);
    const h = head(body);
    assert.doesNotMatch(h, /<nav class="ways"|class="way\b|class="btn/, "no tiles or buttons in the header");
    assert.ok(h.includes(esc(content.involve.intro[lang])), "the intro names the ways");
    assert.match(h, new RegExp(`<a class="text-link" href="#ways"><span>${esc(content.involve.seeWays[lang])}</span>`));
    assert.match(body, /<section class="ways container" id="ways"/, "the link lands on the ways");
    assert.equal(content.involve.ways.length, 4);
    for (const w of content.involve.ways) assert.ok(body.includes(`href="${c.url(ROUTE[w.id])}"`), `${w.id} links to its page`);
    // The H1 is the welcome, marked as draft wording; the page title stays the section's name.
    assert.match(h, new RegExp(`<h1 class="gi-head__title" data-draft>${esc(content.involve.welcomeTitle[lang]).replace(/ /g, "(?:&nbsp;| |\\u00a0)")}</h1>`));
    assert.equal(render(lang).page.title, c.t("getInvolved"));
  });

  test(`${lang}: the window is drawn, never a photo, a person or lettering`, () => {
    const h = head(render(lang).body);
    assert.doesNotMatch(h, /<img|<picture|\/img\/|photo|\/posters\//i);
    const art = h.match(/<div class="gi-stage" aria-hidden="true">[\s\S]*?<span class="gi-ledge"><\/span>\s*<\/div>\s*<\/div>\s*<\/div>/)?.[0];
    assert.ok(art, "the window stage is decorative");
    assert.doesNotMatch(art, /<text|<tspan|<image|<foreignObject|person|child|face|sign|plate/i);
    assert.doesNotMatch(art, /#[0-9a-f]{3,8}\b(?![\w-])|rgba?\(/i, "tokens only");
  });
}

test("no shade or grey fog on the page: the header is warm light, the louver faces are patches", () => {
  const css = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8").replace(/\/\*[\s\S]*?\*\//g, "");
  const gi = css.slice(css.indexOf(".gi-head {"), css.indexOf(".faq {"));
  assert.ok(gi.length > 2000);
  assert.doesNotMatch(gi, /shade-100|shade-300|world-wall-shade/);
  assert.doesNotMatch(css, /\.page-involve \.lit-head/, "the old shade header is gone");
  const face = css.match(/\.louver__face \{[^}]*\}/)[0];
  assert.match(face, /radial-gradient\(ellipse/, "a feathered patch, never a full-width band");
  // The leaves are open at rest (--t 1: 102°) and already open (62°) at the first frame of the stage (--t follows the camera).
  assert.match(gi, /\.gi-stage \{ --t: 1; --a: calc\(62deg \+ 40deg \* var\(--t\)\);/);
  assert.match(gi, /html\.stages\[data-motion="on"\] \.gi-stage \{ --t: var\(--s3d-cam\); \}/);
});

// M9 (Maxwell 13 Nov: "make the animation on the right more realistic and fix it up"): a window in a thick wall, built
// in one perspective, the leaves hinged on the jambs' outer edges inside the wall's thickness.
test("the window is built: reveal, sill, splayed jambs, leaves hinged on the jambs' outer edges, never past the room face", () => {
  const css = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8").replace(/\/\*[\s\S]*?\*\//g, "");
  const rule = (sel) => css.match(new RegExp(`(?:^|\\n)${sel.replace(/[.[\]()-]/g, "\\$&")} \\{([^}]*)\\}`))?.[1] ?? "";
  const num = (decl, name) => parseFloat(decl.match(new RegExp(`--${name}: ([\\d.]+)cqw`))[1]);
  const win = rule(".gi-window");
  assert.match(win, /container-type: size/);
  const [wo, side, lw, wd] = ["wo", "side", "lw", "wd"].map((n) => num(win, n));
  assert.ok(Math.abs(wo + 2 * side - 100) < 0.01, "the opening sits centred in the room-face outline");
  assert.ok(Math.abs(2 * lw - wo) < 0.01, "two leaves close the opening");
  // Each leaf: hinged at the outer edge of its jamb (the far end of the reveal), the opening's full height.
  for (const [side_, sign] of [["l", "-1 \\* "], ["r", ""]]) {
    const leaf = rule(`.gi-leaf--${side_}`);
    assert.match(leaf, new RegExp(`translateZ\\(calc\\(-1 \\* var\\(--wd\\)\\)\\) rotateY\\(${sign.replace("-1 \\* ", "calc\\(-1 \\* ")}var\\(--a\\)`));
    assert.match(leaf, side_ === "l" ? /left: var\(--side\); transform-origin: 0 50%/ : /right: var\(--side\); transform-origin: 100% 50%/);
  }
  assert.match(rule(".gi-leaf"), /top: 0; height: 100%; width: var\(--lw\)/);
  // Through the whole swing the free edge stays behind the room face (z < 0): it never passes the outline or the sill.
  const [a0, da] = rule(".gi-stage").match(/--a: calc\((\d+)deg \+ (\d+)deg \* var\(--t\)\)/).slice(1).map(Number);
  for (let a = a0; a <= a0 + da; a += 1) assert.ok(-wd + lw * Math.sin((a * Math.PI) / 180) < 0, `${a}°`);
  // The jambs are splayed so the leaf at rest stands clear of its jamb, with a strip of the jamb (and the leaf's
  // shadow on it) showing: the wall's thickness reads in the rest pose too (M9 fix round: 102°, 10.76° off).
  const splay = 90 - parseFloat(rule(".gi-jamb--l").match(/rotateY\(([\d.]+)deg\)/)[1]);
  assert.ok(Math.abs(Math.tan((splay * Math.PI) / 180) * wd - side) < 0.2, "the jamb runs from the opening to the room face");
  const rest = a0 + da;
  assert.ok(rest - 90 < splay - 6, `the leaf at rest (${rest}°) stands well clear of its jamb (${90 + splay}°)`);
  // Its free edge stays inside the opening's side of the jamb (never crosses it) at every angle.
  for (let a = a0; a <= rest; a += 1) {
    const r = (a * Math.PI) / 180, fx = side + lw * Math.cos(r), fz = -wd + lw * Math.sin(r);
    assert.ok(fx > side * (-fz / wd) - 0.01, `${a}°: free edge clear of the jamb`);
  }
});

test("the window is cheap to style and lay out: under 100 elements (M8's had about 340)", () => {
  const h = head(render("vi").body);
  const art = h.match(/<div class="gi-stage" aria-hidden="true">[\s\S]*?<span class="gi-ledge"><\/span>\s*<\/div>\s*<\/div>\s*<\/div>/)[0];
  const n = (art.match(/<[a-zA-Z]/g) ?? []).length;
  assert.ok(n < 100, `${n} elements`);
});

// M9 fix round: the street was rastered on every scrolled frame. Chromium re-invalidates any SVG shape painted with a
// gradient (directly or through a pattern) each frame the stage moves, so the drawing carries no SVG gradient, and no
// turbulence or blur filter (they made the reveal's raster a 400 ms GPU task). Its sky and haze are CSS.
test("the window's SVG has no gradients or filters (rastered once, not every scrolled frame)", () => {
  const art = head(render("vi").body).match(/<div class="gi-stage"[\s\S]*?<span class="gi-ledge">/)[0];
  assert.doesNotMatch(art, /<(linear|radial)Gradient|<filter|filter="url|<fe[A-Z]/);
  assert.equal((art.match(/<svg /g) ?? []).length, 2, "the far side and the tree: two depths");
});

// M9 fix round (critique): no bars of light and no white streaks under the window or on its jambs; the light the
// sill throws back is a warm glow.
test("no barred light: the pool under the window is gone, the jamb and sill shadows are soft", () => {
  const css = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8").replace(/\/\*[\s\S]*?\*\//g, "");
  assert.doesNotMatch(css, /gi-stripes/);
  for (const sel of [".gi-jamb--r::before", ".gi-jamb--l::before", ".gi-sill::before", ".gi-ledge::before", ".gi-ledge::after"]) {
    const r = css.match(new RegExp(`\\n${sel.replace(/[.\-:]/g, "\\$&")} \\{([^}]*)\\}`))?.[1] ?? "";
    assert.ok(r, sel);
    assert.doesNotMatch(r, /repeating-/, `${sel}: no bars`);
    assert.doesNotMatch(r, /lime-50|white|#fff/i, `${sel}: no white`);
  }
});

// M10 (Maxwell 14 Nov: "keep the blue-green, make it bigger on desktop"): only the desktop box's size changed. The
// drawing has no absolute length (every plane, the sill and the nosing are in fractions of the box), so the swing
// checked above holds at any size; the desktop box fills the stage (the right half less the page's margin) up to a cap
// and a hang below the header; phones keep M9's rules.
test("the window's size is free: drawn in fractions of the box, bigger on desktop, phones untouched", () => {
  const css = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8").replace(/\/\*[\s\S]*?\*\//g, "");
  const from = css.indexOf(".gi-stage { --t");
  const drawing = css.slice(from, css.indexOf("@media (min-width: 1024px)", from));
  for (const [, sel, body] of drawing.matchAll(/(\.gi-[^{]*)\{([^}]*)\}/g))
    assert.doesNotMatch(body, /\d(px|vw|vh|svh|dvh|rem|em)\b/, `${sel.trim()}: only box fractions`);
  // Phones (< 1024): M9's box and stage.
  assert.match(css, /\n\.gi-window \{ position: absolute; top: 3%; bottom: 15%; left: 50%; aspect-ratio: 1\.04; translate: -50% 0; container-type: size;/);
  assert.match(css, /@media \(max-width: 1023\.98px\) \{\n  \.gi-stage \{ height: clamp\(220px, 36svh, 320px\); \}/);
  // Desktop: the stage and the box's width.
  const desk = css.slice(css.indexOf("@media (min-width: 1024px)", from));
  const stage = desk.match(/\.gi-stage \{ --gi-r: max\(([\d.]+)vw, min\(([\d.]+)vw, 50vw - (\d+)px, (\d+)px\)\); --gi-x: max\(var\(--gi-r\), \(100% - var\(--container\)\) \/ 2 - (\d) \* var\(--gutter\)\); --gi-hang: (\d+)px;[^}]*right: var\(--gi-x\);[^}]*width: calc\(50% - var\(--gi-x\)\); container-type: size; \}/);
  assert.ok(stage, "the stage is the right half less the margin, at most two gutters past the column, a size container");
  const [rLo, rVw, rK, rMax, gutters, hang] = stage.slice(1).map(Number);
  const win = desk.match(/\.gi-window \{ top: 4%; bottom: auto; left: 50%; aspect-ratio: 1\.22; width: min\(([\d.]+)cqw, calc\(\(96cqh \+ var\(--gi-hang\)\) \/ ([\d.]+)\), (\d+)px\); \}/);
  assert.ok(win, "the box: as wide as the stage allows, a hang below the header, a cap");
  const [fill, perW, cap] = win.slice(1).map(Number);
  const DRAWN = 1.043; // the sill runs 2.15% past each side of the box
  assert.ok(fill * DRAWN <= 98.01, "with the sill it stays inside the stage: never into the words' column or off the page");
  assert.ok(Math.abs(perW - (1 / 1.22 + 0.034)) < 0.001, "box height plus the nosing (3.4cqw) per unit of width");
  assert.match(desk, /\.gi-head \{ z-index: 1; \}/, "the hang paints over the day's background");
  assert.match(desk, /\.ways \{ position: relative; z-index: 2; \}/, "and under the ways' words");
  // M10 fix round: from 1280 the header grows and the window hangs less by the same amount (same box, ways lower).
  const wide = css.match(/@media \(min-width: 1280px\) \{([\s\S]*?)\n\}/)?.[1] ?? "";
  const grow = wide.match(/\.gi-head \{ min-height: min\(calc\(60svh - var\(--hdr\) \+ (\d+)px\), (\d+)px\); \}/);
  const pad = wide.match(/\.gi-head__text \{ padding-bottom: (\d+)px; \}/);
  const hang2 = +(wide.match(/\.gi-stage \{ --gi-hang: (\d+)px; \}/)?.[1] ?? NaN);
  assert.ok(grow && pad, "from 1280 the header's height and its words' foot both grow");
  const g = +grow[1];
  assert.ok(+grow[2] === 520 + g && +pad[1] === 40 + g, "by the same amount, so the header is M9's plus that at every height");
  assert.ok(g >= 36 && hang - hang2 === g, "and the window hangs that much less: its foot stays put, the ways move down");
  // The header heights M9 (and M10 below 1280) give at each width, VI (qa/m10/tools/geo.cjs): the box at each,
  // against M9's (92% of a 50vw stage, 84% of the header's height tall, aspect 1.22).
  const gutter = (vw) => Math.min(48, Math.max(16, 0.04 * vw));
  const place = (vw, head) => {
    const r = Math.max((rLo * vw) / 100, Math.min((rVw * vw) / 100, vw / 2 - rK, rMax));
    const x = Math.max(r, (vw - 1320) / 2 - gutters * gutter(vw));
    const sw = vw / 2 - x, h = vw >= 1280 ? head + g : head, hg = vw >= 1280 ? hang2 : hang;
    const b = Math.min((fill * sw) / 100, (0.96 * h + hg) / perW, cap);
    return { b, x, sw, right: vw - x - (sw - b * DRAWN) / 2 };
  };
  const box = (vw, head) => place(vw, head).b;
  const m9 = (vw, head) => Math.min(0.46 * vw, 0.84 * 1.22 * head);
  const cases = [[1024, 403], [1024, 520], [1100, 520], [1150, 520], [1180, 520], [1200, 520], [1280, 446], [1280, 520], [1366, 429], [1440, 460], [1680, 520], [1920, 520], [2560, 520]];
  for (const [vw, head] of cases) {
    const { b, sw, right } = place(vw, head);
    assert.ok(b <= cap && b * DRAWN <= sw + 0.01, `${vw}x${head}: inside the stage, so inside the right half`);
    assert.ok(b >= m9(vw, head) - 0.5, `${vw}x${head}: never smaller than M9's (${b.toFixed(1)} vs ${m9(vw, head).toFixed(1)})`);
    const col = Math.min(vw - gutter(vw), (vw + 1320) / 2);
    assert.ok(right - col <= gutters * gutter(vw), `${vw}: at most ${gutters} gutters past the content column`);
  }
  assert.ok(box(1440, 460) * DRAWN >= 1.35 * 490, "1440: at least 1.35x M9's window with its sill");
  assert.ok(box(1920, 520) >= box(1440, 460) && box(2560, 520) <= cap, "it grows to 1920 and stops there");
  assert.ok(Math.abs(box(1920, 520) - box(2560, 520)) < 0.5 && place(2560, 520).right < 2560 - 400, "on wide screens it keeps to the column, not the page's edge");
});
