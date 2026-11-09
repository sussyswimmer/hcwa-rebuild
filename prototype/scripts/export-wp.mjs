// The WordPress export (docs/plan/11-WP-PORT.md 1.1, 1.3 and 5.8): `npm run export:wp`.
//
// 1. Builds src/main.js with vite.wp.config.js into wp/themes/hcwa-2026/assets/dist/ (relative base,
//    manifest) and removes the Netlify files from it. The theme enqueues that bundle; dist/ is gitignored.
// 2. Writes the markup and data the theme and hcwa-core print without the bundle, all from the
//    prototype's own functions, so nothing is copied by hand:
//      wp/themes/hcwa-2026/assets/generated/   head-inline (both variants), page-reveal.js, svg/*.svg,
//                                              stills.json, guards.json, classes.json, sources.json
//      wp/plugins/hcwa-core/includes/prototype-data.php
//      wp/tests/fixtures/golden.json           input and output pairs for the PHP ports
//    and the editor palette in wp/themes/hcwa-2026/theme.json (settings.color.palette only).
//
// Every generated file is deterministic: running the export twice gives the same bytes.
// Options: --no-build skips step 1 (classes.json then needs an existing dist).
//
// The prototype is not changed by any of this: render.mjs is imported for its exported guards only,
// and the pages are rendered in memory (never written) to collect the picture and icon calls they make.
import { spawnSync } from "node:child_process";
import { createHash } from "node:crypto";
import { existsSync, mkdirSync, readFileSync, readdirSync, renameSync, rmSync, statSync, writeFileSync } from "node:fs";
import { dirname, join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";

import { BLOCKERS, LITTLE_ROSE_DOOR, MIN_PARTNER_LOGOS } from "../src/render.mjs";
import { headInline } from "../src/lib/head-inline.mjs";
import { pageReveal } from "../src/motion/transitions.js";
import { str } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { pageRoutes } from "../src/routes.mjs";
import { layout } from "../src/partials/layout.mjs";
import { pages } from "../src/pages/index.mjs";
import { AREA_BLOCK, AREA_PATCH, GROUT_PX, TILE, blockIcon, gachArt, gachSprite, gachStrip, screenArt } from "../src/partials/blocks.mjs";
import { icon, quatrefoil } from "../src/partials/components.mjs";
import { keepHyphens, viKeep } from "../src/lib/vikeep.mjs";
import { splitWords } from "../src/lib/split-words.mjs";
import { FORMATS, STILLS, litStill, poster, stillPicture, stillReady } from "../src/lib/posters.mjs";
import { BUCKETS as WORLD_BUCKETS } from "../src/world/buckets.js";
import { cropsOf } from "../src/world/still-shots.js";
import { hasStill, stillAspect } from "../src/partials/still.mjs";
import { EMBLEMS, doorLight } from "../src/world/objects/door.js";
import { parse as parseTokens } from "./tokens-to-js.mjs";

export const PROTO = resolve(import.meta.dirname, "..");
export const REPO = resolve(PROTO, "..");
export const THEME = join(REPO, "wp/themes/hcwa-2026");
export const DIST = join(THEME, "assets/dist");
export const GENERATED = join(THEME, "assets/generated");
export const PROTOTYPE_DATA = join(REPO, "wp/plugins/hcwa-core/includes/prototype-data.php");
export const GOLDEN = join(REPO, "wp/tests/fixtures/golden.json");
export const THEME_JSON = join(THEME, "theme.json");

const rel = (p) => relative(REPO, p).replaceAll("\\", "/");
const read = (p) => readFileSync(p, "utf8");
const sha256 = (data) => createHash("sha256").update(data).digest("hex");
const files = (dir) => readdirSync(dir, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? files(join(dir, e.name)) : [join(dir, e.name)]));
// Sorted by code point, as Python's sorted() does (wp-static-check.py recomputes these hashes).
const byCodePoint = (a, b) => (a < b ? -1 : a > b ? 1 : 0);

// ---------------------------------------------------------------------------------------------------
// Step 1: the bundle
// ---------------------------------------------------------------------------------------------------

// The prototype's public/ holds Netlify's own files; a theme has no use for them.
const NETLIFY = ["_headers", "_redirects", "robots.txt"];

// Built next to dist/ and swapped in at the end, so a site serving the theme never sees a half-written
// bundle (a running local WordPress reads the manifest on every request).
export function build() {
  const vite = join(PROTO, "node_modules/vite/bin/vite.js");
  const next = `${DIST}.next`, old = `${DIST}.old`;
  rmSync(next, { recursive: true, force: true });
  const run = spawnSync(process.execPath, [vite, "build", "--config", "vite.wp.config.js", "--outDir", next], { cwd: PROTO, stdio: "inherit" });
  if (run.status !== 0) throw new Error("vite build --config vite.wp.config.js failed");
  for (const f of NETLIFY) rmSync(join(next, f), { force: true });
  rmSync(old, { recursive: true, force: true });
  if (existsSync(DIST)) renameSync(DIST, old);
  renameSync(next, DIST);
  rmSync(old, { recursive: true, force: true });
}

export function readManifest(dist = DIST) {
  const f = join(dist, "manifest.json");
  return existsSync(f) ? JSON.parse(read(f)) : null;
}

// ---------------------------------------------------------------------------------------------------
// Small parsers: the generated data is read back out of the prototype's own output, never re-typed.
// ---------------------------------------------------------------------------------------------------

