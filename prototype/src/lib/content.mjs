import { existsSync, readFileSync, readdirSync } from "node:fs";
import { fileURLToPath } from "node:url";

// content/ sits at the repo root so the WordPress phase can reuse it.
const dir = fileURLToPath(new URL("../../../content/draft/", import.meta.url));
const load = (name) => JSON.parse(readFileSync(dir + name + ".json", "utf8"));
// Files a stream adds later (PLAN-M4 7.1): an empty object until the file exists.
const loadOptional = (name) => (existsSync(dir + name + ".json") ? load(name) : {});

// ui.json plus every ui-*.json beside it (ui-world.json from the 3D story stream, ui-pages.json and
// ui-openings.json from the M4 streams). Each stream keeps its strings in its own file; a key in two files
// is a build error, so no stream can quietly override another's wording. _note keys never render.
export function mergeUi(files) {
  const ui = {};
  const from = {};
  for (const [file, strings] of files) {
    for (const k of Object.keys(strings)) {
      if (k === "_note") continue;
      if (k in ui) throw new Error(`${file} repeats the ui key "${k}" (already in ${from[k]})`);
      ui[k] = strings[k];
      from[k] = file;
    }
  }
  return ui;
}

const uiFiles = () => [
  ["ui.json", load("ui")],
  ...readdirSync(dir)
    .filter((f) => /^ui-[\w-]+\.json$/.test(f))
    .sort()
    .map((f) => [f, load(f.slice(0, -5))]),
];

const { figures, ...impactPage } = load("impact");

export const content = {
  site: load("site"),
  ui: mergeUi(uiFiles()),
  impact: figures,
  // The Impact & Reports page's own strings and its report library (PLAN-M4 4.6); figures stay above.
  impactPage,
  work: load("work"),
  news: load("news"),
  involve: load("involve"),
  // PLAN-M4: About pages (who we are, story, team, partners, policies) and the Get Involved subpages.
  about: loadOptional("about"),
  involvePages: loadOptional("involve-pages"),
  // Safeguarding switches for the world (traces, the Little Rose door); read by the PROD guards.
  world: load("world"),
};

// Unverified figures never render on production; on the prototype they show as tokens.
export const visibleFigures = (figures, prod) => figures.filter((f) => f.verified || !prod);
