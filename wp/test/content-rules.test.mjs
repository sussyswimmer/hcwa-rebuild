// Hard rules from CLAUDE.md, checked on the content the WordPress seed reads.
import assert from "node:assert/strict";
import { existsSync, readFileSync } from "node:fs";
import { join, resolve } from "node:path";
import { test } from "node:test";
import { fileURLToPath } from "node:url";

const root = resolve(fileURLToPath(new URL("../..", import.meta.url)));
const json = (f) => JSON.parse(readFileSync(join(root, f), "utf8"));

test("no impact figure carries a value unless it is verified with who and when", () => {
  for (const f of json("content/draft/impact.json").figures) {
    if (f.verified) {
      assert.equal(typeof f.value, "number", `${f.id}: verified without a number`);
      assert.ok(f.verifiedBy && f.verifiedOn, `${f.id}: verified without verifiedBy/verifiedOn`);
    } else {
      assert.equal(f.value, null, `${f.id}: unverified figure has a value`);
    }
  }
});

test("draft content has no bank details, and donation data only ever comes from content/verified", () => {
  const draft = ["site", "involve", "work", "news"].map((n) => readFileSync(join(root, `content/draft/${n}.json`), "utf8")).join("\n");
  // Long digit runs look like account numbers (a tap-to-call number starts with +).
  assert.doesNotMatch(draft, /(?<![+\d])\d{9,}\b/);
  const seed = readFileSync(join(root, "wp/dev/seed.php"), "utf8");
  assert.match(seed, /content\/verified\/donation\.json/);
  if (existsSync(join(root, "content/verified/donation.json"))) {
    const d = json("content/verified/donation.json");
    assert.ok(d.donation_confirmed_by && d.donation_confirmed_on, "verified donation details need who confirmed them and when");
  }
});

test("center founding years stay unset in the seed until HCWA verifies them", () => {
  const seed = readFileSync(join(root, "wp/dev/seed.php"), "utf8");
  assert.doesNotMatch(seed, /'hcwa_since'\s*=>/);
  assert.doesNotMatch(seed, /'hcwa_since_verified'\s*=>\s*(1|true)/);
  assert.doesNotMatch(seed, /'hcwa_verified'\s*=>\s*(1|true)/);
});

test("Little Rose is marked no-identifiable-children", () => {
  const lr = json("content/draft/work.json").centers.find((c) => c.id === "little-rose");
  assert.equal(lr.illustrationOnly, true);
});
