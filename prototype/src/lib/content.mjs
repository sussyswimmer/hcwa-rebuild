import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";

// content/ sits at the repo root so the WordPress phase can reuse it.
const dir = fileURLToPath(new URL("../../../content/draft/", import.meta.url));
const load = (name) => JSON.parse(readFileSync(dir + name + ".json", "utf8"));

export const content = {
  site: load("site"),
  ui: load("ui"),
  impact: load("impact").figures,
  work: load("work"),
  news: load("news"),
  involve: load("involve"),
};

// Unverified figures never render on production; on the prototype they show as tokens.
export const visibleFigures = (figures, prod) => figures.filter((f) => f.verified || !prod);
