// Renders every WordPress template at 390×844 and 1440×900, saves screenshots and fails on
// the problems 09-QA-CHECKLIST treats as blockers. Needs a running site (npm run dev).
//
//   node scripts/check-pages.mjs [--base=http://127.0.0.1:9400] [--out=../qa/wp-local] [--production]
//
// Checks per page and size: HTTP 200 (404 page: 404), no PHP warnings/notices in the HTML,
// no console errors or failed requests, no sideways scroll (also at 320px), one H1, <html lang>
// matches the URL, every <img> has alt, tap targets ≥ 44px, hero H1 + both buttons in the first
// screen on the homepages. With --production: no data-draft, no pending chips, no [bracket]
// placeholders and no donation placeholders anywhere in the HTML. The seeded content is all
// draft, so on production whole sections, the hero H1 and draft-marked centers/programs (404)
// are hidden by design: those are reported as notes, not failures.
// Browser: playwright-core; set CHROME_PATH if its own Chromium isn't downloaded.
import { mkdirSync, writeFileSync } from "node:fs";
import { join, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { chromium } from "playwright-core";

const arg = (name, fallback) => process.argv.find((a) => a.startsWith(`--${name}=`))?.split("=").slice(1).join("=") ?? fallback;
const production = process.argv.includes("--production");
const base = arg("base", production ? "http://127.0.0.1:9401" : "http://127.0.0.1:9400").replace(/\/$/, "");
const out = resolve(arg("out", fileURLToPath(new URL(`../../qa/wp-${production ? "production" : "review"}`, import.meta.url))));
const SIZES = { mobile: [390, 844], desktop: [1440, 900] };

export const PAGES = [
  ["/", "home"],
  ["/en/", "home"],
  ["/hoat-dong/", "page"],
  ["/en/our-work/?area=education", "page"],
  ["/hoat-dong/co-so/tre-xanh/", "center"],
  ["/en/our-work/centers/little-rose/", "center"],
  ["/en/our-work/programs/thrive-together/", "program"],
  ["/hoat-dong/du-an/cung-vuon-len/", "program"],
  ["/tac-dong/", "page"],
  ["/en/impact/reports/", "reports"],
  ["/tin-tuc/", "news"],
  ["/en/news/", "news"],
  ["/en/news/?untranslated=__VI_ONLY__", "news"],
  ["/chung-tay/", "page"],
  ["/en/get-involved/donate/", "page"],
  ["/lien-he/", "page"],
  ["/en/about/policies/", "page"],
  ["/?s=tr%E1%BA%BB+em", "search"],
  ["/khong-co-trang-nay/", "404"],
  ["/en/no-such-page/", "404"],
];

const slug = (path) => path.replace(/[?=&%+]/g, "_").replace(/^\/|\/$/g, "").replace(/\//g, "_") || "home-vi";

async function main() {
  mkdirSync(out, { recursive: true });
  const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
  const failures = [];
  const report = [];

  // A news post to test the language notice: the newest VI post without an EN version.
  const viOnly = await (await fetch(`${base}/wp-json/wp/v2/posts?slug=bai-viet-chi-co-tieng-viet&_fields=id`)).json().catch(() => []);
  const pages = PAGES.map(([p, kind]) => [p.replace("__VI_ONLY__", viOnly[0]?.id ?? "0"), kind]);

  for (const [path, kind] of pages) {
    for (const [size, [w, h]] of Object.entries(SIZES)) {
      const locale = path.startsWith("/en/") ? "en-GB" : "vi-VN";
      const page = await browser.newPage({ viewport: { width: w, height: h }, deviceScaleFactor: 1, locale });
      const problems = [];
      // The 404 page's own status shows up as a console error; that one is expected.
      page.on("console", (m) => m.type() === "error" && !((kind === "404" || production) && /status of 404/.test(m.text()) && m.location().url === page.url()) && problems.push(`console: ${m.text()}`));
      page.on("pageerror", (e) => problems.push(`js: ${e.message}`));
      page.on("requestfailed", (r) => problems.push(`request failed: ${r.url()}`));
      page.on("response", (r) => r.status() >= 400 && r.url() !== base + path && problems.push(`HTTP ${r.status()}: ${r.url()}`));
      const notes = [];
      const res = await page.goto(base + path, { waitUntil: "networkidle", timeout: 120000 });
      const expected = kind === "404" ? 404 : 200;
      const hiddenDraft = production && ["center", "program"].includes(kind) && res.status() === 404;
      if (hiddenDraft) notes.push("404: draft wording, hidden on production");
      else if (res.status() !== expected) problems.push(`status ${res.status()} (expected ${expected})`);
      await page.evaluate(() => document.fonts.ready);
      const html = await page.content();
      const facts = await page.evaluate(() => {
        const vis = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.visibility !== "hidden" && s.display !== "none"; };
        const small = [...document.querySelectorAll("a[href], button, input, select, summary")]
          // Footer column titles are <summary> only for the phone accordion; on desktop they are plain headings.
          .filter((el) => vis(el) && !el.closest(".sr-only, .skip-link, .prose, .program-row__main p, .news-item__meta") && !(innerWidth >= 1024 && el.matches(".foot-col summary")))
          .filter((el) => { const r = el.getBoundingClientRect(); return (r.height < 44 && r.width < 44) || r.height < 24; })
          .map((el) => (el.textContent || el.getAttribute("aria-label") || el.tagName).trim().slice(0, 30));
        const h1 = document.querySelector("h1");
        const fold = (sel) => { const el = document.querySelector(sel); if (!el) return null; const r = el.getBoundingClientRect(); return r.bottom <= innerHeight && r.top >= 0; };
        return {
          lang: document.documentElement.lang,
          h1: document.querySelectorAll("h1").length,
          overflow: document.documentElement.scrollWidth - innerWidth,
          noAlt: [...document.images].filter((i) => !i.hasAttribute("alt")).map((i) => i.src.split("/").pop()),
          small,
          heroH1: h1 && h1.closest(".hero") ? fold(".hero h1") : null,
          heroButtons: document.querySelector(".hero__actions") ? [...document.querySelectorAll(".hero__actions .btn")].every((b) => { const r = b.getBoundingClientRect(); return r.bottom <= innerHeight; }) : null,
          drafts: document.querySelectorAll("[data-draft]").length,
          // 08 task 2.8 budget: requests (images excluded) and bytes, from the Resource Timing API.
          requests: 1 + performance.getEntriesByType("resource").filter((r) => r.initiatorType !== "img" && !/\.(png|jpe?g|webp|avif|svg)(\?|$)/.test(r.name)).length,
          bytes: [performance.getEntriesByType("navigation")[0], ...performance.getEntriesByType("resource")].reduce((n, r) => n + (r.encodedBodySize || 0), 0),
        };
      });
      const wantLang = path.startsWith("/en/") ? "en-GB" : "vi";
      if (!facts.lang.startsWith(wantLang.slice(0, 2))) problems.push(`html lang=${facts.lang}`);
      if (facts.h1 > 1 || (facts.h1 === 0 && !production)) problems.push(`${facts.h1} h1 elements`);
      else if (facts.h1 === 0) notes.push("no h1 (draft heading hidden on production)");
      if (facts.overflow > 0) problems.push(`scrolls sideways by ${facts.overflow}px`);
      if (facts.noAlt.length) problems.push(`img without alt: ${facts.noAlt.join(", ")}`);
      if (facts.small.length) problems.push(`tap targets under 44px: ${[...new Set(facts.small)].slice(0, 8).join(" | ")}`);
      if (kind === "home" && (facts.heroH1 === false || facts.heroButtons === false)) problems.push("hero H1 or buttons below the first screen");
      if (kind === "home" && facts.requests >= 15) problems.push(`${facts.requests} requests without images (budget: under 15)`);
      if (kind === "home" && facts.bytes >= 1024 * 1024) problems.push(`${Math.round(facts.bytes / 1024)} KB (budget: under 1 MB)`);
      if (kind === "home") notes.push(`${facts.requests} requests, ${Math.round(facts.bytes / 1024)} KB`);
      if (/<b>(Warning|Notice|Deprecated|Fatal error)<\/b>|Warning: |Deprecated: /.test(html)) problems.push("PHP warning/notice in the HTML");
      if (production) {
        if (/data-draft|data-pending|data-donation-placeholder|chip-pending/.test(html)) problems.push("draft/pending markers on production");
        const text = await page.evaluate(() => document.body.innerText);
        const token = text.match(/\[[^\]\n]{1,60}\]/);
        if (token) problems.push(`placeholder text on production: ${token[0]}`);
        if (/proto-note/.test(html)) problems.push("review note on production");
      }
      // Full-page screenshot with every section revealed (fade-ups run on scroll).
      await page.evaluate(() => document.querySelectorAll(".reveal-pending").forEach((e) => e.classList.remove("reveal-pending")));
      await page.waitForTimeout(450);
      await page.screenshot({ path: join(out, `${slug(path)}-${size}.png`), fullPage: true });
      // Narrowest phones: no sideways scroll at 320px either.
      if (size === "mobile") {
        await page.setViewportSize({ width: 320, height: 640 });
        const o = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
        if (o > 0) problems.push(`scrolls sideways by ${o}px at 320px`);
      }
      report.push({ path, size, status: res.status(), drafts: facts.drafts, requests: facts.requests, kb: Math.round(facts.bytes / 1024), problems, notes });
      if (problems.length) failures.push(`${path} @${size}:\n    ${problems.join("\n    ")}`);
      console.log(`${problems.length ? "FAIL" : "ok  "} ${path} @${size}${problems.length ? ` (${problems.length})` : ""}${notes.length ? `  note: ${notes.join("; ")}` : ""}`);
      await page.close();
    }
  }
  await browser.close();
  writeFileSync(join(out, "report.json"), JSON.stringify({ base, production, date: new Date().toISOString(), report }, null, 2));
  if (failures.length) {
    console.error(`\n${failures.length} page/size combinations failed:\n  ${failures.join("\n  ")}`);
    process.exit(1);
  }
  console.log(`\nall ${report.length} checks passed; screenshots in ${out}`);
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) main();
