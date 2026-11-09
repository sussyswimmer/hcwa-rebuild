// Writes the bông gió block set as SVG masks (PLAN section 5: sheet edges, footer edge, cast light),
// from the same SDF numbers the shaders use (src/world/glsl/blocks.js through src/partials/blocks.mjs).
// face-<kind>.svg: the block with its holes cut (sheet and footer edges); hole-<kind>.svg: the holes only
// (the CSS sun layer: light through that block); sun-hoa-thi.svg: the Hoa thị sunlight as it lands on the
// floor (the gạch bông light band, 05-DESIGN 0.6.3); cement.svg: the speckle of the floor's cement ground;
// plain-tiles.svg: the plain tiles' tones round the patches (fix r2).
// Run with npm run svg; fails over the 6 KB budget.
import { mkdirSync, readdirSync, rmSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { KINDS, maskFile, sunMaskFile, cementMaskFile, plainTilesFile } from "../src/partials/blocks.mjs";

const BUDGET = 6 * 1024;
const dir = fileURLToPath(new URL("../public/svg/", import.meta.url));
const kebab = (s) => s.replace(/[A-Z]/g, (c) => "-" + c.toLowerCase());

export function generate() {
  const masks = Object.fromEntries(KINDS.flatMap((k) => ["face", "hole"].map((part) => [`${part}-${kebab(k)}.svg`, maskFile(k, part)])));
  return { ...masks, "sun-hoa-thi.svg": sunMaskFile(), "cement.svg": cementMaskFile(), "plain-tiles.svg": plainTilesFile() };
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  const files = generate();
  const bytes = Object.values(files).reduce((s, t) => s + Buffer.byteLength(t), 0);
  if (bytes > BUDGET) throw new Error(`public/svg would be ${bytes} bytes; the budget is ${BUDGET}`);
  mkdirSync(dir, { recursive: true });
  for (const f of readdirSync(dir)) if (/^((face|hole|sun)-.*|cement|plain-tiles)\.svg$/.test(f) && !(f in files)) rmSync(dir + f);
  for (const [name, text] of Object.entries(files)) writeFileSync(dir + name, text);
  console.log(`blocks-svg: ${Object.keys(files).length} files, ${bytes} bytes → public/svg/`);
}
