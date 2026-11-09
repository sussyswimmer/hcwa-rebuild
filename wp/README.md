# HCWA WordPress: hcwa-core and hcwa-2026

Phase 2 of the HCWA website rebuild. The current WordPress install, its posts and its URLs
stay. What changes is the theme and the page layouts, ported from the Phase 1 prototype
(`prototype/`).

- **`plugins/hcwa-core`** holds everything that must survive a theme change: the content
  types (centers, programs, reports, impact figures, partners), their fields, the bilingual
  UI strings, the per-language URLs, Settings → HCWA, and the safety gates. The gates are:
  unverified figures never show on the live site, unapproved photos never render, donation
  details only appear after HCWA finance confirms them, and draft text cannot be published
  on the live site. It also adds `wp hcwa coverage` and `wp hcwa draft-scan`.
- **`themes/hcwa-2026`** is a hybrid theme. It has PHP header, footer and templates, design
  tokens in `theme.json`, and page bodies built from block patterns in Vietnamese/English
  pairs. The custom blocks (impact strip, center grid and others) live in hcwa-core.

`CONTRACT.md` is the agreement between the parts: names, file ownership, function
signatures and gating rules. Read it before changing anything here.

## Permission note

Maxwell gave permission on 9 Nov 2026 to download and run WordPress locally in the shared
build environment. Since then the theme and plugin run in a real local WordPress (7.1.4,
Polylang 3.9.1, SQLite), and that instance is the main check of every change (see
"Run it locally"). The stub tests (`php wp/tests/run.php`) and the static check still run
without WordPress, for CI and a fresh clone.

`scripts/wp-local.sh` still asks for the permission to be stated. Commands that download or
run WordPress (`setup`, `start`, `seed`, `wp`, `reset`) run from a terminal, or from an
automated run with `HCWA_WP_LOCAL_OK=1` set. Without either they are refused with a message
pointing here. Automated runs set it on every command, for example
`HCWA_WP_LOCAL_OK=1 scripts/wp-local.sh smoke main`.

Root containers: cloud build containers only have the root user, and WP-CLI refuses to run
as root unless it is given `--allow-root`. `scripts/wp-local.sh` passes that flag itself
whenever it runs as root (`id -u` is 0), on every WP-CLI call it makes (`setup`, `seed`,
`wp`, and the language-cache reset of `start`), so nobody needs to add it by hand. That is safe here because the
instance only listens on 127.0.0.1 and runs this repo's code plus the pinned, hash-checked
downloads. Do not pass `--allow-root` on HCWA's own servers.

Rules for the shared instances (`docs/plan/11-WP-PORT.md` 5.9): the seeded instance `main`
(port 8881) is shared by every agent, so never `stop`, `reset`, `setup` or `env` it; re-seed
it with `seed main` after a content-model change. Production checks and editor checks run on
a throwaway instance with its own name and port (`setup scratch-<label> <port>`), stopped
when done. Never install another plugin or theme from the internet.

## Run it locally

You need PHP 8.1 or newer with `pdo_sqlite` linked against SQLite 3.37.0 or newer (the
sqlite-database-integration plugin refuses older ones; `setup` checks it) and `mbstring`
(and ideally `intl`), plus `curl` and `unzip`. You do not need MySQL or Docker: the
database is one SQLite file. The smoke test needs the repo's Python virtualenv with
Playwright (`.venv`, as for `scripts/live-audit.py`). `smoke` uses the repo-local Chromium
in `.playwright-browsers/` when that folder exists.

```sh
npm --prefix prototype ci && npm --prefix prototype run export:wp   # the theme's assets/dist/ (not committed)
scripts/wp-local.sh setup main      # once: WordPress + Polylang + SQLite, our plugin and theme
scripts/wp-local.sh seed main       # languages, pages, centers, programs, figures, news, menus
scripts/wp-local.sh start main      # http://127.0.0.1:8881/  (English: /en/)
scripts/wp-local.sh smoke main      # browser check, screenshots in qa/wp/<date>-<time>/
scripts/wp-local.sh parity main -- --quick   # side by side with the built prototype
```

