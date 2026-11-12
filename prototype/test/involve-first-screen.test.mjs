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
    const art = h.match(/<div class="gi-stage" aria-hidden="true">[\s\S]*?<span class="gi-stripes"><\/span>\s*<\/div>/)?.[0];
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
  // The leaves are open at rest (112°) and already open (62°) at the first frame of the stage.
  assert.match(gi, /\.gi-leaf \{ --a: 112deg;/);
  assert.match(gi, /--a: calc\(62deg \+ 50deg \* var\(--s3d-cam\)\)/);
});
