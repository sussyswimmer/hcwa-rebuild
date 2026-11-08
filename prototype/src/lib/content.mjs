import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";

// content/ sits at the repo root so the WordPress phase can reuse it.
const dir = fileURLToPath(new URL("../../../content/draft/", import.meta.url));
const load = (name) => JSON.parse(readFileSync(dir + name + ".json", "utf8"));
const mergeUi = (ui, extra) => {
  const clash = Object.keys(extra).filter((k) => k !== "_note" && k in ui);
  if (clash.length) throw new Error(`ui-world.json repeats ui.json keys: ${clash.join(", ")}`);
  return { ...extra, ...ui };
};

export const content = {
  site: load("site"),
  // ui-world.json holds the 3D story stream's strings; a key in both files is a build error.
  ui: mergeUi(load("ui"), load("ui-world")),
  impact: load("impact").figures,
  work: load("work"),
  news: load("news"),
  involve: load("involve"),
  // Safeguarding switches for the world (traces, the Little Rose door); read by the PROD guards.
  world: load("world"),
};

// Unverified figures never render on production; on the prototype they show as tokens.
export const visibleFigures = (figures, prod) => figures.filter((f) => f.verified || !prod);
