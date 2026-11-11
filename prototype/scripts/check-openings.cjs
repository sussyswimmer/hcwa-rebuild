// Frame strips and checks for the openings (PLAN-M4 5, 8; M6): Contact, News and Get Involved, VI and EN,
// 390 and 1440. M6 adds a vertical hard-line scan inside the stage on every frame, and a Tier 1 check that
// the motion is clearly visible.
// Serve a build first (any static server), then:
//   node scripts/check-openings.cjs http://localhost:4173 ../qa/m4/openings [frames=16]
// For each page and size it scrolls from the top to a little past the point where the stage lets go
// (--s3d-end) in equal steps and saves one screenshot per step plus a contact sheet of them all
// (<page>-<lang>-<width>-strip.png). At every step it checks: one H1, no horizontal scroll, the
// readable text it can see has 4.5:1 (H1) or 7:1 (running text) against the pixels behind it (sampled
// from the screenshot), and the H1 has not moved except with the scroll. Then the rest frames: reduced
// motion, ?tier=0 and no JS must show every word with no stage. Exits 1 on any FAIL.
// Playwright is preinstalled outside the project (require("playwright")).
const { chromium } = require("playwright");
const fs = require("fs");
const path = require("path");

const base = (process.argv[2] || "http://localhost:4173").replace(/\/$/, "");
const out = process.argv[3] || "../qa/m4/openings";
const FRAMES = Number(process.argv[4] || 16);
const PAGES = [
  ["contact", "/lien-he/", "/en/contact/"],
  ["news", "/tin-tuc/", "/en/news/"],
  ["involve", "/chung-tay/", "/en/get-involved/"],
].filter(([n]) => !process.env.ONLY || process.env.ONLY === n);
const SIZES = [[390, 844], [1440, 900]];
fs.mkdirSync(out, { recursive: true });
let failed = 0;
const ok = (cond, msg) => { if (!cond) { failed++; console.log("FAIL " + msg); } else if (process.env.VERBOSE) console.log("PASS " + msg); };

// Runs in the page: the text a visitor can read right now, with its colour and the box it covers.
function visibleText() {
  const vh = innerHeight, out = [];
  // Any CSS colour (rgb, oklab, color-mix…) to 0-255 RGBA, through a 1×1 canvas.
  const g = (window.__px ??= document.createElement("canvas").getContext("2d", { willReadFrequently: true }));
  const paint = (c, solid) => { g.clearRect(0, 0, 1, 1); g.fillStyle = solid ? "#fff" : "rgba(0,0,0,0)"; g.fillRect(0, 0, 1, 1); g.fillStyle = c; g.fillRect(0, 0, 1, 1); return [...g.getImageData(0, 0, 1, 1).data]; };
  const els = document.querySelectorAll("main h1, main h2, main h3, main p, main li, main a, main dt, main dd, main label, main time");
  for (const el of els) {
    if (!el.childNodes.length || ![...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim())) continue;
    const r = el.getBoundingClientRect();
    if (r.width < 2 || r.height < 2 || r.bottom < 70 || r.top > vh - 4) continue;
    const cs = getComputedStyle(el);
    let alpha = 1;
    for (let e = el; e; e = e.parentElement) alpha *= Number(getComputedStyle(e).opacity);
    // Text still fading in (a reveal) is not judged mid-fade; the frame after it lands is.
    if (cs.visibility === "hidden" || alpha < 0.99 || el.closest("[aria-hidden='true'], .sr-only, [hidden]")) continue;
    // Skip text a stage layer covers on purpose (it never does, but say so if it did).
    const cx = Math.min(innerWidth - 1, Math.max(0, r.left + Math.min(r.width, 40) / 2)), cy = Math.min(vh - 1, Math.max(0, r.top + r.height / 2));
    const top = document.elementFromPoint(cx, cy);
    if (top && !el.contains(top) && !top.contains(el)) continue;
    // An element with its own solid fill (a chip, a button) is judged against that fill.
    const own = paint(cs.backgroundColor);
    out.push({ tag: el.tagName, text: el.textContent.trim().slice(0, 40), color: paint(cs.color, true), own: own && own[3] > 230 ? own : null, size: parseFloat(cs.fontSize), weight: Number(cs.fontWeight), box: [r.left, r.top, r.width, r.height] });
  }
  return out;
}

