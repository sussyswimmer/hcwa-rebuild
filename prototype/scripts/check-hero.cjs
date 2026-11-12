// Hero matrix (PLAN H2, A1-A4, A9): VI and EN at 390 x 844 and 1440 x 900, in every way a visitor can
// arrive: default tier, ?tier=2/1/0, WebGL disabled, reduced motion, paused on an earlier page, paused
// on this page before the world loads, no JS. For each: a screenshot, console errors, whether the
// three.js chunk was requested, H1 lines, the screen silhouette under the H1, header text contrast
// over the hero, sideways scroll at 320. Run against a built preview:
//   npm run build && npx vite preview --port 4173 & node scripts/check-hero.cjs http://localhost:4173 ../qa/m1
// Exits 1 on any FAIL. Headless Chromium draws WebGL with SwiftShader, which the world treats as a
// software GPU (stills), so only the ?tier flags show the live world here.
const { chromium } = require("playwright");
const fs = require("fs");
const path = require("path");

const base = process.argv[2] || "http://localhost:4173";
const out = path.resolve(process.argv[3] || path.join(__dirname, "../../qa/m1"));
const dist = path.join(__dirname, "../dist/assets");
fs.mkdirSync(out, { recursive: true });

// The chunk that carries three.js, whatever its hash.
const threeChunks = fs.readdirSync(dist).filter((f) => f.endsWith(".js") && fs.readFileSync(path.join(dist, f), "utf8").includes("WebGLRenderer"));
const isThree = (u) => threeChunks.some((f) => u.includes("/assets/" + f));

const SIZES = { 390: { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }, 1440: { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 } };
const LANGS = { vi: "/", en: "/en/" };
const MODES = {
  default: { live: "gpu" },
  t2: { q: "tier=2&debug=1", live: true },
  t1: { q: "tier=1&debug=1", live: true },
  t0: { q: "tier=0", live: false },
  nowebgl: { browser: "nowebgl", live: false },
  reduced: { ctx: { reducedMotion: "reduce" }, live: false },
  paused: { init: () => { try { localStorage.setItem("hcwa:motion", "off"); } catch {} }, live: false },
  "paused-now": { init: () => document.addEventListener("DOMContentLoaded", () => document.querySelector(".hero__pause")?.click()), live: false },
  nojs: { ctx: { javaScriptEnabled: false }, live: false },
};

let fails = 0;
const ok = (cond, msg) => { console.log((cond ? "PASS " : "FAIL ") + msg); if (!cond) fails++; };

