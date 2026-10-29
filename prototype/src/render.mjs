// Renders every prototype page in VI and EN to static HTML (default: prototype/.site).
// PROD=1 leaves out unverified figures and fails if anything unapproved would ship (08 task 1.6).
import { existsSync, mkdirSync, readdirSync, rmSync, writeFileSync } from "node:fs";
import { dirname, join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { str } from "./lib/html.mjs";
import { ctx } from "./lib/ctx.mjs";
import { pageRoutes } from "./routes.mjs";
import { layout } from "./partials/layout.mjs";
import { pages } from "./pages/index.mjs";

// What must never reach production HTML.
export const BLOCKERS = [
  [/data-draft/, "draft text (data-draft)"],
  [/\[DRAFT/, "[DRAFT marker"],
  [/data-pending/, "unverified figure"],
  [/data-donation-placeholder/, "donation placeholder"],
  // Any [token] in visible text ([N], [year], [ngày]…), even where a template forgot data-draft.
  [/>[^<]*\[[^\]<]{1,80}\][^<]*</, "bracket placeholder token in text"],
];

// The renderer only cleans folders it created, so a mistyped path can't wipe real files.
const MARKER = ".hcwa-render";
const htmlFiles = (dir) =>
  readdirSync(dir, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? htmlFiles(join(dir, e.name)) : e.name.endsWith(".html") ? [join(dir, e.name)] : []));

export function findBlockers(htmlText) {
  return BLOCKERS.filter(([re]) => re.test(htmlText)).map(([, why]) => why);
}

export function renderAll(outDir, prod) {
  if (existsSync(outDir) && readdirSync(outDir).length && !existsSync(join(outDir, MARKER))) {
    throw new Error(`Refusing to render into ${outDir}: it has files and no ${MARKER} marker`);
  }
  mkdirSync(outDir, { recursive: true });
  writeFileSync(join(outDir, MARKER), "rendered by prototype/src/render.mjs\n");
  const stale = new Set(htmlFiles(outDir));
  const problems = [];
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
  const entry = relative(outDir, fileURLToPath(new URL("./main.js", import.meta.url))).replaceAll("\\", "/");
  writeFileSync(join(outDir, "main.js"), `import "${entry.startsWith(".") ? entry : "./" + entry}";\n`);
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
  } catch (e) {
    console.error(e.message);
    process.exit(1);
  }
}
