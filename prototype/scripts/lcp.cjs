// Mobile LCP and CLS (PLAN A5, H0): Lighthouse's mobile conditions through the DevTools protocol,
// cold cache, median of N runs, with the world disabled (?tier=0) and enabled (?tier=1), and when
// three.js was requested relative to the LCP, and on the default path (no flag: headless Chromium has
// a software GPU, so this is the stills path CI's Lighthouse takes) no long task over 200 ms in the
// first 10 s. Serves dist/ gzipped, as GitHub Pages does.
//   npm run build && node scripts/lcp.cjs [runs=3] [path=/]
// Conditions: Moto G Power viewport (412 x 823, DPR 1.75), Slow 4G as DevTools applies it
// (562.5 ms RTT, 1474.56 kbps down, 675 kbps up), CPU 4x slower. Exits 1 over 2.5 s.
const { chromium } = require("playwright");
const http = require("http");
const zlib = require("zlib");
const fs = require("fs");
const path = require("path");

const runs = Number(process.argv[2] || 3);
const route = process.argv[3] || "/";
const dist = path.join(__dirname, "../dist");
const TYPES = { ".html": "text/html; charset=utf-8", ".js": "text/javascript", ".css": "text/css", ".json": "application/json", ".svg": "image/svg+xml", ".woff2": "font/woff2", ".avif": "image/avif", ".webp": "image/webp", ".jpg": "image/jpeg", ".png": "image/png", ".ico": "image/x-icon", ".txt": "text/plain" };
const GZIP = /^(text\/|application\/json|image\/svg)/;
const gz = new Map();

const server = http.createServer((req, res) => {
  let p = decodeURIComponent(new URL(req.url, "http://x").pathname);
  if (p.endsWith("/")) p += "index.html";
  const file = path.join(dist, p);
  if (!file.startsWith(dist) || !fs.existsSync(file)) { res.writeHead(404).end(); return; }
  const type = TYPES[path.extname(file)] || "application/octet-stream";
  let body = fs.readFileSync(file);
  const headers = { "content-type": type, "cache-control": "no-store" };
  if (GZIP.test(type) && /gzip/.test(req.headers["accept-encoding"] || "")) {
    if (!gz.has(file)) gz.set(file, zlib.gzipSync(body, { level: 9 }));
    body = gz.get(file);
    headers["content-encoding"] = "gzip";
  }
  // A length, as GitHub Pages sends: Chrome's low-entropy rule reads it, and a chunked image never becomes the LCP.
  headers["content-length"] = body.length;
  res.writeHead(200, headers).end(body);
});

const median = (a) => [...a].sort((x, y) => x - y)[Math.floor(a.length / 2)];

async function once(browser, base, query) {
  const ctx = await browser.newContext({
    viewport: { width: 412, height: 823 }, deviceScaleFactor: 1.75, isMobile: true, hasTouch: true, locale: "vi-VN",
    userAgent: "Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36",
  });
  await ctx.addInitScript(() => {
    window.__lcp = []; window.__cls = 0; window.__long = [];
    try { new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__long.push({ t: Math.round(e.startTime), d: Math.round(e.duration) }); }).observe({ type: "longtask", buffered: true }); } catch {}
    new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__lcp.push({ t: e.startTime, el: e.element ? e.element.tagName + (e.element.className ? "." + String(e.element.className).split(" ")[0] : "") : "?", url: e.url, size: e.size }); }).observe({ type: "largest-contentful-paint", buffered: true });
    new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; }).observe({ type: "layout-shift", buffered: true });
  });
  const page = await ctx.newPage();
  const cdp = await ctx.newCDPSession(page);
  await cdp.send("Network.enable");
  await cdp.send("Network.setCacheDisabled", { cacheDisabled: true });
  await cdp.send("Network.emulateNetworkConditions", { offline: false, latency: 562.5, downloadThroughput: (1474.56 * 1024) / 8, uploadThroughput: (675 * 1024) / 8 });
  await cdp.send("Emulation.setCPUThrottlingRate", { rate: 4 });
  await page.goto(base + route + query, { waitUntil: "load", timeout: 120000 });
  // Long enough for load + idle and the world chunk on a throttled phone.
  await page.waitForTimeout(9000);
  const r = await page.evaluate(() => {
    const res = performance.getEntriesByType("resource");
    const start = (re) => res.find((e) => re.test(e.name))?.startTime ?? null;
    const nav = performance.getEntriesByType("navigation")[0];
    return {
      lcp: window.__lcp.at(-1), cls: window.__cls,
      fcp: performance.getEntriesByName("first-contentful-paint")[0]?.startTime,
      load: nav.loadEventEnd, world: start(/\/assets\/world-/), three: start(/\/assets\/three-/),
      live: document.documentElement.classList.contains("world-live"), tier: document.documentElement.dataset.tier,
      long: window.__long.filter((e) => e.t < 10000), sunIn: window.hcwa?.state.sunIn,
      bytes: Math.round(res.reduce((s, e) => s + (e.transferSize || 0), 0) / 1024) + Math.round((nav.transferSize || 0) / 1024),
    };
  });
  await ctx.close();
  return r;
}

(async () => {
  await new Promise((ok) => server.listen(0, "127.0.0.1", ok));
  const base = `http://127.0.0.1:${server.address().port}`;
  const browser = await chromium.launch({ args: ["--use-angle=swiftshader", "--enable-unsafe-swiftshader"] });
  let fail = false;
  for (const [label, query] of [["world off (?tier=0)", "?tier=0"], ["world on (?tier=1)", "?tier=1"], ["default (no flag)", ""]]) {
    const all = [];
    for (let i = 0; i < runs; i++) {
      const r = await once(browser, base, query);
      all.push(r);
      console.log(`${label} run ${i + 1}: LCP ${Math.round(r.lcp?.t)} ms (${r.lcp?.el}${r.lcp?.url ? " " + r.lcp.url.split("/").pop() : ""}) · FCP ${Math.round(r.fcp)} · load ${Math.round(r.load)} · CLS ${r.cls.toFixed(3)} · world chunk ${r.world == null ? "-" : Math.round(r.world)} · three ${r.three == null ? "-" : Math.round(r.three)} · live ${r.live} · ${r.bytes} KB · longest task ${Math.max(0, ...r.long.map((e) => e.d))} ms`);
    }
    const lcp = median(all.map((r) => r.lcp?.t ?? Infinity));
    const after = all.every((r) => r.three == null || r.three > (r.lcp?.t ?? Infinity));
    console.log(`${label}: median LCP ${Math.round(lcp)} ms (limit 2500, goal 1800) · max CLS ${Math.max(...all.map((r) => r.cls)).toFixed(3)} · three.js after LCP: ${after}`);
    const longest = Math.max(0, ...all.flatMap((r) => r.long.map((e) => e.d)));
    if (!query) console.log(`${label}: longest main-thread task in the first 10 s ${longest} ms (limit 200)`);
    if (lcp > 2500 || !after || (!query && longest > 200)) fail = true;
  }
  await browser.close();
  server.close();
  process.exitCode = fail ? 1 : 0;
})();
