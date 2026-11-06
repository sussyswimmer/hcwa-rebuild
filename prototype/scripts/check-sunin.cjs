// S1 Nắng vào, frame by frame (05-DESIGN D5.3): the CSS sun-in's animations are paused and seeked every
// 100 ms from 0 to 2200 ms (and at rest) with the hero text hidden, and each frame is compared with the rest frame.
//   seam:   no light edge within 20 px of the screen outline that the rest frame does not have (pixels
//           in that band brighter than at rest by > 12/255, as a share of the band)
//   change: share of pixels off the first frame by > 24/255 (>= 8% at 390); and between the 1700 and
//           1800 ms frames pixels still change (the light is still sliding at full strength)
// Run against a built preview: node scripts/check-sunin.cjs http://localhost:4173 [outdir]
const { chromium } = require("playwright");
const fs = require("fs");
const path = require("path");

const base = process.argv[2] || "http://localhost:4173";
const out = process.argv[3] && path.resolve(process.argv[3]);
if (out) fs.mkdirSync(out, { recursive: true });
const SIZES = { 390: { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 1 }, 1440: { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 } };
const HIDE = ".hero__inner, .site-header, .proto-note { visibility: hidden !important; }";
let fails = 0;
const ok = (c, m) => { console.log((c ? "PASS " : "FAIL ") + m); if (!c) fails++; };