// Runs in the page: each notice's box (with its transform) against the board's content box, and the
// notice's turn about its pin (rotateX, from its computed matrix: atan2(m23, m22)).
function stageNotices() {
  const board = document.querySelector(".newswall__board").getBoundingClientRect();
  const rects = [...document.querySelectorAll(".notice")].map((el, i) => {
    const r = el.getBoundingClientRect();
    const t = getComputedStyle(el).transform;
    let deg = 0;
    if (t && t !== "none") {
      const m = t.match(/matrix3d\(([^)]+)\)/);
      if (m) { const v = m[1].split(",").map(Number); deg = Math.abs((Math.atan2(v[6], v[5]) * 180) / Math.PI); }
    }
    return { i, left: r.left, right: r.right, top: r.top, deg };
  }).filter((r) => r.right > r.left);
  return { gutter: [board.left, board.right], rects };
}

// Runs in the page: the Contact still's base image, css px drawn per px of the largest source file it
// offers, at the middle of the picture: the layout box's cover factor times the camera's dolly (the turn
// enlarges the near edge a little more; that is perspective, not resolution).
function stillScale() {
  const img = document.querySelector(".s3d--contact .lit__base img");
  if (!img || !img.currentSrc || !img.naturalWidth) return 0;
  const cover = Math.max(img.offsetWidth / img.naturalWidth, img.offsetHeight / img.naturalHeight);
  const lit = getComputedStyle(img.closest(".lit"));
  const dolly = 1 + (parseFloat(lit.getPropertyValue("--zoom")) || 0) * (parseFloat(lit.getPropertyValue("--s3d-cam")) || 0);
  const sources = [...img.closest("picture").querySelectorAll("source")].filter((s) => !s.media || matchMedia(s.media).matches);
  const widths = sources.flatMap((s) => [...s.srcset.matchAll(/ (\d+)w/g)].map((m) => Number(m[1])));
  const max = Math.max(img.naturalWidth, ...widths);
  return (cover * dolly * img.naturalWidth) / max;
}

const lum = ([r, g, b]) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p); return (x + 0.05) / (y + 0.05); };
const rgb = (s) => (Array.isArray(s) ? s.slice(0, 3) : s.match(/[\d.]+/g).slice(0, 3).map(Number));

// M6: the box a stage's picture is drawn in, and the boxes of objects in it (data-art) and of the words.
const STAGE_BOX = { contact: ".s3d--contact .lit-head__media", news: ".newswall__bg", involve: ".gi-stage" };
function stageBoxes(sel) {
  const box = document.querySelector(sel)?.getBoundingClientRect();
  // An object's soft shadow reaches past its box: 16 px around art, 2 px around words.
  const skip = [...document.querySelectorAll("[data-art], main h1, main h2, main p, main a, main li, main .btn, main nav")]
    .map((e) => [e.getBoundingClientRect(), e.hasAttribute("data-art") ? 16 : 0]).filter(([r]) => r.width > 1 && r.height > 1).map(([r, m]) => [r.left - m, r.top - m, r.right + m, r.bottom + m]);
  return box ? { box: [Math.max(0, box.left), Math.max(0, box.top), Math.min(innerWidth, box.right), Math.min(innerHeight, box.bottom)], skip } : null;
}
const L = (d, k) => 0.2126 * d[k] + 0.7152 * d[k + 1] + 0.0722 * d[k + 2];
// A vertical hard line: a column pair 2 px apart whose luminance steps by more than 24 levels over more
// than half the box's height (outside the words and the objects). Returns the worst column's share.
function hardColumns(png, { box: [x0, y0, x1, y1], skip }) {
  const H = Math.max(1, Math.round(y1 - y0)), out = [];
  for (let x = Math.round(x0) + 1; x < Math.round(x1) - 3; x++) {
    let n = 0;
    for (let y = Math.round(y0); y < Math.round(y1); y++) {
      if (skip.some(([a, b, c, d]) => x + 2 >= a - 2 && x <= c + 2 && y >= b - 2 && y <= d + 2)) continue;
      const k = (y * png.w + x) * 4;
      if (Math.abs(L(png.d, k) - L(png.d, k + 8)) > 24) n++;
    }
    out.push([n / H, x]);
  }
  return out.sort((a, b) => b[0] - a[0])[0] ?? [0, 0];
}
// How much the picture changes between two frames inside the box (mean absolute difference, 0-255).
// dy: how far the box moved up between the frames (0 for a pinned stage): the same point of the picture
// is compared, so scrolling alone never counts as motion.
function meanDiff(a, b, [x0, y0, x1, y1], dy = 0) {
  let sum = 0, n = 0;
  for (let y = Math.round(y0); y < Math.round(y1); y += 2) for (let x = Math.round(x0); x < Math.round(x1); x += 2) {
    const yb = y - dy;
    if (yb < 0 || yb >= b.h) continue;
    const k = (y * a.w + x) * 4, kb = (yb * b.w + x) * 4;
    sum += (Math.abs(a.d[k] - b.d[kb]) + Math.abs(a.d[k + 1] - b.d[kb + 1]) + Math.abs(a.d[k + 2] - b.d[kb + 2])) / 3; n++;
  }
  return n ? sum / n : 0;
}

