// Copies the shared design system from the prototype into the WordPress theme and plugin,
// so HCWA's feedback on the prototype lands in both with one edit:
//   prototype/src/styles/tokens.css, site.css → theme assets/css (site.css minus @font-face:
//     theme.json declares the fonts)
//   prototype/src/main.js → theme assets/js/site.js (imports removed, wrapped in an IIFE)
//   prototype/public/fonts, img → theme assets/fonts, assets/img
//   content/draft/ui.json → plugin strings/ui.json
//
//   node scripts/sync-assets.mjs           write the copies
//   node scripts/sync-assets.mjs --check   exit 1 if any copy is out of date (used by npm test)
import { copyFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const wp = resolve(fileURLToPath(new URL("..", import.meta.url)));
const root = resolve(wp, "..");
const proto = join(root, "prototype");
const theme = join(wp, "themes/hcwa-2026");
const plugin = join(wp, "plugins/hcwa-core");
const check = process.argv.includes("--check");

const banner = (src) => `/* Copied from ${src} by wp/scripts/sync-assets.mjs. Edit the original, then run npm run sync in wp/. */\n`;

export const transforms = {
  tokens: (css) => banner("prototype/src/styles/tokens.css") + css,
  site: (css) =>
    banner("prototype/src/styles/site.css") +
    css
      .split("\n")
      .filter((line) => !line.startsWith("@font-face"))
      .join("\n"),
  js: (js) =>
    banner("prototype/src/main.js").replace("/*", "//").replace(" */", "") +
    "(() => {\n" +
    js
      .split("\n")
      .filter((line) => !/^import\s/.test(line))
      .join("\n") +
    "\n})();\n",
};

const textJobs = [
  [join(proto, "src/styles/tokens.css"), join(theme, "assets/css/tokens.css"), transforms.tokens],
  [join(proto, "src/styles/site.css"), join(theme, "assets/css/site.css"), transforms.site],
  [join(proto, "src/main.js"), join(theme, "assets/js/site.js"), transforms.js],
  [join(root, "content/draft/ui.json"), join(plugin, "strings/ui.json"), (s) => s],
];
const binaryJobs = [
  ...["fraunces-vf.woff2", "be-vietnam-pro-400.woff2", "be-vietnam-pro-500.woff2", "be-vietnam-pro-600.woff2", "Fraunces-OFL.txt", "BeVietnamPro-OFL.txt"].map((f) => [join(proto, "public/fonts", f), join(theme, "assets/fonts", f)]),
  ...["hcwa-logo.png", "favicon-32.png", "apple-touch-icon.png"].map((f) => [join(proto, "public/img", f), join(theme, "assets/img", f)]),
];

const stale = [];
for (const [from, to, transform] of textJobs) {
  const want = transform(readFileSync(from, "utf8"));
  if (check) {
    if (!existsSync(to) || readFileSync(to, "utf8") !== want) stale.push(to);
  } else {
    mkdirSync(dirname(to), { recursive: true });
    writeFileSync(to, want);
  }
}
for (const [from, to] of binaryJobs) {
  if (check) {
    if (!existsSync(to) || !readFileSync(to).equals(readFileSync(from))) stale.push(to);
  } else {
    mkdirSync(dirname(to), { recursive: true });
    copyFileSync(from, to);
  }
}

if (check && stale.length) {
  console.error(`Out of date (run npm run sync in wp/):\n  ${stale.map((f) => f.replace(root + "/", "")).join("\n  ")}`);
  process.exit(1);
}
console.log(check ? "theme and plugin copies are up to date" : `synced ${textJobs.length + binaryJobs.length} files`);