(async () => {
  const { POSTER_FRAMES } = await import(path.join(__dirname, "../src/world/poster-frames.js"));
  const { bucketFor } = await import(path.join(__dirname, "../src/world/buckets.js"));
  const browser = await chromium.launch();
  const report = [];
  for (const size of ["390", "1440"]) {
    const ctx = await browser.newContext({ ...SIZES[size], locale: "vi-VN" });
    const page = await ctx.newPage();
    await page.goto(base + "/?tier=0", { waitUntil: "commit" });
    await page.waitForFunction(() => document.documentElement.classList.contains("sun-in"), null, { timeout: 8000 });
    const n = await page.evaluate((css) => {
      // A style read creates the CSS animations the class just switched on.
      document.querySelectorAll(".lit__light, .lit__screen, .lit__light img").forEach((e) => getComputedStyle(e).opacity);
      const list = document.getAnimations().filter((a) => /^sun-/.test(a.animationName ?? ""));
      list.forEach((a) => a.pause());
      const st = document.createElement("style"); st.textContent = css; document.head.append(st);
      return list.length;
    }, HIDE);
    ok(n >= 5, `${size}: the sun-in's animations are running (${n})`);
    // The screen outline of this bucket's still, in viewport px (cover, centred across, bottom-anchored).
    const { box, vw, vh } = await page.evaluate(() => { const r = document.querySelector('[data-world="room"]').getBoundingClientRect(); return { box: { x: r.left, y: r.top, w: r.width, h: r.height }, vw: innerWidth, vh: innerHeight }; });
    const b = bucketFor(vw, vh), f = POSTER_FRAMES[b.name], [pw, ph] = f.view;
    const k = Math.max(box.w / pw, box.h / ph), ox = box.x + (box.w - pw * k) / 2, oy = box.y + box.h - ph * k;
    const outline = f.silhouette.map(([x, y]) => [ox + x * k, oy + y * k]);
    const seek = (t) => page.evaluate((t) => document.getAnimations().filter((a) => /^sun-/.test(a.animationName ?? "")).forEach((a) => { a.currentTime = t; }), t);
    const shot = async () => (await page.screenshot()).toString("base64");
    const frames = [];
    for (let t = 0; t <= 2200; t += 100) { await seek(t); await page.waitForTimeout(60); frames.push([t, await shot()]); }
    // Last: reaching the end fires animationend, which ends the sun-in (the class goes, the layers rest).
    await seek(4000);
    await page.waitForTimeout(300);
    const rest = await shot();
    frames.push([2500, rest]);
    const stats = await page.evaluate(async ({ rest, frames, outline }) => {
      const load = async (b64) => { const i = new Image(); i.src = "data:image/png;base64," + b64; await i.decode(); const c = new OffscreenCanvas(i.width, i.height), g = c.getContext("2d", { willReadFrequently: true }); g.drawImage(i, 0, 0); return { d: g.getImageData(0, 0, i.width, i.height).data, w: i.width, h: i.height }; };
      const R = await load(rest), W = R.w, H = R.h;
      // Band: pixels within 20 px of an outline edge.
      const seg = (px, py, [ax, ay], [bx, by]) => { const dx = bx - ax, dy = by - ay, l = dx * dx + dy * dy || 1; const t = Math.max(0, Math.min(1, ((px - ax) * dx + (py - ay) * dy) / l)); return Math.hypot(px - ax - t * dx, py - ay - t * dy); };
      const band = [];
      for (let y = 0; y < H; y += 1) for (let x = 0; x < W; x += 1) {
        let d = Infinity;
        for (let i = 0; i < outline.length; i++) d = Math.min(d, seg(x, y, outline[i], outline[(i + 1) % outline.length]));
        if (d <= 20) band.push((y * W + x) * 4);
      }
      const first = await load(frames[0][1]);
      const out = [];
      let prev = first;
      for (const [t, b64] of frames) {
        const F = await load(b64);
        let edge = 0, changed = 0, moving = 0;
        for (const i of band) if ((F.d[i] + F.d[i + 1] + F.d[i + 2] - R.d[i] - R.d[i + 1] - R.d[i + 2]) / 3 > 12) edge++;
        for (let i = 0; i < F.d.length; i += 4) {
          if ((Math.abs(F.d[i] - first.d[i]) + Math.abs(F.d[i + 1] - first.d[i + 1]) + Math.abs(F.d[i + 2] - first.d[i + 2])) / 3 > 24) changed++;
          if ((Math.abs(F.d[i] - prev.d[i]) + Math.abs(F.d[i + 1] - prev.d[i + 1]) + Math.abs(F.d[i + 2] - prev.d[i + 2])) / 3 > 4) moving++;
        }
        prev = F;
        out.push({ t, seam: +((100 * edge) / Math.max(band.length, 1)).toFixed(2), change: +((100 * changed) / (W * H)).toFixed(2), moving: +((100 * moving) / (W * H)).toFixed(2) });
      }
      return { band: band.length, frames: out };
    }, { rest, frames, outline });
    if (out) for (const t of [0, 500, 1000, 1500, 2000]) fs.writeFileSync(path.join(out, `sunin-${size}-${t}ms.png`), Buffer.from(frames.find(([x]) => x === t)[1], "base64"));
    const seam = Math.max(...stats.frames.map((f) => f.seam));
    const last = stats.frames.at(-1).change;
    // Still sliding late: between the 1700 and 1800 ms frames pixels still change (> 4/255).
    const late = stats.frames.find((f) => f.t === 1800).moving;
    report.push({ size, bucket: b.name, ...stats, seam, last });
    console.log(`# ${size} (t:change/moving/seam %): ${stats.frames.map((f) => `${f.t}:${f.change}/${f.moving}/${f.seam}`).join(" ")}`);
    ok(seam < 0.5, `${size}: no light edge along the screen outline that the rest frame lacks (worst ${seam}% of a ${stats.band} px band)`);
    ok(late >= 0.05, `${size}: the light is still moving at 1700-1800 ms (${late}% of pixels change between those frames)`);
    if (size === "390") ok(last >= 8, `390: the sun-in changes ${last}% of pixels by > 24/255 (>= 8%)`);
    await ctx.close();
  }
  await browser.close();
  if (out) fs.writeFileSync(path.join(out, "sunin.json"), JSON.stringify(report, null, 1));
  process.exitCode = fails ? 1 : 0;
})();
