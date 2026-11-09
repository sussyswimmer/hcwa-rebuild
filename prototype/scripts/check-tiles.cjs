// Home work sheet, H5a (05-DESIGN 0.6.3, fix r1 of 9 Nov): the gạch bông floor.
// - Words never sit on a tile: no heading or line of text (the H2, every area's name and line) may touch any
//   patch of tiles or its light band (both live in .gach__floor, which clips them).
// - The sheet rises under the corridor's exit (D0.5), so nothing of the sheet (H2, patches, words) may touch
//   the corridor's own "all work" link or the corridor's cards.
// - Tile count is the hierarchy at every width: Giáo dục shows 2 × 2 whole tiles and Bảo vệ 2 × 1, on phones
//   too; Gia đình and Vận động one each. A tile is never scaled (all four patches share one --T).
// - The light band at rest (reduced motion / Tier 0) shows at least 3 whole crisp quatrefoils on every area,
//   inside the band's unfaded part, computed from the mask's own lattice (sun-hoa-thi.svg, blocks.mjs).
// - All four names inside the first 1440 × 900 view of the section, and no page overflow.
// Widths 390 / 600 / 768 / 1024 / 1440 (and 320 for overflow and patches), VI and EN, Tier 0 with reduced
// motion; plus Tier 1 (the corridor as a stage) at 390 and 1440 for the corridor overlap.
// usage: node scripts/check-tiles.cjs [base=http://localhost:4173]   (exit 1 on any failure)
const { chromium } = require("playwright");

const base = process.argv[2] ?? "http://localhost:4173";
const intersects = (a, b) => a.left < b.right - 0.5 && b.left < a.right - 0.5 && a.top < b.bottom - 0.5 && b.top < a.bottom - 0.5;
const PATCH = { education: [2, 2], protection: [2, 1], family: [1, 1], policy: [1, 1] };

// Whole Hoa thị quatrefoils of the rest band over one area. The mask (blocks.mjs sunMaskFile) is a 300-unit
// block lattice under skewX(-14°) scale(1, 1.1), repeating every 300 units in x and 1650 in y; a hole's body
// reaches 114 units either side in x (the skewed lobes) and 123 in y.
const K = Math.tan((14 * Math.PI) / 180) * 1.1, HX = 114.1, HY = 123.2;
function wholeHoles(a) {
  const u = a.maskW / 300, bw = a.light.right - a.light.left;
  const x0 = a.light.left + a.fade * bw + HX * u, x1 = a.light.right - a.fade * bw - HX * u;
  const lx0 = Math.max(x0, a.floor.left + HX * u), lx1 = Math.min(x1, a.floor.right - HX * u);
  const ly0 = a.floor.top + HY * u, ly1 = a.floor.bottom - HY * u;
  let n = 0;
  for (let rep = 0; rep < 3; rep++) for (let j = 0; j < 5; j++) {
    const cy = a.light.top + u * (1.1 * (150 + 300 * j) + 1650 * rep);
    if (cy < ly0 || cy > ly1) continue;
    for (let i = -6; i <= 12; i++) {
      const cx = a.light.left + u * (150 + 300 * i - 300 * K * j);
      if (cx >= lx0 && cx <= lx1) n++;
    }
  }
  return n;
}

const measure = () => {
  const r = (el) => { const b = el.getBoundingClientRect(); return { left: b.left, top: b.top, right: b.right, bottom: b.bottom }; };
  const areas = [...document.querySelectorAll(".gach")].map((g) => {
    const art = g.querySelector(".gach__art"), light = g.querySelector(".gach__light"), cs = getComputedStyle(light);
    const cols = art.viewBox.baseVal.width / 200;
    return {
      id: g.className.replace("gach gach--", ""), floor: r(g.querySelector(".gach__floor")), light: r(light), T: art.getBoundingClientRect().width / cols,
      maskW: parseFloat(cs.maskSize || cs.webkitMaskSize), fade: +((cs.maskImage || cs.webkitMaskImage).match(/black (\d+(?:\.\d+)?)%/)?.[1] ?? 0) / 100, opacity: +cs.opacity,
      words: [r(g.querySelector(".gach__name")), r(g.querySelector(".gach__text"))],
    };
  });
  const work = document.querySelector("#work").getBoundingClientRect();
  // the corridor's words that can be read right now: the stage header, each card's lines and the link to every
  // center, at an effective opacity (the line and every ancestor up to the section) above 0.1
  const seen = (e) => { let o = 1; for (let n = e; n && n.id !== "centers"; n = n.parentElement) { const cs = getComputedStyle(n); if (cs.visibility === "hidden" || cs.display === "none") return 0; o *= +cs.opacity; } return o; };
  const corridor = [...document.querySelectorAll("#centers .bays__head > *, #centers .bay__text > *, #centers .bays__all a > *")].filter((e) => e.getClientRects().length && seen(e) > 0.1).map(r);
  // The sheet hides what is under it from its top, and from 0.6 of the way up its bông gió row, where that
  // row's fade (linear-gradient to top, black 20% → transparent) is half opaque.
  const edge = parseFloat(getComputedStyle(document.querySelector("#work"), "::before").height) || 0;
  return { areas, title: r(document.querySelector(".floor__title")), sectionTop: work.top, sheet: { left: 0, right: innerWidth, top: work.top - 0.6 * edge, bottom: work.bottom }, corridor, overflow: document.documentElement.scrollWidth - innerWidth, stage: !!document.querySelector("#centers.is-stage") };
};

