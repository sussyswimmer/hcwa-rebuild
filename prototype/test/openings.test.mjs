// The M4 openings (PLAN-M4 section 5) and the News hub (4.13): Contact's "through the screen to the street"
// and News's "notices on the wall". What must hold in the HTML and CSS whatever the motion does: the H1
// never sits on a moving layer, every word is HTML, the stage's tracks agree in CSS and JS, the motion
// driver stays small and never imports the GSAP chunk, and the news cards are the real posts, once each,
// linking out.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { gzipSync } from "node:zlib";
import { str, esc } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { routes, pageRoutes } from "../src/routes.mjs";
import { pages } from "../src/pages/index.mjs";
import { contact, notFound } from "../src/pages/involve.mjs";
import { news } from "../src/pages/news.mjs";
import { layout } from "../src/partials/layout.mjs";
import { TRACKS, litStageVars } from "../src/partials/scroll3d.mjs";
import { cropsOf } from "../src/world/still-shots.js";
import { HEADER_TEXT } from "../src/lib/posters.mjs";
import { findBlockers } from "../src/render.mjs";

const LANGS = ["vi", "en"];
const read = (p) => readFileSync(new URL(p, import.meta.url), "utf8");
const css = read("../src/styles/openings.css");
const driver = read("../src/motion/scroll3d.js");
const main = read("../src/main.js");
const body = (fn, lang, key, prod = false) => str(fn(ctx(lang, key, prod)).body);
const full = (fn, lang, key, prod = false) => { const c = ctx(lang, key, prod); return str(layout(c, fn(c))); };

test("routes: the news hub is built in both languages and renders through pages/news.mjs", () => {
  assert.equal(routes.news.page, "news");
  assert.equal(pages.news, news);
  assert.ok(pageRoutes.some(([k]) => k === "news"));
  for (const lang of LANGS) assert.ok(full(news, lang, "news").includes(`<html lang="${lang}"`));
});