const UNESC = { "&amp;": "&", "&lt;": "<", "&gt;": ">", "&quot;": '"', "&#39;": "'" };
const unesc = (s) => s.replace(/&(?:amp|lt|gt|quot|#39);/g, (m) => UNESC[m]);
const ESC = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" };
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ESC[c]);
const attrsOf = (tag) => Object.fromEntries([...tag.matchAll(/\s([\w-]+)(?:="([^"]*)")?/g)].map(([, k, v]) => [k, v === undefined ? true : unesc(v)]));
const isData = (v) => typeof v === "string" && v.startsWith("data:");

function parsePicture(text) {
  const m = text.match(/^<picture class="([^"]*)">((?:<source[^>]*>)*)<img([^>]*)><\/picture>$/);
  if (!m) throw new Error(`export-wp: not a <picture> the exporter understands: ${text.slice(0, 120)}`);
  return { cls: unesc(m[1]), sources: [...m[2].matchAll(/<source([^>]*)>/g)].map((s) => attrsOf(s[1])), img: attrsOf(m[3]) };
}

// "/posters/a-480.avif 480w, /posters/a-800.avif 800w" → [["posters/a-480.avif", 480], …]
const parseSrcset = (s) => s.split(", ").map((c) => { const [p, w] = c.split(" "); return [p.replace(/^\//, ""), Number(w.replace(/w$/, ""))]; });

// ---------------------------------------------------------------------------------------------------
// Stills: stills.json from the pictures the prototype prints, then the same pictures rebuilt from the
// JSON (what HCWA_Theme_Art does in PHP) and compared, so the JSON can never drift from posters.mjs.
// ---------------------------------------------------------------------------------------------------

const SIZES_MARK = "__hcwa_sizes__";
let NOTHING = null;
const noteNothing = (v) => { if (isData(v)) { if (NOTHING && NOTHING !== v) throw new Error("export-wp: two different empty-source images"); NOTHING = v; } };

function stillLayer(name, layer) {
  const crops = cropsOf(name);
  const pic = parsePicture(str(stillPicture(name, layer, { sizes: SIZES_MARK })));
  const sources = pic.sources.map((a) => {
    noteNothing(a.srcset);
    let crop;
    if (isData(a.srcset)) crop = crops.find((c) => (c.media ?? null) === (a.media ?? null));
    else crop = crops.find((c) => a.srcset.startsWith(`/posters/${name}-${c.name}-${layer}-`));
    if (!crop) throw new Error(`export-wp: ${name} ${layer}: a <source> matches no crop`);
    if (!isData(a.srcset) && a.sizes !== SIZES_MARK) throw new Error(`export-wp: ${name} ${layer}: a crop has its own sizes; the PHP builder does not know that case`);
    return { crop: crop.name, type: a.type ?? null, media: a.media ?? null, srcset: isData(a.srcset) ? null : parseSrcset(a.srcset), width: Number(a.width), height: Number(a.height) };
  });
  noteNothing(pic.img.src);
  return { sources, img: { src: isData(pic.img.src) ? null : pic.img.src.replace(/^\//, ""), width: Number(pic.img.width), height: Number(pic.img.height) } };
}

const HERO_LAYERS = Object.keys(FORMATS);
function heroLayer(layer) {
  const pic = parsePicture(str(poster("hero", layer, { sizes: SIZES_MARK })));
  const buckets = WORLD_BUCKETS;
  const sources = pic.sources.map((a) => {
    noteNothing(a.srcset);
    const bucket = isData(a.srcset)
      ? buckets.find((b) => (b.media ?? null) === (a.media ?? null))
      : buckets.find((b) => a.srcset.startsWith(`/posters/hero-${b.name}-${layer}-`));
    if (!bucket) throw new Error(`export-wp: hero ${layer}: a <source> matches no bucket`);
    return {
      bucket: bucket.name,
      type: a.type ?? null,
      media: a.media ?? null,
      srcset: isData(a.srcset) ? null : parseSrcset(a.srcset),
      sizes: isData(a.srcset) || a.sizes === SIZES_MARK ? null : a.sizes,
    };
  });
  noteNothing(pic.img.src);
  return { sources, img: { src: isData(pic.img.src) ? null : pic.img.src.replace(/^\//, ""), width: Number(pic.img.width), height: Number(pic.img.height) } };
}

// The Little Rose door (11-WP-PORT.md 1.3, 3.4): a still shows it when its scene builds the home
// corridor's three doors (HOME_BEATS), the hub's three-door wall, or a door of unit little-rose.
// Read from the scene functions in src/world/sets/stills.js and what they call; a still the export
// cannot read, or a scene that builds a door whose unit it cannot tell, counts as showing it.
// The world's door builders (objects/door.js): a scene that reaches one builds a door.
const DOOR_BUILDERS = new Set(["doorSpec", "doorMeshes", "doorGeometries"]);
const moduleInfos = new Map();
function moduleInfo(file) {
  if (moduleInfos.has(file)) return moduleInfos.get(file);
  const text = existsSync(file) ? read(file).replace(/\/\*[\s\S]*?\*\//g, "").replace(/(^|[^:"'`\\])\/\/[^\n]*/g, "$1") : "";
  // Module-level definitions, each up to the next one (generous on purpose: more text only adds marks).
  const defs = Object.create(null);
  const starts = [...text.matchAll(/^(?:export\s+)?(?:async\s+)?(?:function\s*\*?\s*([A-Za-z_$][\w$]*)|(?:const|let|var)\s+([A-Za-z_$][\w$]*)\s*=)/gm)];
  starts.forEach((m, i) => { defs[m[1] ?? m[2]] = text.slice(m.index, starts[i + 1]?.index ?? text.length); });
  const imports = Object.create(null);
  for (const [, names, from] of text.matchAll(/^import\s*\{([^}]*)\}\s*from\s*"([^"]+)"/gm)) {
    if (!from.startsWith(".")) continue;
    for (const n of names.split(",")) {
      const [orig, alias] = n.trim().split(/\s+as\s+/);
      if (orig) imports[(alias ?? orig).trim()] = { file: resolve(dirname(file), from), name: orig.trim() };
    }
  }
  const info = { text, defs, imports };
  moduleInfos.set(file, info);
  return info;
}

// Follows every call a scene makes, through the module's own functions and the relative imports, and
// notes whether it reaches HOME_BEATS (the home corridor's three doors) or a door builder.
function sceneReach(src, file) {
  const state = { threeDoors: false, doors: false, unread: false };
  const seen = new Set();
  const walk = (body, at) => {
    if (/\bHOME_BEATS\b/.test(body)) state.threeDoors = true;
    const info = moduleInfo(at);
    for (const [, id] of body.matchAll(/(?<![.\w$])([A-Za-z_$][\w$]*)\s*\(/g)) {
      if (DOOR_BUILDERS.has(id)) state.doors = true;
      const key = `${at}#${id}`;
      if (seen.has(key)) continue;
      seen.add(key);
      if (info.defs[id]) walk(info.defs[id], at);
      else if (info.imports[id]) {
        const { file: f, name } = info.imports[id];
        const there = moduleInfo(f);
        if (DOOR_BUILDERS.has(name)) state.doors = true;
        if (there.defs[name]) walk(there.defs[name], f);
        else state.unread = true; // a re-export or a file the export cannot read: fail closed below
      }
    }
  };
  walk(src, file);
  return state;
}

export async function littleRoseDoorStills() {
  const file = join(PROTO, "src/world/sets/stills.js");
  const { SHOTS } = await import("../src/world/sets/stills.js");
  const out = {};
  for (const name of Object.keys(STILLS).sort(byCodePoint)) {
    const shot = SHOTS[name];
    if (!shot || typeof shot.scene !== "function") { out[name] = true; continue; }
    const src = String(shot.scene);
    const reach = sceneReach(src, file);
    const units = [...src.matchAll(/\bunit:\s*["']([\w-]+)["']/g)].map((m) => m[1]);
    if (STILLS[name].unit) units.push(STILLS[name].unit);
    out[name] = reach.threeDoors || reach.unread || /little-rose/.test(src) || units.includes("little-rose") || (reach.doors && units.length === 0);
  }
  return out;
}

export async function stillsJson() {
  const door = await littleRoseDoorStills();
  const stills = {};
  for (const name of Object.keys(STILLS).sort(byCodePoint)) {
    const s = STILLS[name];
    const pictures = {};
    for (const layer of s.layers) pictures[layer] = stillLayer(name, layer);
    stills[name] = {
      kind: s.kind,
      unit: s.unit ?? null,
      page: s.page ?? null,
      motion: s.motion ?? null,
      layers: [...s.layers],
      aspect: stillAspect(name),
      ready: hasStill(name),
      showsLittleRoseDoor: door[name] ?? true,
      pictures,
    };
  }
  const hero = Object.fromEntries(HERO_LAYERS.map((l) => [l, heroLayer(l)]));
  return { nothing: NOTHING, stills, hero };
}

// The PHP builder, in JS: what HCWA_Theme_Art::still(), lit_still() and poster() print from stills.json.
const url = (base, p) => (p == null ? null : base + p);
const srcsetOf = (base, list, data) => (list ? list.map(([p, w]) => `${base}${p} ${w}w`).join(", ") : data.nothing);
export function pictureFromJson(data, name, layer, { cls = "", sizes = "100vw", eager = false, alt = "" } = {}, base = "/") {
  const pic = data.stills[name].pictures[layer];
  const sourceSizes = (crop) => (typeof sizes === "string" ? sizes : sizes[crop] ?? "100vw");
  const sources = pic.sources.map((s) => {
    const m = s.media ? ` media="${esc(s.media)}"` : "";
    if (!s.srcset) return `<source${m} srcset="${esc(data.nothing)}" width="${s.width}" height="${s.height}">`;
    return `<source type="${esc(s.type)}"${m} srcset="${esc(srcsetOf(base, s.srcset, data))}" sizes="${esc(sourceSizes(s.crop))}" width="${s.width}" height="${s.height}">`;
  });
  const load = eager ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"';
  return `<picture class="${esc(cls)}">${sources.join("")}<img src="${esc(url(base, pic.img.src) ?? data.nothing)}" alt="${esc(alt)}" width="${pic.img.width}" height="${pic.img.height}"${load} data-layer="${layer}"></picture>`;
}
export function litFromJson(data, name, base = "/") {
  const s = data.stills[name];
  const motion = s.motion ? " lit--" + s.motion : "";
  const screen = s.layers.includes("lightscreen") ? pictureFromJson(data, name, "lightscreen", { cls: "lit__screen" }, base) : "";
  return `<div class="lit lit--still${motion}" aria-hidden="true" data-still="${esc(name)}">\n  ${pictureFromJson(data, name, "base", { cls: "lit__base", eager: true }, base)}\n  ${pictureFromJson(data, name, "lightroom", { cls: "lit__light" }, base)}\n  ${screen}\n</div>`;
}
export function posterFromJson(data, layer, { cls, sizes = "100vw" } = {}, base = "/") {
  const pic = data.hero[layer];
  const sources = pic.sources.map((s) => {
    const m = s.media ? ` media="${esc(s.media)}"` : "";
    if (!s.srcset) return `<source${m} srcset="${esc(data.nothing)}">`;
    return `<source type="${esc(s.type)}"${m} srcset="${esc(srcsetOf(base, s.srcset, data))}" sizes="${esc(s.sizes ?? sizes)}">`;
  });
  const priority = layer === "base" ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"';
  return `<picture class="${esc(cls)}">${sources.join("")}<img src="${esc(url(base, pic.img.src) ?? data.nothing)}" alt="" width="${pic.img.width}" height="${pic.img.height}"${priority} data-layer="${layer}"></picture>`;
}

// ---------------------------------------------------------------------------------------------------
// The pages, rendered in memory (staging), for the calls they make
// ---------------------------------------------------------------------------------------------------

export function renderPagesInMemory() {
  const out = [];
  for (const [key, route] of pageRoutes) {
    if (route.prototypeOnly) continue;
    for (const lang of ["vi", "en"]) {
      const c = ctx(lang, key, false);
      out.push({ key, lang, html: str(layout(c, pages[route.page](c))) });
    }
  }
  return out;
}

// The Lucide paths are private to components.mjs: their names are read from its source, their markup
// from icon() itself.
export function iconNames() {
  const text = read(join(PROTO, "src/partials/components.mjs"));
  const body = text.slice(text.indexOf("const PATHS = {"), text.indexOf("\n};", text.indexOf("const PATHS = {")));
  return [...body.matchAll(/^\s+([a-zA-Z]\w*):\s*'/gm)].map((m) => m[1]);
}
const ICON_HEAD = /^<svg class="[^"]*" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">/;
export const iconInner = (name) => str(icon(name, "x")).replace(ICON_HEAD, "").replace(/<\/svg>$/, "");
export const quatrefoilInner = () => str(quatrefoil("x")).replace(/^<svg class="x" viewBox="-112 -112 224 224" aria-hidden="true" focusable="false">/, "").replace(/<\/svg>$/, "");

// ---------------------------------------------------------------------------------------------------
// Data for hcwa-core (prototype-data.php) and the smoke test (guards.json)
// ---------------------------------------------------------------------------------------------------

// A JS RegExp as a PCRE pattern with the u flag (render.mjs's patterns use no JS-only syntax; the
// export test runs each one through PHP's preg_match).
const pcre = (re) => `/${re.source.replace(/(?<!\\)\//g, "\\/")}/${re.flags.replace(/[gyd]/g, "")}u`;
const FOOTNOTE = /^\[\d+\]$/;

export function guards() {
  return {
    blockers: BLOCKERS.map(([re, why]) => ({ source: re.source, flags: re.flags, why })),
    littleRoseDoor: { source: LITTLE_ROSE_DOOR.source, flags: LITTLE_ROSE_DOOR.flags },
    footnote: { source: FOOTNOTE.source, flags: FOOTNOTE.flags },
    minPartnerLogos: MIN_PARTNER_LOGOS,
  };
}

const kebab = (s) => s.replace(/[A-Z]/g, (m) => "-" + m.toLowerCase());
const constArray = (file, name) => {
  const m = read(join(PROTO, file)).match(new RegExp(`const ${name} = (\\[[^\\]]*\\]);`));
  if (!m) throw new Error(`export-wp: ${name} not found in ${file}`);
  return JSON.parse(m[1]);
};
// The heading glossary is private to vikeep.mjs; it is read from the source and checked against viKeep().
export function glossary() {
  const text = read(join(PROTO, "src/lib/vikeep.mjs"));
  const m = text.match(/const GLOSSARY = \[([\s\S]*?)\];/);
  if (!m) throw new Error("export-wp: GLOSSARY not found in src/lib/vikeep.mjs");
  const list = JSON.parse(`[${m[1].replace(/,\s*$/, "")}]`);
  const sorted = [...list].sort((a, b) => b.length - a.length);
  for (const g of sorted) if (viKeep(g) !== g.normalize("NFC").replaceAll(" ", "\u00a0")) throw new Error(`export-wp: viKeep does not keep "${g}" whole; the glossary was not read right`);
  return sorted;
}

export function prototypeData(version) {
  const units = content.work.centers.map((c) => c.id);
  const areas = content.work.focusAreas.map((a) => a.id);
  const unknown = areas.filter((a) => !(a in AREA_BLOCK));
  if (unknown.length || Object.keys(AREA_BLOCK).length !== areas.length) throw new Error(`export-wp: focus areas and AREA_BLOCK differ: ${unknown.join(", ")}`);
  const holeMasks = {};
  for (const unit of units) {
    const emblem = EMBLEMS[unit];
    if (!emblem || doorLight(unit).transomPattern === "open") continue; // a plain transom throws no emblem
    const path = `svg/hole-${kebab(emblem)}.svg`;
    if (!existsSync(join(PROTO, "public", path))) throw new Error(`export-wp: ${path} is missing from public/`);
    holeMasks[emblem] = path;
  }
  const icons = Object.fromEntries(iconNames().map((n) => [n, iconInner(n)]));
  return {
    version,
    areas,
    statuses: constArray("src/pages/work.mjs", "STATUSES"),
    units,
    emblems: Object.fromEntries(units.filter((u) => EMBLEMS[u]).map((u) => [u, EMBLEMS[u]])),
    area_block: Object.fromEntries(areas.map((a) => [a, AREA_BLOCK[a]])),
    area_patch: Object.fromEntries(areas.map((a) => [a, [...AREA_PATCH[a]]])),
    tile: TILE,
    grout_px: GROUT_PX,
    hole_masks: holeMasks,
    vi_keep: glossary(),
    icons,
    quatrefoil: quatrefoilInner(),
    guards: {
      blockers: BLOCKERS.map(([re, why]) => ({ pattern: pcre(re), why })),
      little_rose_door: pcre(LITTLE_ROSE_DOOR),
      footnote: pcre(FOOTNOTE),
      min_partner_logos: MIN_PARTNER_LOGOS,
    },
    header_stills: Object.keys(STILLS).filter((n) => n.startsWith("header-")).sort(byCodePoint),
  };
}

// A PHP array literal in WordPress style (tabs, long array syntax, single quotes).
function php(v, depth = 1) {
  const pad = "\t".repeat(depth), close = "\t".repeat(depth - 1);
  if (v === null) return "null";
  if (typeof v === "boolean") return v ? "true" : "false";
  if (typeof v === "number") { if (!Number.isFinite(v)) throw new Error("export-wp: non-finite number"); return Number.isInteger(v) ? String(v) : String(v); }
  if (typeof v === "string") return `'${v.replace(/\\/g, "\\\\").replace(/'/g, "\\'")}'`;
  if (Array.isArray(v)) return v.length ? `array(\n${v.map((x) => pad + php(x, depth + 1) + ",").join("\n")}\n${close})` : "array()";
  const entries = Object.entries(v);
  return entries.length ? `array(\n${entries.map(([k, x]) => `${pad}${php(k)} => ${php(x, depth + 1)},`).join("\n")}\n${close})` : "array()";
}

export function prototypeDataPhp(data) {
  return `<?php
/**
 * GENERATED by prototype/scripts/export-wp.mjs (npm --prefix prototype run export:wp). Do not edit.
 *
 * The prototype's fixed data, read by hcwa-core through hcwa_prototype_data() (docs/plan/11-WP-PORT.md
 * 6.4): focus areas, statuses, units and their emblems, the gạch tile numbers, the heading glossary,
 * the Lucide icon paths, the quatrefoil, and render.mjs's production guards as PCRE patterns.
 * 'version' is the sha256 of the inputs listed in themes/hcwa-2026/assets/generated/sources.json.
 *
 * @package hcwa-core
 */

defined( 'ABSPATH' ) || exit;

return ${php(data)};
`;
}

// ---------------------------------------------------------------------------------------------------
// classes.json: every class the bundle CSS styles (the static check reads it instead of dist/)
// ---------------------------------------------------------------------------------------------------

export function cssClasses(cssTexts) {
  const out = new Set();
  for (const css of cssTexts) {
    const text = css.replace(/\/\*[\s\S]*?\*\//g, "").replace(/"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'/g, '""').replace(/url\([^)]*\)/g, "url()");
    let prelude = "";
    for (const ch of text) {
      if (ch === "{") {
        const sel = prelude.trim();
        if (sel && !sel.startsWith("@")) for (const [, c] of sel.replace(/\[[^\]]*\]/g, "").matchAll(/\.(-?[_a-zA-Z][\w-]*)/g)) out.add(c);
        prelude = "";
      } else if (ch === "}" || ch === ";") prelude = "";
      else prelude += ch;
    }
  }
  return [...out].sort(byCodePoint);
}

// Every class the prototype's pages print.
export function printedClasses(rendered) {
  const out = new Set();
  for (const { html } of rendered) for (const [, c] of html.matchAll(/\sclass="([^"]*)"/g)) for (const k of c.split(/\s+/)) if (k) out.add(k);
  return [...out].sort(byCodePoint);
}

export function bundleCss(dist = DIST) {
  const manifest = readManifest(dist);
  if (!manifest) return null;
  const css = new Set();
  for (const key of ["src/main.js", "src/styles/draft.css", "src/motion/index.js"]) {
    const e = manifest[key];
    if (!e) throw new Error(`export-wp: ${key} is missing from dist/manifest.json`);
    if (e.file.endsWith(".css")) css.add(e.file);
    for (const f of e.css ?? []) css.add(f);
  }
  return [...css].sort(byCodePoint).map((f) => ({ file: f, text: read(join(dist, f)) }));
}

// ---------------------------------------------------------------------------------------------------
// Golden fixtures (11-WP-PORT.md 5.8): inputs and the prototype's own outputs, for the PHP ports
// ---------------------------------------------------------------------------------------------------

// Every localized string ({ vi, en } fields), or with keyTest only those under a matching key.
function localizedStrings(value, out = new Set(), keyTest = null, key = "") {
  if (Array.isArray(value)) value.forEach((v) => localizedStrings(v, out, keyTest, key));
  else if (value && typeof value === "object") {
    if (!keyTest || keyTest.test(key)) for (const lang of ["vi", "en"]) if (typeof value[lang] === "string") out.add(value[lang]);
    for (const [k, v] of Object.entries(value)) if (k !== "vi" && k !== "en") localizedStrings(v, out, keyTest, k);
  }
  return out;
}

export function golden(stills, rendered) {
  const all = [...localizedStrings({ ...content, ui: Object.values(content.ui) })].filter((s) => s.trim()).sort(byCodePoint);
  // splitWords runs on the passage statement; the cases are that statement in both languages, every
  // heading in content/draft (fields named *title or *name) and the edge cases (runs of spaces, NFD
  // letters, & and <).
  const statement = Object.values(content.site.whatWeDo.summary).filter((v) => typeof v === "string");
  const headings = [...localizedStrings(content, new Set(), /title$|name$/i)];
  const edges = ["  hai   khoảng trắng  ", "trẻ em".normalize("NFD"), "A & B <c>", "một"];
  const split = [...new Set([...statement, ...headings, ...edges])].filter((s) => s.trim()).sort(byCodePoint);
  const g = {};
  g._note = "GENERATED by prototype/scripts/export-wp.mjs from the prototype's own functions. Do not edit; each PHP port must reproduce every output exactly (docs/plan/11-WP-PORT.md 5.8).";
  g.split_words = split.map((s) => { const r = splitWords(s); return { in: s, count: r.count, html: str(r.words) }; });
  g.vi_keep = all.map((s) => ({ in: s, out: viKeep(s) }));
  g.keep_hyphens = all.map((s) => ({ in: s, out: str(keepHyphens(s)) }));

  // Dates: every seeded post date, both languages, as datedPost splits them.
  g.dates = [];
  for (const iso of [...new Set(content.news.posts.map((p) => p.published))].sort(byCodePoint)) {
    for (const lang of ["vi", "en"]) {
      const text = ctx(lang, "home", false).date(iso);
      const [, dm, sep, year] = text.match(/^(.*?)(\D)(\d{4})$/);
      g.dates.push({ iso, lang, text, dm, sep, year });
    }
  }

  // Icons and quatrefoils with every class the pages use, read from the rendered pages.
  const names = iconNames(), inner = Object.fromEntries(names.map((n) => [iconInner(n), n]));
  const iconCases = new Map(), quatCases = new Map(), strips = new Set();
  for (const { html } of rendered) {
    for (const m of html.matchAll(/<svg class="([^"]*)" viewBox="0 0 24 24"[^>]*>(.*?)<\/svg>/g)) {
      const name = inner[m[2]];
      if (!name) throw new Error(`export-wp: an icon on a page matches no Lucide path: ${m[0].slice(0, 120)}`);
      iconCases.set(`${name} ${m[1]}`, { name, cls: unesc(m[1]), html: m[0] });
    }
    for (const m of html.matchAll(/<svg class="([^"]*)" viewBox="-112 -112 224 224"[^>]*>.*?<\/svg>/g)) quatCases.set(m[1], { cls: unesc(m[1]), html: m[0] });
    for (const m of html.matchAll(/<svg class="(gach-strip[^"]*)"[^>]*>.*?<\/svg>/g)) strips.add(m[0]);
  }
  for (const n of names) iconCases.set(`${n} icon`, { name: n, cls: "icon", html: str(icon(n)) });
  quatCases.set("quatrefoil", { cls: "quatrefoil", html: str(quatrefoil()) });
  g.icons = [...iconCases.values()].sort((a, b) => byCodePoint(a.name + a.cls, b.name + b.cls));
  g.quatrefoil = [...quatCases.values()].sort((a, b) => byCodePoint(a.cls, b.cls));

  // The gạch art the theme serves (HCWA_Theme_Art): sprite, patches, strips, transoms, plate marks.
  g.gach_sprite = str(gachSprite());
  g.gach_art = [];
  for (const area of content.work.focusAreas.map((a) => a.id)) {
    for (const variant of ["art", "lit"]) g.gach_art.push({ area, variant, html: str(gachArt(AREA_BLOCK[area], AREA_PATCH[area], `gach__${variant}`)) });
  }
  const sets = new Map();
  const addSet = (areas) => sets.set(areas.join(" "), areas);
  for (const p of content.work.programs) addSet(p.areas);
  for (const a of content.work.focusAreas) addSet([a.id]);
  addSet(content.work.focusAreas.map((a) => a.id));
  addSet(["education", "not-an-area"]);
  addSet(["not-an-area"]);
  addSet([]);
  g.gach_strip = [];
  for (const cls of ["gach-strip program-row__tiles", "gach-strip chip__tile"]) {
    for (const areas of sets.values()) g.gach_strip.push({ areas, cls, html: str(gachStrip(areas, cls)) });
  }
  const stripOut = new Set(g.gach_strip.map((c) => c.html));
  for (const s of strips) if (!stripOut.has(s)) throw new Error(`export-wp: a page prints a gạch strip the golden cases miss: ${s.slice(0, 120)}`);
  const emblems = [...new Set(Object.values(EMBLEMS))].sort(byCodePoint);
  g.screen_art = [], g.block_icon = [];
  for (const emblem of emblems) {
    for (const cls of ["rail__transom", "hcwa-test-art"]) g.screen_art.push({ emblem, cls, html: str(screenArt(emblem, { cols: 3, rows: 1, cls })) });
    for (const cls of ["plate__mark", "hcwa-test-art"]) g.block_icon.push({ emblem, cls, html: str(blockIcon(emblem, cls)) });
  }

  // Stills: the prototype's own pictures for every ready still and option set the PHP API takes.
  const variants = [
    {},
    { cls: "door__img", sizes: "(min-width: 1024px) 76vw, 100vw" },
    { cls: "feature__still", sizes: { desk: "40vw" } },
    { cls: "gap__img", sizes: "(min-width: 1024px) 46vw, 100vw", eager: true, alt: "Một viên gạch & <ánh sáng>" },
  ];
  g.still = [], g.lit_still = [], g.poster = [];
  g.still_aspect = {}, g.has_still = {}, g.shows_little_rose_door = {};
  for (const name of Object.keys(STILLS).sort(byCodePoint)) {
    g.still_aspect[name] = stillAspect(name);
    g.has_still[name] = hasStill(name);
    g.shows_little_rose_door[name] = stills.stills[name].showsLittleRoseDoor;
    if (!hasStill(name)) continue;
    if (STILLS[name].layers.includes("still")) {
      // Every option on one still of each crop set; the default call on every still.
      const first = Object.keys(STILLS).find((n) => STILLS[n].kind === STILLS[name].kind && STILLS[n].layers.includes("still") && hasStill(n));
      for (const o of first === name ? variants : variants.slice(0, 1)) g.still.push({ name, opts: o, html: str(stillPicture(name, "still", o)) });
    }
    if (STILLS[name].layers.includes("base")) g.lit_still.push({ name, html: str(litStill(name)) });
  }
  for (const layer of HERO_LAYERS) {
    for (const o of [{ cls: { base: "lit__base", lightroom: "lit__light", lightscreen: "lit__screen" }[layer] }, { cls: "hcwa-test", sizes: "50vw" }]) {
      g.poster.push({ shot: "hero", layer, opts: o, html: str(poster("hero", layer, o)) });
    }
  }
  // Every <picture> the pages print (except the logo, which the header builds) is one of the cases above.
  const known = new Set([...g.still, ...g.poster].map((c) => c.html));
  for (const c of g.lit_still) for (const m of c.html.matchAll(/<picture class="[^"]*">.*?<\/picture>/g)) known.add(m[0]);
  for (const { key, lang, html } of rendered) {
    for (const m of html.matchAll(/<picture class="[^"]*">.*?<\/picture>/g)) {
      if (known.has(m[0])) continue;
      const pic = parsePicture(m[0]);
      const first = pic.sources.find((s) => !isData(s.srcset))?.srcset ?? "";
      const name = Object.keys(STILLS).find((n) => first.startsWith(`/posters/${n}-`) && cropsOf(n).some((c) => first.startsWith(`/posters/${n}-${c.name}-`)));
      if (!name) throw new Error(`export-wp: ${key} ${lang}: a <picture> from no known still: ${m[0].slice(0, 160)}`);
      const sizesAll = pic.sources.filter((s) => s.sizes).map((s) => s.sizes);
      const o = { cls: pic.cls };
      if (sizesAll.length && sizesAll.some((s) => s !== "100vw")) o.sizes = sizesAll.every((s) => s === sizesAll[0]) ? sizesAll[0] : undefined;
      if (pic.img.fetchpriority === "high") o.eager = true;
      if (pic.img.alt) o.alt = pic.img.alt;
      const out = str(stillPicture(name, pic.img["data-layer"], o));
      if (out !== m[0]) throw new Error(`export-wp: ${key} ${lang}: could not tell the call behind a <picture> of ${name}`);
      g.still.push({ name, opts: o, html: out });
      known.add(out);
    }
  }
  g.head_inline = { staging: headInlineJs(false), production: headInlineJs(true) };
  g.page_reveal = pageRevealJs();
  return g;
}

// One case per line: small and easy to diff.
export function goldenText(g) {
  const section = ([k, v]) => {
    const body = Array.isArray(v) ? `[\n${v.map((x) => "\t" + JSON.stringify(x)).join(",\n")}\n]`
      : v && typeof v === "object" ? `{\n${Object.entries(v).map(([a, b]) => `\t${JSON.stringify(a)}: ${JSON.stringify(b)}`).join(",\n")}\n}`
        : JSON.stringify(v);
    return `${JSON.stringify(k)}: ${body}`;
  };
  return `{\n${Object.entries(g).map(section).join(",\n")}\n}\n`;
}

// ---------------------------------------------------------------------------------------------------
// Everything together
// ---------------------------------------------------------------------------------------------------

export const headInlineJs = (prod) => str(headInline(prod)).replace(/^<script>/, "").replace(/<\/script>$/, "");
export const pageRevealJs = () => `(${pageReveal})();`;

// The prototype files the generated files are made from (wp-static-check.py checks them against
// these hashes; a folder entry hashes its sorted file list).
export function inputPaths() {
  const list = new Set([
    "prototype/scripts/export-wp.mjs",
    "prototype/scripts/tokens-to-js.mjs",
    "prototype/vite.wp.config.js",
    "prototype/package-lock.json",
    "prototype/src/head-inline.js",
    "prototype/src/render.mjs",
    "prototype/src/routes.mjs",
    "prototype/src/motion/transitions.js",
    "prototype/src/world/still-shots.js",
    "prototype/src/world/buckets.js",
    "prototype/src/world/sets/stills.js",
    "prototype/src/world/glsl/blocks.js",
    "prototype/src/world/objects/door.js",
    "prototype/public/posters/",
    // Not a prototype file: its inline form is hashed for the CSP list.
    "wp/themes/hcwa-2026/assets/js/wp-bars.js",
  ]);
  for (const dir of ["src/lib", "src/partials", "src/pages", "src/styles"]) for (const f of files(join(PROTO, dir))) list.add(rel(f));
  for (const f of files(join(REPO, "content/draft"))) if (f.endsWith(".json")) list.add(rel(f));
  return [...list].sort(byCodePoint);
}

export function hashInput(path) {
  const abs = join(REPO, path.replace(/\/$/, ""));
  if (path.endsWith("/")) return sha256(files(abs).map((f) => relative(abs, f).replaceAll("\\", "/")).sort(byCodePoint).join("\n"));
  return sha256(readFileSync(abs));
}

// The theme's two bar scripts as inc/head.php hcwa_theme_bar_script() builds them from wp-bars.js: the
// file's function without its leading comment, called with the element just printed.
export const WP_BARS = join(THEME, "assets/js/wp-bars.js");
export function barScripts() {
  const js = read(WP_BARS).replace(/^\s*\/\*[\s\S]*?\*\//, "").trim().replace(/;+$/, "");
  return Object.fromEntries(["en", "announce"].map((which) => [`wp-bars ${which}`, `${js}(document.currentScript.previousElementSibling, "${which}");`]));
}
// CSP script-src values ('sha256-…', base64) for each inline script as wp_get_inline_script_tag()
// prints it: the text trimmed and wrapped in one newline each side.
const cspHashes = (scripts) => Object.fromEntries(Object.entries(scripts).map(([name, js]) => [name, `'sha256-${createHash("sha256").update(`\n${js.trim()}\n`).digest("base64")}'`]));

// All generated text files, keyed by repo-relative path. dist: the built bundle, for classes.json
// (left out when there is none, as in the export test).
export async function generate({ dist = DIST } = {}) {
  const inputs = Object.fromEntries(inputPaths().map((p) => [p, hashInput(p)]));
  const version = sha256(JSON.stringify(inputs));
  const out = new Map();
  const gen = (p) => rel(join(GENERATED, p));

  const staging = headInlineJs(false), production = headInlineJs(true);
  for (const [variant, js] of [["staging", staging], ["production", production]]) {
    if (Buffer.byteLength(js) > 1536) throw new Error(`export-wp: head-inline.${variant}.js is over 1536 B`);
    out.set(gen(`head-inline.${variant}.js`), js);
  }
  out.set(gen("page-reveal.js"), pageRevealJs());

  out.set(gen("svg/gach-sprite.svg"), str(gachSprite()));
  for (const a of content.work.focusAreas.map((x) => x.id)) {
    for (const v of ["art", "lit"]) out.set(gen(`svg/gach-${v}-${a}.svg`), str(gachArt(AREA_BLOCK[a], AREA_PATCH[a], `gach__${v}`)));
  }
  for (const emblem of [...new Set(Object.values(EMBLEMS))].sort(byCodePoint)) {
    out.set(gen(`svg/rail-transom-${emblem}.svg`), str(screenArt(emblem, { cols: 3, rows: 1, cls: "rail__transom" })));
    out.set(gen(`svg/plate-mark-${emblem}.svg`), str(blockIcon(emblem, "plate__mark")));
  }

  const stills = await stillsJson();
  // The PHP builder's algorithm, in JS, must give back every picture the prototype prints.
  for (const [name, s] of Object.entries(stills.stills)) {
    for (const layer of s.layers) {
      for (const o of [{}, { cls: "a b", sizes: "33vw", eager: true, alt: "x & y" }, { sizes: { desk: "50vw", mobile: "90vw" } }]) {
        if (pictureFromJson(stills, name, layer, o) !== str(stillPicture(name, layer, o))) throw new Error(`export-wp: stills.json does not rebuild ${name} ${layer}`);
      }
    }
    if (s.layers.includes("base") && litFromJson(stills, name) !== str(litStill(name))) throw new Error(`export-wp: stills.json does not rebuild the lit still ${name}`);
  }
  for (const layer of HERO_LAYERS) {
    for (const o of [{ cls: "lit__base" }, { cls: "x", sizes: "50vw" }]) {
      if (posterFromJson(stills, layer, o) !== str(poster("hero", layer, o))) throw new Error(`export-wp: stills.json does not rebuild the hero ${layer}`);
    }
  }
  out.set(gen("stills.json"), JSON.stringify(stills, null, "\t") + "\n");
  out.set(gen("guards.json"), JSON.stringify(guards(), null, "\t") + "\n");

  const rendered = renderPagesInMemory();
  const css = dist ? bundleCss(dist) : null;
  if (css) {
    const classes = cssClasses(css.map((c) => c.text));
    out.set(gen("classes.json"), JSON.stringify({
      _note: "GENERATED by prototype/scripts/export-wp.mjs. classes: every class the bundle CSS (main, draft, motion) styles. unstyled: classes the prototype's own pages print that no rule names (hooks such as dusk__img); WordPress may print them too.",
      files: css.map((c) => c.file.replace(/-[\w-]{8}\.css$/, ".css")),
      classes,
      unstyled: printedClasses(rendered).filter((c) => !classes.includes(c)),
    }, null, "\t") + "\n");
  }

  out.set(rel(PROTOTYPE_DATA), prototypeDataPhp(prototypeData(version)));
  out.set(rel(GOLDEN), goldenText(golden(stills, rendered)));

  const outputs = Object.fromEntries([...out.entries()].sort(([a], [b]) => byCodePoint(a, b)).map(([p, text]) => [p, sha256(text)]));
  out.set(gen("sources.json"), JSON.stringify({
    _note: "GENERATED by prototype/scripts/export-wp.mjs. inputs: sha256 of each prototype file the generated files are made from (a folder: of its sorted file list); outputs: sha256 of each generated file. wp-static-check.py fails when either drifts; run `npm --prefix prototype ci && npm --prefix prototype run export:wp`. csp: the hash of every inline script the theme prints, as WordPress prints it, for a future Content-Security-Policy script-src.",
    version,
    inputs,
    outputs,
    csp: cspHashes({ "head-inline.staging.js": staging, "head-inline.production.js": production, "page-reveal.js": pageRevealJs(), ...barScripts() }),
  }, null, "\t") + "\n");
  return out;
}

// theme.json: the 8 editor colours and the two font stacks, from tokens.css (11-WP-PORT.md 1.5).
// Every other key is hand-kept in theme.json. Written in its hand-kept style: array items whose values
// are all plain on one line.
export const PALETTE = ["ink-900", "ink-700", "blue-700", "blue-900", "sun-400", "sun-100", "lime-50", "lime-100"];
const flat = (x) => x === null || typeof x !== "object" || (Array.isArray(x) && x.every((y) => y === null || typeof y !== "object"));
export function prettyJson(v, depth = 0, item = false) {
  const pad = "\t".repeat(depth + 1), close = "\t".repeat(depth);
  if (v === null || typeof v !== "object") return JSON.stringify(v);
  if (Array.isArray(v)) {
    if (!v.length) return "[]";
    if (flat(v)) return `[ ${v.map((x) => JSON.stringify(x)).join(", ")} ]`;
    return `[\n${v.map((x) => pad + prettyJson(x, depth + 1, true)).join(",\n")}\n${close}]`;
  }
  const entries = Object.entries(v);
  if (!entries.length) return "{}";
  if (item && entries.every(([, x]) => flat(x))) return `{ ${entries.map(([k, x]) => `${JSON.stringify(k)}: ${prettyJson(x)}`).join(", ")} }`;
  return `{\n${entries.map(([k, x]) => `${pad}${JSON.stringify(k)}: ${prettyJson(x, depth + 1)}`).join(",\n")}\n${close}}`;
}
export function themeJson(current) {
  const css = read(join(PROTO, "src/styles/tokens.css"));
  const { colors } = parseTokens(css);
  const json = JSON.parse(current);
  json.settings.color.palette = PALETTE.map((slug) => {
    if (!colors[slug]) throw new Error(`export-wp: --color-${slug} is not in tokens.css`);
    const [family, step] = slug.split("-");
    return { name: `${family[0].toUpperCase()}${family.slice(1)} ${step}`, slug, color: colors[slug] };
  });
  for (const family of json.settings.typography?.fontFamilies ?? []) {
    const stack = css.match(new RegExp(`--font-${family.slug}:\\s*([^;]+);`))?.[1]?.trim();
    if (!stack) throw new Error(`export-wp: --font-${family.slug} is not in tokens.css`);
    family.fontFamily = stack;
  }
  return prettyJson(json) + "\n";
}

async function main() {
  const noBuild = process.argv.includes("--no-build");
  if (!noBuild) build();
  else if (!readManifest()) throw new Error("export-wp: --no-build needs an existing assets/dist (run without it once)");
  const out = await generate({ dist: DIST });
  // Generated files only: remove what an earlier export wrote and this one did not.
  if (existsSync(GENERATED)) for (const f of files(GENERATED)) if (!out.has(rel(f))) rmSync(f);
  let changed = 0;
  for (const [p, text] of out) {
    const abs = join(REPO, p);
    if (existsSync(abs) && read(abs) === text) continue;
    mkdirSync(dirname(abs), { recursive: true });
    writeFileSync(abs, text);
    changed++;
  }
  const theme = themeJson(read(THEME_JSON));
  if (read(THEME_JSON) !== theme) { writeFileSync(THEME_JSON, theme); changed++; }
  const size = (p) => statSync(join(REPO, p)).size;
  console.log(`export:wp → ${rel(DIST)} (bundle), ${out.size} generated files, ${changed} changed`);
  for (const p of [...out.keys()].filter((k) => !k.includes("/svg/"))) console.log(`  ${p} ${size(p)} B`);
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  main().catch((e) => {
    console.error(e.message);
    process.exit(1);
  });
}
