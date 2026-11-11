// Browser checks for the page layer (PLAN section 6, A2-A4, A9): sun-in, reduced motion, ?tier=0, no JS,
// pause toggle persistence, bus events, skip link. Run against a built preview:
//   npm run build && npx vite preview --port 4173 & node scripts/check-page.cjs http://localhost:4173
// Exits 1 on any FAIL. Playwright is preinstalled outside the project (require("playwright")).
const { chromium } = require("playwright");
const zlib = require("zlib");
const base = process.argv[2];

// A 64x64 PNG with a soft translucent patch, standing in for the light layer until it is rendered.
function png() {
  const w = 64, h = 64, raw = Buffer.alloc((w * 4 + 1) * h);
  for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) {
    const i = y * (w * 4 + 1) + 1 + x * 4, d = Math.hypot(x - 40, y - 24) / 30;
    raw[i] = 255; raw[i + 1] = 220; raw[i + 2] = 150; raw[i + 3] = Math.max(0, 1 - d) * 255;
  }
  const crc = (b) => { let c = ~0; for (const x of b) { c ^= x; for (let k = 0; k < 8; k++) c = c & 1 ? (c >>> 1) ^ 0xedb88320 : c >>> 1; } return ~c >>> 0; };
  const chunk = (t, d) => { const l = Buffer.alloc(4); l.writeUInt32BE(d.length); const td = Buffer.concat([Buffer.from(t), d]); const c = Buffer.alloc(4); c.writeUInt32BE(crc(td)); return Buffer.concat([l, td, c]); };
  const ihdr = Buffer.alloc(13); ihdr.writeUInt32BE(w, 0); ihdr.writeUInt32BE(h, 4); ihdr[8] = 8; ihdr[9] = 6;
  return Buffer.concat([Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]), chunk("IHDR", ihdr), chunk("IDAT", zlib.deflateSync(raw)), chunk("IEND", Buffer.alloc(0))]);
}
const PNG = png();
const ok = (cond, msg) => { console.log((cond ? "PASS " : "FAIL ") + msg); if (!cond) process.exitCode = 1; };

