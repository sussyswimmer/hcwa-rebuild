import { execFileSync } from "node:child_process";
import { existsSync, readdirSync } from "node:fs";
import { join, resolve } from "node:path";
import { defineConfig } from "vite";

const here = import.meta.dirname;
const site = resolve(here, ".site");
const render = () => execFileSync(process.execPath, [resolve(here, "src/render.mjs"), site], { stdio: "inherit", env: process.env });

const htmlFiles = (dir) =>
  readdirSync(dir, { withFileTypes: true }).flatMap((e) =>
    e.isDirectory() ? htmlFiles(join(dir, e.name)) : e.name.endsWith(".html") ? [join(dir, e.name)] : [],
  );

// Re-render pages when templates or content change, then reload the browser.
const rerender = {
  name: "hcwa-render",
  configureServer(server) {
    const watched = [resolve(here, "src/pages"), resolve(here, "src/partials"), resolve(here, "src/lib"), resolve(here, "src/routes.mjs"), resolve(here, "src/head-inline.js"), resolve(here, "src/world/buckets.js"), resolve(here, "../content")];
    server.watcher.add(watched);
    server.watcher.on("change", (file) => {
      if (!watched.some((w) => file.startsWith(w))) return;
      try {
        render();
        server.ws.send({ type: "full-reload" });
      } catch {
        // render already printed the error; keep the dev server up
      }
    });
  },
};

// Hosting under a sub-path (GitHub Pages: npm run build:pages). Vite prefixes assets with
// --base; this prefixes page links, hreflang links and form actions, which Vite leaves alone.
let base = "/";
const rebase = {
  name: "hcwa-rebase",
  apply: "build",
  configResolved(config) {
    base = config.base;
  },
  transformIndexHtml: {
    order: "post",
    handler: (html) => (base === "/" ? html : html.replace(new RegExp(`(\\s(?:href|src|action)=")/(?!/|${base.slice(1)})`, "g"), `$1${base}`)),
  },
  generateBundle() {
    // Stops GitHub Pages running Jekyll, which would drop files starting with "_".
    if (base !== "/") this.emitFile({ type: "asset", fileName: ".nojekyll", source: "" });
  },
};

// The page must build and work without the world (it lands separately): a missing src/world/index.js
// resolves to a stub that rejects, so main.js keeps the stills and the build still passes.
const worldFallback = {
  name: "hcwa-world-fallback",
  resolveId(id, importer) {
    if (id === "./world/index.js" && importer?.endsWith("/src/main.js") && !existsSync(resolve(here, "src/world/index.js"))) return "\0hcwa-world-stub";
  },
  load(id) {
    if (id === "\0hcwa-world-stub") return 'export const startWorld = () => Promise.reject(new Error("src/world/index.js is not built yet"));';
  },
};

export default defineConfig(() => {
  render(); // throws (and fails the build) when PROD=1 finds drafts
  return {
    root: site,
    publicDir: resolve(here, "public"),
    appType: "mpa",
    plugins: [rerender, rebase, worldFallback],
    build: {
      outDir: resolve(here, "dist"),
      emptyOutDir: true,
      // The world chunk (three.js + world code, ~157 KB gzip) is a lazy import after load + idle (D6.7).
      chunkSizeWarningLimit: 700,
      rolldownOptions: { input: htmlFiles(site) },
    },
    server: { fs: { allow: [here] } },
  };
});
