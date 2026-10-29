import { execFileSync } from "node:child_process";
import { readdirSync } from "node:fs";
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
    const watched = [resolve(here, "src/pages"), resolve(here, "src/partials"), resolve(here, "src/lib"), resolve(here, "src/routes.mjs"), resolve(here, "../content")];
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

export default defineConfig(() => {
  render(); // throws (and fails the build) when PROD=1 finds drafts
  return {
    root: site,
    publicDir: resolve(here, "public"),
    appType: "mpa",
    plugins: [rerender],
    build: {
      outDir: resolve(here, "dist"),
      emptyOutDir: true,
      rolldownOptions: { input: htmlFiles(site) },
    },
    server: { fs: { allow: [here] } },
  };
});