(async () => {
  const browser = await chromium.launch();
  // The background under a text box, from a screenshot with the text itself hidden: the 92nd-percentile
  // contrast loss, i.e. the lightest (or darkest) pixels near the words, not the average.
  const sampleBehind = async (page, items) => {
    if (!items.length) return [];
    await page.addStyleTag({ content: "main * { color: transparent !important; text-shadow: none !important; } main svg, main .icon { visibility: hidden !important; }" }).then((h) => h.evaluate((n) => n.setAttribute("data-probe", "")));
    const shot = await page.screenshot({ type: "png" });
    await page.evaluate(() => document.querySelector("style[data-probe]")?.remove());
    const png = await decodePng(shot);
    return items.map((it) => {
      const [x, y, w, h] = it.box.map(Math.round);
      const fg = rgb(it.color), vals = [];
      if (it.own) return ratio(fg, rgb(it.own));
      for (let j = Math.max(0, y); j < Math.min(png.h, y + h); j += 3) for (let i = Math.max(0, x); i < Math.min(png.w, x + w); i += 3) {
        const k = (j * png.w + i) * 4; vals.push(ratio(fg, [png.d[k], png.d[k + 1], png.d[k + 2]]));
      }
      vals.sort((a, b) => a - b);
      return vals.length ? vals[Math.floor(vals.length * 0.08)] : 21;
    });
  };

  for (const [name, vi, en] of PAGES) for (const [lang, url] of [["vi", vi], ["en", en]]) for (const [w, h] of SIZES) {
    const tag = `${name}-${lang}-${w}`;
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
    const page = await ctx.newPage();
    const errors = [];
    page.on("pageerror", (e) => errors.push(e.message));
    page.on("console", (m) => m.type() === "error" && errors.push(m.text()));
    page.on("response", (r) => r.status() >= 400 && errors.push(r.status() + " " + r.url()));
    // Tier 2 on desktop, Tier 1 on phones, motion on (the flags only exist on staging).
    await page.goto(`${base}${url}?tier=${w > 600 ? 2 : 1}`, { waitUntil: "networkidle" });
    // The probe below hides the text to sample what is behind it: colour transitions would smear it.
    await page.addStyleTag({ content: "main *, main *::before, main *::after { transition: none !important; }" });
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(2600); // the sun-in (2.28 s) comes to rest
    const end = await page.evaluate(() => parseFloat(getComputedStyle(document.querySelector("[data-scroll3d]")).getPropertyValue("--s3d-end")) || 0);
    ok(end > h * 0.3, `${tag}: stage has a scroll range (${end}px)`);
    const span = end * 1.18;
    const shots = [];
    let h1Start = null, maxScale = 0;
    for (let i = 0; i < FRAMES; i++) {
      const y = Math.round((span * i) / (FRAMES - 1));
      await page.evaluate((y) => window.scrollTo(0, y), y);
      await page.waitForTimeout(120);
      const state = await page.evaluate(() => {
        const h1 = document.querySelectorAll("h1");
        const s = document.querySelector("[data-scroll3d]");
        return { h1: h1.length, h1Top: h1[0]?.getBoundingClientRect().top + scrollY, sx: document.documentElement.scrollWidth > innerWidth, p: getComputedStyle(s).getPropertyValue("--s3d-p"), y: scrollY };
      });
      if (h1Start == null) h1Start = state.h1Top;
      ok(state.h1 === 1, `${tag} f${i}: one H1 (${state.h1})`);
      ok(!state.sx, `${tag} f${i}: no horizontal scroll`);
      ok(Math.abs(state.h1Top - h1Start) < 1.5, `${tag} f${i}: H1 moves only with the scroll`);
      const file = path.join(out, `${tag}-f${String(i).padStart(2, "0")}.png`);
      await page.screenshot({ path: file });
      // News: every notice stays inside the content gutter at every frame, and a notice already in the
      // first screen at scroll 0 is flat or turned by 3° at most.
      if (name === "news") {
        const n = await page.evaluate(stageNotices);
        for (const r of n.rects) ok(r.left >= n.gutter[0] - 0.5 && r.right <= n.gutter[1] + 0.5, `${tag} f${i}: notice ${r.i} inside the gutter (${r.left.toFixed(1)}..${r.right.toFixed(1)} in ${n.gutter[0].toFixed(1)}..${n.gutter[1].toFixed(1)})`);
        if (i === 0) for (const r of n.rects) if (r.top < h * 0.8) ok(r.deg <= 3, `${tag} f0: notice ${r.i} in the first screen is flat or nearly (${r.deg.toFixed(1)}°)`);
      }
      // Contact: the still is never shown far past its own resolution (css px per source px, at 1x).
      if (name === "contact") {
        const m = await page.evaluate(stillScale);
        if (m) { maxScale = Math.max(maxScale, m); ok(m <= (w > 600 ? 1.3 : 0.75), `${tag} f${i}: still at ${m.toFixed(2)} css px per source px`); }
      }
      if (name !== "news") {
        const boxes = await page.evaluate(stageBoxes, STAGE_BOX[name]);
        // Only while at least a quarter of the screen shows the stage (a sliver at the top is all edge).
        if (boxes && boxes.box[3] - boxes.box[1] > h * 0.25) {
          const png = await decodePng(await page.screenshot({ type: "png" }));
          const [share, x] = hardColumns(png, boxes);
          ok(share <= 0.5, `${tag} f${i}: no vertical hard line in the stage (worst column x ${x}: ${(share * 100).toFixed(0)}% of its height)`);
        }
      }
      const items = await page.evaluate(visibleText);
      // There is always something to read on screen (no frame of only the stage).
      ok(items.length > 0, `${tag} f${i}: some text on screen`);
      const ratios = await sampleBehind(page, items);
      items.forEach((it, k) => {
        const large = it.size >= 24 || (it.size >= 18.66 && it.weight >= 700);
        const need = it.tag === "H1" || large ? 4.5 : 7;
        ok(ratios[k] >= need, `${tag} f${i} (p ${Number(state.p).toFixed(2)}): "${it.text}" ${ratios[k].toFixed(2)}:1 < ${need}`);
      });
      shots.push([file, Number(state.p).toFixed(2), state.y]);
    }
    ok(errors.length === 0, `${tag}: no console errors or 4xx (${errors.join("; ")})`);
    if (maxScale) console.log(`${tag}: still at most ${maxScale.toFixed(2)} css px per source px (x DPR for device pixels)`);
    // Contact sheet: every frame scaled into a grid, the progress under each.
    const cols = w > 600 ? 4 : 8, sw = w > 600 ? 480 : 195, sh = Math.round((sw * h) / w);
    const sheet = await browser.newPage({ viewport: { width: cols * (sw + 8) + 8, height: Math.ceil(FRAMES / cols) * (sh + 30) + 8 } });
    const cells = shots.map(([f, p, y]) => `<figure><img src="data:image/png;base64,${fs.readFileSync(f).toString("base64")}"><figcaption>p ${p} · y ${y}</figcaption></figure>`).join("");
    await sheet.setContent(`<style>body{margin:0;padding:4px;background:#222;display:grid;grid-template-columns:repeat(${cols},${sw}px);gap:8px 8px;font:12px sans-serif;color:#ddd}figure{margin:0}img{width:${sw}px;height:${sh}px;display:block}</style>${cells}`);
    await sheet.screenshot({ path: path.join(out, `${tag}-strip.png`), fullPage: true });
    await sheet.close();
    for (const [f] of shots) if (!process.env.KEEP_FRAMES) fs.unlinkSync(f);
    await ctx.close();
  }

  // M6: the motion is clearly visible on Tier 1 too (not only on Tier 2): the picture inside the stage
  // box differs between strip frames 0 and 8 (of 16) by more than 18 levels on average.
  for (const [name, vi] of PAGES) for (const [w, h] of SIZES) {
    if (name === "news") continue;
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
    const page = await ctx.newPage();
    await page.goto(`${base}${vi}?tier=1`, { waitUntil: "networkidle" });
    await page.waitForTimeout(2600);
    const end = await page.evaluate(() => parseFloat(getComputedStyle(document.querySelector("[data-scroll3d]")).getPropertyValue("--s3d-end")) || 0);
    const box = (await page.evaluate(stageBoxes, STAGE_BOX[name]))?.box;
    const a = await decodePng(await page.screenshot({ type: "png" }));
    await page.evaluate((y) => window.scrollTo(0, y), Math.round((end * 1.18 * 8) / (FRAMES - 1)));
    await page.waitForTimeout(400);
    const b = await decodePng(await page.screenshot({ type: "png" }));
    // The stage is pinned on Contact; on Get Involved it scrolls with the page, so the same point of the
    // picture is compared (only the visible part of the box in both frames).
    const dy = name === "contact" ? 0 : await page.evaluate(() => Math.round(scrollY));
    const area = box && [box[0], Math.max(box[1], dy), box[2], box[3]];
    const d = area ? meanDiff(a, b, area, dy) : 0;
    ok(d > 18, `${name}-vi-${w} tier 1: the stage visibly moves (mean difference ${d.toFixed(1)} levels between frames 0 and 8)`);
    console.log(`${name}-vi-${w} tier 1: frames 0 → 8 differ by ${d.toFixed(1)} levels`);
    await ctx.close();
  }

  // Rest frames: every word present, nothing pinned, no stage motion.
  for (const [name, vi, en] of PAGES) for (const [mode, opts, q] of [["reduced", { reducedMotion: "reduce" }, ""], ["tier0", {}, "?tier=0"], ["nojs", { javaScriptEnabled: false }, ""]]) for (const [w, h] of SIZES) {
    const ctx = await browser.newContext({ viewport: { width: w, height: h }, ...opts });
    const page = await ctx.newPage();
    await page.goto(`${base}${en}${q}`, { waitUntil: "networkidle" });
    const st = await page.evaluate(() => {
      const s = document.querySelector("[data-scroll3d]");
      const pinned = [...s.querySelectorAll("*")].some((e) => getComputedStyle(e).position === "sticky");
      const hidden = [...document.querySelectorAll("main h1, main h2, main h3, main p, main a")].filter((e) => !e.closest("[hidden], .sr-only, [aria-hidden='true'], dialog, .form__success, .form__summary, [data-filter-empty]") && getComputedStyle(e).visibility === "hidden").length;
      return { pinned, hidden, h: document.documentElement.scrollHeight, sx: document.documentElement.scrollWidth > innerWidth, h1: document.querySelectorAll("h1").length };
    });
    const tag = `${name}-en-${w}-${mode}`;
    ok(!st.pinned, `${tag}: rest frame (nothing pinned)`);
    ok(st.hidden === 0, `${tag}: no hidden text (${st.hidden})`);
    ok(!st.sx && st.h1 === 1, `${tag}: one H1, no horizontal scroll`);
    await page.screenshot({ path: path.join(out, `${tag}.png`), fullPage: false });
    await ctx.close();
  }
  await browser.close();
  console.log(failed ? `${failed} FAIL` : "all openings checks pass");
  process.exit(failed ? 1 : 0);
})();