test("both openings are stages: one data-scroll3d wrapper, the H1 outside every moving layer", () => {
  for (const [fn, key] of [[contact, "contact"], [news, "news"]]) {
    for (const lang of LANGS) {
      const h = body(fn, lang, key);
      assert.equal((h.match(/data-scroll3d="/g) ?? []).length, 1, `${key} ${lang}: one stage`);
      assert.equal((h.match(/<h1[\s>]/g) ?? []).length, 1, `${key} ${lang}: one H1`);
      // The moving layers are the still (lit-head__media), the wall plane and nothing that holds words.
      for (const layer of [/<div class="lit-head__media"[\s\S]*?<\/div>\s*<div class="container lit-head__text">/, /<div class="newswall__bg"[\s\S]*?<\/div><\/div>/]) {
        const m = h.match(layer);
        if (m) assert.doesNotMatch(m[0], /<h1|<p[\s>]|<a\s/, `${key} ${lang}: no words inside a moving layer`);
      }
    }
  }
});

test("the openings' first screen links past them: Contact to the form, News to the list", () => {
  for (const lang of LANGS) {
    const c = body(contact, lang, "contact");
    assert.match(c, /class="s3d-skip text-link" href="#form-title"/);
    assert.match(c, /id="form-title"/);
    assert.ok(c.includes(esc(content.involve.contactPage.skipToForm[lang])));
    const n = body(news, lang, "news");
    assert.match(n, /class="s3d-skip text-link" href="#news-list"/);
    assert.match(n, /id="news-list"/);
  }
});

test("contact stage geometry comes from the still's own data, never pixels", () => {
  const v = litStageVars("header-contact");
  const desk = cropsOf("header-contact").find((c) => c.name === "desk");
  assert.equal(Number(v["s3d-aspect"]), Number(desk.aspect.toFixed(4)));
  assert.ok(Number(v["s3d-subject"]) > HEADER_TEXT.x + HEADER_TEXT.w, "the subject starts right of the text column");
  for (const lang of LANGS) assert.match(body(contact, lang, "contact"), /style="--s3d-aspect:3\.0000;--s3d-subject:0\.\d+;--s3d-ox:0\.\d+"/);
  const contactCss = css.slice(css.indexOf("Contact: through the screen"), css.indexOf("News: notices on the wall"));
  assert.doesNotMatch(contactCss.replace(/\/\*[\s\S]*?\*\//g, ""), /object-position:[^;]*\dpx/, "no pixel placement of the still");
});

test("the tracks agree: scroll3d.mjs TRACKS = the CSS keyframes = the JS fallback", () => {
  const js = Object.fromEntries([...driver.matchAll(/\["(\w+)", ([\d.]+), ([\d.]+), \[([^\]]+)\]\]/g)].map(([, n, a, b, e]) => [n, { from: +a, to: +b, ease: e.split(",").map(Number) }]));
  assert.deepEqual(js, TRACKS, "motion/scroll3d.js mirrors TRACKS");
  for (const [name, t] of Object.entries(TRACKS)) {
    const kf = css.match(new RegExp(`@keyframes s3d-${name} \\{([^\\n]*)\\}\\s*$`, "m"))?.[1];
    assert.ok(kf, `keyframes s3d-${name}`);
    assert.ok(kf.includes(`cubic-bezier(${t.ease.join(", ")})`), `s3d-${name} eases with TRACKS`);
    assert.ok(kf.includes(`${Math.round(t.to * 100)}%, 100%`), `s3d-${name} holds from ${t.to}`);
    if (t.from > 0) assert.ok(kf.includes(`0%, ${Math.round(t.from * 100)}%`), `s3d-${name} waits until ${t.from}`);
  }
});

test("motion stays native and light: no GSAP or Lenis on these pages, the driver under 2 KB gzip", () => {
  assert.doesNotMatch(driver, /import\s/, "the driver imports nothing (never motion/index.js)");
  assert.doesNotMatch(driver, /preventDefault|wheel|scrollTo|snap/i, "never takes the scroll");
  assert.match(driver, /passive: true/);
  assert.match(driver, /IntersectionObserver/);
  assert.ok(gzipSync(driver).length < 2048, `driver ${gzipSync(driver).length} B gzip`);
  assert.match(main, /import "\.\/styles\/openings\.css";/);
  assert.ok(main.indexOf('import "./styles/openings.css"') > main.indexOf('import "./styles/pages.css"'), "openings.css after pages.css");
  for (const [fn, key] of [[contact, "contact"], [news, "news"]]) for (const lang of LANGS) assert.doesNotMatch(body(fn, lang, key), /data-world|data-stage(?!r)/, `${key}: nothing asks main.js for the GSAP chunk`);
});

test("openings.css: only transform and opacity move, the H1 never animates, blur at most 8 px, the stage only with motion on", () => {
  const plain = css.replace(/\/\*[\s\S]*?\*\//g, "");
  for (const [, name, kf] of plain.matchAll(/@keyframes ([\w-]+) \{([\s\S]*?\})\s*\}/g)) {
    const props = [...kf.matchAll(/([\w-]+)\s*:/g)].map((m) => m[1]).filter((p) => p !== "animation-timing-function");
    assert.ok(props.every((p) => ["transform", "opacity"].includes(p) || p.startsWith("--s3d-")), `${name} animates only transform, opacity or a track (${props})`);
  }
  for (const [, sel] of plain.matchAll(/([^{}]+)\{[^}]*\banimation\b[^}]*\}/g)) assert.doesNotMatch(sel, /h1|title/, `no animation on a title: ${sel.trim()}`);
  for (const [, px] of plain.matchAll(/blur\((\d+)px\)/g)) assert.ok(Number(px) <= 8, `blur ${px}px`);
  for (const [, sel] of plain.matchAll(/([^{}]+)\{[^}]*position:\s*sticky[^}]*\}/g)) assert.match(sel, /html\.stages\[data-motion="on"\]/, `pinned only on the stage: ${sel.trim()}`);
  assert.doesNotMatch(plain, /\bfilter:\s*blur\((?:9|[1-9]\d)/);
  assert.doesNotMatch(plain, /text-transform:\s*uppercase/, "no uppercase (Vietnamese diacritics)");
});

test("news: every real post once, newest first, linking to the live post with the external note", () => {
  const posts = [...content.news.posts].sort((a, b) => b.published.localeCompare(a.published));
  for (const lang of LANGS) {
    const h = body(news, lang, "news");
    const cards = h.match(/<li class="notice"[\s\S]*?<\/li>/g);
    assert.equal(cards.length, posts.length);
    posts.forEach((p, i) => {
      assert.ok(cards[i].includes(`href="${esc(p.url[lang])}"`), `${p.published}: links to the live post`);
      assert.ok(cards[i].includes(`datetime="${p.published}"`), "the real date");
      assert.ok(cards[i].includes(esc(content.ui.externalPost[lang])), "says it opens hcwa.org.vn");
      assert.ok(cards[i].includes(`data-areas="${p.category}"`));
      assert.equal(h.split(`href="${esc(p.url[lang])}"`).length - 1, 1, "one link per post on the page");
    });
    assert.doesNotMatch(cards.join(""), /<img|<picture|<iframe|<video/, "no pictures or embeds in the notices");
    assert.doesNotMatch(h, /<iframe|instagram|fb-page|twitter-timeline|tiktok/i, "no live social feed or embed");
    assert.doesNotMatch(h, new RegExp(`href="${routes.news[lang]}[a-z]`), "no category or article URLs that are not built");
  }
});

test("news: category chips filter in place, with an empty note the build already knows", () => {
  const empty = ["news", "story", "event"].filter((k) => !content.news.posts.some((p) => p.category === k));
  for (const lang of LANGS) {
    const h = body(news, lang, "news");
    assert.match(h, /<section class="newswall"[^>]*data-hub>/);
    for (const k of ["news", "story", "event"]) {
      assert.match(h, new RegExp(`<span class="filters__anchor" id="f-${k}"></span>`));
      assert.match(h, new RegExp(`<a class="chip" href="\\?area=${k}#f-${k}" data-area="${k}">${esc(content.ui["cat_" + k][lang])}</a>`));
      assert.match(css, new RegExp(`\\.newswall:not\\(\\.js-filters\\):has\\(#f-${k}:target\\) \\.notice:not\\(\\[data-areas~="${k}"\\]\\)`), `no-JS filter for ${k}`);
    }
    assert.match(h, new RegExp(`data-filter-empty data-show-for="${empty.join(" ")}" hidden>${esc(content.news.hub.empty[lang])}`));
  }
});

test("news: the featured story waits for HCWA (staging only, guarded); PROD has no placeholder", () => {
  for (const lang of LANGS) {
    assert.match(body(news, lang, "news"), /data-story-placeholder/);
    const prod = full(news, lang, "news", true);
    assert.doesNotMatch(prod, /data-story-placeholder|\[Tên câu chuyện\]|\[Story title\]/);
    // The page's own body (the shared header and footer carry their own drafts, counted elsewhere).
    const left = findBlockers(body(news, lang, "news", true));
    assert.ok(left.every((w) => w === "draft text (data-draft)"), `PROD news only waits on draft wording: ${left}`);
  }
});

test("404: no 'not part of the prototype' note; every link the site makes now has a page", () => {
  for (const lang of LANGS) {
    for (const prod of [false, true]) {
      const h = body(notFound, lang, "notFound", prod);
      assert.doesNotMatch(h, /gap__proto/);
      assert.ok(!h.includes(esc(content.ui.notInPrototype[lang])));
    }
  }
});

// Fix round (11 Nov): the camera follows the finger evenly, the still is never blown up, the notices stay
// in the gutter, and the wall has no visible repeat.
const peakRate = ([x1, y1, x2, y2]) => {
  const at = (a, b, t) => ((1 - 3 * b + 3 * a) * t + (3 * b - 6 * a)) * t * t + 3 * a * t;
  const f = (x) => { let lo = 0, hi = 1, t = 0.5; for (let i = 0; i < 40; i++) { t = (lo + hi) / 2; if (at(x1, x2, t) < x) lo = t; else hi = t; } return at(y1, y2, t); };
  let m = 0; const N = 400;
  for (let i = 0; i < N; i++) m = Math.max(m, (f((i + 1) / N) - f(i / N)) * N);
  return m;
};

test("the camera and the notices ease evenly: no stretch of scroll runs faster than 1.6x the mean", () => {
  assert.ok(peakRate(TRACKS.cam.ease) <= 1.6, `cam peak ${peakRate(TRACKS.cam.ease).toFixed(2)}`);
  assert.ok(peakRate(TRACKS.cam.ease) > 0 && TRACKS.cam.ease[1] > 0, "the camera is moving from the first pixel of scroll");
  assert.ok(TRACKS.cam.to <= 0.88 && TRACKS.cam.to >= 0.85, "the last 12-15% holds still");
  const plain = css.replace(/\/\*[\s\S]*?\*\//g, "");
  for (const name of ["notice-turn", "notice-shadow"]) {
    const kf = plain.match(new RegExp(`@keyframes ${name} \\{([\\s\\S]*?\\})\\s*\\}`))?.[1];
    assert.ok(kf, name);
    const e = kf.match(/cubic-bezier\(([^)]+)\)/)[1].split(",").map(Number);
    assert.ok(peakRate(e) <= 1.6, `${name} peak ${peakRate(e).toFixed(2)}`);
  }
  // A notice turns about its pin only (no turn about the vertical axis, which shears the words).
  assert.doesNotMatch(plain.match(/@keyframes notice-turn \{[\s\S]*?\}\s*\}/)[0], /rotateY|translateZ/);
});

