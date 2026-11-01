// Syntax check of all theme, plugin and dev PHP under PHP 7.4 (the live host's version),
// using WordPress Playground's PHP build, so no local PHP install is needed. Takes ~30 s.
//   npm run lint:php            (or --php=8.3 to check another version)
import { spawn } from "node:child_process";
import { resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { PLAYGROUND } from "./dev.mjs";

const wp = resolve(fileURLToPath(new URL("..", import.meta.url)));
const php = process.argv.find((a) => a.startsWith("--php="))?.split("=")[1] ?? "7.4";
const args = ["-y", PLAYGROUND, "php", `--php=${php}`, "--wordpress-install-mode=do-not-attempt-installing", "--skip-sqlite-setup", `--mount=${wp}:/src`, "--", "/src/dev/lint.php", "/src"];
let out = "";
const child = spawn("npx", args, { stdio: ["ignore", "pipe", "pipe"] });
child.stdout.on("data", (d) => { out += d; process.stdout.write(d); });
child.stderr.on("data", (d) => { if (!/npm warn/.test(d)) process.stderr.write(d); });
// The Playground CLI doesn't always exit after the script ends; stop it once the summary is in.
const timer = setInterval(() => {
  const m = out.match(/files, (\d+) with syntax errors/);
  if (m) { clearInterval(timer); child.kill(); process.exit(Number(m[1]) ? 1 : 0); }
}, 250);
child.on("exit", (code) => { clearInterval(timer); process.exit(code ?? 1); });
