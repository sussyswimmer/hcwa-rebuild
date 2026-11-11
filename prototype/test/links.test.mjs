// PLAN-M4 section 8: no "page can't be found". Every page is rendered in memory (staging and production),
// then every internal href and form action is followed from the home pages: each must land on a built
// page that is not the 404 page, and each #anchor must be an id there.
import { test } from "node:test";
import assert from "node:assert/strict";
import { str } from "../src/lib/html.mjs";
import { ctx } from "../src/lib/ctx.mjs";
import { content } from "../src/lib/content.mjs";
import { routes, pageRoutes } from "../src/routes.mjs";
import { layout } from "../src/partials/layout.mjs";
import { pages } from "../src/pages/index.mjs";
import { checkLinks } from "../scripts/check-links.mjs";

const site = (prod) => {
  const out = new Map();
  for (const [key, r] of pageRoutes) {
    if (prod && r.prototypeOnly) continue;
    for (const lang of ["vi", "en"]) {
      const c = ctx(lang, key, prod);
      out.set(r[lang], str(layout(c, pages[r.page](c))));
    }
  }
  return out;
};

for (const prod of [false, true]) {
  test(`${prod ? "production" : "staging"}: 0 dead links in VI and EN`, () => {
    const s = site(prod);
    const { pages: crawled, dead } = checkLinks((p) => s.get(p) ?? null);
    assert.deepEqual(dead, [], dead.map((d) => `${d.from} → ${d.href}: ${d.why}`).join("\n"));
    // Every built page is reachable from the home pages (no orphan the menus forgot).
    for (const p of s.keys()) assert.ok(crawled.includes(p), `${p} is not linked from anywhere`);
  });
}

test("every route the site links to is built: no key without a page except anchors inside built pages", () => {
  for (const [key, r] of Object.entries(routes)) {
    if (r.page) continue;
    for (const lang of ["vi", "en"]) {
      const [path, hash] = r[lang].split("#");
      assert.ok(hash, `${key} has no page and is not an anchor`);
      assert.ok(pageRoutes.some(([, pr]) => pr[lang] === path), `${key}: ${path} is not built`);
    }
  }
});

test("the anchors the footer and menus use exist in both languages, in production too", () => {
  for (const prod of [false, true]) {
    const s = site(prod);
    for (const key of ["reports", "bakery", "safeguarding", "privacy"]) {
      for (const lang of ["vi", "en"]) {
        const [path, hash] = routes[key][lang].split("#");
        assert.match(s.get(path), new RegExp(`\\sid="${hash}"`), `${prod ? "PROD" : "staging"} ${routes[key][lang]}`);
      }
    }
  }
});

test("each language keeps its own ids: the VI page never carries the EN anchors and the other way round", () => {
  const s = site(false);
  for (const key of ["reports", "bakery", "safeguarding", "privacy"]) {
    const [viPath] = routes[key].vi.split("#");
    const [enPath] = routes[key].en.split("#");
    assert.doesNotMatch(s.get(viPath), new RegExp(`\\sid="${routes[key].en.split("#")[1]}"`));
    assert.doesNotMatch(s.get(enPath), new RegExp(`\\sid="${routes[key].vi.split("#")[1]}"`));
  }
});

test("work.json paths are the routes: a center or program link always lands on its page", () => {
  const byPath = new Map(pageRoutes.flatMap(([k, r]) => [[r.vi, k], [r.en, k]]));
  for (const x of [...content.work.centers, ...content.work.programs]) {
    for (const lang of ["vi", "en"]) assert.ok(byPath.has(x.path[lang]), `${x.id} ${lang}: ${x.path[lang]} is not a built page`);
  }
  assert.equal(content.work.centers.find((x) => x.id === "cau-han").path.en, "/en/our-work/centers/cau-han-project/", "PLAN-M4 decision 4");
});
