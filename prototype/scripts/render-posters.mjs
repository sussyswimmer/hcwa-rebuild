// Renders the hero stills from the world code (05-DESIGN D6.10, PLAN section 5):
//   1. measures the real home page's [data-text-safe] rects and .hero__stage at each aspect
//      bucket's viewport (Vite dev server, ?tier=0 so the world stays out of it),
//   2. renders src/world/poster.html (no HTML text) twice per bucket in Chromium with
//      SwiftShader WebGL: layer=base (shaded) and layer=lit (rest frame),
//   3. hands the PNGs to scripts/encode-posters.py, which writes public/posters/ and fails
//      over budget.
// Usage: npm run posters [-- --bucket landscape] [--keep [dir]] [--debug] [--measure]
//        [--shot x,z,lookX,lookY,lookZ,fov] (try a K0 for one bucket; pair with --keep)
// Stills (inner headers, dusk, doors, 404, story; src/world/still-shots.js): npm run posters -- --still all
//        [--still header-our-work,dusk] [--crop desk] [--keep [dir]] [--debug] [--no-encode]
//        [--no-walls] (the procedural walls alone, for a before/after)
//        tuning one crop: [--cam x,y,z,lookX,lookY,lookZ,fov] [--sun az,el] [--w 800] [--layer lit]
import { createServer as httpServer } from "node:http";
import { mkdirSync, readFileSync, writeFileSync, existsSync, mkdtempSync, rmSync } from "node:fs";
import { extname, join, resolve, sep } from "node:path";
import { tmpdir } from "node:os";
import { spawnSync } from "node:child_process";
import { createRequire } from "node:module";
import { fileURLToPath, pathToFileURL } from "node:url";
import { BUCKETS } from "../src/world/buckets.js";
import { STILLS, budgetKB, cropsOf } from "../src/world/still-shots.js";

const root = resolve(fileURLToPath(new URL("..", import.meta.url)));
const args = process.argv.slice(2);
const opt = (name) => { const i = args.indexOf("--" + name); return i < 0 ? null : args[i + 1] && !args[i + 1].startsWith("--") ? args[i + 1] : true; };
const only = opt("bucket");
const buckets = BUCKETS.filter((b) => !only || b.name === only);
const SHOT = "hero", PIXELS = 1600;

// Playwright is preinstalled outside the project (require('playwright') works from anywhere).
const require = createRequire(import.meta.url);
let chromium;
try { ({ chromium } = require("playwright")); } catch { ({ chromium } = require(require.resolve("playwright", { paths: ["/opt/node-tools/node_modules"] }))); }

// Static server for the poster entry: prototype/ as the web root (src/world, node_modules/three).
const TYPES = { ".webp": "image/webp", ".html": "text/html", ".js": "text/javascript", ".mjs": "text/javascript", ".json": "application/json", ".woff2": "font/woff2" };
function serve(dir) {
  const server = httpServer((req, res) => {
    const rel = "." + decodeURIComponent(new URL(req.url, "http://x").pathname);
    // The site's public/ files (the wall maps, /textures/) where the page asks for them at the root, as Vite serves them.
    let path = resolve(dir, rel);
    if (!existsSync(path) && rel.startsWith("./textures/")) path = resolve(dir, "public", rel);
    if (!path.startsWith(dir + sep) || !existsSync(path)) { res.writeHead(404).end(); return; }
    res.writeHead(200, { "content-type": TYPES[extname(path)] ?? "application/octet-stream" }).end(readFileSync(path));
  });
  return new Promise((ok) => server.listen(0, "127.0.0.1", () => ok(server)));
}

// Text rects on the real page, measured by the same code the live world uses. One still serves every
// layout of its bucket, so it is measured in each: VI and EN; the bucket's own view and the two aspect
// ratios at its edges (the <picture> media queries hand it everything in between); with and without
// the staging bars; and a VI page in an English browser (the EN pointer bar). The first set is the
// bucket's own VI view: the fallback when no frame clears them all.
const EDGES = { portrait: [9 / 21, 10 / 17], tablet: [0.6, 12 / 11], landscape: [1.1, 27 / 16], wide: [27 / 16, 21 / 9] };
function layouts(b) {
  const [w] = b.view, at = (aspect) => [w, Math.round(w / aspect)];
  const out = [];
  for (const bars of [true, false]) {
    out.push({ route: "/", locale: "vi-VN", view: b.view, bars, extreme: false });
    out.push({ route: "/en/", locale: "en-US", view: b.view, bars, extreme: false });
  }
  out.push({ route: "/", locale: "en-US", view: b.view, bars: true, extreme: false });
  for (const aspect of EDGES[b.name]) for (const [route, locale, bars] of [["/", "vi-VN", true], ["/", "vi-VN", false], ["/en/", "en-US", true]]) {
    out.push({ route, locale, view: at(aspect), bars, extreme: true });
  }
  return out;
}

