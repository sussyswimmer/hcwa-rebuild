// Static checks of the theme and plugin that need no running WordPress (npm test).
import assert from "node:assert/strict";
import { existsSync, readdirSync, readFileSync } from "node:fs";
import { join, resolve } from "node:path";
import { test } from "node:test";
import { fileURLToPath } from "node:url";
import { blueprint } from "../scripts/dev.mjs";

const wp = resolve(fileURLToPath(new URL("..", import.meta.url)));
const theme = join(wp, "themes/hcwa-2026");
const plugin = join(wp, "plugins/hcwa-core");
const read = (f) => readFileSync(f, "utf8");
const phpFiles = (dir) =>
  readdirSync(dir, { withFileTypes: true }).flatMap((e) => (e.isDirectory() ? phpFiles(join(dir, e.name)) : e.name.endsWith(".php") ? [join(dir, e.name)] : []));

test("theme.json palette uses the exact token colors (05-DESIGN, contrast-checked)", () => {
  const tokens = Object.fromEntries([...read(join(theme, "assets/css/tokens.css")).matchAll(/--color-([a-z0-9-]+):\s*(#[0-9A-Fa-f]{6})/g)].map((m) => [m[1], m[2].toUpperCase()]));
  const palette = JSON.parse(read(join(theme, "theme.json"))).settings.color.palette;
  assert.ok(palette.length >= 8);
  for (const { slug, color } of palette) {
    assert.ok(tokens[slug], `palette color ${slug} has no --color-${slug} token`);
    assert.equal(color.toUpperCase(), tokens[slug], `palette ${slug}`);
  }
});

test("theme.json fonts point at files that exist (fonts ≤ 4 files, 09-QA)", () => {
  const families = JSON.parse(read(join(theme, "theme.json"))).settings.typography.fontFamilies;
  const files = families.flatMap((f) => f.fontFace.flatMap((face) => face.src.map((s) => s.replace("file:./", ""))));
  assert.ok(files.length <= 4, `${files.length} font files`);
  for (const f of files) assert.ok(existsSync(join(theme, f)), f);
});

test("every UI string used in PHP exists in both languages", () => {
  const ui = JSON.parse(read(join(plugin, "strings/ui.json")));
  const used = new Set();
  for (const file of [...phpFiles(theme), ...phpFiles(plugin), ...phpFiles(join(wp, "dev"))]) {
    for (const m of read(file).matchAll(/hcwa_[te]\(\s*'([A-Za-z_]+)'/g)) if (!m[1].endsWith("_")) used.add(m[1]); // "status_" . $x is a prefix
  }
  // Keys built at runtime ('report_' . $type, 'status_' . $status, …).
  for (const k of ["report_annual", "report_financial", "report_program", "report_policy", "status_active", "status_completed", "status_planned", "langNoticePage", "d_bank", "d_account", "d_holder", "d_note", "d_swift", "d_accountAbroad", "logoLine1", "logoLine2"]) used.add(k);
  const missing = [...used].filter((k) => !ui[k]?.vi || !ui[k]?.en);
  assert.deepEqual(missing, [], `missing or one-language strings: ${missing.join(", ")}`);
});

test("Vietnamese UI strings are never written in capitals (CLAUDE.md)", () => {
  const ui = JSON.parse(read(join(plugin, "strings/ui.json")));
  // Two or more consecutive all-caps words with Vietnamese letters would be a typed-in-caps label.
  const shouting = Object.entries(ui).filter(([k, v]) => k !== "_note" && /\b[A-ZĐÀ-Ỹ]{2,}\s+[A-ZĐÀ-Ỹ]{2,}\b/u.test(v.vi.replace(/\b(HCWA|USD|EUR|SWIFT|PDF|VI|EN|TP\.HCM)\b/g, "")));
  assert.deepEqual(shouting.map(([k]) => k), []);
});

test("each block folder has a block.json, a render file, and an editor registration", () => {
  const editor = read(join(plugin, "assets/editor.js"));
  const blocksPhp = read(join(plugin, "inc/blocks.php"));
  const dirs = readdirSync(join(plugin, "blocks"));
  assert.ok(dirs.length >= 10);
  for (const dir of dirs) {
    const meta = JSON.parse(read(join(plugin, "blocks", dir, "block.json")));
    assert.equal(meta.name, `hcwa/${dir}`);
    assert.equal(meta.render, "file:./render.php");
    assert.ok(existsSync(join(plugin, "blocks", dir, "render.php")), `${dir}/render.php`);
    assert.ok(blocksPhp.includes(`'${dir}'`), `${dir} missing from hcwa_block_names()`);
    assert.ok(editor.includes(`'hcwa/${dir}'`), `${dir} not registered in editor.js`);
  }
});

test("the theme never loads a social feed, Google Fonts or jQuery (CLAUDE.md, 08 task 2.8)", () => {
  // checks.php is excluded: it names feed shortcodes in order to detect them. A plain link to
  // the Instagram profile (footer) is fine; a feed plugin, shortcode or embed is not.
  const all = [...phpFiles(theme), ...phpFiles(plugin)].filter((f) => !f.endsWith("checks.php")).map(read).join("\n");
  assert.doesNotMatch(all, /fonts\.googleapis|instagram-feed|\[instagram|sb_instagram|wp:embed|wp_enqueue_script\(\s*'jquery'/i);
});

test("local WordPress matches the live host and the review/production split", () => {
  const review = blueprint();
  const prod = blueprint({ production: true });
  assert.equal(review.preferredVersions.php, "7.4");
  const consts = (b) => b.steps.find((s) => s.step === "defineWpConfigConsts").consts;
  assert.equal(consts(review).WP_ENVIRONMENT_TYPE, "local");
  assert.equal(consts(prod).WP_ENVIRONMENT_TYPE, "production");
  assert.ok(review.steps.some((s) => s.step === "installPlugin" && s.pluginData.slug === "polylang"));
});