(async () => {
  const browser = await chromium.launch();
  const fresh = async (opts = {}) => {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, ...opts });
    const requests = [];
    ctx.on("request", (r) => requests.push(r.url()));
    await ctx.route(/\/posters\/hero-.*-light(room|screen)-/, (route) => route.fulfill({ status: 200, contentType: "image/png", body: PNG }));
    return { ctx, requests };
  };

  // 1. Sun-in: pending at first paint, running after decode, at rest after ~2.3 s, once per session.
  {
    const { ctx } = await fresh();
    const page = await ctx.newPage();
    const errors = [];
    page.on("pageerror", (e) => errors.push(e.message));
    await page.goto(base + "/", { waitUntil: "commit" });
    await page.waitForSelector(".lit__light img");
    const first = await page.evaluate(() => document.documentElement.className);
    ok(/sun-pending/.test(first) || /sun-in/.test(first), `sun-pending at first paint (${first})`);
    await page.waitForFunction(() => document.documentElement.classList.contains("sun-in"), null, { timeout: 3000 });
    const mid = await page.evaluate(() => [getComputedStyle(document.querySelector(".lit__light")).maskImage, getComputedStyle(document.querySelector(".lit__light")).animationName]);
    ok(/gradient/.test(mid[0]) && /sun-fade/.test(mid[1]), `sun-in runs mask sweep + fade (${mid[1]})`);
    const t0 = Date.now();
    await page.waitForFunction(() => window.hcwa?.state.sunIn === "done", null, { timeout: 4000 });
    const took = Date.now() - t0;
    ok(took > 1700 && took < 2900, `sun-in finished after ~2.3 s (${took} ms)`);
    const rest = await page.evaluate(() => [document.documentElement.className, getComputedStyle(document.querySelector(".lit__light")).opacity, getComputedStyle(document.querySelector(".lit__light")).maskImage]);
    ok(!/sun-/.test(rest[0]) && rest[1] === "1" && rest[2] === "none", `light layer at rest, no mask (${rest.join(" | ")})`);
    await page.reload({ waitUntil: "load" });
    const again = await page.evaluate(() => document.documentElement.className);
    ok(!/sun-pending|sun-in/.test(again), "no replay on reload in the same session");
    ok(errors.length === 0, "no page errors " + errors.join("; "));
    await ctx.close();
  }

  // 2. Reduced motion: no motion chunk, no world, no sun-pending, light layer at rest, no pause toggle.
  {
    const { ctx, requests } = await fresh({ reducedMotion: "reduce" });
    const page = await ctx.newPage();
    await page.goto(base + "/", { waitUntil: "load" });
    await page.waitForTimeout(2500);
    const s = await page.evaluate(() => ({ cls: document.documentElement.className, motion: document.documentElement.dataset.motion, tier: document.documentElement.dataset.tier, op: getComputedStyle(document.querySelector(".lit__light")).opacity, pause: getComputedStyle(document.querySelector(".hero__pause")).display, lenis: document.documentElement.classList.contains("lenis") }));
    ok(s.motion === "off" && s.tier === "0" && !/sun-pending/.test(s.cls), `reduced motion: data-motion off, tier 0 (${JSON.stringify(s)})`);
    ok(s.op === "1" && s.pause === "none" && !s.lenis, "reduced motion: light at rest, pause toggle hidden, no Lenis");
    ok(!requests.some((u) => /motion-|three|world|\/live-/i.test(u)), "reduced motion: motion and world chunks never requested");
    await ctx.close();
  }

  // 3. ?tier=0 keeps the stills: motion yes, world no.
  {
    const { ctx, requests } = await fresh();
    const page = await ctx.newPage();
    await page.goto(base + "/?tier=0", { waitUntil: "load" });
    await page.waitForTimeout(3000);
    ok(await page.evaluate(() => document.documentElement.dataset.tier === "0"), "?tier=0 sets data-tier 0");
    ok(!requests.some((u) => /world|three|\/live-/i.test(u)), "?tier=0: world never requested");
    await ctx.close();
  }

  // 4. No JS: every text present, light layer visible at rest, pause toggle hidden.
  {
    const { ctx } = await fresh({ javaScriptEnabled: false });
    const page = await ctx.newPage();
    await page.goto(base + "/", { waitUntil: "load" });
    const s = await page.evaluate(() => ({ h1: document.querySelector("h1").innerText, op: getComputedStyle(document.querySelector(".lit__light")).opacity, pause: getComputedStyle(document.querySelector(".hero__pause")).display, canvas: getComputedStyle(document.querySelector(".world-canvas")).visibility }));
    ok(s.h1.length > 20 && s.op === "1" && s.pause === "none" && s.canvas === "hidden", `no JS: H1, light at rest, toggle hidden, canvas hidden (${JSON.stringify(s)})`);
    await ctx.close();
  }

  // 5. Pause toggle: state, bus event, persistence, next load behaves as reduced motion.
  {
    const { ctx, requests } = await fresh();
    const page = await ctx.newPage();
    await page.goto(base + "/", { waitUntil: "load" });
    await page.evaluate(() => { window.__pauses = []; window.hcwa.addEventListener("pause", (e) => window.__pauses.push(e.detail.paused)); });
    const label = () => page.evaluate(() => document.querySelector(".hero__pause").textContent.trim());
    ok((await label()) === "Tạm dừng chuyển động", "pause toggle named in Vietnamese on the VI page");
    await page.click(".hero__pause");
    const s = await page.evaluate(() => ({ pressed: document.querySelector(".hero__pause").dataset.paused, foot: document.querySelector(".site-footer [data-motion-toggle]").dataset.paused, motion: document.documentElement.dataset.motion, stored: localStorage.getItem("hcwa:motion"), events: window.__pauses, sun: window.hcwa.state.sunIn, lenis: document.documentElement.classList.contains("lenis") }));
    ok(s.pressed === "true" && s.foot === "true" && s.motion === "off" && s.stored === "off" && s.events.join() === "true", `pause: pressed, stored, bus event (${JSON.stringify(s)})`);
    ok(s.sun === "done" && !s.lenis, "pause: sun-in finished at rest, Lenis gone");
    // The label says what the next press does.
    ok((await label()) === "Bật lại chuyển động", "paused, the toggle offers to play again (Vietnamese)");
    requests.length = 0;
    await page.reload({ waitUntil: "load" });
    await page.waitForTimeout(2500);
    const r = await page.evaluate(() => ({ motion: document.documentElement.dataset.motion, tier: document.documentElement.dataset.tier, pressed: document.querySelector(".hero__pause").dataset.paused }));
    ok(r.motion === "off" && r.tier === "0" && r.pressed === "true", `next load: paused, tier 0 (${JSON.stringify(r)})`);
    ok(!requests.some((u) => /motion-|world|three|\/live-/i.test(u)), "next load: motion and world chunks never requested");
    await page.click(".hero__pause");
    ok(await page.evaluate(() => localStorage.getItem("hcwa:motion") === null && document.documentElement.dataset.motion === "on"), "resume clears the stored choice");
    await ctx.close();
  }

  // 6. Bus: hero progress and scroll velocity (spring) while scrolling with the wheel; header compacts.
  //    Progress is read only while a live world can use it (?tier=1 forces one past the GPU probe); a
  //    stills page switches ScrollTrigger off.
  {
    const { ctx } = await fresh();
    const page = await ctx.newPage();
    await page.goto(base + "/?tier=1", { waitUntil: "load" });
    // Lenis is created at the first wheel event (pointer: fine), so nothing to wait for but the page.
    await page.waitForFunction(() => window.hcwa?.state, null, { timeout: 5000 });
    await page.evaluate(() => { window.__v = []; window.__p = []; window.hcwa.addEventListener("velocity", (e) => window.__v.push(e.detail.value)); window.hcwa.addEventListener("progress", (e) => window.__p.push([e.detail.world, e.detail.progress])); });
    await page.mouse.move(700, 450);
    for (let i = 0; i < 6; i++) { await page.mouse.wheel(0, 120); await page.waitForTimeout(40); }
    await page.waitForTimeout(1500);
    // With a live world on a software GPU frames are slow, so the spring needs longer to reach rest. The M6 glide
    // (Lenis lerp 0.07, about 1.7 s from a 720 px flick to rest, was 1 s at 0.12) comes first, and the spring's own
    // dt is capped at 50 ms a frame, so under a busy software GPU it reaches 0 after up to ~10 s of wall time.
    await page.waitForFunction(() => window.hcwa.state.velocity === 0, null, { timeout: 12000 }).catch(() => {});
    const s = await page.evaluate(() => ({ v: window.__v, p: window.__p, y: scrollY, compact: document.querySelector("[data-header]").classList.contains("is-compact"), state: window.hcwa.state }));
    const peak = Math.max(...s.v);
    ok(s.v.length > 10 && peak > 0.1 && s.v.at(-1) === 0, `velocity events rise and settle to 0 (n=${s.v.length}, peak=${peak.toFixed(2)}, last=${s.v.at(-1)})`);
    ok(s.p.length > 3 && s.p.every(([w]) => w === "room") && s.p.at(-1)[1] > 0, `hero progress events (n=${s.p.length}, last=${s.p.at(-1)?.[1].toFixed(3)}, scrollY=${s.y})`);
    ok(s.compact, "header compacts after 80 px");
    await ctx.close();
  }

  // 7. EN page: toggle named in English; Tab starts at the skip link.
  {
    const { ctx } = await fresh();
    const page = await ctx.newPage();
    await page.goto(base + "/en/", { waitUntil: "load" });
    ok(await page.evaluate(() => document.querySelector(".hero__pause").textContent.trim() === "Pause motion"), "pause toggle named in English on the EN page");
    await page.keyboard.press("Tab");
    ok(await page.evaluate(() => document.activeElement.classList.contains("skip-link")), "first Tab lands on the skip link");
    await ctx.close();
  }
  // 8. Get Involved (M6 "Cửa mở"): the four ways and their buttons are above the fold at 390x844 and
  // 1440x900, VI and EN, with the staging bars shown; at 320 the page never scrolls sideways.
  for (const [w, h] of [[390, 844], [1440, 900], [320, 640]]) for (const path of ["/chung-tay/", "/en/get-involved/"]) {
    const { ctx } = await fresh({ viewport: { width: w, height: h } });
    const page = await ctx.newPage();
    await page.goto(base + path + "?tier=1", { waitUntil: "load" });
    await page.evaluate(() => document.fonts.ready);
    const r = await page.evaluate(() => ({
      bottoms: [...document.querySelectorAll(".gi-head .way__btn")].map((b) => b.getBoundingClientRect().bottom),
      vh: innerHeight, sx: document.documentElement.scrollWidth > innerWidth,
    }));
    ok(r.bottoms.length === 4, `${path} ${w}: four ways buttons in the header`);
    if (w >= 390) ok(r.bottoms.every((b) => b < r.vh), `${path} ${w}x${h}: every ways button above the fold (${r.bottoms.map(Math.round).join(", ")} < ${r.vh})`);
    ok(!r.sx, `${path} ${w}: no horizontal scroll`);
    await ctx.close();
  }
  // 9. Wheel smoothing (M6 fix): a filled textarea keeps the wheel (it scrolls, the page stays put) on every page
  // with a message box, and the News wall and the Little Rose garden never load the glide (native scroll).
  for (const path of ["/lien-he/", "/en/contact/", "/chung-tay/tinh-nguyen/", "/en/get-involved/volunteer/", "/chung-tay/doi-tac-doanh-nghiep/", "/en/get-involved/partner/"]) {
    const { ctx, requests } = await fresh();
    const page = await ctx.newPage();
    const ta = await page.goto(base + path + "?tier=1", { waitUntil: "load" }).then(() => page.$("main textarea"));
    if (!ta) { ok(false, `${path}: a textarea to test`); await ctx.close(); continue; }
    await page.mouse.move(5, 5);
    // The glide's chunk comes in after load + idle; Lenis is made in idle time.
    await page.waitForFunction(() => document.documentElement.classList.contains("lenis"), null, { timeout: 6000 }).catch(() => {});
    await ta.fill(Array.from({ length: 60 }, (_, i) => `Dòng ${i + 1} của một lời nhắn dài.`).join("\n"));
    await ta.evaluate((t) => { t.scrollTop = 0; t.scrollIntoView({ block: "center" }); });
    await page.waitForTimeout(300);
    const box = await ta.boundingBox();
    const y0 = await page.evaluate(() => scrollY);
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    for (let i = 0; i < 3; i++) { await page.mouse.wheel(0, 100); await page.waitForTimeout(60); }
    await page.waitForTimeout(900);
    const r = await page.evaluate(() => ({ y: scrollY, top: document.querySelector("main textarea").scrollTop, lenis: document.documentElement.classList.contains("lenis") }));
    ok(r.top >= 200 && Math.abs(r.y - y0) <= 2, `${path}: the wheel over a filled textarea scrolls the textarea (scrollTop ${r.top}), not the page (moved ${r.y - y0} px; glide ${r.lenis ? "on" : "off"})`);
    await ctx.close();
  }
  for (const path of ["/tin-tuc/", "/en/news/", "/hoat-dong/co-so/hoa-hong-nho/", "/en/our-work/centers/little-rose/"]) {
    const { ctx, requests } = await fresh();
    const page = await ctx.newPage();
    const res = await page.goto(base + path + "?tier=1", { waitUntil: "load" });
    if (res.status() >= 400) { ok(false, `${path}: ${res.status()}`); await ctx.close(); continue; }
    await page.mouse.move(700, 450);
    await page.waitForTimeout(2500);
    await page.mouse.wheel(0, 100);
    await page.waitForTimeout(300);
    const lenis = await page.evaluate(() => document.documentElement.classList.contains("lenis"));
    ok(!lenis && !requests.some((u) => /smooth-wheel/.test(u)), `${path}: native wheel (no glide chunk on a page with heavy scroll-linked paint)`);
    await ctx.close();
  }
  await browser.close();
})();
