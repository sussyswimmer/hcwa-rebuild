// Renders every prototype page in VI and EN to static HTML (default: prototype/.site).
// PROD=1 leaves out unverified figures and fails if anything unapproved would ship (08 task 1.6).
import { existsSync, mkdirSync, readdirSync, rmSync, writeFileSync } from "node:fs";
import { dirname, join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { str } from "./lib/html.mjs";
import { ctx } from "./lib/ctx.mjs";
import { content } from "./lib/content.mjs";
import { pageRoutes } from "./routes.mjs";
import { layout } from "./partials/layout.mjs";
import { pages } from "./pages/index.mjs";
import { missingPosters, BUCKETS, WIDTHS, FORMATS } from "./lib/posters.mjs";

// What must never reach production HTML.
export const BLOCKERS = [
  [/data-draft/, "draft text (data-draft)"],
  [/\[DRAFT/, "[DRAFT marker"],
  [/data-pending/, "unverified figure"],
  [/data-donation-placeholder/, "donation placeholder"],
  // Any [token] in visible text ([N], [year], [ngày]…), even where a template forgot data-draft.
  [/>[^<]*\[[^\]<]{1,80}\][^<]*</, "bracket placeholder token in text"],
  // PLAN section 6: the featured story waits for an approved story in content/verified/, the partner
  // strip for four approved logos. Production leaves both out; these catch a leak.
  [/data-story-placeholder/, "featured story placeholder"],
  [/data-partners-pending/, "partners placeholder strip"],
  // PLAN-M4 4.3: the Team page's places wait for people who agree to be named; production leaves them out.
  [/data-team-pending/, "team placeholder (people not yet agreed to be listed)"],
];

// HCWA safeguarding sign-off (05-DESIGN D6.8 and section 13, PLAN section 6): until world.json says
// approved, no page may show the Little Rose door in any form (the corridor bay the live world builds its
// door from, a door or bay still, a painted door), and traces may not be switched on.
export const LITTLE_ROSE_DOOR = /data-unit="little-rose"[^>]*\sdata-door="|(?:door|bay)-little-rose-|hub-doors-wide/;
export const littleRoseDoorLeak = (htmlText, world) => world?.littleRoseDoor?.approved !== true && LITTLE_ROSE_DOOR.test(htmlText);
export const tracesBlocked = (world) => world?.traces?.enabled === true && world?.traces?.approved !== true;

// A partners section needs at least four approved one-tone logos (05-DESIGN D7, PLAN H9).
export const MIN_PARTNER_LOGOS = 4;
export const partnersLeak = (htmlText, approved) => /\sdata-partners="/.test(htmlText) && approved < MIN_PARTNER_LOGOS;
const approvedLogos = () => (content.site.partnerLogos ?? []).filter((l) => l.approved === true).length;

// The renderer only cleans folders it created, so a mistyped path can't wipe real files.
const MARKER = ".hcwa-render";
const htmlFiles = (dir) =>
  readdirSync(dir, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? htmlFiles(join(dir, e.name)) : e.name.endsWith(".html") ? [join(dir, e.name)] : []));

export function findBlockers(htmlText, { approved = approvedLogos(), world = content.world } = {}) {
  const found = BLOCKERS.filter(([re]) => re.test(htmlText)).map(([, why]) => why);
  if (partnersLeak(htmlText, approved)) found.push(`partners section with ${approved} approved logo(s), fewer than ${MIN_PARTNER_LOGOS}`);
  if (littleRoseDoorLeak(htmlText, world)) found.push("Little Rose door without HCWA safeguarding sign-off (world.json littleRoseDoor)");
  return found;
}

export function renderAll(outDir, prod) {
  if (existsSync(outDir) && readdirSync(outDir).length && !existsSync(join(outDir, MARKER))) {
    throw new Error(`Refusing to render into ${outDir}: it has files and no ${MARKER} marker`);
  }
  mkdirSync(outDir, { recursive: true });
  writeFileSync(join(outDir, MARKER), "rendered by prototype/src/render.mjs\n");
  const stale = new Set(htmlFiles(outDir));
  const problems = prod && tracesBlocked(content.world) ? ["content/draft/world.json: traces enabled without HCWA safeguarding approval"] : [];
  const files = [];
  for (const [key, route] of pageRoutes) {
    if (prod && route.prototypeOnly) continue;
    for (const lang of ["vi", "en"]) {
      const c = ctx(lang, key, prod);
      const page = pages[route.page](c);
      const out = str(layout(c, page));
      const path = route[lang];
      const file = join(outDir, path.endsWith(".html") ? path : join(path, "index.html"));
      mkdirSync(dirname(file), { recursive: true });
      writeFileSync(file, out);
      files.push(file);
      stale.delete(file);
      if (prod) for (const why of findBlockers(out)) problems.push(`${path}: ${why}`);
    }
  }
  // Pages from an earlier render that no longer exist (the folder itself stays, so Vite's watcher keeps working).
  for (const f of stale) rmSync(f);
  // Vite's HTML entry: pulls in the client script and styles that live outside the root.
  // Draft outlines and the prototype note only exist on staging (05-DESIGN D7), so PROD never imports them.
  const rel = (file) => {
    const r = relative(outDir, fileURLToPath(new URL(file, import.meta.url))).replaceAll("\\", "/");
    return r.startsWith(".") ? r : "./" + r;
  };
  writeFileSync(join(outDir, "main.js"), `${prod ? "" : `import "${rel("./styles/draft.css")}";\n`}import "${rel("./main.js")}";\n`);
  if (problems.length) {
    const unique = [...new Set(problems)];
    throw new Error(`PROD build blocked: ${unique.length} problem(s)\n  ` + unique.join("\n  "));
  }
  return files;
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const out = resolve(process.argv[2] ?? fileURLToPath(new URL("../.site", import.meta.url)));
  try {
    const files = renderAll(out, process.env.PROD === "1");
    console.log(`rendered ${files.length} pages → ${out}`);
    // The stills come from the world's poster renderer; until they exist the lit header shows its shade fill.
    const missing = missingPosters("hero").length;
    const all = BUCKETS.length * WIDTHS.length * Object.values(FORMATS).reduce((n, exts) => n + exts.length, 0);
    if (missing) console.warn(`posters: ${missing} of ${all} hero stills not rendered yet (npm run posters); the hero holds without them`);
  } catch (e) {
    console.error(e.message);
    process.exit(1);
  }
}