test("the stages say what they pin, so the driver knows where each lets go", () => {
  for (const [fn, key, sel] of [[contact, "contact", ".lit-head__media"], [news, "news", ".newswall__plane"]]) {
    for (const lang of LANGS) assert.match(body(fn, lang, key), new RegExp(`data-scroll3d="${key}"[^>]*data-s3d-pin="\\${sel}"`));
  }
  assert.match(driver, /s3dPin/);
});

test("contact: the still is never blown up (short dolly, stage no taller than the still at about 1x)", () => {
  const plain = css.replace(/\/\*[\s\S]*?\*\//g, "");
  const zooms = [...plain.matchAll(/--zoom:\s*([\d.]+)/g)].map((m) => Number(m[1]));
  assert.ok(zooms.length >= 2 && zooms.every((z) => z <= 0.2), `zooms ${zooms}`);
  const runs = [...plain.matchAll(/--run:\s*([\d.]+)svh/g)].map((m) => Number(m[1]));
  assert.ok(Math.min(...runs) <= 12, `phone run ${Math.min(...runs)}svh`);
  assert.match(plain, /--stage-h: min\(100svh - var\(--hdr\), 62svh\)/);
  assert.match(plain, /mask-composite: intersect/, "the desktop stage feathers into the header as well as the wall");
});

test("news: the wall's tiles share no short period, and there is a texture hook for the real plaster map", () => {
  const plain = css.replace(/\/\*[\s\S]*?\*\//g, "");
  const sizes = [Number(plain.match(/--wall-tex-size:\s*(\d+)px/)[1]), Number(plain.match(/var\(--wall-wash\) 0 0 \/ (\d+)px/)[1])];
  const gcd = (a, b) => (b ? gcd(b, a % b) : a);
  assert.ok(gcd(...sizes) <= 8, `tile sizes ${sizes} repeat together every ${(sizes[0] * sizes[1]) / gcd(...sizes)} px`);
  assert.match(plain, /var\(--wall-tex\) 0 0 \/ var\(--wall-tex-size\)/);
  assert.match(plain, /--wall-marks:/, "the wall carries marks drawn once (cracks, patches), not only noise");
  // The board itself never moves toward the camera (that pushed the notices past the gutter).
  assert.doesNotMatch(plain, /\.notices \{[^}]*translateZ/);
});
