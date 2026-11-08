// Text-over-world contrast gate (05-DESIGN D1 and D0.5, PLAN A6). Renders every page whose text sits on a
// still or on the world's light, with the light at rest, hides the text of each line that sits there, and
// measures the line's own colour against the real pixels behind it (the 2nd percentile, so a stray
// antialiased pixel does not decide). The H1 and other large text (24 px, or 18.66 px bold: WCAG's large
// text) ≥ 4.5; running text ≥ 7.
// node scripts/light-contrast.mjs [base url, default http://localhost:4173] [report.json]
import { createRequire } from "node:module";
import { writeFileSync } from "node:fs";

const { chromium } = createRequire(import.meta.url)("playwright");
const BASE = process.argv[2] ?? "http://localhost:4173";
const OUT = process.argv[3];
const PAGES = [
  "/", "/en/",
  "/hoat-dong/", "/en/our-work/", "/hoat-dong/co-so/tre-xanh/", "/en/our-work/centers/green-bamboo/",
  "/hoat-dong/du-an/cung-vuon-len/", "/en/our-work/programs/thrive-together/",
  "/chung-tay/", "/en/get-involved/", "/lien-he/", "/en/contact/", "/404.html", "/en/404.html",
];
// Where text meets the world: the hero over its poster, the lit headers over their stills, the passage
// statement in the light, the corridor cards set into their views, help arriving on the sill.
const ZONES = [".hero__inner", ".lit-head__text", ".passage__text", ".bay__text", ".bays__head", ".gap__text", ".help .container > h2"];
const SIZES = [[1440, 900], [390, 844]];

const ratio = (a, b) => {
  const L = ([r, g, b]) => { const f = (c) => ((c /= 255) <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4); return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
  const [x, y] = [L(a), L(b)].sort((p, q) => q - p);
  return (x + 0.05) / (y + 0.05);
};

async function measure(browser, path, w, h) {
  // Reduced motion: the light layers sit at rest (opacity 1), the state every visitor ends on.
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, reducedMotion: "reduce", deviceScaleFactor: 1 });
  const page = await ctx.newPage();
  await page.goto(BASE + path, { waitUntil: "load" });
  await page.addStyleTag({ content: "[data-draft]{outline-color:transparent!important}" });
  await page.waitForTimeout(600);
  const lines = await page.evaluate((ZONES) => {
    const out = [];
    let n = 0;
    for (const zone of document.querySelectorAll(ZONES.join(","))) {
      for (const el of zone.querySelectorAll("*")) {
        const own = [...el.childNodes].some((t) => t.nodeType === 3 && t.textContent.trim());
        if (!own || el.closest(".sr-only, [aria-hidden=true], .btn, .status, .chip-pending, .chip, button, [hidden]")) continue;
        const cs = getComputedStyle(el);
        if (cs.visibility === "hidden" || cs.display === "none") continue;
        const r = document.createRange(); r.selectNodeContents(el);
        const rects = [...r.getClientRects()].filter((q) => q.width > 2 && q.height > 2);
        if (!rects.length) continue;
        const id = "lc" + n++;
        el.setAttribute("data-lc", id);
        const px = parseFloat(cs.fontSize), large = !!el.closest("h1") || px >= 24 || (px >= 18.66 && +cs.fontWeight >= 600);
        out.push({ id, tag: el.tagName.toLowerCase(), cls: el.className, text: el.textContent.trim().slice(0, 50), color: cs.color, large, rects: rects.map((q) => ({ x: q.left + scrollX, y: q.top + scrollY, w: q.width, h: q.height })) });
      }
    }
    return out;
  }, ZONES);
  // Hide the measured text (and its underline) so the pixels behind it are what the screenshot sees.
  await page.addStyleTag({ content: "[data-lc],[data-lc] *{color:transparent!important;-webkit-text-fill-color:transparent!important;text-decoration-color:transparent!important;text-shadow:none!important}[data-lc] svg{visibility:hidden!important}" });
  await page.waitForTimeout(150);
  const results = [];
  for (const line of lines) {
    const fg = line.color.match(/[\d.]+/g).slice(0, 3).map(Number);
    let px = [];
    for (const q of line.rects) {
      const clip = { x: Math.max(0, q.x), y: Math.max(0, q.y), width: Math.max(1, Math.min(q.w, w - q.x)), height: Math.max(1, q.h) };
      await page.evaluate((y) => window.scrollTo(0, Math.max(0, y - 200)), q.y);
      const buf = await page.screenshot({ clip, fullPage: true });
      px = px.concat(await decode(page, buf));
    }
    const rs = px.map((p) => ratio(fg, p)).sort((a, b) => a - b);
    const p02 = rs[Math.floor(rs.length * 0.02)] ?? 0, med = rs[Math.floor(rs.length / 2)] ?? 0;
    const need = line.large ? 4.5 : 7;
    results.push({ path, w, tag: line.tag, cls: line.cls, text: line.text, color: line.color, large: line.large, p02: +p02.toFixed(2), median: +med.toFixed(2), need, pass: p02 >= need });
  }
  await ctx.close();
  return results;
}

// PNG → RGB samples, decoded by the browser itself (no image library needed), every 2nd pixel.
async function decode(page, buf) {
  return page.evaluate(async (b64) => {
    const bmp = await createImageBitmap(await (await fetch("data:image/png;base64," + b64)).blob());
    const c = new OffscreenCanvas(bmp.width, bmp.height); const g = c.getContext("2d"); g.drawImage(bmp, 0, 0);
    const d = g.getImageData(0, 0, bmp.width, bmp.height).data; const out = [];
    for (let i = 0; i < d.length; i += 8) out.push([d[i], d[i + 1], d[i + 2]]);
    return out;
  }, buf.toString("base64"));
}

const browser = await chromium.launch();
const all = [];
for (const p of PAGES) for (const [w, h] of SIZES) all.push(...(await measure(browser, p, w, h)));
await browser.close();
const fails = all.filter((r) => !r.pass);
if (OUT) writeFileSync(OUT, JSON.stringify({ base: BASE, lines: all.length, fails, results: all }, null, 1));
for (const f of fails) console.log(`FAIL ${f.path} @${f.w} ${f.tag}.${f.cls} "${f.text}" ${f.p02} < ${f.need} (median ${f.median})`);
console.log(`light-contrast: ${all.length} lines over the world measured, ${fails.length} below the bar`);
process.exit(fails.length ? 1 : 0);
