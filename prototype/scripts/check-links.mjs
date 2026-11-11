// Dead-link check for the built prototype (PLAN-M4 section 8): start from the home pages, the components
// pages and the 404 pages, follow every internal <a href> and form action, and fail on a link that lands on
// a missing page, on the 404 page, or on an #anchor that is not an id on its target. Base-path aware, so
// it also checks the build:pages output. A port of qa/m4/crawl.mjs; test/links.test.mjs runs the same
// checkLinks() on pages rendered in memory.
//   node scripts/check-links.mjs [dist] [base]        (defaults: dist, /)
//   npm run check:links                               (after npm run build; check:links:pages after build:pages)
import { existsSync, readFileSync, statSync } from "node:fs";
import { join, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const START = ["/", "/en/", "/components/", "/en/components/", "/404.html", "/en/404.html"];

const idsOf = (h) => new Set([...h.matchAll(/\s(?:id|name)="([^"]+)"/g)].map((m) => m[1]));
const linksOf = (h) => [
  ...[...h.matchAll(/<a\b([^>]*)>/g)].flatMap((m) => {
    const href = m[1].match(/\shref="([^"]*)"/);
    return href ? [href[1].replace(/&amp;/g, "&")] : [];
  }),
  ...[...h.matchAll(/<form\b[^>]*\saction="([^"]*)"/g)].map((m) => m[1].replace(/&amp;/g, "&")),
];
// The 404 page, by its own markup (notFound() in pages/involve.mjs): never a link's target.
const isNotFound = (h) => /class="[^"]*\bpage-gap\b/.test(h) || /gap__proto/.test(h);

// load(path) returns the HTML for a site path ("/x/", "/404.html") or null. Returns { pages, dead }.
export function checkLinks(load, { start = START, base = "/" } = {}) {
  base = base.replace(/\/?$/, "/");
  const strip = (p) => (base !== "/" && p.startsWith(base) ? "/" + p.slice(base.length) : p);
  const cache = new Map();
  const get = (p) => {
    if (!cache.has(p)) cache.set(p, load(p));
    return cache.get(p);
  };
  const queue = [...start];
  const seen = new Set();
  const dead = [];
  while (queue.length) {
    const p = queue.shift();
    if (seen.has(p)) continue;
    seen.add(p);
    const h = get(p);
    if (h == null) continue;
    for (const href of linksOf(h)) {
      if (/^(mailto:|tel:|javascript:|https?:\/\/)/.test(href)) continue;
      let [path, hash] = href.split("#");
      path = path.split("?")[0];
      if (path === "") path = p;
      else if (!path.startsWith("/")) path = new URL(path, "http://x" + p).pathname;
      path = strip(path);
      if (/\.(png|jpe?g|webp|avif|pdf|svg|css|js|woff2?)$/.test(path)) continue;
      const target = get(path);
      if (target == null) { dead.push({ from: p, href, why: "no page" }); continue; }
      if (isNotFound(target) && !/404/.test(path)) dead.push({ from: p, href, why: "the 404 page" });
      if (hash && !idsOf(target).has(hash)) dead.push({ from: p, href, why: `no #${hash} on ${path}` });
      if (!seen.has(path)) queue.push(path);
    }
  }
  return { pages: [...seen].filter((p) => get(p) != null), dead };
}

// A built folder (dist/) as a loader.
export const distLoader = (dist) => (p) => {
  let f = join(dist, decodeURIComponent(p));
  if (p.endsWith("/") || (existsSync(f) && statSync(f).isDirectory())) f = join(f, "index.html");
  return existsSync(f) ? readFileSync(f, "utf8") : null;
};

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const dist = resolve(process.argv[2] ?? fileURLToPath(new URL("../dist", import.meta.url)));
  const base = process.argv[3] ?? "/";
  const { pages, dead } = checkLinks(distLoader(dist), { base });
  const vi = pages.filter((p) => !/^(\/[^/]+)?\/en\//.test(p)).length;
  console.log(`check-links: ${pages.length} pages (${vi} VI, ${pages.length - vi} EN) in ${dist}`);
  const targets = new Map();
  for (const d of dead) targets.set(`${d.href} (${d.why})`, (targets.get(`${d.href} (${d.why})`) ?? 0) + 1);
  for (const [t, n] of targets) console.log(`  DEAD ${t}: ${n} link(s)`);
  console.log(dead.length ? `FAIL: ${targets.size} dead target(s), ${dead.length} link(s)` : "PASS: 0 dead links");
  process.exitCode = dead.length ? 1 : 0;
}
