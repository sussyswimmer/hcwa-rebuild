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

Nothing in this folder has run inside WordPress yet. In the shared build environment,
downloading and running WordPress and WP-CLI needs Maxwell's permission. Until he gives it,
the only checks are `php -l`, `node --check` and `php wp/tests/run.php`, which uses
WordPress stubs (see "Checks without WordPress" below).

`scripts/wp-local.sh` enforces this. Commands that download or run WordPress (`setup`,
`start`, `seed`, `wp`, `reset`) only run from a terminal, or with `HCWA_WP_LOCAL_OK=1`
set once permission is given. Automated runs are refused with a message pointing here.

## Run it locally

You need PHP 8.1 or newer with `pdo_sqlite` and `mbstring` (and ideally `intl`), plus `curl`
and `unzip`. You do not need MySQL or Docker: the database is one SQLite file. The smoke
test needs the repo's Python virtualenv with Playwright (`.venv`, as for
`scripts/live-audit.py`).

```sh
scripts/wp-local.sh setup main      # once: WordPress + Polylang + SQLite, our plugin and theme
scripts/wp-local.sh seed main       # languages, pages, centers, programs, figures, news, menus
scripts/wp-local.sh start main      # http://127.0.0.1:8881/  (English: /en/)
scripts/wp-local.sh smoke main      # browser check, screenshots in qa/wp/<date>/
```

Log in at `http://127.0.0.1:8881/wp-admin/` as `admin` / `admin`. That account is fine
because the server only listens on this computer. Never expose it.

| Command | What it does |
|---|---|
| `setup <name> [port]` | Creates `.wp-local/<name>/` (default port 8881). Downloads into `.wp-local/cache/` once: WordPress (`wordpress.org/latest.zip`, SHA-1 checked), WP-CLI (`wp-cli.phar`, SHA-512 checked), Polylang 3.9.1 and sqlite-database-integration 3.1.3 from downloads.wordpress.org, and the Vietnamese language packs. It then writes the SQLite drop-in (`wp-content/db.php`, from the plugin's `db.copy`) and `wp-config.php` (environment `local`, `WP_DEBUG` and `WP_DEBUG_LOG` on, no outgoing HTTP, no cron). It installs WordPress, links `wp/plugins/hcwa-core` and `wp/themes/hcwa-2026` into `wp-content` as symlinks, activates Polylang, hcwa-core and hcwa-2026, and sets permalinks to `/%postname%/`. Safe to run again. |
| `start <name> [port]` | Runs PHP's built-in server in the background with `scripts/wp-router.php` and `PHP_CLI_SERVER_WORKERS` (default 4). Giving a port moves the instance there; the database is not touched. |
| `stop [name]` | Stops one instance, or all of them. |
| `seed <name>` | Fills the instance from `content/draft/` (details below). Safe to run again. |
| `smoke <name> [-- args]` | Runs `scripts/wp-smoke.py` against the running instance (details below). |
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
  translation is on, and taxonomies, custom fields, page parent and featured image are
  synced. The English site title is set as a Polylang string translation.
- **Focus areas** (`education`, `protection`, `family`, `policy`; Vietnamese slugs end in
  `-vi`) and **news categories** (Tin tức/News, Câu chuyện/Stories, Sự kiện/Events,
  Thông báo/Announcements), each linked as translations.
- **A page for every logical page** in `hcwa_page_paths()`, in both languages, at the
  URLs from `prototype/src/routes.mjs`. Home, Our Work, Get Involved, Donate and Contact
  take their body from the theme's `hcwa-2026/page-*-vi|en` patterns. The other pages get
  the page header plus one `[DRAFT: …]` paragraph, and the headings other pages link to
  (`#bao-cao`, `#bao-ve-tre-em`, `#quyen-rieng-tu`, `#tiem-banh` and their English
  versions). If a page pattern is not there yet, the seed says so; run it again later.
- **Front page and posts page** for each language.
- **Centers and programs** from `work.json`. Little Rose gets
  `hcwa_no_identifiable_children`. Funders named in `work.json` (CapitaLand Vietnam) become
  partner records, hidden from the home page and without logo permission.
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
and the production publish guard catch it. Seeded items carry `hcwa_seed_id`, so running
the seed again updates them in place and never duplicates them. Content you add by hand is
left alone. The seed refuses to run when the instance is set to `production`.

One known difference from `work.json`: Polylang free cannot give an English post the same
slug as its Vietnamese translation. Cau Han wants `cau-han` in both languages, so its
English URL becomes `/en/our-work/centers/cau-han-project/`. The seed prints a warning when
this happens.

## What the smoke test checks

`scripts/wp-smoke.py` loads every URL from `seed-urls.json`, plus one missing page per
language, at 390×844 and 1440×900. On each page it checks:

- the response is HTTP 200 with no redirect (404 for the missing pages)
- `<html lang>` is right for the language
- the `hreflang` links for `vi`, `en` and `x-default` are there, and the one for the other
  language points to the seeded translation
- there is exactly one `<h1>`
- there are no PHP warnings, notices, deprecations or fatal errors, and no WordPress error
  page
- the page does not scroll sideways
- no Vietnamese text has `text-transform: uppercase`
- on a non-production instance: a `noindex` robots tag, and a pending chip on pages with
  impact figures
- with `--production` (set automatically after `env <name> production`): no pending
  chips, no `noindex`, and no `[DRAFT` anywhere. The seeded content is draft by design, so
  this last check fails on a seeded instance until HCWA's real content replaces it. Add
  `-- --allow-draft` to check the other production gates on seeded content.

Both failures listed here earlier are fixed in the code: hcwa-core adds `x-default` (the
Vietnamese URL) through Polylang's `pll_rel_hreflang_attributes` filter
(`includes/polylang.php`), and the `hcwa/impact-strip` block prints the pending chip on
staging. Polylang prints hreflang links only for content that has a translation, so a
VI-only news post has none; that is expected.

It saves full-page screenshots and `report.json` to `qa/wp/<date>/`, prints a summary,
and exits with 1 if anything fails. Images from the media library and from other sites are
blurred before the page renders, so screenshots of a copy of the live site never show an
identifiable child. Without `--manifest`, it checks the built pages from
`prototype/src/routes.mjs`.

## Checks without WordPress

Run these before every report (from `CONTRACT.md`):

```sh
find wp -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v 'No syntax errors'
find wp -name '*.js' -print0 | xargs -0 -n1 node --check
php wp/tests/run.php
bash -n scripts/wp-local.sh && php -l scripts/wp-seed.php && php -l scripts/wp-router.php
.venv/bin/python -I -m py_compile scripts/wp-smoke.py
python3 -I scripts/wp-static-check.py
```

`wp-static-check.py` cross-checks the parts against each other and against `CONTRACT.md`:
string keys, function names, block names and attributes, pattern slugs and links, CSS
classes, meta keys, the text rules, plus the lint commands above. It exits 1 on any error;
warnings are listed for review.

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
