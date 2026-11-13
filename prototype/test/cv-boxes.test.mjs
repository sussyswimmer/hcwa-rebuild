import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";

// M8: the boxes that skip layout until near the screen (layout.css) and the boxes ui/cv-warm.js lays out once after
// load are one list; a box on the first screen at any width never skips (Chrome scores its late render as a shift).
const css = readFileSync(new URL("../src/styles/layout.css", import.meta.url), "utf8");
const js = readFileSync(new URL("../src/ui/cv-warm.js", import.meta.url), "utf8");
const norm = (s) => s.split(",").map((x) => x.trim().replace(/\s+/g, " ")).sort();

test("layout.css and cv-warm.js name the same content-visibility boxes", () => {
  const lists = [...css.matchAll(/:is\(([^{]+?)\)(\.cv-warm)?\s*\{/g)].filter((m) => /content-visibility/.test(css.slice(m.index, css.indexOf("}", m.index))));
  assert.equal(lists.length, 2, "the rule and its .cv-warm override");
  const fromJs = js.match(/CV_BOXES = "([^"]+)"/)[1];
  for (const m of lists) assert.deepEqual(norm(m[1]), norm(fromJs));
});

test("only boxes the browser skips at the first frame: the home's and the kit's, never an inner page's", () => {
  const list = norm(js.match(/CV_BOXES = "([^"]+)"/)[1]);
  // An inner page's boxes, the first kit section and a bare dusk band or footer are all laid out
  // at the first frame on a phone (qa/m8/fix/logs/skipped-*.tsv): skipping them first is a second pass, and on the
  // first screen a layout shift.
  for (const s of list) {
    assert.doesNotMatch(s, /\.day\b/, s);
    assert.ok(/^\.page-(home|kit) |^\.kit-sec > |^\.kit__body > \.kit-sec ~ /.test(s), `${s}: scoped to the home or the kit`);
  }
});
