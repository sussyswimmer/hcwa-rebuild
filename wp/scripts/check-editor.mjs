// Opens every page, center and program in the block editor and fails if a block is invalid
// ("This block contains unexpected or invalid content"), if a block type is missing, or if the
// editor logs an error. Catches pattern markup that drifted from what core blocks save.
//
//   node scripts/check-editor.mjs [--base=http://127.0.0.1:9400] [--user=admin --pass=password]
import { chromium } from "playwright-core";

const arg = (name, fallback) => process.argv.find((a) => a.startsWith(`--${name}=`))?.split("=").slice(1).join("=") ?? fallback;
const base = arg("base", "http://127.0.0.1:9400").replace(/\/$/, "");

const browser = await chromium.launch(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {});
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
page.setDefaultTimeout(180000); // Playground is slow on the first admin requests
await page.goto(`${base}/wp-login.php`, { waitUntil: "domcontentloaded" });
await page.fill("#user_login", arg("user", "admin"));
await page.fill("#user_pass", arg("pass", "password"));
// Don't wait for the dashboard after login: it fetches remote feeds and can take minutes.
await page.click("#wp-submit", { noWaitAfter: true });
for (let i = 0; i < 120 && !(await page.context().cookies()).some((c) => c.name.startsWith("wordpress_logged_in")); i++) await page.waitForTimeout(500);

const items = [];
for (const type of ["pages", "hcwa_center", "hcwa_program"]) {
  // Logged-in REST requests return both languages; dedupe by id.
  const res = await (await page.request.get(`${base}/wp-json/wp/v2/${type}?per_page=100&_fields=id,title,lang`)).json();
  for (const p of Array.isArray(res) ? res : []) if (!items.some((i) => i.id === p.id)) items.push({ id: p.id, title: p.title?.rendered ?? "", type, lang: p.lang ?? "" });
}

let failed = 0;
for (const item of items) {
  const errors = [];
  const onConsole = (m) => m.type() === "error" && errors.push(m.text());
  page.on("console", onConsole);
  // Playground serves the editor slowly; one retry before giving up on an item.
  let loaded = false;
  for (let attempt = 0; attempt < 2 && !loaded; attempt++) {
    loaded = await page.goto(`${base}/wp-admin/post.php?post=${item.id}&action=edit`, { waitUntil: "domcontentloaded", timeout: 180000 }).then(() => true, () => false);
  }
  if (!loaded) errors.push("editor did not load");
  await page.waitForFunction(() => window.wp?.data?.select("core/block-editor")?.getBlocks()?.length > 0 || window.wp?.data?.select("core/editor")?.isCleanNewPost?.() === false, null, { timeout: 120000 }).catch(() => {});
  await page.waitForTimeout(1500);
  const result = await page.evaluate(() => {
    const walk = (blocks, out = []) => {
      for (const b of blocks) {
        if (!b.isValid) out.push(`invalid ${b.name}`);
        if (b.name === "core/missing") out.push(`missing block type ${b.attributes.originalName}`);
        walk(b.innerBlocks, out);
      }
      return out;
    };
    const blocks = window.wp?.data?.select("core/block-editor")?.getBlocks() ?? [];
    return { count: blocks.length, problems: walk(blocks) };
  });
  page.off("console", onConsole);
  const problems = [...result.problems, ...errors.filter((e) => !/favicon|DevTools/.test(e)).map((e) => `console: ${e}`)];
  if (problems.length) failed++;
  console.log(`${problems.length ? "FAIL" : "ok  "} ${item.type} #${item.id} (${item.lang}) ${item.title} — ${result.count} top-level blocks${problems.length ? "\n    " + [...new Set(problems)].join("\n    ") : ""}`);
}
await browser.close();
if (failed) {
  console.error(`\n${failed} of ${items.length} items have editor problems`);
  process.exit(1);
}
console.log(`\nall ${items.length} items open cleanly in the block editor`);
