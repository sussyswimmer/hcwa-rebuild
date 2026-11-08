// The corridor's cards never run past their column (critique r2: a VI link pair wider than a 390 px screen
// clipped the Little Rose card). For each width and locale, on the stage and stacked: no element of a card
// reaches past its .bay__text box or the screen's right edge, and on the stage (where the card has no
// feathered pane: the stacked pane runs edge to edge on purpose, as a ::before) scrollWidth ≤ clientWidth + 1.
//   npm run build && npx vite preview --port 4173 & node scripts/check-bays.cjs http://localhost:4173
// Exits 1 on any FAIL. Playwright is preinstalled outside the project (require("playwright")).
const { chromium } = require("playwright");
const base = process.argv[2] ?? "http://localhost:4173";
const ok = (cond, msg) => { console.log((cond ? "PASS " : "FAIL ") + msg); if (!cond) process.exitCode = 1; };

(async () => {
  const browser = await chromium.launch();
  for (const width of [360, 390]) for (const [lang, path] of [["vi", "/"], ["en", "/en/"]]) for (const tier of ["1", "0"]) {
    const page = await browser.newPage({ viewport: { width, height: 844 }, isMobile: true, hasTouch: true });
    await page.goto(`${base}${path}?tier=${tier}`, { waitUntil: "load" });
    await page.evaluate(() => document.fonts.ready);
    // The stage decides once the motion chunk is in (motion/stages.js): give it the time it takes.
    if (tier !== "0") await page.waitForFunction(() => document.querySelector(".bays-section")?.classList.contains("is-stage"), null, { timeout: 15000 }).catch(() => {});
    const rows = await page.evaluate(() => {
      const sec = document.querySelector(".bays-section");
      const stage = sec.classList.contains("is-stage");
      // Every card in turn, shown as the stage shows it (the others are faded, not removed).
      return { stage, cards: [...sec.querySelectorAll(".bay")].map((bay) => {
        const t = bay.querySelector(".bay__text"), r = t.getBoundingClientRect();
        const over = [...t.querySelectorAll("*")].filter((el) => { const e = el.getBoundingClientRect(); return e.width && (e.right > Math.min(innerWidth, r.right) + 1); }).map((el) => el.textContent.trim().slice(0, 30));
        return { unit: bay.dataset.unit, sw: stage ? t.scrollWidth : t.clientWidth, cw: t.clientWidth, right: Math.round(r.right), over };
      }) };
    });
    for (const c of rows.cards) ok(c.sw <= c.cw + 1 && c.over.length === 0, `${width} ${lang} tier ${tier}${rows.stage ? " stage" : " stacked"} ${c.unit}: ${c.sw}/${c.cw}${c.over.length ? " past the edge: " + c.over.join(" | ") : ""}`);
    await page.close();
  }
  await browser.close();
})();
