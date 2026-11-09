import { resolve } from "node:path";
import { defineConfig } from "vite";

// The WordPress build (docs/plan/11-WP-PORT.md 1.1): the same src/main.js the prototype pages load,
// built with a relative base and a manifest into the hcwa-2026 theme, so the theme enqueues the
// prototype's own CSS and JS and nothing is copied by hand. It renders no HTML pages and shares
// nothing with vite.config.js, so the prototype's own build is untouched by it.
//
// A relative base makes every URL the bundle resolves itself (CSS url(/fonts/…) and url(/svg/…), the
// preload helper's list, the gạch worker's new URL(…, import.meta.url)) land next to the files, so the
// bundle works under any theme URL. draft.css is its own entry: on staging the theme prints it before
// the main CSS (the prototype imports it first on staging only), and production never loads it.
// Run through `npm run export:wp` (scripts/export-wp.mjs), which also writes the generated files.
const here = import.meta.dirname;

export default defineConfig({
  root: here,
  base: "./",
  publicDir: resolve(here, "public"),
  logLevel: "warn",
  build: {
    outDir: resolve(here, "../wp/themes/hcwa-2026/assets/dist"),
    emptyOutDir: true,
    manifest: "manifest.json",
    copyPublicDir: true,
    // The world chunk (three.js and the world code) is a lazy import after load + idle, as in the prototype.
    chunkSizeWarningLimit: 700,
    rolldownOptions: {
      input: { main: resolve(here, "src/main.js"), draft: resolve(here, "src/styles/draft.css") },
    },
  },
});
