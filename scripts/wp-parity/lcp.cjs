// Mobile LCP and CLS against any base URL, under the exact conditions of prototype/scripts/lcp.cjs
// (docs/plan/11-WP-PORT.md 5.5, gate 7.11): Moto G Power viewport (412 x 823, DPR 1.75), Slow 4G as
// DevTools applies it (562.5 ms RTT, 1474.56 kbps down, 675 kbps up), CPU 4x slower, cold cache, median
// of N runs, with ?tier=0, ?tier=1 and the default path; when three.js (the live chunk) was requested
// relative to the LCP; and on the default path no long task over 200 ms in the first 10 s.
//
// The prototype's own script serves prototype/dist itself; this one measures whatever base it is given
// (WordPress through scripts/wp-parity/gzip-proxy.mjs, so both sides are compressed alike).
//
//   node scripts/wp-parity/lcp.cjs <base> [runs=3] [path=/] [report.json]
//
// Prints the same lines as the prototype's script, then one line "JSON {...}" with the numbers.
// Exits 1 when a median LCP is over 2.5 s, three.js loads before the LCP, or (default path) a long
// task passes 200 ms.
const { chromium } = require("playwright");
const fs = require("fs");

const base = (process.argv[2] || "").replace(/\/$/, "");
const runs = Number(process.argv[3] || 3);
const route = process.argv[4] || "/";
const out = process.argv[5];
if (!/^https?:\/\//.test(base)) {
  console.error("usage: node scripts/wp-parity/lcp.cjs <base> [runs=3] [path=/] [report.json]");
  process.exit(2);
}

const median = (a) => [...a].sort((x, y) => x - y)[Math.floor(a.length / 2)];

async function once(browser, query) {
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
  await page.waitForTimeout(9000);
  const r = await page.evaluate(() => {
    const res = performance.getEntriesByType("resource");
    const start = (re) => res.find((e) => re.test(e.name))?.startTime ?? null;
    const nav = performance.getEntriesByType("navigation")[0];
    return {
      lcp: window.__lcp.at(-1), cls: window.__cls,
      fcp: performance.getEntriesByName("first-contentful-paint")[0]?.startTime,
      load: nav.loadEventEnd, world: start(/\/assets\/world-/), three: start(/\/assets\/live-/),
      live: document.documentElement.classList.contains("world-live"), tier: document.documentElement.dataset.tier,
      long: window.__long.filter((e) => e.t < 10000), sunIn: window.hcwa?.state.sunIn,
      bytes: Math.round(res.reduce((s, e) => s + (e.transferSize || 0), 0) / 1024) + Math.round((nav.transferSize || 0) / 1024),
    };
  });
  await ctx.close();
  return r;
}

(async () => {
  const browser = await chromium.launch({ args: ["--use-angle=swiftshader", "--enable-unsafe-swiftshader"] });
  let fail = false;
  const summary = { base, route, runs, paths: [] };
  for (const [label, query] of [["world off (?tier=0)", "?tier=0"], ["world on (?tier=1)", "?tier=1"], ["default (no flag)", ""]]) {
    const all = [];
    for (let i = 0; i < runs; i++) {
      const r = await once(browser, query);
      all.push(r);
      console.log(`${label} run ${i + 1}: LCP ${Math.round(r.lcp?.t)} ms (${r.lcp?.el}${r.lcp?.url ? " " + r.lcp.url.split("/").pop() : ""}) · FCP ${Math.round(r.fcp)} · load ${Math.round(r.load)} · CLS ${r.cls.toFixed(3)} · world chunk ${r.world == null ? "-" : Math.round(r.world)} · three ${r.three == null ? "-" : Math.round(r.three)} · live ${r.live} · ${r.bytes} KB · longest task ${Math.max(0, ...r.long.map((e) => e.d))} ms`);
    }
    const lcp = median(all.map((r) => r.lcp?.t ?? Infinity));
    const after = all.every((r) => r.three == null || r.three > (r.lcp?.t ?? Infinity));
    const cls = Math.max(...all.map((r) => r.cls));
    console.log(`${label}: median LCP ${Math.round(lcp)} ms (limit 2500, goal 1800) · max CLS ${cls.toFixed(3)} · three.js after LCP: ${after}`);
    const longest = Math.max(0, ...all.flatMap((r) => r.long.map((e) => e.d)));
    if (!query) console.log(`${label}: longest main-thread task in the first 10 s ${longest} ms (limit 200)`);
    if (lcp > 2500 || !after || (!query && longest > 200)) fail = true;
    summary.paths.push({ label, query, medianLcp: Number.isFinite(lcp) ? Math.round(lcp) : null, maxCls: +cls.toFixed(3), threeAfterLcp: after, longestTask: longest, runs: all.map((r) => ({ lcp: r.lcp?.t ? Math.round(r.lcp.t) : null, el: r.lcp?.el ?? null, cls: +r.cls.toFixed(3), three: r.three == null ? null : Math.round(r.three), kb: r.bytes })) });
  }
  await browser.close();
  summary.fail = fail;
  console.log("JSON " + JSON.stringify(summary));
  if (out) fs.writeFileSync(out, JSON.stringify(summary, null, 1));
  process.exitCode = fail ? 1 : 0;
})();