// Pixel statistics inside rects of a CSS-px screenshot, computed in the page (no image library here).
async function pixels(page, png, rects) {
  return page.evaluate(async ({ src, rects }) => {
    const img = new Image();
    img.src = src;
    await img.decode();
    const c = new OffscreenCanvas(img.width, img.height), g = c.getContext("2d", { willReadFrequently: true });
    g.drawImage(img, 0, 0);
    const lin = (v) => { v /= 255; return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; };
    return rects.map((r) => {
      const x = Math.max(0, Math.round(r.x)), y = Math.max(0, Math.round(r.y));
      const w = Math.min(img.width - x, Math.round(r.w)), h = Math.min(img.height - y, Math.round(r.h));
      if (w <= 0 || h <= 0) return { face: 0, lum1: 1, n: 0 };
      const d = g.getImageData(x, y, w, h).data, lums = [];
      let face = 0;
      for (let i = 0; i < d.length; i += 4) {
        const [R, G, B] = [d[i], d[i + 1], d[i + 2]];
        // The silhouette (world-screen-face #3D4370): clearly blue and dark. Wall and floor in shade are greyer and lighter.
        if (B - R >= 40 && (R + G + B) / 3 < 140) face++;
        lums.push(0.2126 * lin(R) + 0.7152 * lin(G) + 0.0722 * lin(B));
      }
      lums.sort((a, b) => a - b);
      return { face: face / (w * h), lum1: lums[Math.floor(lums.length * 0.01)], n: w * h };
    });
  }, { src: "data:image/png;base64," + png.toString("base64"), rects });
}
// Mean absolute difference (0-255) between two screenshots of the same size, and the share of pixels off by more than 24.
async function diff(page, a, b) {
  return page.evaluate(async ([a, b]) => {
    const load = async (src) => { const i = new Image(); i.src = src; await i.decode(); const c = new OffscreenCanvas(i.width, i.height), g = c.getContext("2d", { willReadFrequently: true }); g.drawImage(i, 0, 0); return g.getImageData(0, 0, i.width, i.height).data; };
    const [x, y] = await Promise.all([load(a), load(b)]);
    let sum = 0, far = 0;
    for (let i = 0; i < x.length; i += 4) { const d = (Math.abs(x[i] - y[i]) + Math.abs(x[i + 1] - y[i + 1]) + Math.abs(x[i + 2] - y[i + 2])) / 3; sum += d; if (d > 24) far++; }
    return { mean: +(sum / (x.length / 4)).toFixed(2), far: +((100 * far) / (x.length / 4)).toFixed(2) };
  }, ["data:image/png;base64," + a.toString("base64"), "data:image/png;base64," + b.toString("base64")]);
}
const lin = (v) => { v /= 255; return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; };
const lum = ([r, g, b]) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
const INK = lum([0x1d, 0x21, 0x31]);
// Text color against the darkest 1% behind it (text is darker than every world surface it may sit on).
const contrast = (l, text = INK) => (Math.max(l, text) + 0.05) / (Math.min(l, text) + 0.05);
// Share of pixels (mean channel difference > `by`) that changed between two screenshots.
async function changed(page, a, b, by = 12) {
  return page.evaluate(async ([a, b, by]) => {
    const load = async (src) => { const i = new Image(); i.src = src; await i.decode(); const c = new OffscreenCanvas(i.width, i.height), g = c.getContext("2d", { willReadFrequently: true }); g.drawImage(i, 0, 0); return g.getImageData(0, 0, i.width, i.height).data; };
    const [x, y] = await Promise.all([load(a), load(b)]);
    let n = 0;
    for (let i = 0; i < x.length; i += 4) if ((Math.abs(x[i] - y[i]) + Math.abs(x[i + 1] - y[i + 1]) + Math.abs(x[i + 2] - y[i + 2])) / 3 > by) n++;
    return +((100 * n) / (x.length / 4)).toFixed(3);
  }, ["data:image/png;base64," + a.toString("base64"), "data:image/png;base64," + b.toString("base64"), by]);
}
const BARE = ".hero__inner, .hero__pause, .site-header .logo img, .site-header .lang-switch, .site-header .lang-switch a, .site-header .menu-btn, .site-header .logo__name, .site-header .nav__link, .site-header .nav__toggle { visibility: hidden !important; } [data-draft] { outline: 0 !important; }";

// Text rects (line boxes) of the H1 and the intro, viewport px.
const textRects = (page) => page.evaluate(() => {
  const r = document.createRange(), box = (b) => ({ x: b.left, y: b.top, w: b.width, h: b.height });
  return [".hero__title", ".hero__intro"].map((sel) => (r.selectNodeContents(document.querySelector(sel)), box(r.getBoundingClientRect())));
});
const bare = (page) => page.evaluate((css) => { const st = document.createElement("style"); st.id = "bare"; st.textContent = css; document.head.append(st); }, BARE);

