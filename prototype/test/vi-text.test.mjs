// PLAN section 6 / CLAUDE.md: Vietnamese is never set in capitals, and running text keeps a
// line-height of 1.6 or more. Display type and one-line controls may sit tighter.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync, readdirSync } from "node:fs";
import { join } from "node:path";
import { fileURLToPath } from "node:url";

const src = fileURLToPath(new URL("../src/", import.meta.url));
const styles = join(src, "styles");
const sheets = readdirSync(styles).filter((f) => f.endsWith(".css")).map((f) => [f, readFileSync(join(styles, f), "utf8").replace(/\/\*[\s\S]*?\*\//g, "")]);
const files = (dir) => readdirSync(dir, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? files(join(dir, e.name)) : /\.(css|m?js)$/.test(e.name) ? [join(dir, e.name)] : []));

// Custom properties from every sheet, so var(--lh-*) resolves to its number.
const vars = Object.fromEntries(sheets.flatMap(([, css]) => [...css.matchAll(/(--[\w-]+):\s*([^;}]+)/g)].map((m) => [m[1], m[2].trim()])));
const resolve = (v) => { for (let i = 0; i < 5 && /var\(/.test(v); i++) v = v.replace(/var\((--[\w-]+)[^)]*\)/, (_, n) => vars[n] ?? "?"); return v; };

// Headings, figures, wordmarks and single-line controls: not running text.
const DISPLAY = /^(h[1-4]|\.h[34]|\.display|\.hero__title|\.btn|\.menu-btn|\.pause-toggle|\.logo__name|\.ph__code|\.figure__value|\.gift-list__amount|\.foot-brand__name|\.foot-col summary h2|\.sheet__link|\.sheet summary)$/;

test("no capitals by CSS: no uppercase or capitalize transform, no small caps", () => {
  const found = files(src).flatMap((f) => {
    const text = readFileSync(f, "utf8");
    return [...text.matchAll(/text-transform\s*:\s*(uppercase|capitalize)|font-variant(?:-caps)?\s*:\s*[^;]*small-caps/gi)].map((m) => `${f}: ${m[0]}`);
  });
  assert.deepEqual(found, []);
});

test("body and lead line-heights are 1.6 or more", () => {
  for (const name of ["--lh-body", "--lh-lead"]) assert.ok(Number(resolve(`var(${name})`)) >= 1.6, `${name} is ${vars[name]}`);
});

test("running text never sits below line-height 1.6", () => {
  const tight = [];
  for (const [file, css] of sheets) {
    for (const [, selector, body] of css.matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
      const values = [...body.matchAll(/line-height:\s*([^;]+)/g), ...body.matchAll(/(?:^|;)\s*font:[^;]*?\/\s*([^\s;]+)/g)].map((m) => resolve(m[1].trim()));
      for (const v of values) {
        const n = Number(v);
        assert.ok(!Number.isNaN(n), `${file} ${selector.trim()}: line-height ${v} must be a unitless number`);
        const parts = selector.split(",").map((s) => s.trim());
        if (n < 1.6 && !parts.every((p) => DISPLAY.test(p))) tight.push(`${file} ${selector.trim()}: ${v}`);
      }
    }
  }
  assert.deepEqual(tight, []);
});