async function measure(browser) {
  let vite;
  try {
    const { createServer } = await import("vite");
    vite = await createServer({ configFile: join(root, "vite.config.js"), root: join(root, ".site"), server: { port: 0, host: "127.0.0.1" }, logLevel: "error" });
    await vite.listen();
  } catch (e) {
    console.warn("posters: no dev server, rendering without text rects:", e.message);
    return {};
  }
  const url = vite.resolvedUrls.local[0].replace(/\/$/, "");
  const frameModule = "/@fs" + join(root, "src/world/frame.js");
  const out = {};
  for (const b of buckets) {
    out[b.name] = [];
    for (const l of layouts(b)) {
      const page = await browser.newPage({ viewport: { width: l.view[0], height: l.view[1] }, locale: l.locale });
      await page.goto(url + l.route + "?tier=0", { waitUntil: "load" });
      out[b.name].push(await page.evaluate(async ({ mod, bars, l }) => {
        if (!bars) { document.querySelector(".proto-note")?.remove(); await new Promise((ok) => requestAnimationFrame(() => requestAnimationFrame(ok))); }
        await document.fonts.ready;
        const { readTextRects, readBox } = await import(mod);
        return { ...l, rects: readTextRects(), stage: readBox(document.querySelector(".hero__stage")), box: readBox(document.querySelector('[data-world="room"]')) };
      }, { mod: frameModule, bars: l.bars, l }));
      await page.close();
    }
  }
  await vite.close();
  return out;
}

const browser = await chromium.launch({ args: ["--use-angle=swiftshader", "--enable-unsafe-swiftshader", "--ignore-gpu-blocklist"] });
const keep = opt("keep");
const pngDir = typeof keep === "string" ? resolve(keep) : keep ? join(root, ".posters") : mkdtempSync(join(tmpdir(), "hcwa-posters-"));
mkdirSync(pngDir, { recursive: true });
const server = await serve(root);
const origin = `http://127.0.0.1:${server.address().port}`;
const py = existsSync(join(root, "../.venv/bin/python")) ? join(root, "../.venv/bin/python") : "python3";

// One page per still, crop and layer, rendered at the crop's largest width; the encoder scales down.
// Plain stills render their lit frame only; lit headers render base and lit.
async function renderStills() {
  const names = opt("still") === "all" || opt("still") === true ? Object.keys(STILLS) : String(opt("still")).split(",");
  const out = [];
  for (const name of names) {
    if (!STILLS[name]) throw new Error(`posters: no still named ${name}`);
    const s = STILLS[name];
    for (const crop of cropsOf(name).filter((c) => !opt("crop") || c.name === opt("crop"))) {
      const width = Math.max(...crop.widths);
      const result = { still: name, crop: crop.name, view: crop.view, widths: crop.widths, layers: s.layers, budgets: {} };
      for (const layer of s.layers) result.budgets[layer] = Object.fromEntries(crop.widths.map((w) => [w, budgetKB(name, crop.name, layer, w)]));
      for (const layer of (s.layers.includes("base") ? ["base", "lit"] : ["lit"]).filter((l) => !opt("layer") || l === opt("layer"))) {
        const page = await browser.newPage({ viewport: { width: crop.view[0], height: crop.view[1] } });
        const errors = [];
        page.on("pageerror", (e) => errors.push(e.message));
        page.on("console", (msg) => { if (msg.type() === "error") errors.push(msg.text()); });
        const q = new URLSearchParams({ still: name, crop: crop.name, w: String(opt("w") ?? width), layer, ...(opt("debug") ? { debug: "frame" } : {}), ...(typeof opt("cam") === "string" ? { cam: opt("cam") } : {}), ...(typeof opt("sun") === "string" ? { sun: opt("sun") } : {}), ...(opt("no-walls") ? { walls: "0" } : {}) });
        await page.goto(`${origin}/src/world/still.html?${q}`);
        await page.waitForFunction(() => document.title === "done", null, { timeout: 300000, polling: 500 }).catch(() => {});
        const r = await page.evaluate(() => window.__posterResult ?? null);
        if (!r) throw new Error(`posters: ${name}/${crop.name}/${layer} did not render\n  ${errors.join("\n  ")}`);
        writeFileSync(join(pngDir, `${name}-${crop.name}-${layer}.png`), Buffer.from(r.png.split(",")[1], "base64"));
        if (opt("debug")) await page.screenshot({ path: join(pngDir, `${name}-${crop.name}-${layer}-debug.png`) });
        if (layer === "lit") Object.assign(result, { silhouettes: r.silhouettes, frame: r.frame, shafts: r.shafts });
        console.log(`posters: ${name} ${crop.name} ${layer} → ${width} px · ${r.calls} draw calls · ${r.triangles} triangles${r.frame.sx ? ` · lens shift ${r.frame.sx} px (text zone)` : ""}`);
        await page.close();
      }
      out.push(result);
    }
  }
  return out;
}