// The critique gates beyond the matrix: short phones, the hero scrolling, motion at rest, pointer
// extremes and GPU memory (PLAN H2, 05-DESIGN D6.6-D6.7).
async function extras(browser) {
  const rows = { shortPhones: [], live: [] };
  const lang = (l) => (l === "vi" ? "vi-VN" : "en-US");
  // 1. Phones shorter than the 390 x 844 the stills are framed for (Safari's visible 390 x 664, small
  //    Androids): the stills are bottom-anchored, so the lattice never slides into the H1.
  for (const [w, h] of [[390, 664], [360, 640], [412, 780]]) for (const [l, route] of Object.entries(LANGS)) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, isMobile: true, hasTouch: true, deviceScaleFactor: 2, locale: lang(l) });
    const page = await ctx.newPage();
    await page.goto(base + route + "?tier=0", { waitUntil: "load" });
    await page.waitForFunction(() => window.hcwa?.state.sunIn === "done", null, { timeout: 8000 }).catch(() => {});
    await page.waitForTimeout(500);
    const [h1, intro] = await textRects(page);
    await bare(page);
    const [a, b] = await pixels(page, await page.screenshot({ scale: "css" }), [h1, intro]);
    await page.evaluate(() => document.querySelectorAll(".lit__light, .lit__screen").forEach((e) => (e.style.visibility = "hidden")));
    await page.waitForTimeout(80);
    const [c, d] = await pixels(page, await page.screenshot({ scale: "css" }), [h1, intro]);
    const row = { tag: `${l}-${w}x${h}`, face: +(a.face * 100).toFixed(2), h1: +contrast(a.lum1).toFixed(2), intro: +contrast(b.lum1).toFixed(2), shadeH1: +contrast(c.lum1).toFixed(2), shadeIntro: +contrast(d.lum1).toFixed(2) };
    rows.shortPhones.push(row);
    ok(a.face < 0.002 && row.h1 >= 4.5 && row.shadeH1 >= 4.5 && row.intro >= 7 && row.shadeIntro >= 7, `short phone ${row.tag}: no silhouette under the H1 (${row.face}%), H1 ${row.h1} / ${row.shadeH1} (shaded), intro ${row.intro} / ${row.shadeIntro}`);
    await ctx.close();
  }
  // 2. The live world: motion at rest, pause, memory, pointer extremes, the hero scrolling out.
  for (const [l, route] of Object.entries(LANGS)) for (const size of ["390", "1440"]) {
    const tier = size === "390" ? 1 : 2, tag = `${l}-${size}-t${tier}`;
    const ctx = await browser.newContext({ ...SIZES[size], locale: lang(l) });
    const page = await ctx.newPage();
    await page.goto(base + route + `?tier=${tier}&debug=1`, { waitUntil: "load" });
    const live = await page.waitForFunction(() => document.documentElement.classList.contains("world-live"), null, { timeout: 30000 }).then(() => true, () => false);
    ok(live, `${tag}: world live`);
    if (!live) { await ctx.close(); continue; }
    // The QA hooks (?debug) are their own chunk, imported after the world goes live; since M6 the posters' atlas and
    // the wall maps decode in the same idle time, so the hooks can arrive after world-live by a second or more.
    await page.waitForFunction(() => window.__hcwaWorld?.room, null, { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(1200);
    const clock = () => page.evaluate(() => window.__hcwaWorld.room.U.uTime.value);
    // One second of the world's own clock between two frames, with no input. SwiftShader draws a frame
    // in hundreds of ms and the loop caps a step at 100 ms, so wall time would understate it.
    const until = async (t) => { for (let i = 0; i < 80 && (await clock()) < t; i++) await page.waitForTimeout(150); };
    // The canopy fades in over 2.5 s of world time after the handoff: measure its steady state.
    for (let i = 0; i < 120 && (await page.evaluate(() => window.__hcwaWorld.room.U.uLeafAmt.value)) < 1; i++) await page.waitForTimeout(200);
    const t0 = await clock(); const a = await page.screenshot({ scale: "css" });
    await until(t0 + 1); const t1 = await clock(); const b = await page.screenshot({ scale: "css" });
    const ambient = await changed(page, a, b);
    await page.evaluate(() => document.querySelector(".hero__pause").click());
    await page.waitForTimeout(1500);
    const tp = await clock();
    const c = await page.screenshot({ scale: "css" }); await page.waitForTimeout(1200); const d = await page.screenshot({ scale: "css" });
    const clockFrozen = (await clock()) === tp;
    const pausedChange = await changed(page, c, d);
    await page.evaluate(() => document.querySelector(".hero__pause").click());
    // Everything on the GPU (M7, memory.js): the drawing buffers, the shafts' target, the baked textures, and once they
    // are in, the wall maps, the poster atlas and the corridor's runner (loaded in idle time: wait for all three).
    await page.waitForFunction(() => { const m = window.__hcwaWorld.memory?.(); return m && m.walls && m.posters && m.gach; }, null, { timeout: 45000 }).catch(() => {});
    const mem = await page.evaluate(() => window.__hcwaWorld.memoryMB());
    const memParts = await page.evaluate(() => window.__hcwaWorld.memory?.());
    const budget = tier === 2 ? 48 : 24;
    ok(ambient >= 0.6, `${tag}: the world moves with no input (${ambient}% of pixels change by > 12/255 over ${(t1 - t0).toFixed(2)} s of world time)`);
    ok(pausedChange === 0 && clockFrozen, `${tag}: pause freezes it (${pausedChange}% change, clock frozen ${clockFrozen})`);
    ok(mem <= budget, `${tag}: GPU memory ~${mem} MB <= ${budget} MB (D6.7; ${JSON.stringify(memParts)})`);
    const row = { tag, ambient, worldSeconds: +(t1 - t0).toFixed(2), pausedChange, memoryMB: mem, parallax: await page.evaluate(() => window.__hcwaWorld.parallax()), pointer: [], scroll: [] };
    await bare(page);
    const [h1, intro] = await textRects(page);
    // Pointer extremes (fine pointers only): the light and the camera at each corner.
    if (size === "1440") for (const [x, y] of [[-1, -1], [1, -1], [-1, 1], [1, 1]]) {
      await page.evaluate(([x, y]) => window.__hcwaWorld.pointer(x, y), [x, y]);
      await page.waitForTimeout(900);
      const [p, q] = await pixels(page, await page.screenshot({ scale: "css" }), [h1, intro]);
      row.pointer.push({ x, y, face: +(p.face * 100).toFixed(2), h1: +contrast(p.lum1).toFixed(2), intro: +contrast(q.lum1).toFixed(2) });
    }
    if (row.pointer.length) {
      await page.evaluate(() => window.__hcwaWorld.pointer(0, 0));
      ok(row.pointer.every((p) => p.face < 0.2 && p.h1 >= 4.5 && p.intro >= 7), `${tag}: pointer extremes keep the H1 >= 4.5 and the intro >= 7, no silhouette under the H1 (${row.pointer.map((p) => `${p.h1}/${p.intro}/${p.face}%`).join(", ")})`);
    }
    // The hero scrolling out, 10 positions over 100svh: the room scrolls with its text, so the
    // silhouette never reaches the H1 or the intro.
    const vh = await page.evaluate(() => innerHeight);
    for (let i = 0; i <= 9; i++) {
      await page.evaluate((y) => window.scrollTo({ top: y, behavior: "instant" }), Math.round((vh * i) / 9));
      await page.waitForTimeout(450);
      // Only what shows of each rect: below the header bar (it covers the canvas) and above the fold.
      const top = await page.evaluate(() => document.querySelector(".site-header").getBoundingClientRect().bottom);
      const rects = (await textRects(page)).map((r) => ({ ...r, h: Math.max(0, Math.min(r.y + r.h, vh) - Math.max(r.y, top)), y: Math.max(r.y, top) }));
      const [p, q] = await pixels(page, await page.screenshot({ scale: "css" }), rects);
      row.scroll.push({ y: Math.round((vh * i) / 9), h1Face: +(p.face * 100).toFixed(2), introFace: +(q.face * 100).toFixed(2), t: await page.evaluate(() => window.hcwa.state.progress.room ?? null) });
    }
    const worst = Math.max(...row.scroll.map((r) => Math.max(r.h1Face, r.introFace)));
    ok(worst === 0, `${tag}: scrolling 0 → 100svh, 0% silhouette inside the H1 and intro on every frame (worst ${worst}%)`);
    rows.live.push(row);
    console.log(`  ${JSON.stringify(row)}`);
    await ctx.close();
  }
  return rows;
}

(async () => {
  const browsers = {
    gpu: await chromium.launch(),
    nowebgl: await chromium.launch({ args: ["--disable-webgl", "--disable-webgl2", "--disable-3d-apis"] }),
  };
  const summary = [];
  for (const [lang, route] of Object.entries(LANGS)) for (const [size, sizeOpts] of Object.entries(SIZES)) for (const [mode, m] of Object.entries(MODES)) {
    const tag = `${lang}-${size}-${mode}`;
    if (process.env.ONLY && !new RegExp(process.env.ONLY).test(tag)) continue;
    // Each page in its readers' browser language (a VI page in an English browser adds the EN pointer bar).
    const ctx = await browsers[m.browser || "gpu"].newContext({ ...sizeOpts, locale: lang === "vi" ? "vi-VN" : "en-US", ...(m.ctx || {}) });
    if (m.init) await ctx.addInitScript(m.init);
    const requests = [], errors = [], info = [];
    ctx.on("request", (r) => requests.push(r.url()));
    const page = await ctx.newPage();
    page.on("console", (msg) => { if (msg.type() === "error") errors.push(msg.text()); if (/\[world\]/.test(msg.text())) info.push(msg.text()); });
    page.on("pageerror", (e) => errors.push(e.message));
    page.on("requestfailed", (r) => { if (!/ERR_ABORTED/.test(r.failure()?.errorText || "")) errors.push("request failed " + r.url()); });
    const url = base + route + (m.q ? "?" + m.q : "");
    await page.goto(url, { waitUntil: "load" });
    const js = m.ctx?.javaScriptEnabled !== false;
    if (m.live === true) await page.waitForFunction(() => document.documentElement.classList.contains("world-live"), null, { timeout: 25000 }).catch(() => {});
    else if (js) await page.waitForFunction(() => window.hcwa?.state.sunIn === "done", null, { timeout: 6000 }).catch(() => {});
    // Let load + idle pass, so a world that should not load has had every chance to.
    await page.waitForLoadState("networkidle").catch(() => {});
    await page.waitForTimeout(m.live === true ? 900 : 2600);
    const shot = path.join(out, `hero-${tag}.png`);
    await page.screenshot({ path: shot, scale: "css" });

    const s = await page.evaluate(() => {
      const h = document.documentElement, h1 = document.querySelector(".hero__title");
      const range = document.createRange();
      range.selectNodeContents(h1);
      const tops = [];
      for (const r of range.getClientRects()) if (r.height > 10 && !tops.some((t) => Math.abs(t - r.top) < r.height / 2)) tops.push(r.top);
      const box = (r) => ({ x: r.left, y: r.top, w: r.width, h: r.height });
      // Header text by its element boxes (a text range would also take in the visually hidden language
      // names), each with the color it is drawn in (the inactive language is not ink-900 everywhere).
      const rgb = (c) => c.match(/\d+(\.\d+)?/g).slice(0, 3).map(Number);
      const header = [...document.querySelectorAll(".site-header .lang-switch a, .site-header .lang-switch, .site-header .menu-btn, .site-header .logo__name, .site-header .nav__link")]
        .filter((e) => e.getClientRects().length && getComputedStyle(e).visibility !== "hidden")
        .map((e) => ({ ...box(e.getBoundingClientRect()), color: rgb(getComputedStyle(e).color) })).filter((r) => r.w > 0);
      range.selectNodeContents(h1);
      const h1r = box(range.getBoundingClientRect());
      const intro = document.querySelector(".hero__intro");
      range.selectNodeContents(intro);
      const actions = document.querySelector(".hero__actions").getBoundingClientRect();
      return {
        tier: h.dataset.tier, motion: h.dataset.motion, live: h.classList.contains("world-live"), cls: h.className,
        lines: tops.length, h1: h1r, intro: box(range.getBoundingClientRect()), header,
        actionsBottom: actions.bottom, vh: innerHeight, paused: window.hcwa?.state.paused ?? null,
        pausePressed: document.querySelector(".hero__pause").dataset.paused,
        font: getComputedStyle(h1).fontSize,
      };
    });

    // The world (or stills) behind the text: hide text and draft outlines, keep scrims and fills.
    await page.evaluate((css) => {
      const st = document.createElement("style");
      st.textContent = css;
      document.head.append(st);
    }, BARE);
    await page.waitForTimeout(120);
    const bare = await page.screenshot({ scale: "css" });
    const [h1px, introPx, ...headPx] = await pixels(page, bare, [s.h1, s.intro, ...s.header]);
    const headerMin = headPx.length ? Math.min(...headPx.map((p, i) => contrast(p.lum1, lum(s.header[i].color)))) : null;
    // The darkest sun state: the shaded base with no light at all (sun patches, the cloud dip and the
    // pointer only add light to it). Measured on the stills; the live rest frame matches them.
    let shade = null;
    if (!s.live && js) {
      await page.evaluate(() => document.querySelectorAll(".lit__light, .lit__screen").forEach((e) => e.style.setProperty("visibility", "hidden", "important")));
      await page.waitForTimeout(80);
      const dark = await pixels(page, await page.screenshot({ scale: "css" }), [s.h1, s.intro, ...s.header]);
      shade = { h1: +contrast(dark[0].lum1).toFixed(2), intro: +contrast(dark[1].lum1).toFixed(2), header: dark.length > 2 ? +Math.min(...dark.slice(2).map((p, i) => contrast(p.lum1, lum(s.header[i].color)))).toFixed(2) : null };
      await page.evaluate(() => document.querySelectorAll(".lit__light, .lit__screen").forEach((e) => e.style.removeProperty("visibility")));
    }

    // Handoff: the live rest frame against the stills it replaced (text hidden). Dropping world-live brings
    // the stills back over 400 ms; the canvas hides under them.
    let handoff = null;
    if (m.live === true && s.live) {
      await page.evaluate(() => window.__hcwaWorld?.redraw());
      const liveShot = await page.screenshot({ scale: "css" });
      await page.evaluate(() => document.documentElement.classList.remove("world-live"));
      await page.waitForTimeout(700);
      const stillShot = await page.screenshot({ scale: "css" });
      await page.evaluate(() => document.documentElement.classList.add("world-live"));
      await page.waitForTimeout(700);
      handoff = await diff(page, liveShot, stillShot);
    }

    // Paused live world: two frames 700 ms apart are identical; running, they differ (motes).
    let frozen = null;
    if (m.live === true && s.live) {
      // SwiftShader draws a frame in hundreds of ms, so the world clock says whether it moves; pixels say it froze.
      const clock = () => page.evaluate(() => window.__hcwaWorld?.room.U.uTime.value);
      const t0 = await clock(); await page.waitForTimeout(1200); const t1 = await clock();
      await page.evaluate(() => document.querySelector(".hero__pause").click());
      await page.waitForTimeout(600);
      const c = await page.screenshot(); const t2 = await clock(); await page.waitForTimeout(1200); const d = await page.screenshot(); const t3 = await clock();
      frozen = { movingBefore: t1 > t0, frozenAfter: c.equals(d) && t3 === t2, worldPaused: await page.evaluate(() => window.__hcwaWorld?.paused()) };
    }

    let overflow320 = null;
    if (size === "390") {
      await page.setViewportSize({ width: 320, height: 700 });
      await page.waitForTimeout(300);
      overflow320 = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    }

    const three = requests.some(isThree), world = requests.some((u) => /\/assets\/world-/.test(u));
    const row = { tag, tier: s.tier, motion: s.motion, live: s.live, three, world, errors: errors.length, lines: s.lines, font: s.font, face: +(h1px.face * 100).toFixed(2), h1C: +contrast(h1px.lum1).toFixed(2), introC: +contrast(introPx.lum1).toFixed(2), headerC: headerMin && +headerMin.toFixed(2), shade, handoff, firstScreen: s.actionsBottom <= s.vh, overflow320, frozen, info: info.join(" | ") };
    summary.push(row);
    console.log(`\n# ${tag}  ${url}\n  ${JSON.stringify(row)}`);
    if (errors.length) console.log("  errors:", errors.join(" || "));

    ok(errors.length === 0, `${tag}: 0 console errors`);
    const mustNotLoad = ["t0", "nowebgl", "reduced", "paused", "paused-now", "nojs"].includes(mode) || (mode === "default" && !s.live);
    if (mustNotLoad) ok(!three, `${tag}: three.js not requested`);
    if (m.live === true) ok(s.live, `${tag}: world live`);
    ok(s.lines <= 3, `${tag}: H1 ${s.lines} lines`);
    ok(h1px.face < 0.002, `${tag}: no screen silhouette under the H1 (${(h1px.face * 100).toFixed(2)}% face pixels)`);
    ok(contrast(h1px.lum1) >= 4.5, `${tag}: H1 contrast ${contrast(h1px.lum1).toFixed(2)} >= 4.5`);
    ok(contrast(introPx.lum1) >= 7, `${tag}: intro contrast ${contrast(introPx.lum1).toFixed(2)} >= 7`);
    if (headerMin != null) ok(headerMin >= 7, `${tag}: header text contrast ${headerMin.toFixed(2)} >= 7`);
    if (shade) ok(shade.h1 >= 4.5 && shade.intro >= 7 && (shade.header == null || shade.header >= 7), `${tag}: contrast over the shaded base (darkest sun state) H1 ${shade.h1} / intro ${shade.intro} / header ${shade.header}`);
    ok(s.actionsBottom <= s.vh, `${tag}: H1 and both buttons inside the first screen (${Math.round(s.actionsBottom)} <= ${s.vh})`);
    if (overflow320 != null) ok(overflow320 <= 0, `${tag}: no sideways scroll at 320 (${overflow320})`);
    if (handoff) ok(handoff.mean <= 6, `${tag}: handoff matches the stills (mean diff ${handoff.mean}/255, ${handoff.far}% of pixels off by > 24)`);
    if (frozen) ok(frozen.movingBefore && frozen.frozenAfter && frozen.worldPaused, `${tag}: pause freezes the live world (${JSON.stringify(frozen)})`);
    if (mode === "paused" || mode === "paused-now") ok(s.motion === "off" && s.pausePressed === "true", `${tag}: paused state shown (motion ${s.motion}, pressed ${s.pausePressed})`);
    await ctx.close();
  }
  fs.writeFileSync(path.join(out, "hero-matrix.json"), JSON.stringify(summary, null, 1));
  if (!process.env.NO_EXTRAS) fs.writeFileSync(path.join(out, "hero-extras.json"), JSON.stringify(await extras(browsers.gpu), null, 1));
  for (const b of Object.values(browsers)) await b.close();
  console.log(`\n${fails ? fails + " FAIL" : "all PASS"} · three.js chunk(s): ${threeChunks.join(", ")}`);
  process.exitCode = fails ? 1 : 0;
})();