(async () => {
  const browser = await chromium.launch();
  const fails = [];
  const fail = (msg) => { console.log(`FAIL ${msg}`); fails.push(msg); };
  for (const lang of ["/", "/en/"]) {
    for (const width of [320, 390, 600, 768, 1024, 1440]) {
      const path = `${lang}?tier=0`, tag = `${path} ${width}`;
      const page = await browser.newPage({ viewport: { width, height: 900 }, reducedMotion: "reduce" });
      await page.goto(base + path, { waitUntil: "load" });
      await page.locator(".floor").scrollIntoViewIfNeeded();
      await page.waitForTimeout(150);
      const m = await page.evaluate(measure);
      if (m.overflow > 0) fail(`${tag}: the page scrolls sideways by ${m.overflow}px`);
      // one --T for every patch, and every patch shows its whole tile count (phones included)
      const T = m.areas[0].T;
      for (const a of m.areas) {
        if (Math.abs(a.T - T) > 0.5) fail(`${tag}: ${a.id} tiles are ${a.T.toFixed(1)}px, not ${T.toFixed(1)}px (a tile is never scaled)`);
        const [c, rr] = PATCH[a.id], w = a.floor.right - a.floor.left, h = a.floor.bottom - a.floor.top;
        if (Math.abs(w - c * a.T) > 1 || Math.abs(h - rr * a.T) > 1) fail(`${tag}: ${a.id} shows ${(w / a.T).toFixed(2)} × ${(h / a.T).toFixed(2)} tiles, not ${c} × ${rr}`);
      }
      if (width === 320) { console.log(`ok   ${tag} (overflow and patches)`); await page.close(); continue; }
      const texts = [["H2", m.title], ...m.areas.flatMap((a) => [[`${a.id} name`, a.words[0]], [`${a.id} line`, a.words[1]]])];
      for (const a of m.areas) {
        for (const [name, t] of texts) if (intersects(a.floor, t)) fail(`${tag}: ${name} touches the ${a.id} tiles`);
        const n = wholeHoles(a);
        if (n < 3) fail(`${tag}: the ${a.id} light at rest shows ${n} whole quatrefoils (at least 3)`);
        if (a.opacity < 0.85) fail(`${tag}: the ${a.id} light rests at opacity ${a.opacity}`);
      }
      for (const cr of m.corridor) for (const [name, t] of [...texts, ...m.areas.map((a) => [`${a.id} tiles`, a.floor])]) if (intersects(cr, t)) fail(`${tag}: ${name} touches the corridor's text`);
      if (width === 1440) {
        // the section's first 1440 × 900 view: from its top edge
        const limit = m.sectionTop + 900;
        for (const a of m.areas) if (a.words[0].bottom > limit) fail(`${tag}: the ${a.id} name is below the section's first view`);
      }
      console.log(`ok   ${tag}  (--T ${T.toFixed(0)}px, whole quatrefoils ${m.areas.map((a) => `${a.id} ${wholeHoles(a)}`).join(", ")})`);
      await page.close();
    }
    // Tier 1: the corridor is a sticky stage and the sheet rises over its last frame. Walk the overlap in 40 px
    // steps: the sheet (from its bông gió row down) never covers a corridor word that can still be read, and
    // none of the sheet's words or tiles touch one.
    for (const width of [390, 1440]) {
      const path = `${lang}?tier=1`, tag = `${path} ${width}`;
      const mobile = width < 600, height = mobile ? 844 : 900;
      const ctx = await browser.newContext({ viewport: { width, height }, isMobile: mobile, hasTouch: mobile });
      const page = await ctx.newPage();
      await page.goto(base + path, { waitUntil: "load" });
      await page.waitForFunction(() => document.querySelector("#centers.is-stage"), null, { timeout: 15000 }).catch(() => {});
      const y0 = await page.evaluate(() => document.querySelector("#work").getBoundingClientRect().top + scrollY - innerHeight);
      let stage = false, steps = 0, bad = 0;
      for (let y = Math.round(y0 - 400); y <= y0 + height * 0.6; y += 40, steps++) {
        await page.evaluate((y) => scrollTo(0, y), y);
        await page.waitForTimeout(700); // the cards fade over 200-520 ms; judge the settled frame
        const m = await page.evaluate(measure);
        stage ||= m.stage;
        if (m.overflow > 0) { fail(`${tag}: the page scrolls sideways by ${m.overflow}px`); bad++; }
        const texts = [["H2", m.title], ...m.areas.flatMap((a) => [[`${a.id} name`, a.words[0]], [`${a.id} line`, a.words[1]], [`${a.id} tiles`, a.floor]])];
        for (const cr of m.corridor) {
          if (cr.bottom < 0 || cr.top > height) continue;
          // a line is hidden once the sheet covers more than a third of its height (its descenders may go under
          // the bông gió row's soft top as the card leaves)
          const covered = (cr.bottom - Math.max(cr.top, m.sheet.top)) / (cr.bottom - cr.top);
          if (intersects(cr, m.sheet) && covered > 1 / 3) { fail(`${tag} @${y}: the sheet covers ${Math.round(covered * 100)}% of a corridor line that can still be read (top ${Math.round(cr.top)})`); bad++; }
          for (const [name, t] of texts) if (intersects(cr, t)) { fail(`${tag} @${y}: ${name} touches the corridor's text`); bad++; }
        }
      }
      if (!stage) console.log(`note ${tag}: the corridor is not a stage here`);
      console.log(`${bad ? "FAIL" : "ok  "} ${tag}  (stage ${stage}, ${steps} scroll steps through the overlap)`);
      await ctx.close();
    }
  }
  await browser.close();
  if (fails.length) { console.error(`${fails.length} failure(s)`); process.exit(1); }
})();
