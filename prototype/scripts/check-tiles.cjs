// Home work sheet (PLAN H5a): no focus tile's heading may touch its block art, at 1024 and 1440, VI and EN.
// The art is clipped by its tile, so the visible art is the overlap of the two rects.
// usage: node scripts/check-tiles.cjs [base=http://localhost:4173]   (exit 1 on any overlap)
const { chromium } = require("playwright");

const base = process.argv[2] ?? "http://localhost:4173";
const intersects = (a, b) => a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;

(async () => {
  const browser = await chromium.launch();
  const fails = [];
  for (const path of ["/?tier=0", "/en/?tier=0"]) {
    for (const width of [1024, 1440]) {
      const page = await browser.newPage({ viewport: { width, height: 900 }, reducedMotion: "reduce" });
      await page.goto(base + path, { waitUntil: "load" });
      await page.locator(".bento").scrollIntoViewIfNeeded();
      const tiles = await page.$$eval(".tile", (els) => els.map((t) => {
        const r = (el) => { const b = el.getBoundingClientRect(); return { left: b.left, top: b.top, right: b.right, bottom: b.bottom }; };
        const tile = r(t), art = r(t.querySelector(".tile__art")), h3 = r(t.querySelector("h3"));
        const seen = { left: Math.max(tile.left, art.left), top: Math.max(tile.top, art.top), right: Math.min(tile.right, art.right), bottom: Math.min(tile.bottom, art.bottom) };
        return { name: t.className, h3, art: seen };
      }));
      for (const t of tiles) {
        const hit = intersects(t.h3, t.art);
        console.log(`${hit ? "FAIL" : "ok  "} ${path} ${width} ${t.name}`);
        if (hit) fails.push(`${path} ${width} ${t.name}`);
      }
      await page.close();
    }
  }
  await browser.close();
  if (fails.length) { console.error(`${fails.length} tile heading(s) touch their art`); process.exit(1); }
})();
