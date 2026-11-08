// Writes the bông gió block set as SVG masks (PLAN section 5: sheet edges, footer edge, cast light),
// from the same SDF numbers the shaders use (src/world/glsl/blocks.js through src/partials/blocks.mjs).
// face-<kind>.svg: the block with its holes cut (sheet and footer edges); hole-<kind>.svg: the holes only
// (the CSS sun layer: light through that block). Run by predev / prebuild; fails over the 6 KB budget.
import { mkdirSync, readdirSync, rmSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { KINDS, maskFile } from "../src/partials/blocks.mjs";

const BUDGET = 6 * 1024;
const dir = fileURLToPath(new URL("../public/svg/", import.meta.url));
const kebab = (s) => s.replace(/[A-Z]/g, (c) => "-" + c.toLowerCase());

export function generate() {
  return Object.fromEntries(KINDS.flatMap((k) => ["face", "hole"].map((part) => [`${part}-${kebab(k)}.svg`, maskFile(k, part)])));
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  const files = generate();
  const bytes = Object.values(files).reduce((s, t) => s + Buffer.byteLength(t), 0);
  if (bytes > BUDGET) throw new Error(`public/svg would be ${bytes} bytes; the budget is ${BUDGET}`);
  mkdirSync(dir, { recursive: true });
  for (const f of readdirSync(dir)) if (/^(face|hole)-.*\.svg$/.test(f) && !(f in files)) rmSync(dir + f);
  for (const [name, text] of Object.entries(files)) writeFileSync(dir + name, text);
  console.log(`blocks-svg: ${Object.keys(files).length} files, ${bytes} bytes → public/svg/`);
}
