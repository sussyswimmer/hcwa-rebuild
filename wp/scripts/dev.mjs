// Local WordPress without Docker or PHP installed: WordPress Playground (PHP in WebAssembly).
// Starts a fresh site with Polylang, the hcwa-core plugin and the hcwa-2026 theme mounted from
// this repo (edits show on reload), then seeds the draft content in both languages.
//
//   npm run dev                      http://127.0.0.1:9400, review mode (WP_ENVIRONMENT_TYPE=local)
//   npm run dev -- --production      same content, production rules (drafts and unverified hidden)
//   npm run dev -- --port=9500 --php=8.3
//
// PHP defaults to 7.4 because the live host runs PHP 7.4.33 (checked 1 Nov 2026).
// Admin: /wp-login.php (user admin, password password). Docker users can use .wp-env.json instead.
import { spawn } from "node:child_process";
import { mkdtempSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const wp = resolve(fileURLToPath(new URL("..", import.meta.url)));
const root = resolve(wp, "..");
const arg = (name, fallback) => process.argv.find((a) => a.startsWith(`--${name}=`))?.split("=")[1] ?? fallback;
const production = process.argv.includes("--production");
const port = arg("port", production ? "9401" : "9400");
const php = arg("php", "7.4");

export const PLAYGROUND = "@wp-playground/cli@3.1.61";

export function blueprint({ production = false, php = "7.4" } = {}) {
  return {
    $schema: "https://playground.wordpress.net/blueprint-schema.json",
    landingPage: "/",
    login: false, // true would log in every visitor, so pages could not be checked logged out
    // The server's --php flag alone is ignored when a blueprint is given (Playground CLI 3.1.61).
    preferredVersions: { php, wp: "latest" },
    steps: [
      {
        step: "defineWpConfigConsts",
        method: "rewrite-wp-config", // write them into wp-config.php so every request sees them
        consts: {
          WP_ENVIRONMENT_TYPE: production ? "production" : "local",
          WP_DEBUG: true,
          WP_DEBUG_DISPLAY: true,
          SCRIPT_DEBUG: false,
        },
      },
      { step: "installPlugin", pluginData: { resource: "wordpress.org/plugins", slug: "polylang" }, options: { activate: true } },
      { step: "activatePlugin", pluginPath: "hcwa-core/hcwa-core.php" },
      { step: "activateTheme", themeFolderName: "hcwa-2026" },
      { step: "runPHP", code: "<?php require '/hcwa/dev/setup-languages.php';" },
      { step: "runPHP", code: "<?php require '/hcwa/dev/seed.php';" },
    ],
  };
}

export function mounts() {
  return [
    `${join(wp, "plugins/hcwa-core")}:/wordpress/wp-content/plugins/hcwa-core`,
    `${join(wp, "themes/hcwa-2026")}:/wordpress/wp-content/themes/hcwa-2026`,
    `${join(root, "content")}:/hcwa/content`,
    `${join(wp, "dev")}:/hcwa/dev`,
    `${join(root, "prototype/public/img")}:/hcwa/img`,
  ];
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const dir = mkdtempSync(join(tmpdir(), "hcwa-wp-"));
  const file = join(dir, "blueprint.json");
  writeFileSync(file, JSON.stringify(blueprint({ production, php }), null, 2));
  const args = ["-y", PLAYGROUND, "server", `--port=${port}`, `--php=${php}`, `--blueprint=${file}`, ...mounts().map((m) => `--mount-before-install=${m}`)];
  console.log(`Starting WordPress (${production ? "production rules" : "review mode"}, PHP ${php}) on http://127.0.0.1:${port}`);
  const child = spawn("npx", args, { stdio: "inherit" });
  child.on("exit", (code) => process.exit(code ?? 0));
}