if (opt("still")) {
  let stills;
  try { stills = await renderStills(); } finally { await browser.close(); server.close(); }
  writeFileSync(join(pngDir, "stills.json"), JSON.stringify({ stills }, null, 2));
  if (opt("no-encode")) { console.log(`posters: PNGs in ${pngDir}`); process.exit(0); }
  const enc = spawnSync(py, [join(root, "scripts/encode-posters.py"), pngDir, join(root, "public/posters"), "--stills"], { stdio: "inherit" });
  if (!keep) rmSync(pngDir, { recursive: true, force: true });
  process.exit(enc.status ?? 1);
}
const report = [];
let measured = {};
try {
  measured = await measure(browser);
  if (opt("measure")) {
    // Rects only, for tuning shots: npm run posters -- --measure --keep <dir>
    writeFileSync(join(pngDir, "measured.json"), JSON.stringify(measured, null, 2));
    buckets.length = 0;
  }
  for (const b of buckets) {
    const sets = measured[b.name] ?? [];
    if (!sets[0]?.rects?.length) console.warn(`posters: ${b.name}: no [data-text-safe] rects on the page; framing without text`);
    for (const layer of ["base", "lit"]) {
      const page = await browser.newPage({ viewport: { width: b.view[0], height: b.view[1] } });
      const errors = [];
      page.on("pageerror", (e) => errors.push(e.message));
      page.on("console", (msg) => { if (msg.type() === "error") errors.push(msg.text()); });
      await page.addInitScript((input) => { window.__posterInput = input; }, { sets });
      const q = new URLSearchParams({ bucket: b.name, w: String(PIXELS), layer, ...(opt("debug") ? { debug: "frame" } : {}), ...(typeof opt("shot") === "string" ? { shot: opt("shot") } : {}), ...(opt("no-walls") ? { walls: "0" } : {}) });
      await page.goto(`${origin}/src/world/poster.html?${q}`);
      await page.waitForFunction(() => document.title === "done" || window.__posterError, null, { timeout: 300000, polling: 500 }).catch(() => {});
      const result = await page.evaluate(() => window.__posterResult ?? null);
      if (!result) throw new Error(`posters: ${b.name}/${layer} did not render\n  ${errors.join("\n  ")}`);
      writeFileSync(join(pngDir, `${SHOT}-${b.name}-${layer}.png`), Buffer.from(result.png.split(",")[1], "base64"));
      if (opt("debug")) await page.screenshot({ path: join(pngDir, `${SHOT}-${b.name}-${layer}-debug.png`) });
      delete result.png;
      report.push(result);
      console.log(`posters: ${b.name} ${layer} ${result.view.join("×")} → ${PIXELS} px · ${result.calls} draw calls · ${result.triangles} triangles · shift ${Math.round(result.frame.sx)},${Math.round(result.frame.sy)} · clears ${result.frame.clears}/${result.frame.of} layouts${result.frame.ok ? (result.frame.headerClear ? "" : " · header rects given up (scrim carries them)") : " · TEXT NOT CLEAR"}${result.frame.heroInStage ? "" : " · hero block outside stage"}`);
      await page.close();
    }
  }
} finally {
  await browser.close();
  server.close();
}
writeFileSync(join(pngDir, "render.json"), JSON.stringify({ shot: SHOT, buckets: report, measured }, null, 2));
if (opt("measure")) process.exit(0);

// The shift each still was framed with, for the live world: at rest it uses the same shift while
// it keeps the text clear, so the handoff lands on the still pixel for pixel (live.js).
const framesFile = join(root, "src/world/poster-frames.js");
const frames = existsSync(framesFile) ? (await import(pathToFileURL(framesFile).href + "?t=" + Date.now())).POSTER_FRAMES : {};
for (const r of report) if (r.layer === "lit") frames[r.bucket] = { view: r.view, sx: +r.frame.sx.toFixed(2), sy: +r.frame.sy.toFixed(2), silhouette: r.silhouette, caps: r.caps };
writeFileSync(framesFile, `// GENERATED by scripts/render-posters.mjs: per hero still, its framing shift, the screen outline and the\n// text caps it was lit with (poster px).\nexport const POSTER_FRAMES = ${JSON.stringify(frames)};\n`);

// Encode: the project venv when present (Pillow with AVIF), else system python.
const enc = spawnSync(py, [join(root, "scripts/encode-posters.py"), pngDir, join(root, "public/posters"), "--shot", SHOT, ...(only ? ["--bucket", only] : [])], { stdio: "inherit" });
if (!keep) rmSync(pngDir, { recursive: true, force: true });
process.exit(enc.status ?? 1);
