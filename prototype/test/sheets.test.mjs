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

test("public/svg matches the block SDF numbers (run npm run svg)", () => {
  for (const [name, svg] of Object.entries(generate())) assert.equal(readFileSync(new URL(name, svgDir), "utf8"), svg, `${name} drifted`);
  const r = HOA_THI.r * 1000;
  assert.match(holes("hoaThi"), new RegExp(`^M${r} ${-r}A${r} ${r} `), "quatrefoil lobes use HOA_THI.r");
  assert.match(generate()["face-hoa-thi.svg"], new RegExp(`viewBox="${(-BLOCK / 2) * 1000} ${(-BLOCK / 2) * 1000} ${BLOCK * 1000} ${BLOCK * 1000}"`));
  for (const kind of Object.values(AREA_BLOCK)) assert.ok(KINDS.includes(kind), `${kind} has an SDF`);
  // The floor's sunlight: the Hoa thị holes under the floor projection, crisp (no blur), repeating in x and y.
  const sun = generate()["sun-hoa-thi.svg"];
  assert.match(sun, /<path id="h" transform="matrix\(0\.6767 0 -0\.1046 0\.7444 0 0\)"/, "0.6 of a cell, leaning 8°, stretched 1.1");
  assert.ok(sun.includes(`d="${holes("hoaThi")}"`), "the same quatrefoil as the screen");
  assert.doesNotMatch(sun, /filter|blur|Gaussian/i);
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

test("our work sheet: a floor of gạch bông (4 / 2 / 1 / 1 tiles), figures hidden on production while unverified", () => {
  const stage = str(workSheet(ctx("vi", "home", false)));
  const prod = str(workSheet(ctx("vi", "home", true)));
  assert.equal((stage.match(/<li class="gach /g) ?? []).length, 4);
  assert.doesNotMatch(stage, /class="(tile|bento)[ "]/, "the 8 Nov bento is gone");
  assert.equal((stage.match(/class="gach-sprite"/g) ?? []).length, 1, "one sprite per page");
  for (const kind of Object.values(AREA_BLOCK)) assert.equal((stage.match(new RegExp(`<symbol id="gach-${kind}"`, "g")) ?? []).length, 1, `${kind} symbol`);
  const tiles = { education: 4, protection: 2, family: 1, policy: 1 };
  for (const [area, n] of Object.entries(tiles)) {
    const li = stage.match(new RegExp(`<li class="gach gach--${area}">[\\s\\S]*?</li>`))[0];
    const art = li.match(/<svg class="gach__art"[\s\S]*?<\/svg>/)[0], lit = li.match(/<svg class="gach__lit"[\s\S]*?<\/svg>/)[0];
    assert.equal((art.match(/<use /g) ?? []).length, n, `${area}: ${n} tiles, the hierarchy is the tile count`);
    assert.equal(lit.replace("gach__lit", "gach__art"), art, `${area}: the sunlit copy is the same tiles, registered`);
    assert.match(li, /<div class="gach__floor" aria-hidden="true">/, `${area}: the tiles and the light are decoration`);
    assert.match(li, new RegExp(`<h3 class="gach__name"><a href="/hoat-dong/\\?area=${area}#f-${area}">`), `${area}: links to Our Work filtered`);
  }
  // Bậc thang rises left to right: never turned, its nosings climb as x grows (y down in SVG).
  const policy = stage.match(/<li class="gach gach--policy">[\s\S]*?<\/li>/)[0];
  assert.doesNotMatch(policy, /rotate\(/, "the stair is never turned");
  const stairPath = stage.match(/<symbol id="gach-bacThang">[\s\S]*?stroke-linejoin="miter"[^>]*d="([^"]+)"/)[1];
  const corners = [...stairPath.matchAll(/H([\d.]+)V([\d.]+)/g)].map((m) => [+m[1], +m[2]]);
  assert.equal(corners.length, 6, "six equal steps");
  for (let i = 1; i < corners.length; i++) assert.ok(corners[i][0] > corners[i - 1][0] && corners[i][1] < corners[i - 1][1], "each nosing is right of and above the last");
  const en = str(workSheet(ctx("en", "home", false)));
  assert.match(en, /<h3 class="gach__name"><a href="\/en\/our-work\/\?area=education#f-education">/);
  assert.match(stage, /class="plaques"/);
  assert.doesNotMatch(prod, /class="plaques"|data-pending/, "fewer than 2 verified figures: no plaques at all");
  assert.match(prod, /id="work"/, "the corridor's skip link lands here");
});

test("our work sheet, fix r1: each patch is one laid floor, with a crisp light lattice and a cement ground", () => {
  const stage = str(workSheet(ctx("vi", "home", false)));
  // a tile is a square of cement with its medallion 2 mm in, and the corner motifs run to the tile's edge
  for (const kind of Object.values(AREA_BLOCK)) {
    const sym = stage.match(new RegExp(`<symbol id="gach-${kind}">[\\s\\S]*?</symbol>`))[0];
    assert.match(sym, /^<symbol id="[^"]+"><rect width="200" height="200" style="fill:var\(--cream\)"\/><rect x="2" y="2" width="196" height="196" rx="1.5" style="fill:var\(--pig\)"\/>/, `${kind}: cement square, then the medallion at 2 mm`);
  }
  assert.match(stage, /<clipPath id="gach-clip"><rect width="200" height="200"\/><\/clipPath>/, "corner motifs clip to the tile, not the medallion");
  // tiles in a patch meet at one 1.5 px grout joint, drawn only between tiles (never round the patch)
  const joints = { education: "M200 0V400M0 200H400", protection: "M200 0V200" };
  for (const area of ["education", "protection", "family", "policy"]) {
    const art = stage.match(new RegExp(`<li class="gach gach--${area}">[\\s\\S]*?<svg class="gach__art"[\\s\\S]*?</svg>`))[0];
    const grout = art.match(/<path fill="none" stroke-width="1.5" vector-effect="non-scaling-stroke" style="stroke:var\(--grout\)" d="([^"]+)"\/>/);
    if (joints[area]) assert.equal(grout?.[1], joints[area], `${area}: joints between its tiles`);
    else assert.equal(grout, null, `${area}: one tile, no joint`);
  }
  // the CSS sun layer is gone from the sheet (the tile light carries it); nothing else reaches the plaster
  assert.doesNotMatch(stage, /sun-layer--work/);
  const files = generate();
  // the cement ground's speckle: the tiles' own wear noise, seamless, alpha only (no colour in the file)
  const cement = files["cement.svg"];
  assert.match(cement, /<feTurbulence type="fractalNoise" baseFrequency=".62" numOctaves="2" seed="7" stitchTiles="stitch"\/>/);
  assert.match(stage, /<feTurbulence type="fractalNoise" baseFrequency=".62" numOctaves="2" seed="7"/, "the same speckle as the tiles' wear");
  assert.doesNotMatch(cement, /#[0-9a-f]{3,6}\b|rgb\(/i);
  // pages.css: the band (2 T, 10% fades) and the rise under the corridor
  const css = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8");
  assert.match(css, /--band: calc\(2 \* var\(--T\)\)/);
  assert.match(css, /#centers \+ #work \{ --rise: calc\(var\(--section-y\) - var\(--s-8\)\); margin-top: calc\(-1 \* var\(--rise\)\); padding-top: clamp\(24px, 3vw, 48px\);/);
  assert.match(css, /@media \(max-width: 599px\) \{ html\.stages #centers\.is-stage \+ #work \{ --rise: 16svh; \} \}/);
});

test("our work sheet, fix r2: isolated quatrefoils, a sheet that lays no lace over the corridor, a floor of plain tiles", () => {
  const files = generate(), sun = files["sun-hoa-thi.svg"];
  // three rows to a 990-unit repeat, each a third of a cell further along: seamless in x and y
  assert.match(sun, /viewBox="0 0 300 990"/);
  const at = [...sun.matchAll(/<use href="#h" x="(-?[\d.]+)" y="([\d.]+)"\/>/g)].map((m) => [+m[1], +m[2]]);
  const rows = [...new Set(at.map(([, y]) => y))].sort((a, b) => a - b);
  assert.deepEqual(rows, [165, 495, 825], "three rows, 330 units apart");
  rows.forEach((y, j) => assert.ok(at.some(([x, yy]) => yy === y && (((x - (150 - 100 * j)) % 300) + 300) % 300 === 0), `row ${j} on the lattice`));
  assert.equal((((150 - 100 * 3) % 300) + 300) % 300, 150, "the fourth row would land on the first: the y repeat is seamless");
  // each hole is 0.6 of its cell (pips included): 0.4 of a cell of tile between neighbours, in x and in y
  const reach = (HOA_THI.pipAt + HOA_THI.pipR) * 1000, s = 0.6767;
  assert.ok(Math.abs((2 * reach * s) / 300 - 0.6) < 0.001, "a hole is 0.6 of its cell");
  assert.ok(300 - 2 * reach * s >= 0.4 * 300 - 0.5, "0.4 of a cell between two holes in a row");
  assert.ok(330 - 1.1 * 2 * reach * s >= 0.4 * 330 - 0.5, "and between rows");
  const css = readFileSync(new URL("../src/styles/pages.css", import.meta.url), "utf8");
  // the light: a 0.54-tile cell (two whole rows on one tile), centred on the tile; crisp wear
  assert.match(css, /--sun: calc\(0\.54 \* var\(--T\)\)/);
  assert.match(css, /url\("\/svg\/sun-hoa-thi\.svg"\) 0 calc\(0\.5 \* var\(--T\) - 1\.1 \* var\(--sun\)\) \/ var\(--sun\) auto repeat, linear-gradient\(to right, transparent, black 10%, black 90%, transparent\)/);
  assert.match(css, /:is\(\.gach, \.gach-strip\) \{[^}]*--wear: 0\.22/);
  assert.match(css, /\.gach__lit \{[^}]*--cream: var\(--color-sun-200\)[^}]*--wear: 0\.08/);
  // over the live corridor the sheet has no bông gió row and is plaster from its top (the canvas fades itself)
  assert.match(css, /html\.stages #centers\.is-stage \+ #work \{ --rise: 24svh; background: var\(--wall\); \}/);
  assert.match(css, /html\.stages #centers\.is-stage \+ #work::before \{ display: none; \}/);
  // the plain floor: 1.5 px joints (half on each side of a tile's edge) at exactly --T, in whole pixels
  assert.match(css, /--joint: color-mix\(in oklab, var\(--color-tile-cream\), var\(--color-ink-700\) 10%\)/);
  assert.match(css, /--jx: linear-gradient\(to right, var\(--joint\) 0\.75px, transparent 0\.75px calc\(100% - 0\.75px\), var\(--joint\) 0\)/);
  assert.match(css, /--T: round\(down, min\(clamp\(136px, 11\.7vw, 176px\), 100cqi \/ 8\), 1px\)/, "desktop: eight tiles across the container");
  assert.match(css, /grid-template-columns: repeat\(4, calc\(2 \* var\(--T\)\)\)/, "desktop columns are two tiles each, no gaps");
  // the plain tiles' tones: black alpha only (0-4.5%), laid on the same lattice, 5 × 4 tiles
  assert.doesNotMatch(files["plain-tiles.svg"], /fill="#|rgb\(|fill-opacity="\.(?:0[5-9]|[1-9])/, "no colour, never darker than 4.5%");
  assert.match(css, /url\("\/svg\/plain-tiles\.svg"\), var\(--color-tile-cream\); background-size: var\(--T\) var\(--T\), var\(--T\) var\(--T\), calc\(5 \* var\(--T\)\) calc\(4 \* var\(--T\)\), auto; background-position: var\(--gutter\) var\(--s-7\);/);
});

test("programs, fix r2: each row carries its areas as gạch tiles from the page's one sprite", () => {
  const byId = Object.fromEntries(content.work.programs.map((p) => [p.id, p]));
  for (const lang of ["vi", "en"]) {
    // a row on its own (Our Work, the components page) brings the sprite; a second row on the same page does not
    const c = ctx(lang, "ourWork", false);
    const first = str(programRow(c, byId["thrive-together"])), second = str(programRow(c, byId["disability-pilot"]));
    assert.equal((first.match(/class="gach-sprite"/g) ?? []).length, 1);
    assert.doesNotMatch(second, /gach-sprite/);
    for (const [row, p] of [[first, byId["thrive-together"]], [second, byId["disability-pilot"]]]) {
      const strip = row.match(/<span class="program-row__floor" aria-hidden="true">[\s\S]*?<svg class="gach-strip program-row__tiles" viewBox="0 0 (\d+) 200"[^>]*>([\s\S]*?)<\/svg>/);
      assert.ok(strip, `${p.id}: a decorative strip of tiles`);
      assert.equal(+strip[1], 200 * p.areas.length, "one whole tile per area, never scaled per row");
      assert.deepEqual([...strip[2].matchAll(/<use href="#gach-(\w+)" class="pig--(\w+)"/g)].map((m) => [m[1], m[2]]), p.areas.map((a) => [AREA_BLOCK[a], a]));
      assert.doesNotMatch(strip[2], /rotate\(/, "never turned");
    }
    // the home sheet: one sprite, before the rows (never inside one, which a filter may hide)
    const home = str(workSheet(ctx(lang, "home", false)));
    assert.equal((home.match(/class="gach-sprite"/g) ?? []).length, 1);
    assert.ok(home.indexOf('class="gach-sprite"') < home.indexOf('class="program-row"'));
    assert.equal((home.match(/class="gach-strip program-row__tiles"/g) ?? []).length, Math.min(4, content.work.programs.filter((p) => p.areas.length).length));
  }
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
