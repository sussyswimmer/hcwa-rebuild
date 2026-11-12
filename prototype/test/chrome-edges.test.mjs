// M7 (Maxwell, 12 Nov 2026): the staging bars and the header end in soft light, never a straight edge (05-DESIGN
// 0.5). The pixels are checked in the browser (qa/m7/chrome, luminance-step scan); this pins the CSS that does it.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";

const css = (f) => readFileSync(new URL(`../src/styles/${f}`, import.meta.url), "utf8").replace(/\/\*[\s\S]*?\*\//g, "");
const components = css("components.css");
const draft = css("draft.css");
const rule = (src, sel) => src.match(new RegExp(`(?:^|\\n)${sel.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")} \\{([^}]*)\\}`))?.[1] ?? "";
const PLASTER = "color-mix(in srgb, var(--color-lime-50) 97%, transparent)";

test("the review note and the EN pointer are the header's own plaster: no dark bar, no gradient of their own", () => {
  const note = rule(draft, ".proto-note");
  const en = rule(components, ".en-bar");
  assert.ok(note.includes(`background: ${PLASTER}`), "review note in the header's plaster");
  assert.ok(en.includes(`background: ${PLASTER}`), "EN pointer in the header's plaster");
  assert.match(rule(components, ".site-header"), /background: color-mix\(in srgb, var\(--color-lime-50\) 97%, transparent\)/);
  for (const r of [note, en]) assert.doesNotMatch(r.match(/background[^;]*/)?.[0] ?? "", /ink-900|blue-9|gradient/, "no dark fill or own gradient");
  // Ink words on the plaster, the toggle's "on" state in gold-700 (5.3:1 on lime-50), never the on-dusk colours.
  assert.match(note, /color: var\(--color-ink-900\)/);
  assert.doesNotMatch(draft.slice(draft.indexOf(".proto-note")), /on-dusk|sun-400|focus-on-dark/);
});

test("over the home hero the scrim starts in the bars' plaster when a bar is showing", () => {
  const i = components.indexOf("body:has(> :is(.proto-note, .en-bar:not([hidden]))) .site-header::before {");
  assert.ok(i > 0);
  const block = components.slice(i, components.indexOf("}", i));
  assert.match(block, /--bar: color-mix\(in srgb, var\(--color-lime-50\) 97%, transparent\)/);
  assert.match(block, /var\(--bar\) 0,/, "the scrim's first stop is the bar's plaster");
});

test("the night follows the scroll in CSS (view timelines on the skies), with or without JS", () => {
  const i = components.indexOf("@supports (animation-timeline: --a) and (timeline-scope: --a)");
  assert.ok(i > 0, "scroll-driven night block");
  const block = components.slice(i, components.indexOf("@keyframes evening-band", i));
  assert.match(block, /html \{ timeline-scope: --night-band, --night-foot; \}/);
  assert.match(block, /\.dusk \{ view-timeline: --night-band block; \}/);
  assert.match(block, /\.site-footer \{ view-timeline: --night-foot block; \}/);
  assert.match(block, /view-timeline-inset: var\(--night-line\) calc\(100% - var\(--night-line\) - 1px\)/);
  assert.match(block, /animation-fill-mode: none/, "outside the evening and the night the bar is its usual plaster");
  assert.match(block, /animation-duration: auto/, "the minifier's 0s duration would finish the track at once");
  // Bar and skirt: the sky's colour over its light half, then the night, on both skies, on the same ranges.
  assert.match(block, /\.site-header \{ animation-name: evening-band, night-fill, evening-foot, night-fill; \}/);
  assert.match(block, /\.site-header::after \{ animation-name: evening-band-skirt, night-skirt, evening-foot-skirt, night-skirt; \}/);
  assert.match(block, /\.site-header::before \{ animation-name: night-scrim, night-scrim, night-scrim, night-scrim; \}/, "no scrim over the evening fill");
  assert.match(block, /animation-range: cover 1px cover var\(--dusk-night\), cover var\(--dusk-night\) cover 100%, cover 1px cover var\(--foot-night\), cover var\(--foot-night\) cover 100%;/);
  assert.match(components, /@keyframes night-skirt \{ from, to \{ background-color: color-mix\(in srgb, var\(--color-blue-900\) 97%, transparent\); opacity: 1; \} \}/);
  // The evening holds at the last sky stop where ink-900 keeps 7:1 (sun-200 halfway to the crossing's middle).
  for (const k of ["evening-band", "evening-band-skirt"]) assert.match(components, new RegExp(`@keyframes ${k} \\{[\\s\\S]*?62%, 100% \\{ background-color: color-mix\\(in srgb, color-mix\\(in oklab, var\\(--color-sun-200\\) 50%, var\\(--eve-mid\\)\\) 97%, transparent\\);`));
  for (const k of ["evening-foot", "evening-foot-skirt"]) assert.match(components, new RegExp(`@keyframes ${k} \\{[\\s\\S]*?75\\.6%, 100% \\{ background-color: color-mix\\(in srgb, color-mix\\(in oklab, var\\(--color-sun-200\\) 50%, var\\(--eve-mid\\)\\) 97%, transparent\\);`));
  // The same night marks as the JS fallback's [data-night] (0.5 of a band's sky, 0.45 of the footer's).
  assert.match(components, /--dusk-night: calc\(var\(--dusk-sky\) \* 0\.5 \+ 1px\)/);
  assert.match(components, /--foot-night: calc\(var\(--foot-sky-h\) \* 0\.45 \+ 1px\)/);
  assert.match(components, /\.dusk > \[data-night\] \{ top: calc\(var\(--sky\) \* 0\.5\); \}/);
  assert.match(components, /--sky: var\(--dusk-sky\);/);
  assert.match(components, /--foot-sky: var\(--foot-sky-h\);/);
  // The JS fallback stands down where CSS does it, and its skirt eases with the bar.
  const js = readFileSync(new URL("../src/ui/header.js", import.meta.url), "utf8");
  assert.match(js, /if \(window\.CSS\?\.supports\?\.\("animation-timeline: --a"\) && CSS\.supports\("timeline-scope: --a"\)\) return;/);
  assert.match(rule(components, ".site-header::after"), /background-color var\(--d-ui\)/);
});

// M7 fix round (critique): two straight edges the luminance scan missed at 90% of columns over 6 levels, found by the
// header-foot scan (50% of columns over 4 levels in the top 260 px; qa/m7/fix/tools/lum-scan.py --foot).
test("the header's foot meets no picture in one row: the lit stills and the unit mark ease in under the skirt", () => {
  const pages = css("pages.css");
  // The lit header's still fades in from the plaster over its first --top px (Cầu Hàn and Green Bamboo stepped 9 to 11
  // levels at the bar's foot: the skirt shows 8% of what is under it).
  const lit = pages.match(/\.lit-head__media \.lit \{\s*mask-image:\s*([\s\S]*?);\s*mask-composite: intersect;/)?.[1] ?? "";
  assert.match(lit, /^linear-gradient\(to bottom, transparent, [^)]*\)[^,]*,[\s\S]*black var\(--top\)\)/, "a top feather, first of the intersected masks");
  assert.match(pages, /\.lit-head__media \{[^}]*--top: 64px;/);
  // The unit's mark has no solid core where the bar ends: it starts transparent and rises out of the skirt.
  const unit = rule(pages, ".lit-head--unit::before");
  assert.match(unit, /background: linear-gradient\(to bottom, transparent 12px,/);
  assert.doesNotMatch(unit, /var\(--unit\) \d+px/, "no stop of solid unit paint");
});

test("no backdrop blur on the bar or its skirt: a blur cut square at the bar's foot is a straight edge", () => {
  assert.doesNotMatch(rule(components, ".site-header"), /backdrop-filter/);
  assert.doesNotMatch(rule(components, ".site-header::after"), /backdrop-filter/);
  assert.doesNotMatch(components, /backdrop-filter: blur/, "no blur on any of the chrome");
});