The theme takes every CSS, JS, font, still and SVG file from `assets/dist/`, which
`npm --prefix prototype run export:wp` builds from the prototype's own sources
(`docs/plan/11-WP-PORT.md` 1.1). It is not committed (the posters alone are 14 MB), so run
the export once after a clone and again after any prototype change. The generated files
the PHP reads (`assets/generated/`, `hcwa-core/includes/prototype-data.php`,
`wp/tests/fixtures/golden.json`) are committed, so the tests run without it.

Log in at `http://127.0.0.1:8881/wp-admin/` as `admin` / `admin`. That account is fine
because the server only listens on this computer. Never expose it.

| Command | What it does |
|---|---|
| `setup <name> [port]` | Creates `.wp-local/<name>/` (default port 8881). Downloads into `.wp-local/cache/` once: WordPress (`wordpress.org/latest.zip`, SHA-1 checked), WP-CLI (`wp-cli.phar`, SHA-512 checked), Polylang 3.9.1 and sqlite-database-integration 3.1.3 from downloads.wordpress.org, and the Vietnamese language packs. It then writes the SQLite drop-in (`wp-content/db.php`, from the plugin's `db.copy`) and `wp-config.php` (environment `local`, `WP_DEBUG` and `WP_DEBUG_LOG` on, no outgoing HTTP, no cron). It installs WordPress, links `wp/plugins/hcwa-core` and `wp/themes/hcwa-2026` into `wp-content` as symlinks, activates Polylang, hcwa-core and hcwa-2026, and sets permalinks to `/%postname%/`. Safe to run again. |
| `start <name> [port]` | Runs PHP's built-in server in the background with `scripts/wp-router.php` and `PHP_CLI_SERVER_WORKERS` (default 4). Giving a port moves the instance there; the database is not touched. `wp-config.php` sets `PLL_CACHE_HOME_URL` to false, and a port change also clears Polylang's language cache, so links follow the new port. |
| `stop [name]` | Stops one instance, or all of them. |
| `seed <name>` | Fills the instance from `content/draft/` (details below). Safe to run again. |
| `smoke <name> [-- args]` | Runs `scripts/wp-smoke.py` against the running instance (details below). |
| `parity <name> [-- args]` | Runs `scripts/wp-parity/run.py`: the built prototype (`prototype/dist`, served by `vite preview`) and this instance side by side (details below). Reads the instance, never changes it. `--quick`, `--full`, `--only home`, `--self-test`, `--production-asserts`. |
| `env <name> local\|staging\|production` | Sets `WP_ENVIRONMENT_TYPE`. `production` switches on the live-site gates, so you can check them locally. No restart needed. |
| `wp <name> -- <args>` | WP-CLI passthrough, for example `scripts/wp-local.sh wp main -- hcwa coverage`. |
| `reset <name> [--yes]` | Deletes the instance and sets it up again on the same port. The downloads cache stays. The repo's plugin and theme are symlinks, so they are not touched. |
| `list` | Shows each instance with its address, environment and whether it is running. |

What lives where:

```
.wp-local/
  cache/                 downloads, reused by every instance (delete a file to fetch it again)
  <name>/
    wp/                  WordPress (the web root); wp-content/plugins/hcwa-core and
                         wp-content/themes/hcwa-2026 are symlinks into wp/
    wp-config.php        one folder above the web root, never served
    hcwa-env.php         the environment type, written by `env`
    data/hcwa.sqlite     the database
    logs/                debug.log (PHP notices), server.log (requests)
    seed-urls.json       the URLs the seed created, read by `smoke`
```

Edits to `wp/plugins/hcwa-core` and `wp/themes/hcwa-2026` show on the next page load.
`WP_DEVELOPMENT_MODE` is `theme`, so new patterns appear without clearing a cache.

The router serves files that exist, sends everything else through WordPress's
`index.php`, and never serves dotfiles, `.sqlite`, `.log` or `wp-config.php`.

Settings you can change with environment variables: `PHP` (php binary), `HCWA_WP_VERSION`
(default `latest`; set for example `7.1.4` to pin), `HCWA_POLYLANG_VERSION` (`3.9.1`),
`HCWA_SQLITE_VERSION` (`3.1.3`), `HCWA_PHP_WORKERS` (`4`). The seed was checked against the
Polylang 3.9.1 source, so change that version only on purpose.

## What the seed creates

The seed runs `scripts/wp-seed.php` through WP-CLI in two passes. The first pass sets up
the languages. The second adds the content in a fresh run, because Polylang only connects
its content hooks when languages already exist at startup.

- **Languages and Polylang settings** (07-BILINGUAL): Tiếng Việt (`vi`, default) and
  English (`en`, locale `en_GB`). The language comes from the URL directory, Vietnamese has
  no prefix, and the English front page is `/en/`. Browser detection is off, media
  translation is on, and taxonomies, page parent and featured image are synced.
  Polylang's "Custom fields" sync stays off, because it would copy per-language text (such
  as WordPress's footnotes) between the languages. hcwa-core syncs its own shared fields
  (years, status, the verified flag and so on) either way. The English site title is set
  as a Polylang string translation.
- **Focus areas** (`education`, `protection`, `family`, `policy`; Vietnamese slugs end in
  `-vi`) and **news categories** (Tin tức/News, Câu chuyện/Stories, Sự kiện/Events,
  Thông báo/Announcements), each linked as translations.
- **A page for every logical page** in `hcwa_page_paths()`, in both languages, at the
  URLs from `prototype/src/routes.mjs`. Home, Our Work, Get Involved, Donate and Contact
  take their body from the theme's `hcwa-2026/page-*-vi|en` patterns. The other pages get
  the page header plus one `[DRAFT: …]` paragraph, and the headings other pages link to
  (`#bao-cao`, `#bao-ve-tre-em`, `#quyen-rieng-tu`, `#tiem-banh` and their English
  versions). The Impact page gets the impact strip and the report list, and the Partners
  page the partner wall; each `hcwa/*` block sits in a `container` group with its heading,
  as in the page patterns. If a page pattern is not there yet, the seed says so; run it
  again later.
- **Front page and posts page** for each language.
- **Centers and programs** from `work.json`. Little Rose gets
  `hcwa_no_identifiable_children`. Each center gets a linked VI / EN post tag with its own
  slugs (`hoa-hong-nho` / `little-rose` and so on) as its news tag, which is what extends
  the Little Rose photo rule to its news; `wp hcwa coverage` fails while a center with
  that flag has no news tag. No seeded post is tagged, because none of the posts in
  `news.json` is about one center. Funders named in `work.json` (CapitaLand Vietnam) become
  partner records, hidden from the home page and without logo permission.
  On the imported live site the slugs are the other way round: the Vietnamese Little Rose
  tag is `hoa-hong-nho-vi` and `hoa-hong-nho` is the English one. Either slug works in the
  center's news tag field: hcwa-core protects the tag's whole Polylang translation group,
  plus the always-protected `hoa-hong-nho`, `hoa-hong-nho-vi`, `little-rose` and `lrws`
  tags (`hcwa_protected_tag_slugs()`). `wp hcwa coverage` still asks for the Vietnamese
  slug, and lists posts and tags that name Little Rose but are not protected.
- **A photo-gate fixture**: one synthetic flat-colour photo (no person in it) with a GPS
  position, uploaded the way staff upload and set as the Vietnamese Little Rose center's
  featured image, marked "Approved for web" and "Identifiable child". The seed stops if
  any file WordPress kept still carries a location, and warns if the file or title did not
  get a neutral `hcwa-…` name. The gate refuses the photo only because of the Little Rose
  context, which the smoke test checks.
- **Impact figures** from `impact.json`. All are unverified, with no values, so staging
  shows them with the "Đang xác minh / Pending verification" chip and production shows
  none.
- **News posts** from `news.json`. Titles, dates and slugs come from the current site. Each
  body is a `[DRAFT]` note with a link to the original post. The placeholder story is
  included too.
- **Menus**: a main menu and two footer menus per language. They are assigned through
  Polylang's per-language menu locations.
- **Settings → HCWA**: address, phone, emails, Facebook and map link from `site.json`,
  marked `[DRAFT]` where `site.json` says so, plus the page map. No donation details, since
  HCWA finance has not confirmed any.

Every draft field in `content/draft/` is wrapped in `[DRAFT: …]`, so `wp hcwa draft-scan`
and the production publish guard catch it. Seeded items carry the protected meta key
`_hcwa_seed_id`, so running the seed again updates them in place and never duplicates
them. Content you add by hand is left alone: Polylang never copies a protected key, so a
translation you create with "+" is not mistaken for a seeded one. (Instances seeded before
this change used `hcwa_seed_id`; the seed renames it on its next run.) The seed refuses to
run when the instance is set to `production`.

One known difference from `work.json`: Polylang free cannot give an English post the same
slug as its Vietnamese translation. Cau Han wants `cau-han` in both languages, so its
English URL becomes `/en/our-work/centers/cau-han-project/`. The seed prints a warning when
this happens.

## What the smoke test checks

`scripts/wp-smoke.py` loads every URL from `seed-urls.json`, plus one missing page per
language, at 390×844 and 1440×900. On each page it checks:

- the response is HTTP 200 with no redirect (404 for the missing pages)
- `<html lang>` is right for the language
- the `hreflang` links for `vi`, `en` and `x-default` are there: the page's own language
  points to the page, the other language to the seeded translation, `x-default` to the
  Vietnamese URL, and all of them to the address being tested (so a stale port fails)
- there is exactly one `<h1>`
- there are no PHP warnings, notices, deprecations or fatal errors, and no WordPress error
  page
- the page does not scroll sideways
- no Vietnamese text has `text-transform: uppercase`
- on a non-production instance: a `noindex` robots tag, and a pending chip on pages with
  impact figures
- with `--production` (set automatically after `env <name> production`): no pending
  chips, no `noindex`, no `[DRAFT` anywhere, and none of the publish guard's other draft
  markers in the page text (placeholder tokens such as `[N]`, `[năm]`, `[year]`, and
  bracketed notes that mention HCWA). The seeded content is draft by design, so these
  draft checks fail on a seeded instance until HCWA's real content replaces it. Add
  `-- --allow-draft` to check the other production gates on seeded content.

Both failures listed here earlier are fixed in the code: hcwa-core adds `x-default` (the
Vietnamese URL) through Polylang's `pll_rel_hreflang_attributes` filter
(`includes/polylang.php`), and the `hcwa/impact-strip` block prints the pending chip on
staging. Polylang prints hreflang links only for content that has a translation, so a
VI-only news post has none; that is expected.

With the seed's URL list it also sends plain HTTP requests, without a browser and without
following redirects, for gates that no page view shows: a center's `/embed/` URL answers
404 (embeds are off), `/wp-json/wp/v2/media` answers 401 to a visitor (also in upper case,
since WordPress matches REST routes in any case), and a center URL under the English base
with the Vietnamese slug (`/en/our-work/centers/tre-xanh/`) answers one 301 to the
Vietnamese permalink on the host being tested. It also checks that the SEO plugin's head
stays out of public REST (`/wp-json/wp/v2/posts` carries no `yoast_head` or
`yoast_head_json`, `/wp-json/yoast/v1/get_head` never answers 200) and that
`post-sitemap.xml` lists no `<image:loc>`; these pass without Yoast too and become real
checks once it is installed. For the seed's photo-gate fixture (a refused Little Rose
photo), the Little Rose center page must not contain the photo's file name anywhere, so
neither the page nor an SEO plugin's `og:image`, `twitter:image` or JSON-LD prints it. Its
attachment page is probed in every form that reaches it: `/?attachment_id=<id>`,
`/?p=<id>`, `/?page_id=<id>`, the same through `post_type=attachment` (`p`, `page_id`,
`name`, `post_type[]`) and `/?attachment=<slug>`. Each must answer 404 with no `Location`
header and a body that names neither the file nor the Little Rose path (`/hoa-hong-nho/`,
`/little-rose/`); each is also requested as an `rss2`, `atom` and `comments-rss2` feed,
which must answer 404 the same way with no `<item>` or `<entry>` (fix round 2 found the
feed forms printing the photo's item with a 404 status). The center and program type
feeds and the search feeds (`?feed=rss2|atom&post_type=hcwa_center|hcwa_program`,
`?s=a&feed=rss2|atom`) must answer 404 with no item, and the any-type feed must list no
center or program page. A probe declares what it checks with `status`, `no_location`,
`body_absent` (text the body must not contain) and `body_absent_re` (patterns it must not
match). 48 HTTP probes in all on `main`, 32 of them feeds.

**Port checks** (`docs/plan/11-WP-PORT.md` 5.7) run on every seeded view as well: the head
order (head-inline, then `pageReveal`, `#i18n` with its 11 keys), the CSS and module from
`assets/dist/` with no `?ver` and no jQuery, Fraunces, `wp-embed` or emoji script, the
`body` order and the lit-page pairs, the language switch against `hreflang`, every menu
link answering 200 in the page's language, the corridor's beat count, the Little Rose rules
(no location line, no visit link, no unit colour; on production no door in any form and no
listed still), traces off production, impact numbers that never count up, no mixed
language, and the no-JS Contact submit on staging. Under `--production` the exported
`BLOCKERS` scan, the Little Rose scans and the still-name scan always run, whatever
`--allow-draft` says. `--unpublished <file>` lists URLs that must answer 404 (the 5.9
production pass below). The script's docstring lists every check.

It saves full-page screenshots and `report.json` to `qa/wp/<date>-<time>/` (one folder
per run, `-production` added for a production run), prints a summary, and exits with 1 if
anything fails. Images from the media library and from other sites are blurred before the
page renders, so screenshots of a copy of the live site never show an identifiable child.
Without `--manifest`, it checks the built pages from `prototype/src/routes.mjs`.

## Parity with the prototype

`scripts/wp-local.sh parity main` (`scripts/wp-parity/run.py`, spec in
`docs/plan/11-WP-PORT.md` section 5) serves `prototype/dist` with `vite preview` and loads
the prototype and WordPress side by side on the 16 parity routes (VI and EN of home, Our
Work, Green Bamboo, Thrive Together, Get Involved, Donate, Contact and the 404) at 390 and
1440, in the reduced, no-JS, no-WebGL and Tier 0 modes. Per section it compares the
structure, the visible text, the links and a screenshot (height within 2 px, at most 1.0%
of pixels differing). Images served from `/wp-content/uploads/` are blurred as in the smoke
test, so their boxes, and the prototype image in the same place, are masked out of the
pixel count (`masked` and `maskedShare` in each pixel row). Each page also gets one H1, no
sideways scroll, no console error or failed request, and the same `<meta name=description>`,
`og:title`, `og:description` and `og:locale` as the prototype. The default run adds the
prototype's own browser checks against both sides, the world (`world-live` at Tier 1 and 2)
and the byte and request budgets; `--full` adds the home frame strips and mobile LCP through
a local gzip proxy. It also fails on any new `debug.log` line naming our code and on a hard
line WordPress adds between sections. Accepted differences live in
`scripts/wp-parity/allow-assets.json` and `allow-templates.json`, each entry with a reason
and optionally scoped to one width or mode. The report goes to
`qa/wp-port/parity/<date>-<time>/` (`report.json`, `report.html` with the images side by
side, `strips/`). At most 2 headless browsers run at a time. `--self-test` compares the
prototype with itself (the driver's noise floor).

## Production pass

Production behaviour is checked on a throwaway instance, never on `main`
(`docs/plan/11-WP-PORT.md` 5.9):

```sh
scripts/wp-local.sh setup scratch-prod 8890 && scripts/wp-local.sh seed scratch-prod
scripts/wp-local.sh env scratch-prod production && scripts/wp-local.sh start scratch-prod
scripts/wp-local.sh wp scratch-prod -- hcwa protect-little-rose --apply
scripts/wp-local.sh wp scratch-prod -- hcwa draft-scan --printed=raw --format=urls > qa/wp-port/scratch-prod/unpublished.json
scripts/wp-local.sh wp scratch-prod -- post update $(scripts/wp-local.sh wp scratch-prod -- hcwa draft-scan --printed=raw --format=ids) --post_status=draft
scripts/wp-local.sh smoke scratch-prod -- --unpublished qa/wp-port/scratch-prod/unpublished.json
scripts/wp-local.sh parity scratch-prod -- --production-asserts
scripts/wp-local.sh wp scratch-prod -- hcwa draft-scan --printed=raw   # lists nothing
scripts/wp-local.sh wp scratch-prod -- hcwa coverage                    # no blocking row
scripts/wp-local.sh stop scratch-prod
```

`protect-little-rose --apply` is the review step a live site runs once (Phase 3, before
3.6); a fresh production instance has never had it, so `coverage` blocks until it runs (on
seeded content it marks nothing). `draft-scan --format=urls` must run before the posts are
set to draft: a draft's permalink is `?p=N`, not its public path. Rows marked `guarded`
stay published, because the pass proves their draft text never prints on production.

## Checks without WordPress

Run these before every report (from `CONTRACT.md`):

```sh
find wp scripts -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v 'No syntax errors'
find wp -name '*.js' -print0 | xargs -0 -n1 node --check
bash -n scripts/wp-local.sh && python3 -m py_compile scripts/wp-smoke.py scripts/wp-parity/run.py
php wp/tests/run.php
python3 -I scripts/wp-static-check.py
python3 -I scripts/wp-static-check.py --self-test
```

`wp-static-check.py` cross-checks the parts against each other and against `CONTRACT.md`:
string keys, function names, block names and attributes, pattern slugs and links, CSS
classes (against the exported `classes.json` and `wp.css`), meta keys, the text rules,
the freshness of the generated files (`sources.json`) and of the patterns, the blocks
`hcwa_render_sections()` removes from a sections page, plus the lint commands above. It
exits 1 on any error; warnings are listed for review. Both it and `php wp/tests/run.php`
run in a fresh clone without `assets/dist/` (the bundle checks are then skipped).

On real WordPress, before a report (see `CONTRACT.md`): `wp main -- hcwa coverage` (no
blocking row), `smoke main` (every view passes) and `parity main -- --quick`, plus the
production pass above when production behaviour changed. `.wp-local/main/logs/debug.log`
must gain no warning, notice or deprecation naming `hcwa-2026` or `hcwa-core`.

## Phase 2 status

As of 5 Nov 2026. "Written" means checked with the commands above but not yet run inside
WordPress (see the permission note).

| # | Task | Status |
|---|---|---|
| 2.1 | Spike: block theme or hybrid | Done. Hybrid, decided from the Polylang 3.9.1 and WordPress 7.1.4 source (`docs/plan/03`). Still to confirm on a running install. |
| 2.2 | Import a copy of the live site | Not started. Needs the host's staging or a migration export, and permission to run WordPress. |
| 2.3 | hcwa-core: types, fields, media approval, Polylang, `wp hcwa coverage` | Written. Stub tests pass. |
| 2.4 | Theme: `theme.json`, fonts, header and footer, templates | Written. Stub tests pass. |
| 2.5 | Custom blocks | Written. The nine `hcwa/*` blocks are in `plugins/hcwa-core/blocks/`, loaded by `includes/blocks.php`. Stub tests pass. |
| 2.6 | Block patterns | Written. Includes the section patterns and the five page compositions the seed uses (`page-home`, `page-our-work`, `page-get-involved`, `page-donate`, `page-contact`), each as a VI/EN pair. Pattern markup test passes. |
| 2.7 | Forms | Blocked. Needs HCWA's answer to Q9 and a choice of form plugin. |
| 2.8 | Performance baseline | Not started. Needs a running site. |
| – | Integration of the parts | Done statically: `scripts/wp-static-check.py` is clean and `php wp/tests/run.php` passes (see `CONTRACT.md` Status). Not yet run in WordPress. |
| – | Local runtime, seed and smoke test (this page) | Written. Checked with `bash -n`, `php -l` and `py_compile`. The router and smoke test were also tried on a stub site. Not yet run against WordPress. |

The first run once permission is given: `setup`, `seed`, `start`, `smoke`, then
`wp main -- hcwa coverage` and `wp main -- hcwa draft-scan`. File what fails against the
owning part in `CONTRACT.md`.