// A small PNG decoder (8-bit RGB/RGBA, no interlace), enough for Chromium screenshots.
async function decodePng(buf) {
  const zlib = require("zlib");
  let p = 8, w = 0, h = 0, ct = 0;
  const idat = [];
  while (p < buf.length) {
    const len = buf.readUInt32BE(p), type = buf.toString("ascii", p + 4, p + 8), data = buf.subarray(p + 8, p + 8 + len);
    if (type === "IHDR") { w = data.readUInt32BE(0); h = data.readUInt32BE(4); ct = data[9]; }
    else if (type === "IDAT") idat.push(data);
    p += 12 + len;
  }
  const raw = zlib.inflateSync(Buffer.concat(idat)), bpp = ct === 6 ? 4 : 3, stride = w * bpp, d = Buffer.alloc(w * h * 4);
  let prev = Buffer.alloc(stride);
  for (let y = 0; y < h; y++) {
    const f = raw[y * (stride + 1)], line = Buffer.from(raw.subarray(y * (stride + 1) + 1, (y + 1) * (stride + 1)));
    for (let i = 0; i < stride; i++) {
      const a = i >= bpp ? line[i - bpp] : 0, b = prev[i], c = i >= bpp ? prev[i - bpp] : 0;
      const pa = Math.abs(b - c), pb = Math.abs(a - c), pc = Math.abs(a + b - 2 * c);
      line[i] = (line[i] + [0, a, b, (a + b) >> 1, pa <= pb && pa <= pc ? a : pb <= pc ? b : c][f]) & 255;
    }
    for (let x = 0; x < w; x++) { const s = x * bpp, t = (y * w + x) * 4; d[t] = line[s]; d[t + 1] = line[s + 1]; d[t + 2] = line[s + 2]; d[t + 3] = 255; }
    prev = line;
  }
  return { w, h, d };
}
