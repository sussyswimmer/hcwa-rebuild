// PLAN section 6: the generated JS matches tokens.css, tokens.css holds 05-DESIGN D1 verbatim,
// and no hex color appears anywhere in src/ outside tokens.css (CSS, JS, GLSL strings).
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync, readdirSync } from "node:fs";
import { join, relative } from "node:path";
import { fileURLToPath } from "node:url";
import { generate, paths } from "../scripts/tokens-to-js.mjs";

const root = fileURLToPath(new URL("..", import.meta.url));
const css = readFileSync(paths.css, "utf8");

test("src/motion/tokens.js and src/world/tokens.js are generated from tokens.css", () => {
  const out = generate(css);
  assert.equal(readFileSync(paths.motion, "utf8"), out.motion, "motion/tokens.js drifted: run npm run tokens");
  assert.equal(readFileSync(paths.world, "utf8"), out.world, "world/tokens.js drifted: run npm run tokens");
});

test("tokens.css carries the 05-DESIGN D1 block verbatim", () => {
  const block = (text) => text.match(/\/\* tokens:start \*\/[\s\S]*?\/\* tokens:end \*\//)?.[0];
  const design = readFileSync(join(root, "../docs/plan/05-DESIGN.md"), "utf8");
  assert.ok(block(design), "05-DESIGN.md lost its tokens:start/end markers");
  assert.equal(block(css), block(design));
});

test("no hex color outside tokens.css", () => {
  const generated = new Set([paths.css, paths.motion, paths.world]);
  const files = (dir) => readdirSync(dir, { withFileTypes: true }).flatMap((e) =>
    e.isDirectory() ? files(join(dir, e.name)) : /\.(css|m?js|html|glsl)$/.test(e.name) ? [join(dir, e.name)] : []);
  // A # followed by 3, 4, 6 or 8 hex digits and nothing word-like: not "&#39;", not "#co-so".
  const HEX = /(?<![&\w])#(?:[0-9a-f]{8}|[0-9a-f]{6}|[0-9a-f]{3,4})(?![\w-])/gi;
  const found = files(join(root, "src"))
    .filter((f) => !generated.has(f))
    .flatMap((f) => [...readFileSync(f, "utf8").matchAll(HEX)].map((m) => `${relative(root, f)}: ${m[0]}`));
  assert.deepEqual(found, [], "use a token (CSS var, or color/world from the generated tokens.js)");
});
