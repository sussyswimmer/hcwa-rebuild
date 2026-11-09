# WordPress port of the Bông gió prototype (11-WP-PORT.md)

Written 9 Nov 2026, revised the same day after an adversarial review (section 9). This is the spec for
porting the finished Phase 1 prototype (`prototype/`) to the WordPress theme `wp/themes/hcwa-2026` and
the plugin `wp/plugins/hcwa-core`. The theme and plugin were ported on 5 Nov from the old prototype
(Fraunces, hero split, bento focus areas, no world) and are stale.

Binding sources, in this order: `CLAUDE.md`, the port's hard limits (the task brief of 9 Nov),
`docs/plan/05-DESIGN.md` (0.5, 0.6, 0.6.7 "As built"), `prototype/PLAN.md` (section 9 logs every
departure), then this file. `wp/CONTRACT.md` stays the contract between the WordPress parts; the lead
updates it from section 6.4 before the streams start.

The prototype is the design source of truth. Where WordPress output differs from the prototype, the
difference must be listed in section 2.8 or in a parity allow list (section 5.4) with a reason.

Real WordPress is available (Maxwell, 9 Nov 2026). The seeded instance `main` runs at
`http://127.0.0.1:8881/` and is the primary verification. Other agents share it: never stop, reset,
re-setup it or switch its environment. Production checks run on a throwaway instance (section 5.9).

## 0. Decisions in one page

1. **One source for every asset.** A second Vite config in `prototype/` (`vite.wp.config.js`) and an
   export script (`prototype/scripts/export-wp.mjs`, `npm run export:wp`) build the same `src/main.js`
   with `base: "./"` and a manifest into `wp/themes/hcwa-2026/assets/dist/`. PHP enqueues from the
   manifest, with no query string on any dist URL (1.2). No CSS or JS is copied by hand.
   `vite.config.js`, `render.mjs` and every normal output stay byte-identical.
2. **Fixed generated markup is exported as files; data-driven markup is ported to PHP and tested against
   golden fixtures from the prototype's own functions.** No full-template stub renderer (5.8).
3. **No wrappers between sections.** Home sections and inner-page shells are dynamic `hcwa/*` blocks
   whose render callbacks print the prototype's markup exactly, with no wrapper element. Pages built
   from these blocks are printed by one helper, `hcwa_render_sections()`, which skips the `the_content`
   filters for our markup but still gates every block staff could add (2.1 rule 4).
4. **Page bodies stay locked block patterns (VI/EN pairs).** The lock is a wrapper-less container block,
   `hcwa/sections`, itself locked against moving and removal. Whether its `templateLock` is
   `"contentOnly"` or `"all"` is decided by a spike on one simple and one array block before the other
   blocks are built (4.2). Editable text is plain-text block attributes (4.1).
5. **Stills, posters and SVG art come from the theme through one interface** (`hcwa_art()`, 1.4), so
   hcwa-core never calls a theme function and still renders text-only without the theme. hcwa-core wraps
   the theme's provider in a guard that refuses every still showing the Little Rose door while that door
   is not approved.
6. **Little Rose on production, until HCWA's safeguarding lead approves the door:** no door in any form
   anywhere: no bay, no door card, no door chip, no door span, and no still that shows the door, whatever
   the still is named. The corridor builds N−1 doors and Little Rose appears in words only (3.4). One
   switch, `hcwa_little_rose_door_allowed()`, turns every one of these on together once approved; there is
   no partial state. Q1 is asked before the templates stream builds 3.4; until HCWA answers, this default
   holds.
7. **Traces stay staging-only** (port hard limit). There is no production approval path for them.
8. **Forms do not render on production** until task 2.7 (Q9 and the form plugin) gives them a real
   handler; production shows only the contact lines HCWA has confirmed (3.5).
9. **Parity is measured, not eyeballed only:** a Playwright driver loads the built prototype and the real
   WordPress side by side and compares section structure, visible text, link targets, per-section
   screenshots, the prototype's own browser checks, frame strips, LCP and budgets (section 5). The
   production variants are checked by explicit assertions, not by parity (5.9, gate 7.7).

## 1. Architecture: how the theme gets the prototype's assets

### 1.1 The WordPress build mode (approved by this plan)

`prototype/PLAN.md` and the port's hard limits allow a build mode only if the plan says so and normal
outputs stay byte-identical. This plan says so, on these terms:

| Item | Spec |
|---|---|
| New file | `prototype/vite.wp.config.js`. Imports nothing from `vite.config.js` and does not call `render.mjs`'s `renderAll` (no HTML pages are built). |
| Root, base | `root: prototype/`, `base: "./"`. A relative base makes CSS `url(/fonts/…)`, `url(/svg/…)`, the preload helper's `assets/…` list and the worker's `new URL("./gach-worker.js", import.meta.url)` resolve next to the files, so the bundle works under any theme URL. |
| Entries | `{ main: "src/main.js", draft: "src/styles/draft.css" }`. `draft.css` is its own entry so production never loads it (in the prototype it is imported first by `.site/main.js` on staging only). |
| Output | `outDir: ../wp/themes/hcwa-2026/assets/dist`, `emptyOutDir: true`, `manifest: "manifest.json"`, `copyPublicDir: true` (posters, svg, fonts, img), `chunkSizeWarningLimit: 700`. |
| package.json | Two scripts only: `"preexport:wp": "npm run tokens"` and `"export:wp": "node scripts/export-wp.mjs"`. No dependency changes. |
| Export steps | `export-wp.mjs` runs `vite build --config vite.wp.config.js`, deletes the Netlify files from dist (`_headers`, `_redirects`, `robots.txt`), then writes the generated files (1.3) and the golden fixtures (5.8). It imports `render.mjs` only for its exported constants (`BLOCKERS`, `LITTLE_ROSE_DOOR`, `MIN_PARTNER_LOGOS`); importing it has no side effect beyond loading content. |
| Proof of no change | After the port: `git diff --stat prototype/` lists only `package.json`, `vite.wp.config.js`, `scripts/export-wp.mjs` and, optionally, `test/export-wp.test.mjs`. `npm test`, `npm run build`, `npm run build:pages` pass; `PROD=1 npm run build` still fails with exactly the 59 problems in `qa/wp-port/prototype-map/prod-guards.log`. |
| Git | `assets/dist/` is already ignored by the root `.gitignore` rule `dist/`. It holds the 14 MB of posters, so it is never committed; deploys and local WordPress run `npm --prefix prototype ci && npm --prefix prototype run export:wp` first. `assets/generated/**`, `hcwa-core/includes/prototype-data.php` and `wp/tests/fixtures/golden.json` (small, deterministic text) are committed, so the unit tests and the static check run in a fresh clone without the export. |

Measured today (`prototype/dist`, gzip -9), which the WordPress build must reproduce within 2% when
compared as the same sums (gate 7.2): main CSS 150.1 KB (28.6 KB gz), which already includes `draft.css`
(1.0 KB gz on its own); the JS entry and its static imports, `main` 19.9 KB (7.4 KB gz) plus `common`
22.3 KB (8.6 KB gz), 16.0 KB gz together; the motion chunk (GSAP, ScrollTrigger, Lenis) 147.6 KB (55.3 KB
gz); the world set (world, journey with three.js, live, frame, corridor, shutters, gach-worker) 664.8 KB
(183.8 KB gz); four fonts 122.6 KB; the home HTML 82.3 KB (13.5 KB gz). With one JS entry, rolldown may
split `main` and `common` differently; only the sums are compared.

### 1.2 What PHP prints, and in what order

| # | Output | Where | Mechanism |
|---|---|---|---|
| 1 | `<meta charset>`, `<meta name="viewport">` | `header.php` | as today |
| 2 | Head-inline script (≤ 1536 B), first script, before any stylesheet | `header.php` calls `hcwa_theme_print_head_first()` before `wp_head()` | prints `assets/generated/head-inline.production.js` when `hcwa_theme_is_production()`, else `head-inline.staging.js` (the `__PROTO__` flags `?tier`, `?motion=off`, `?drafts=1` then work on local and staging) |
| 3 | View Transitions `pageReveal` script | same function, right after 2 | prints `assets/generated/page-reveal.js` (the function text exported from `src/motion/transitions.js`) |
| 4 | `<title>` | `wp_head` priority 1 | `document_title_parts` filter: home is `{orgName} (HCWA)`, other pages `{title} \| {shortName}` |
| 5 | Meta description, robots noindex (non-production), hreflang trio | `wp_head` | existing theme and hcwa-core code, Polylang |
| 6 | Font preloads: `archivo-w78-600-700.woff2`, `be-vietnam-pro-400.woff2`, `crossorigin` | `wp_preload_resources` | URLs under `assets/dist/fonts/` with no query string (the same URLs the bundle CSS uses, so the preload is reused) |
| 7 | Staging only: draft CSS, then the main CSS | `wp_enqueue_scripts` priority 20 | `wp_enqueue_style( 'hcwa-draft', …, array(), null )` (manifest entry `src/styles/draft.css`) before `hcwa-main` (version `null` too), keeping the prototype's cascade order. `hcwa-wp` (`assets/css/wp.css`, 2.7) follows `hcwa-main` only on WordPress-only pages (body class `page-wp`), when the announcement bar prints, or when the admin bar shows; the parity routes never load it. |
| 8 | `<script type="module" crossorigin src=…main-*.js>` and `<link rel="modulepreload" crossorigin>` for the manifest's static `imports` | `wp_head` priority 9, printed by `inc/assets.php` from the manifest | Not `wp_enqueue_script_module`: in a classic theme WordPress 7.1.4 prints script modules in the footer (`class-wp-script-modules.php` `add_hooks()`), and the prototype loads the module in `<head>`. |
| 9 | `<script type="application/json" id="i18n">` | `wp_head` priority 99 | `wp_json_encode` of the 11 keys `copy copied copyFailed f_sending f_send e_summary close motionPause motionPlay draftHide draftShow` in the page language, with `JSON_HEX_TAG`. Replaces `window.hcwaI18n`. |
| 10 | Favicons from `assets/dist/img/` | `wp_head` priority 99 | existing fallback, new paths |
| 11 | `<html … data-lit>` | `language_attributes` filter | when `hcwa_theme_page_context()['lit']` (6.4) |

**No query string on any dist URL.** Every URL under `assets/dist/` is printed exactly as the bundle
itself resolves it: absolute, built from `get_theme_file_uri()`, and with no `?ver=`. Styles and scripts
are enqueued with version `null`; the module tag and the modulepreload links are printed by hand. The
hashed file names do the cache busting. Reason: the motion and world chunks statically import the entry
(`motion-*.js` and `live-*.js` both contain `from"./main-BwCzEVtk.js"` today), so a module tag with
`?ver` would evaluate `main.js` twice (two buses, two headers, two worlds); and Vite's preload helper,
with a relative base, skips a CSS dependency only when an existing `<link>`'s resolved `href` equals the
dependency URL exactly, so a main CSS with `?ver` would be fetched and applied a second time. The theme
assets test and the smoke check both enforce this (gate 7.10).

**Dequeued on sections pages.** On every page printed by `hcwa_render_sections()` or by the center,
program and 404 templates (all of which hold only `hcwa/*` markup): `wp-block-library` and the per-block
core styles, `global-styles` and its inline CSS, `block-style-variation-styles`, `classic-theme-styles`,
`wp-img-auto-sizes-contain`, and front-end `wp_print_font_faces` (fonts come from the bundle's
`@font-face`). Today these add about 30 KB of inline CSS to every head (global styles alone 17 KB).
WordPress-only pages (`page-wp`) keep them, because news posts use core blocks. Removed everywhere:
`window.hcwaI18n`, `site.js`, `site.css`, `patterns.css`, the Fraunces preload, the emoji script,
`wp-embed`.

**WordPress-only scripts** (no prototype counterpart; 2.8):

- `assets/js/wp-bars.js`, printed inline by `hcwa_theme_bar_script()` right after the EN bar and right
  after the announcement slot, as today's `bars.js` does: it shows the EN bar and hides a dismissed
  announcement before first paint, and remembers an announcement's dismissal per text
  (`hcwa-announce-<hash>` in `localStorage`). The prototype's `ui/en-bar.js` still binds both dismiss
  buttons; running both is harmless (they set the same state). This keeps the CONTRACT round-1 decision
  (no layout shift from the bars).
- `assets/js/wp-share.js`, a deferred classic script of a few lines, enqueued only on single posts:
  reveals `li[data-share-native]` and calls `navigator.share`.

Inline scripts are generated or theme files, so their sha256 values are recorded in
`assets/generated/sources.json` for a future CSP.

### 1.3 Build-time markup: static files or PHP

| Markup | Decision | File or function | Test |
|---|---|---|---|
| Head-inline, both variants | export | `generated/head-inline.{staging,production}.js` | byte-equal to `headInline(false/true)` minus the `<script>` tags; ≤ 1536 B |
| `pageReveal` | export | `generated/page-reveal.js` (`(${pageReveal})();`) | equal to the prototype's |
| Gạch sprite (8,738 B) | export | `generated/svg/gach-sprite.svg` | golden |
| `gachArt` for 4 areas × `gach__art` / `gach__lit` | export | `generated/svg/gach-{art,lit}-{education,protection,family,policy}.svg` | golden |
| `screenArt` transoms (3 × 1 blocks) | export | `generated/svg/rail-transom-{dotTre,vomCau,hoaThi}.svg` | golden |
| `blockIcon` for emblems | export | `generated/svg/plate-mark-{dotTre,vomCau,hoaThi}.svg` | golden |
| Quatrefoil path, the 16 Lucide paths | export as data into hcwa-core, so blocks print them without the theme | `wp/plugins/hcwa-core/includes/prototype-data.php` (generated, returns the array in 6.4) | PHP builds `<svg class=…>`; golden for each class used |
| Maps: `AREA_BLOCK`, `AREA_PATCH`, `EMBLEMS`, `TILE`, `GROUT_PX`, area order, statuses, the `viKeep` glossary | export as data | the same `prototype-data.php` | read by hcwa-core and, through the bridge, the theme; no hand copy anywhere |
| Production guards: `BLOCKERS`, `LITTLE_ROSE_DOOR`, `MIN_PARTNER_LOGOS` from `render.mjs` | export as data | `prototype-data.php` (`guards`) for PHP and `generated/guards.json` for `wp-smoke.py` | `export-wp.test.mjs` checks the exported sources equal `String(re.source)`; a PHP unit test runs the PHP patterns over every `[…]` string in `content/draft/*.json` (3.3) |
| Stills and hero posters | export a manifest; PHP builds `<picture>` | `generated/stills.json`: per still name its kind, motion, layers, widest aspect, **`showsLittleRoseDoor`** (3.4), and per layer the exact `<source>` list (media, type, srcset with paths relative to dist, width, height) and the fallback `<img>`, computed from the files that exist (as `posters.mjs` does); the hero's per-bucket list likewise | PHP `<picture>` byte-equal to `stillPicture()`, `poster()`, `litStill()` for every call the prototype pages make (dist base set to `/`) |
| Class names the bundle CSS styles | export as data | `generated/classes.json` (every class selector in the main, draft and motion CSS) | read by `wp-static-check.py` instead of the gitignored dist CSS |
| `splitWords`, `viKeep` (27-entry glossary, longest first, letter-bounded), `keepHyphens` | PHP port in hcwa-core | `hcwa_split_words()`, `hcwa_vi_keep()`, `hcwa_keep_hyphens()` in `includes/text.php`, reading the glossary from `prototype-data.php` | golden over every heading and statement in `content/draft/*.json`, both languages |
| `gachStrip` | PHP port | `HCWA_Theme_Art::gach_strip()` | golden for every area set used by programs and chips |
| Dates (`dd/mm/yyyy`, `d MMM yyyy`, year on its own line) | existing `hcwa_date()` plus a split helper | `hcwa_date_parts()` | golden for the 3 seeded dates |
| `motion/tokens.js`, `world/tokens.js`, `POSTER_FRAMES`, buckets | stay in the JS bundle | none | none |
| `public/svg/*.svg` masks (15 files) | copied by Vite with the public dir | `dist/svg/` | referenced from CSS only, plus `--emblem:url(…)` built by `hcwa_art()->asset_url()` |

`generated/sources.json` records the sha256 of every prototype input the export read (head-inline.js,
transitions.js, blocks.mjs, components.mjs, vikeep.mjs, split-words.mjs, still-shots.js, sets/stills.js,
buckets.js, tokens.css, render.mjs, the CSS files, the poster file list). `wp-static-check.py` recomputes
them from `prototype/` (no dist needed) and fails with "run `npm --prefix prototype ci && npm --prefix
prototype run export:wp`" when they drift.

**Which stills show the Little Rose door.** `showsLittleRoseDoor` is true for every still whose scene
contains the Little Rose door at all, whether or not a crop frames it: scenes built from `HOME_BEATS`
(`passage-corridor`, `bay-*`), the hub wall (`hub-doors-wide`, `hub-door-*`, whose crops come from the
three-door wall) and any scene with `unit: "little-rose"` (`door-little-rose`). When the export cannot
tell, the still counts as showing the door (fails closed). Expected today: `passage-corridor`,
`bay-green-bamboo`, `bay-little-rose`, `bay-cau-han`, `hub-doors-wide`, `hub-door-green-bamboo`,
`hub-door-little-rose`, `hub-door-cau-han`, `door-little-rose`. `export-wp.test.mjs` asserts this list
and that `door-green-bamboo`, `door-cau-han`, `gallery-wall` and the header stills are false.

### 1.4 The art interface (hcwa-core declares it, the theme implements it)

hcwa-core must work with any theme and never calls theme functions (`CONTRACT.md`). Stills and SVG are
theme assets. So hcwa-core declares an interface, a null implementation and a guard, and the theme
registers its implementation through a filter:

```php
// hcwa-core/includes/art.php (templates stream)
interface HCWA_Art {
	public function has_still( string $name ): bool;
	public function shows_little_rose_door( string $name ): bool;       // stills.json; true for unknown names
	public function still_aspect( string $name ): float;                 // widest crop, 0 if none
	public function still( string $name, array $o = array() ): string;   // layer 'still'; o: cls, sizes (string|array crop=>sizes), eager, alt
	public function lit_still( string $name ): string;                   // div.lit.lit--still[.lit--{motion}] with 2-3 pictures, '' if not ready
	public function poster( string $shot, string $layer, array $o = array() ): string; // hero base|lightroom|lightscreen
	public function gach_sprite_once(): string;                          // the sprite the first time per request, then ''
	public function gach_art( string $area, string $variant ): string;  // variant art|lit
	public function gach_strip( array $areas, string $cls ): string;
	public function screen_art( string $emblem, string $cls ): string;
	public function block_icon( string $emblem, string $cls ): string;
	public function asset_url( string $path ): string;                   // 'svg/hole-dot-tre.svg', 'img/hcwa-logo.webp'
}
function hcwa_art(): HCWA_Art;  // HCWA_Art_Guard around apply_filters( 'hcwa_art_provider', null ), or around HCWA_Art_None
```

`HCWA_Art_Guard` passes every call through, except that `has_still()`, `still()` and `lit_still()` act as
for a missing still (false / '') for any name the provider reports with `shows_little_rose_door()` while
`hcwa_little_rose_door_allowed()` is false. It is the second lock under the per-place rules of 3.4: a
template that asks for a listed still on production before approval gets the prototype's missing-still
output, never the picture.

The theme's implementation is `HCWA_Theme_Art` in `wp/themes/hcwa-2026/inc/art.php` (assets stream),
reading `generated/` and `dist/manifest.json` once per request. `inc/art.php` declares the class only
when `interface_exists( 'HCWA_Art' )`, inside an `after_setup_theme` callback that also adds the
`hcwa_art_provider` filter, so the theme loads without a fatal error when hcwa-core is inactive
(CONTRACT: the theme degrades without the plugin). The theme assets test loads the theme without the
plugin and asserts no error.

### 1.5 theme.json, fonts, editor

- `theme.json` palette: only the colours editors need for news posts (`ink-900`, `ink-700`, `blue-700`,
  `blue-900`, `sun-400`, `sun-100`, `lime-50`, `lime-100`), slugs = the token names without `--color-`
  (05-DESIGN), values written by the export from `src/styles/tokens.css` into
  `settings.color.palette` (other keys are hand-kept). `defaultPalette`, `defaultGradients`,
  `defaultFontSizes`, custom colours and `appearanceTools` off. No `styles` element rules. The other
  tokens stay CSS custom properties in the bundle only.
- Font families `Archivo HCWA` (display) and `Be Vietnam Pro` (body) with the prototype's metric
  fallbacks; `fontFace` sources point at `file:./assets/dist/fonts/…` for the editor only.
  `python3 -I scripts/build-fonts.py --check wp/themes/hcwa-2026/assets/dist/fonts` must pass.
- Editor: `add_editor_style()` with the manifest's main CSS plus `assets/css/editor.css`, registered in
  `inc/assets.php` (assets stream; the call moves out of `inc/setup.php`), so block previews look like
  the front end. The world and motion chunks never load in the editor.
- Removed: `assets/fonts/fraunces-vf.woff2`, `Fraunces-OFL.txt`, the theme's copies of Be Vietnam Pro
  and `assets/img/*` (all come from `dist/`).

## 2. The DOM contract

### 2.1 Rules for every page

1. **The prototype's built HTML is the reference.** For each route in 5.2, `prototype/dist/<route>`
   (staging build) is what WordPress must print, section by section, except where 2.8 lists a
   difference.
2. **No element between siblings the CSS pairs.** These selectors must keep matching:
   `#centers + #work`, `[data-world] + .wall`, `.wall--sun + .wall--shade`, `.lit-head + .day`,
   `.day + .dusk`, `main:has(> :last-child.dusk) + .site-footer`, `.site-footer__still + .footer-edge`,
   `body:has(> .proto-note)`, `body:has(> .en-bar:not([hidden]))`, `body:has(.day)`,
   `html.stages .shutters + .help`. So `main`'s children are the section roots themselves.
3. **Section blocks print no wrapper.** Render callbacks return the prototype's markup and never call
   `get_block_wrapper_attributes()`. Container blocks (`hcwa/sections`, `hcwa/day`) print only what
   4.1 says.
4. **Sections pages print through `hcwa_render_sections( WP_Post $post )`** (hcwa-core,
   `includes/sections.php`), never through `the_content`, so `wptexturize`, `wpautop`, shortcodes,
   smilies and `wp_filter_content_tags` (which would add `loading`/`decoding` to the hero's LCP image and
   refuse our theme stills as "not in the library") never touch our markup. The helper:
   1. returns the page's sun head plus `get_the_password_form()` when `post_password_required()`;
   2. parses the content and renders only the first `hcwa/sections` block; anything outside it is
      ignored;
   3. before rendering, cleans the parsed tree: inside `hcwa/sections` and `hcwa/day`, every literal
      HTML string between inner blocks is dropped, and every inner block named `core/html`,
      `core/freeform`, `core/shortcode` or with no name (classic HTML) is removed, at any depth;
      so are the blocks that pull in other content when they render (`core/block`, a synced
      pattern; `core/pattern`, `core/template-part`, `core/post-content`) and embeds (`core/embed`,
      the old `core-embed/*`, `core/rss`: no live feed or outside page). A `pre_render_block` filter
      prints nothing for any of them while the page renders, so one reached at render time (the
      `core/html` inside a synced pattern, found in fix round 2) is stopped too;
   4. while rendering, a `render_block` filter runs on the output of every block whose name does not
      start with `hcwa/` (core blocks staff may add after unlocking a section, for example a paragraph
      with an inline image): `wp_filter_content_tags()` and then `hcwa_content_strip_refused()`, so the
      classic-HTML half of the photo gate (CONTRACT rule 2: `hcwa_gate_content_img_tag`, the refusal
      markers, the cache priming) runs exactly as through `the_content`. `render_block_core/image`,
      `cover` and `media-text` gates run as always.

   Staff can enter a contentOnly section in WordPress 7.1.4 (`editContentOnlySection` in
   `wp-includes/js/dist/block-editor.js`), so the patterns test and the static check are not the
   guarantee; this helper is. Gate 7.12 tests it with a negative case. Other pages (news, About, Team,
   Policies, reports) print through `the_content` as today (2.7).
5. **Ids the CSS or JS read keep their exact names:** `#hero`, `#passage`, `#centers`, `#work`,
   `#news`, `#shutters`, `#help`, `#partners`, `#contact`, `#main`, `#menu-sheet`, `#world`,
   `#f-education`, `#f-protection`, `#f-family`, `#f-policy`, `#f--active`, `#f--completed`,
   `#co-so`/`#centers` and `#du-an`/`#programs` on Our Work (by language), `#trong-nuoc`/`#in-vietnam`,
   `#tu-nuoc-ngoai`/`#from-abroad`, `#bao-cho-hoi`/`#tell-us` on Donate, the form field ids
   (`d-name`, `d-email`, `d-amount`, `d-center`, `d-consent`, `name`, `phone`, `email`, `topic`,
   `message`, `consent`), `sub-N`, `foot-col-N`, `#i18n`. Other heading ids may be generic
   (`center-visit` for the prototype's `gb-visit`).
6. **Only the corridor's cards carry `data-beat`.** The world builds one door per `[data-beat]` in the
   whole document.
7. **Draft text renders like the prototype, and never on production.** Text stored as `[DRAFT: …]` or
   holding a placeholder token (3.3) prints, off production, as its inner text on an element with
   `data-draft`, so the visible text equals the prototype's. On production `hcwa_draft_text()` returns
   nothing and the element is left out, not printed empty. A container whose `data-draft` the prototype
   prints unconditionally (`ul.foot-contact`, `p.lit-head__actions`, `dl.office__lines`,
   `dl.dusk__lines`, `p.office__address`) carries `data-draft` only when one of its lines is draft, and
   on production prints only its non-draft lines and is left out when none remain. Staging-only branches
   (since tokens, gallery hang, gift examples, Zalo chip, `gap__proto`, `form__note`, report
   placeholders, featured story placeholder, partners pending strip, plaques with unverified figures)
   print only off production. On production no element carries `data-draft`, `data-pending` or
   `data-donation-placeholder`.
8. **Body classes are an allow list.** WordPress adds `search` to the body on search results, and
   `.search` is a prototype component (`components.css:544`). The theme's `body_class` filter returns
   only the prototype page class (`page-home`, `page-work`, `page-center`, `page-program`,
   `page-involve`, `page-donate`, `page-contact`, `page-gap`, or `page-wp` for WordPress-only pages),
   `hcwa-staging` (off production), `hcwa-lang-vi|en`, and `logged-in admin-bar` when present.
9. **Escaping:** every value is escaped (`esc_html`, `esc_attr`, `esc_url`); generated SVG and the
   picture builders are the only raw output, and they come from build files, not from the database.
10. **Left-out headings leave no dangling reference.** When a section's heading is left out (draft on
    production), the section drops its `aria-labelledby`. A section left with no content at all is left
    out, except the four world sections (hero, passage, corridor, shutters), whose structure the world and
    the stages need. The hero's H1 is the exception to "left out": when its title is draft on
    production, `hcwa/hero` prints `h1.sr-only#hero-title` with the site name, so `aria-labelledby` and
    the one-H1 rule still hold (`front-page.php`'s own sr-only H1 stays only as a backstop).
11. **No link to a target that is not printed.** Jump links (`a.sun-head__go`, `ul.sun-head__jump`
    items, skip links) are printed only when their target id is printed on the same page.
12. **Contact lines come from one helper.** `hcwa_contact_line( 'phone' | 'email' | 'sponsorship' )`
    (3.2) returns the display text, the `tel:`/`mailto:` URL and whether the line is draft. A line is
    printed only when its display text is not empty; on production only when it is not draft. A `tel:`
    or `mailto:` link never prints with empty text.
13. **Attribute text is plain text.** Every text attribute passes `hcwa_attr_text()` (3.2) before the text
    helpers and escaping (4.1).

### 2.2 Document shell

```
<html lang="vi|en-GB" [data-lit]>
<head> … 1.2 … </head>
<body class="page-…">
  [wp_body_open output: nothing from our code]
  a.skip-link[href=#main]                                   (ui skip)
  div.proto-note                                             (off production only)
    > .container.proto-note__inner > p > span.proto-note__full + span.proto-note__short
      + button.proto-note__toggle[type=button][data-draft-toggle]   (ui prototypeNote, prototypeNoteShort, draftShow)
  aside.en-bar[aria-label][data-en-bar][hidden]              (VI pages with an EN target; .container.en-bar__inner[lang=en] > a[hreflang=en] + button.icon-btn.icon-btn--sm[data-dismiss-en-bar])
  [script: hcwa_theme_bar_script('en')]                      (WordPress only, 1.2)
  [div.announce-slot > .announce[data-announce] + script]    (WordPress only, when Settings switch it on; 2.8)
  div.header-slot[.header-slot--over] > header.site-header[.site-header--over][data-header]
  canvas.world-canvas#world[aria-hidden=true][role=presentation]   (front page only)
  main#main[tabindex=-1] > section roots
  footer.site-footer
  dialog.sheet#menu-sheet[aria-labelledby=menu-title]
  [wp_footer output: speculation rules, admin bar when logged in]
</body>
```

WordPress prints `lang="en-GB"` on English pages (Polylang's locale `en_GB`); the prototype prints
`lang="en"`. Kept and listed in 2.8: no prototype CSS or JS reads the exact value (no `[lang]` or
`:lang()` selector in `src/styles`, no `documentElement.lang` read in `src/ui`, `src/motion` or
`src/world`), and the smoke checks only the primary subtag.

The staging note is renamed from `.staging-note` to the prototype's `.proto-note` with its draft toggle;
its wording is ui `prototypeNote` / `prototypeNoteShort` (both reworded in ui.json since the 5 Nov
dictionary).

### 2.3 Header, navigation, language switch, menu sheet, footer

Markup is exactly `prototype/src/partials/layout.mjs`:

- `header.site-header > .container.site-header__bar`: `a.logo[data-text-safe]` (picture with
  `dist/img/hcwa-logo.webp` and `.png`, 97×128, alt = site name; `span.logo__name[aria-hidden]` with two
  spans, ui `logoLine1`/`logoLine2`); `nav.nav[aria-label][data-text-safe] > ul.nav__list >
  li.nav__item[.has-sub]` with `a.nav__link[aria-current=page|true]`, `button.nav__toggle
  [aria-expanded=false][aria-controls=sub-N][aria-label]` (ui `submenu` with `{x}`) and
  `ul.nav__sub#sub-N[hidden]`; `.site-header__tools[data-text-safe]` with
  `.lang-switch.lang-switch--header[role=group][aria-label][data-lang-switch]` (each
  `a[lang][hreflang][aria-current=true on the current]` with the visible code `VI`/`EN` and an sr-only
  endonym; each `href` equals the page's hreflang alternate for that language), `a.btn.btn--primary.
  btn--header[data-donate]`, `button.menu-btn[aria-haspopup=dialog][aria-controls=menu-sheet]
  [data-open-sheet]` with the visible word Menu.
- `aria-current="page"` on the exact page, `"true"` on the parent hub (Our Work for centers and programs,
  Get Involved for its children). The walker (`HCWA_Theme_Nav_Walker`) prints only prototype classes,
  never `menu-item-*`.
- Menu sheet: `dialog.sheet#menu-sheet` with `.sheet__head` (`p.sheet__title#menu-title`,
  `button.icon-btn[data-close-sheet]`), `nav > ul.sheet__list` (`details/summary` groups whose first
  link repeats the hub; plain items as `a.sheet__link`), `.sheet__foot` (donate button + lang switch).
- Footer (H11): `.site-footer__still[aria-hidden] > picture.site-footer__dusk` (still `dusk`, lazy),
  `.footer-edge`, `.container.site-footer__sign` (logo 73×96 alt "", lazy; `p.site-footer__name` through
  `hcwa_vi_keep`), `.site-footer__cols > .container.site-footer__grid` with `.foot-brand` (description,
  `p.foot-brand__reg` registration, each through `hcwa_draft_text()`) and three `.foot-col`
  (`h2.foot-col__title > span.foot-col__label + button.foot-col__toggle[aria-expanded=true]
  [aria-controls=foot-col-N]` with the quatrefoil `foot-col__mark`, `.foot-col__body#foot-col-N`):
  Contact (`ul.foot-contact[data-draft?]` with `li.foot-contact__address` and quatrefoil
  `foot-contact__sun`, phone, email and map link, each line under rules 7 and 12; the map link counts as
  draft while the address is draft, because its URL holds the address; a Contact column with no line
  left is left out on production), Explore and Trust (the `footer-explore` / `footer-trust` menus); then
  `.site-footer__bar > .container.site-footer__bottom` with `© {year} HCWA`, `p.foot-social` (Facebook
  link, never an embed), the lang switch and `button.pause-toggle.pause-toggle--dusk[data-motion-toggle]
  [data-paused=false]`.

### 2.4 Home (front page, body class `page-home`, `data-lit`, world canvas, header `--over`)

Section roots in this order, each printed by one block (4.1):

| # | Root | Must carry | Data |
|---|---|---|---|
| H2 | `section.hero#hero[data-world=room][aria-labelledby=hero-title]` | `.hero__media.lit[aria-hidden]` with three pictures from `hcwa_art()->poster('hero', …)` (`lit__base` eager with `fetchpriority=high`, `lit__light`, `lit__screen` lazy); `.hero__stage[data-stage][aria-hidden]`; `.container.hero__inner` with `h1.hero__title#hero-title[data-text-safe] > span[data-draft?]` (vi-keep; on production with a draft title: `h1.sr-only#hero-title`, rule 10), `p.hero__intro[data-text-safe] > span` (keep-hyphens; left out on production when draft), `.hero__actions[data-text-safe]` (primary `exploreWork` → Our Work, secondary `getInvolved`), `button.pause-toggle.hero__pause[data-paused=false][data-motion-toggle][data-text-safe=display]` | block attrs `title`, `intro` |
| H3 | `section.passage#passage[data-world=passage][data-stage][aria-labelledby=what-title]` | `.passage__frame > .passage__halo[aria-hidden] + .container.passage__text` (`h2.passage__title#what-title`, `p.statement[data-draft?][style=--n:{count}]` containing `span.sr-only` sentence + `span.statement__words[aria-hidden]` of `span.w[style=--w:i]` words, each with its trailing space inside); `.passage__exit[aria-hidden]` holding `picture.passage__still` (still `passage-corridor`, sizes `100vw`), or nothing on production before approval (3.4) | attrs `title`, `statement` |
| H4 | `section.bays-section#centers[data-world=corridor][data-stage][data-traces?][style=--n:{N}][aria-labelledby=centers-title]` | `a.bays__skip[href=#work]`; `.bays__track[data-stage-track] > .bays__frame > .bays__glass[aria-hidden] + .container.bays__panel` with `.bays__head` (`h2#centers-title` ui `centers`, `p.bays__intro`), `nav.bays__rail[aria-label] > ol > li > a[href=#center-{unit}][data-to=i][style=--unit:…]` (transom `screen_art(emblem,'rail__transom')` + `span` short name), `ol.bays > li.bay#center-{unit}[data-beat][data-unit][data-door][data-emblem][style=--unit:…;--spill:…;--i:i]` (`.bay__view[aria-hidden]` still `bay-{unit}` sizes `(min-width: 1024px) 76vw, 100vw`; `.bay__text` with `h3.bay__name`, staging-only `p.bay__since[data-draft]`, `p.bay__serves`, `p.bay__where` not for a protected center, `p.bay__note` privacy note for a protected center, `p.bay__links` text links: `visitCenter`, or `aboutShelter` + `bakery` for a protected center); `p.bays__all` text link to Our Work `#co-so`/`#centers` (ui `allWork`) | centers CPT in menu order; attr `intro`; `data-traces` only when `hcwa_traces_on()`; Little Rose rules 3.4 (`N` counts the bays printed) |
| H5 | `section.wall.wall--sun.wall--edge.work#work[aria-labelledby=focus-title]` | `.container > .floor` (`h2.wall__title.floor__title#focus-title[data-reveal][data-draft?]`, `ul.floor__areas[data-reveal=stagger] > li.gach.gach--{area}` with `.gach__floor[aria-hidden]` = `gach_art(area,'art')` + `span.gach__light` > `gach_art(area,'lit')`, `.gach__body` with `h3.gach__name > a[href={ourWork}?area={area}#f-{area}] > span + &#8288; + svg.icon.gach__go`, `p.gach__text`); the sprite `svg.gach-sprite` right after `.floor` (never inside a hidden subtree); `.plaques[role=group][aria-label]` only with ≥ 2 visible org figures (`ul.plaques__row > li.plaque > p.plaque__value.num`, `p.plaque__label`, `span.chip-pending[data-pending]` off production; `a.plaques__link.text-link`); nested `section.programs[aria-labelledby=programs-title] > .container` with `.programs__head[data-reveal]` (`span.lam`, `h2.wall__title#programs-title`), `ul.program-list.program-list--home[data-reveal=stagger]` (active first, max 4, program rows 2.6, then `li.program-more > a.program-more__link`) | focus-area terms (fixed order), impact CPT (org scope), programs CPT; attr `focusTitle` |
| H6 | `section.wall.wall--shade.news[.news--solo]#news[aria-labelledby=latest-title]` | `.container.news__grid > .news__list[data-reveal]` (`h2.wall__title#latest-title`, `ol.dated-list > li.dated` 2.6, `a.news__all.text-link`); staging-only `article.feature[data-story-placeholder][data-draft]` (`.feature__wall` with still `story` or `span.lit-wall`, `.feature__text` with cat, `h3.feature__title > a`, excerpt, `p.feature__note` ui `namesChanged`); with no published post in the language the section is left out | `hcwa/news-latest`: 3 latest posts in the language; featured = rule in 3.4 |
| H7 | `section.shutters#shutters[data-world=shutters][data-stage]` | `a.shutters__skip[href=#help]`; `.shutters__track[data-stage-track] > .shutters__frame[aria-hidden]` (`template.shutters__beats` holding `.shutters__view--closed` and `--louvers` with their stills, `.shutters__view.shutters__view--open` with still `shutters-open` sizes 100vw, `.shutters__flood`) + `.shutters__sill[aria-hidden]` | none (ui `skipShutters`) |
| H8 | `section.wall.wall--light.help#help[aria-labelledby=help-title]` | `span.sun-layer.sun-layer--help[aria-hidden]`; `.container > h2.help__title#help-title[data-reveal][data-reveal-on=entry]`; `ul.louvers[data-reveal=stagger] > li.louver[.louver--donate][data-way]` (`span.louver__face`, `h3.louver__title` with `span.louver__label` (linked except donate), `.louver__body` with `p.louver__text`, the donate `a.btn.btn--primary.louver__btn`, in-kind `a.text-link.louver__extra` → bakery; `svg.icon.louver__go` except donate) | attrs `title`, `ways[]` (fixed ids donate, volunteer, partner, in-kind) |
| H9 | `section.wall.wall--light.partners#partners[data-partners={n}][data-partners-pending?]` | `.container.partners__inner[data-reveal]` (`h2.partners__title#partners-title`, `ul.partner-wall > li > img[loading=lazy][alt]` with ≥ 4 allowed logos, else staging-only `p.partners__pending[data-draft]`, `p.partners__links` two text links); production omits the whole section below 4 | `hcwa/partner-wall`: partner CPT with `hcwa_show_on_home` and rule 5 |
| H10 | `section.dusk#contact[aria-labelledby=contact-title]` | `.dusk__still[aria-hidden] > picture.dusk__img` (still `dusk`); `.container.dusk__inner` with `h2.dusk__title#contact-title`, `dl.dusk__lines[data-draft?]` (phone `tel:`, email `mailto:`, rules 7 and 12), `a.btn.btn--on-dusk.dusk__btn` → Contact | `hcwa/dusk-band` with `lines: [phone,email]`, `actions: [contact]`, anchor `contact` |

### 2.5 Inner pages

Shell for lit pages: `main > header.lit-head + div.day.day--{variant} + [section.dusk]`. The lit header
(`partials/lit-header.mjs`): `header.lit-head[.lit-head--display|--program][.lit-head--unit][style=
--unit:…]` > `.lit-head__media[data-still={name}]` (`hcwa_art()->lit_still()`, or an empty
`div.lit.lit--still[aria-hidden]` when the still is not ready) + `.container.lit-head__text` with
`nav.breadcrumb[aria-label] > ol > li…` (last item `span[aria-current=page]`), optional
`.lit-head__meta`, `h1.lit-head__title` (or `span.lit-head__main` + sr-only `: ` +
`span.lit-head__sub`), optional `p.lit-head__intro[data-draft?]`, optional extra.

| Page | Body class, `data-lit` | Header | `.day` | Ends on | Source |
|---|---|---|---|---|---|
| P1 Our Work (`/hoat-dong/`, `/en/our-work/`) | `page-work`, lit | still `header-our-work`, `--display`, intro | `div.day.day--hub.hub[data-hub]` > `nav.filters.container` (`span.filters__anchor#f-{area}` ×4, the sprite, `p.filters__label#filters-label`, `ul.filters__list` with `a.chip.chip--all[href=./][data-area=""]` and per area `a.chip[href=?area={a}#f-{a}][data-area]` with `gach_strip([a],'gach-strip chip__tile')`, `p.sr-only[data-filter-status][aria-live=polite]` with `data-centers-one/many`, `data-programs-one/many`, `data-template`); `section.places[data-filter-group]` (`h2.places__title#co-so\|#centers[data-reveal=rise] > span`, `.doorwall[.doorwall--pano][style=--ratio]` with `.doorwall__pano` still `hub-doors-wide` and `ul.doors[data-reveal=stagger] > li.door[data-areas][data-unit][data-door][style=--unit]` (`.door__still` + `span.door__spill`, `.door__plate` with `h3.door__name > a`, staging `p.door__since`, `p.door__serves`, `p.door__where` with pin, `p.door__note` (protected), `p.door__go[aria-hidden]`, `p.door__extra` bakery (protected)), `.container.places__foot` with `p.places__lead` and `p.filter-empty[data-filter-empty][data-show-for][hidden]`); `section.times.container[data-filter-group]` (`.times__head` with `span.filters__anchor#f--active`, `#f--completed`, `span.lam.lam--head`, `h2#du-an\|#programs`, `p.times__lead`, `nav.seg` with `a.seg__opt[data-status]` and `span.seg__light--{s}`; `.times__rows` with `ul.program-list.program-list--hub` and its empty note). `data-show-for` and the filter counts are computed from the doors and rows actually printed (3.4). | dusk `#hub-cta`: `div.dusk__actions` donate + volunteer (`btn--on-dusk`) | `hcwa/work-hub` inside `hcwa/day {variant: hub}` |
| P2 Center (`single-hcwa_center.php`) | `page-center`, lit | still from `hcwa_header_still` (Green Bamboo: `header-green-bamboo`), `--unit`, meta `img.lit-head__logo` 56×56 + `p.lit-head__facts` (staging since token; location with pin, not for protected), EN pages add `p.lit-head__vi[lang=vi]` | `div.day.day--center[style=--unit]` > `section.plate.container` (glance `dl.plate__facts`, `.plate__serves`, `.plate__does` with `block_icon(emblem,'plate__mark')` and `ul.does`), `section.story.container` (`span.sun-layer--story`, `h2.story__title`, `.story__text` with `p.story__first`, paragraphs, `details.more.story__more > .more__body` with the history paragraphs; nested `section.counts.counts--nested`), then either the approved gallery (below) or, off production when there is none, `section.hang`; `section.here` (`ul.bands > li.band[data-status]` with `span.band__bar[data-span][style=--at]`), `section.give` (`ul.give__needs` with quatrefoil `give__mark`, `.give__act` donate button `?center={unit}` + volunteer link), `section.related` (`ol.dated-list.related__list`, left out when empty) | dusk `#center-visit`: `dl.dusk__lines` (contact person, phone; only with `hcwa_contact_approved`, rules 7 and 12), `p.dusk__note`, contact button | PHP template + builders |
| P3 Program (`single-hcwa_program.php`) | `page-program`, lit | still from `hcwa_header_still` (`header-thrive-together`), `--program`, title split on `: ` (full name), meta status pill, `span.lit-head__years[data-draft?]`, `span.lit-head__funder` | `div.day.day--program` > `section.brief` (`span.lam.lam--brief`, sr-only h2, `p.brief__lead`, `p.brief__focus`), `section.span` (`.timeband.timeband--{status}[style=--at][data-draft?]`), `section.aims` (two `.aims__col` with `ol.slats[.slats--lit] > li.slat`), counts section, `section.where` (`ul.places-row > li.place[data-unit][data-door][style=--unit;--emblem:url({asset_url})]` with `span.place__door` > transom, way, leaf; `span.place__name` linked only for a built, unprotected center; `.where__notes`), `section.docs` (staging placeholders or real `li.doc` rows; production with none: `p.docs__empty` + annual reports link), related news. The program's post content is not printed (every field is meta; the edit screen says so). | dusk `#program-cta`: donate + partner label | PHP template + builders |
| P4 Get Involved (`/chung-tay/`) | `page-involve`, lit | still `header-get-involved` (motion louver), `--display`, intro | `div.day.day--involve` > `ul.louvers.louvers--page` (level 2), `section.faq.container` (`h2.faq__title > span`, `.faq__list[data-reveal=stagger] > details.ask > summary (span.ask__q, span.ask__mark) + .ask__a > p[data-draft?]`) | dusk `#gi-contact`: general email line + contact button | `hcwa/louvers`, `hcwa/faq` in `hcwa/day {variant: involve}` |
| P5 Donate (`/chung-tay/ung-ho/`) | `page-donate`, not lit | `header.sun-head` (`span.sun-layer--donate`, `.container.sun-head__inner` with breadcrumb, `h1.sun-head__title`, `.sun-head__side`: intro, `a.btn.sun-head__go` → first panel, `ul.sun-head__jump`; rule 11 drops links whose panel is not printed) | `div.day.day--donate` > staging-only `section.gifts`; `.give-grid.container` (`section.slip[data-tabs]` with `h2.slip__head`, `div.tabs[role=tablist][hidden]`, `.slip__panels > section.slip__panel#{id}[data-tab-panel]`, `dl.slip__rows`, copy buttons, `.slip__qr`, PayPal row, `p.slip__note#pay-note`, `.slip__center`; `section.tell#{tell}` with the form, or on production 3.5); `nav.trust` | dusk `#donate-questions`: sponsorship email line (rule 12) + contact button | `hcwa/sun-head`; `hcwa/gifts`, `hcwa/donation-methods` in `hcwa/day {variant: donate}` |
| P6 Contact (`/lien-he/`) | `page-contact`, lit | still `header-contact`, `--display`, intro, extra `p.lit-head__actions[data-draft?]` (tel and mailto `btn--secondary`, one per line that rule 12 prints; left out when none) | `div.day.day--contact` > `.reach.container` (`section.reach__write` with the form, or on production 3.5; `section.office` with `p.office__address[data-draft?]`, `dl.office__lines[data-draft?]`, `p.office__map` (draft while the address is draft), staging-only `p.office__chat`; left out when empty), `section.units.container` (`ul.units__list > li.unit[data-unit][data-door][style=--unit]` with `span.unit__door > span.unit__leaf`, `.unit__text`) | none: the footer shows its own dusk still | `hcwa/contact-reach`, `hcwa/contact-units` in `hcwa/day {variant: contact}` |
| P7 404 (`404.php`) | `page-gap`, not lit | none | `div.day.day--gap > section.gap.container` (`.gap__still[aria-hidden]` still `not-found` eager, sizes `(min-width: 1024px) 46vw, 100vw`; `.gap__text` with `h1.gap__title#nf-title`, `p.gap__lead`, staging `p.gap__proto[data-draft]`, `form.search[action={home}][method=get][role=search]` with `label[for=s]` and `.search__row > input#s[name=s][type=search] + button`, `nav.gap__nav` with `p.gap__nav-title#nf-links` and three links) | none | PHP |

**P2 story and history (center post content).** The prototype prints the story and history as plain
paragraphs; `the_content` would curl apostrophes, add `<p>` and `<br>` of its own and print draft
brackets raw. So `hcwa_center_body( WP_Post $center ): array` (3.2) splits the raw `post_content` at
`<!--more-->` (story before, history after), then into paragraphs (the inner text of `core/paragraph`
blocks, or blank-line separated text when there are no blocks), runs each through `hcwa_attr_text()` and
`hcwa_draft_text()`, and the template prints each with `esc_html` into `p.story__first`, the following
`p`, and the paragraphs of `details.more > .more__body`. No `the_content` filter runs. Images and other
blocks in a center's content are not printed (center photos come only through the gallery meta, rule 2).
A unit case in `theme-markup-test.php` covers an apostrophe, a draft paragraph, `&`, and the split.

**P2 gallery once photos are approved.** For a center that is not protected, photos in
`hcwa_gallery_ids` that pass `hcwa_image()` in the center's context print as
`section.photos.container[aria-labelledby=center-photos]` > `h2#center-photos` (ui `photos`) +
`ul.gallery[data-gallery] > li > button.gallery__item[type=button][aria-label={openLarger}] > img`, and
one `dialog.lightbox[data-lightbox]` before `</main>` with `[data-lightbox-stage]`,
`p.lightbox__count[data-lightbox-count]`, and `button.icon-btn.lightbox__prev[data-lightbox-prev]`,
`.lightbox__next[data-lightbox-next]`, `.lightbox__close[data-lightbox-close]` (ui `prevPhoto`,
`nextPhoto`, `close`): the markup the bundle's `ui/lightbox.js` and the prototype's `.gallery` and
`.lightbox` CSS expect. Off production with no approved photo, `section.hang` (the `gallery-wall` still)
prints instead. A protected center never prints a gallery or the hang. No seeded photo is approved, so
the parity routes never show this state; tastegate judges it on a scratch instance with a test image
that is not a child.

The components page (`/components/`) is prototype-only and is not ported.

### 2.6 Shared components (one builder each, used by blocks and templates)

| Component | Markup (from `partials/components.mjs`) | Builder in hcwa-core |
|---|---|---|
| Program row | `li.program-row[data-areas][data-status]` > `.program-row__head` (`span.program-row__floor[aria-hidden]` with the sprite once and `gach_strip(areas,'gach-strip program-row__tiles')`; `p.program-row__meta` status pill + `span.program-row__years[data-draft?]`; `h3.program-row__name > a`), `.program-row__body` (`p.program-row__summary`, `dl.program-row__facts` with fundedBy names and runsAt `ul.door-chips > li.door-chip[data-unit][style=--unit] > span.door-chip__door + short name`), `svg.icon.program-row__arrow`. Every chip carries `data-unit` (2.8). A protected center's chip follows 3.4. | `hcwa_program_row_html()` |
| Dated post | `li.dated` > `time.dated__date[datetime]` (`span.dated__dm`, sr-only separator, `span.dated__y`), `.dated__body` (`p.dated__cat`, `h3.dated__title > a`) | `hcwa_dated_post_html()` (no external label or icon: posts are local) |
| Dusk band | `section.dusk[aria-labelledby][#id?]` > `.dusk__still` + `.container.dusk__inner` (`h2.dusk__title[data-draft?]`, body). A band whose lines are all left out keeps its title and its button; a band with neither gets the Contact button. | `hcwa_dusk_band_html()` |
| Lit header, sun head | 2.5 | `hcwa_lit_header_html()`, `hcwa_sun_head_html()` |
| Counts | `section.counts[.counts--nested\|.container]` > `h2\|h3.counts__title` + `ul.counts__list > li.count` (`p.count__value.num`, `p.count__label`, pending chip) | `hcwa_counts_html()`, also used by `hcwa/impact-strip` |
| Louvers, door, place, unit, field, choice field, consent, form sent, copy button, pause toggle, button, text link, status pill, breadcrumb, quatrefoil, icon | as in the prototype; form controls carry `name="c-{id}"` on Contact and `name="{id}"` (already `d-…`) on Donate, and forms carry `method="post"` (2.8) | one PHP function each in `includes/blocks.php`; the theme reaches them through `hcwa_theme_*` bridge functions |

### 2.7 WordPress-only pages

The news hub (`home.php`, `archive.php`, categories), single posts, the report archive and report
pages, search results, attachment pages and every other page that is not a sections page (About, Our
Story, Team, Partners, Policies, Safeguarding, Privacy, Impact, Volunteer, Partner, In-kind, Bakery) have
no prototype page. They reuse prototype components only: `header.sun-head` with breadcrumb and H1,
`div.day.day--donate` as the day gradient (the sun head's own), `ol.dated-list` for post lists, `li.doc`
rows for reports, `nav.breadcrumb`, the `.search` form, and the footer's own dusk. Body class `page-wp`.
Bodies print through `the_content` (so the whole photo gate and the password check apply) inside
`div.entry-content.container`; their typography, the language notice, the announcement slot,
pagination, share links and the empty state live in `assets/css/wp.css`, written with prototype tokens
only (no hex values, no `text-transform: uppercase`, running text line-height ≥ 1.6). These pages are
judged by tastegate, the smoke test and the guards, not by parity.

### 2.8 Differences from the prototype that are allowed

| Where | WordPress | Why |
|---|---|---|
| `<head>` | WordPress adds its own tags (speculation rules in the footer, Polylang hreflang with absolute URLs, Yoast when installed); global styles and block CSS only on `page-wp` pages | Only the items in 1.2 are compared |
| `<html lang>` | `en-GB` on English pages | Polylang locale; nothing reads the exact value (2.2) |
| News list (home, related) | Local permalinks; no `(externalPost)` sr-only text and no `svg.dated__ext` | Posts are on this site |
| Heading ids on center and program pages | `center-*`, `program-*` instead of `gb-*`, `tt-*` | One template serves every center and program; no CSS or JS reads them |
| Door chips | `data-unit` on every `li.door-chip` | Lets the smoke and the production assertions see a Little Rose chip; no CSS or JS reads `data-unit` (the world reads only `[data-beat]`) |
| Forms | Contact controls named `c-{id}` (ids unchanged); both forms `method="post"` | WordPress reads public query vars (`name` among them) from GET and POST, so a no-JS submit of `name=…` asked WordPress for a post by that name (a 404 with personal data in the URL and the logs). `forms.js` reads fields through `[data-field]` and ids, never names. |
| Bars | `hcwa_theme_bar_script()` inline after the EN bar and the announcement slot; the announcement slot itself | WordPress-only announcement; both bars decided before first paint (CONTRACT round 1) |
| Single posts | `wp-share.js` for `li[data-share-native]` | Native share is WordPress-only |
| Center gallery | `section.photos` with `.gallery` and `dialog.lightbox` once photos are approved | The prototype has no approved photo; markup follows its `lightbox.js` and CSS |
| Announcement bar | `div.announce-slot` between the EN bar and the header slot when switched on | WordPress-only feature, off by default and off for parity runs |
| Logged-in views | Admin bar after the menu sheet, `html` margin | Parity runs logged out |
| Production | The variants in 2.1 rules 7, 10 to 12, 3.4 and 3.5 | The prototype has no production rendering for them (its PROD build fails instead) |

The prototype behaviours that lived in the old `site.js` are all in the bundle now (header, submenus,
sheet, footer folds, EN bar, reveals, filters, lightbox, copy, `?center` preselect) except native share
and the remembered announcement dismissal, which the two WordPress-only scripts above keep.

## 3. Data mapping

### 3.1 Where each prototype content source goes

| Prototype source | WordPress home | Notes |
|---|---|---|
| `ui.json` (146 keys) + `ui-world.json` | Polylang strings through `hcwa-core/includes/strings-data.php` | Regenerate with `scripts/build-wp-strings.py`, which must also merge `ui-world.json` and fail on a duplicate key, as `content.mjs` does. Today 43 keys are missing and `prototypeNote`, `prototypeNoteShort` changed wording. Theme keys in `inc/strings.php` that ui.json now owns (for example `runsAt`, `fundedBy`) are deleted from the theme so one wording exists. |
| `site.orgName`, `site.shortName` | Site title (Polylang string) and new Settings → HCWA `short_name_vi`/`_en` | Title format 1.2 #4 |
| `site.logoLines` | ui `logoLine1`, `logoLine2` (theme keys today) | |
| `site.description`, `registration`, `address`, `facebook`, `mapUrl` | Settings → HCWA (tagline, `registration_*`, `address_*`, `facebook`, `map_url`) | Exists; `hcwa_setting_l()` drops draft text on production |
| `site.phone`, `site.email.general`, `site.email.sponsorship` | Settings → HCWA `phone`, `phone_tel`, `emails` (lines 1 and 2) plus the new `contact_confirmed` switch (3.2) | `emails` are sanitized as addresses, so they cannot carry `[DRAFT: …]`; the switch carries site.json's `draft: true` for phone and email alike |
| `site.hero.title/intro`, `whatWeDo.title/summary`, `centersIntro`, `helpTitle`, `partnersTitle`, `contactBand.title` | Attributes of the front page's blocks, per language | Seeded from the VI/EN home pattern |
| `site.partnerLogos` | `hcwa_partner` posts (logo = featured image) | Rule 5 plus the minimum of 4 (3.4) |
| `work.focusTitle`, `hubIntro`, `hub.*` | Block attributes on Home (`hcwa/work-sheet`) and Our Work (`hcwa/lit-header`, `hcwa/work-hub`, `hcwa/dusk-band`) | |
| `work.littleRose.privacyNote` | New Settings → HCWA `little_rose_note_vi`/`_en` | Shown wherever Little Rose is (corridor, hub, program, contact) |
| `work.focusAreas[]` | `hcwa_focus_area` terms: name, description = text | Order and slugs fixed by `prototype-data.php` (education, protection, family, policy); a term outside the four renders without a tile and is reported by `wp hcwa coverage` |
| `work.centers[]` | `hcwa_center` posts | Field table 3.2 |
| `work.centerPages[...]` | Center meta + post content (story before `<!--more-->`, history after) | Printed by `hcwa_center_body()` (2.5) |
| `work.programs[]`, `programPages[...]` | `hcwa_program` posts + meta; `reports[]` become `hcwa_report` posts linked by `hcwa_program_id` | 3.2 |
| `impact.json figures[]` | `hcwa_impact` posts (exists) | Rule 1 |
| `news.json posts[]` | Posts (exists) | `featuredStory` is the seeded story post |
| `involve.ways[]`, `faq[]`, `faqAnswer`, `faqTitle`, `intro` | Block attributes (`hcwa/help`, `hcwa/louvers`, `hcwa/faq`, `hcwa/lit-header`) | |
| `involve.giftExamples[]` | `hcwa/gifts` attributes | Staging only; contains `[amount]` tokens |
| `involve.bankFields[]`, `abroadFields[]` | Labels and hints: Polylang `pay_*` strings; values: Settings → HCWA `donation_*` only after finance confirms (rule 3) | Nothing from `content/verified/` exists yet, so no value |
| `involve.donatePage.*` | `hcwa/sun-head`, `hcwa/gifts`, `hcwa/donation-methods`, `hcwa/dusk-band` attributes | |
| `involve.contactPage.*` | `hcwa/lit-header` (intro, callUs, writeUs), `hcwa/contact-reach`, `hcwa/contact-units` attributes | |
| `world.json traces.enabled` | Settings → HCWA `world_traces_enabled` | Staging-only in every case (3.4) |
| `world.json littleRoseDoor` | New protected Settings → HCWA "Safeguarding" pair (3.2) | |
| `kit.json`, components page | Not ported | |

Keys present in the JSON but never read by the prototype stay unmapped: `site.hero.eyebrow`,
`site.hero.image`, `focusAreas[].icon`, `centers[].image`, `ways[].art`, `ways[].page`,
`featuredStory.image`, `featuredStory.date`.

### 3.2 Content model changes in hcwa-core

| Type | Key | Kind | Replaces / notes |
|---|---|---|---|
| center | `hcwa_accent` | sync enum `green-bamboo \| little-rose \| cau-han` | `blue` is dropped (no token in tokens.css); a stored `blue` renders `--unit:var(--color-blue-700)` and is listed by `wp hcwa coverage`. The accent is the center's **unit key**: `data-unit`, `#center-{unit}`, `?center={unit}`, still names `bay-{unit}`, `door-{unit}`, `hub-door-{unit}`, emblem from `EMBLEMS` in `prototype-data.php` |
| center | `hcwa_short_name` | per language | rail labels, door chips, breadcrumb |
| center | `hcwa_header_still` | sync, enum of `header-*` still names or '' | lit header still; '' prints the placeholder |
| center | `hcwa_since_verified` | sync bool, approver capability | `hcwa_since_year` prints on production only with it; off production a missing or unverified year prints `{since} [năm]`/`[year]` with `data-draft` |
| center | `hcwa_story_title`, `hcwa_figures_title`, `hcwa_help_title`, `hcwa_needs` (lines), `hcwa_donate_label`, `hcwa_visit_title`, `hcwa_contact_note`, `hcwa_logo_alt` | per language | center page sections; `hcwa_what_we_do` = activities, `hcwa_who_we_serve` = serves, `hcwa_location`, `hcwa_contact_name` = contact person (exist) |
| program | `hcwa_center_ids` | sync int[] (VI originals) | replaces `hcwa_center_id`, which stays readable as a one-item fallback until the seed and the coverage command have migrated it |
| program | `hcwa_full_name` | per language | lit header title (split on `: `); post title = short name |
| program | `hcwa_years_label` | per language, draft-guarded | used when `hcwa_start_year`/`hcwa_end_year` are not both set (seeded with the `[năm bắt đầu]–[năm kết thúc]` token) |
| program | `hcwa_focus`, `hcwa_runs_at_note`, `hcwa_cta_title`, `hcwa_partner_label` | per language | program page; `hcwa_goals`, `hcwa_activities`, `hcwa_summary` exist |
| program | `hcwa_header_still` | sync | as for centers |
| report | `hcwa_program_id` | sync int | the program page's documents |
| setting | `short_name_vi`, `short_name_en`, `little_rose_note_vi`, `little_rose_note_en` | text | |
| setting | `contact_confirmed` | bool | "HCWA has confirmed the phone number and email addresses"; while off, `hcwa_contact_line()` reports phone and email as draft. Saved by `manage_options`; the seed leaves it off (site.json marks both draft) |
| setting | `world_traces_enabled` | bool | seeded true (world.json); never takes effect on production |
| setting | `world_lr_door_approved_by`, `world_lr_door_approved_date` | text, Y-m-d | saved only by users with the new capability `hcwa_approve_safeguarding` (given to Administrators on activation until Q3 is answered); the sanitizer clears a half-filled pair; never written by the seed, a script or a WP-CLI helper |

There are no traces approval fields: traces never render on production (port hard limit). If Maxwell
prefers 05-DESIGN 6.8's approval route, the lead records that decision in CONTRACT.md before the
templates stream starts, and adds the pair then.

New core functions (templates stream; signatures go into CONTRACT.md):

```php
hcwa_attr_text( string $v ): string                  // <br> → space, wp_strip_all_tags, html_entity_decode (ENT_QUOTES|ENT_HTML5, UTF-8), NFC, whitespace collapsed
hcwa_draft_text( string $s ): array                  // ['text' => inner text, 'draft' => bool]; production: ['text' => '', 'draft' => true] for any draft marker (3.3)
hcwa_split_words( string $text ): array              // ['count' => int, 'html' => string]
hcwa_vi_keep( string $text ): string                 // returns plain text with U+00A0 joins (escape after)
hcwa_keep_hyphens( string $text ): string            // escaped HTML with span.nowrap
hcwa_contact_line( string $which ): array            // which: phone|email|sponsorship → ['text', 'href', 'draft']; draft while contact_confirmed is off or the text has a marker
hcwa_center_unit( int $center_id ): string           // accent / unit key, '' if unknown
hcwa_center_is_protected( int $center_id ): bool     // protective flag on any translation OR unit little-rose (fails closed)
hcwa_center_body( WP_Post $center ): array           // ['story' => string[], 'history' => string[], 'draft' => bool[]] (2.5)
hcwa_little_rose_door_allowed(): bool                // true off production; production only with both approval fields
hcwa_traces_on(): bool                               // world_traces_enabled && ! hcwa_is_production()
hcwa_program_center_ids( int $program_id ): array    // hcwa_center_ids, else [hcwa_center_id], resolved to the current language
hcwa_forms_enabled(): bool                           // false until task 2.7 lands (filterable); see 3.5
hcwa_render_sections( WP_Post $post ): string        // 2.1 rule 4
hcwa_art(): HCWA_Art                                 // 1.4
```

### 3.3 Draft guard

- **One definition.** The prototype's `BLOCKERS` (`render.mjs`) are exported, not re-typed (1.3):
  `prototype-data.php` for PHP and `generated/guards.json` for Python. WordPress applies them with one
  documented exception: a bracket token made only of digits (`[1]`, `[12]`) is a footnote and passes,
  because posts migrated from the live site may carry numeric footnotes and CONTRACT rule 4 already lets
  them pass. `content/draft/*.json` holds no digit-only token today.
- **Storage:** draft copy is stored with its `[DRAFT: …]` wrapper or its placeholder token (block
  attributes, meta, settings), as the seed does today.
- **Rendering:** `hcwa_draft_text()` treats as draft any text with `[DRAFT` (any case) or any bracket
  token the exported bracket rule matches (`[N]`, `[năm]`, `[KB]`, `[Story title]`, `[Second need, …]`,
  bracketed HCWA notes), minus the footnote exception. A unit test runs it over every `[…]` string in
  `content/draft/*.json` (both languages) and expects each to count as draft.
- **Publishing:** `hcwa_contains_draft()` (the publish guard and `wp hcwa draft-scan`) uses the same rule
  on visible text: it removes registered shortcodes and block comment delimiters first, so
  `[gallery ids="1,2"]` and block attributes that are not text still pass, then tests the rest. The
  production publish guard already scans raw `post_content`, so a draft block attribute keeps the page
  from publishing; a stub test proves it for an attribute serialized by `serialize_block_attributes()`.
- **draft-scan output.** `wp hcwa draft-scan` gains a `printed` column: `raw` when the listed field is
  printed without `hcwa_draft_text()` (a post title, or the body of a post, report or non-sections page
  printed through `the_content`), `guarded` when it is printed only through `hcwa_draft_text()` (block
  attributes of sections pages, center and program meta, center body paragraphs). It also gains
  `--format=ids` and `--printed=raw|guarded` filters (used by 5.9).
- **HTML scan:** `wp-smoke.py --production` runs the exported `BLOCKERS` (with the footnote exception)
  on each page's HTML after removing every `<script>…</script>` and `<style>…</style>`. The prototype's
  bracket rule matches its own inlined head script (`w[s]`, `/^[012]$/`, `["js"]`) on every page;
  WordPress must not copy that bug.

### 3.4 Safeguarding switches and how they render

**Little Rose door.** The prototype's guard (`LITTLE_ROSE_DOOR` in `render.mjs`) refuses
`data-unit="little-rose"` together with `data-door`, any `door-little-rose-*` or `bay-little-rose-*`
still, and `hub-doors-wide`. That guard misses stills that show the door under another name
(`passage-corridor` is "the home corridor's own three doors … Little Rose shut", and the bay stills of
the other two centers are rendered from the same three-door corridor). WordPress cannot fail a build, so
it renders a decided safe variant until `hcwa_little_rose_door_allowed()` is true. "Protected" below
means `hcwa_center_is_protected()`; "listed still" means `showsLittleRoseDoor: true` in
`generated/stills.json` (1.3).

| Place | Off production, or production after approval (= prototype) | Production before approval |
|---|---|---|
| Home passage H3 | `.passage__exit` with still `passage-corridor` | `.passage__exit[aria-hidden]` empty (the prototype's own output when the still is missing) |
| Home corridor H4 | protected bay: `data-door="closed"`, `--spill:var(--color-sun-100)`, still `bay-{unit}`, no `bay__where`, links `aboutShelter` + `bakery`, rail entry; other bays with `bay-{unit}` | no bay and no rail entry for a protected center; `--n` and the beats count N−1; the live world builds N−1 doors; the other bays' `.bay__view` prints the single-door card still `door-{unit}` (not listed) instead of the listed `bay-{unit}`, so Tier 0, reduced-motion, no-JS and no-WebGL visitors see one door per card, matching the N−1 live corridor; `.bays__head` gains, after `p.bays__intro`, a `p.bay__note` with the protected center's name, the privacy note and the `aboutShelter` and `bakery` text links (words only) |
| Our Work P1 | panorama `hub-doors-wide`; `li.door[data-door=closed]` with `hub-door-{unit}` | no panorama (the prototype's non-panorama layout with `door-{unit}` card stills for the others, never `hub-door-*`); no `li.door` for a protected center; `.places__foot` adds a `p.places__lead` with its name and the privacy note and a `p.door__extra` with `aboutShelter` and `bakery` links; `data-show-for` and the `[data-filter-status]` counts are computed from the doors printed |
| Program rows (H5, P1, `hcwa/program-list`) | protected center chip: `li.door-chip[data-unit][style=--unit:…]` with `span.door-chip__door` | protected center chip: `li.door-chip[data-unit]` with the short name only: no `span.door-chip__door` and no `--unit` in its style |
| Program P3 `where` | `li.place[data-door=closed]` with door spans, name only | `li.place[data-unit]` without `data-door` and without `span.place__door`; name only |
| Contact P6 units | `li.unit[data-door=closed]` with `span.unit__door` | `li.unit[data-unit]` without `data-door` and without `span.unit__door` |
| Protected center page | no still in its lit header (none exists), no location, no gallery, `supportTitle` instead of `visitTitle` (exists) | same |
| Any still | as asked | `HCWA_Art_Guard` returns the missing-still output for every listed still (1.4) |

The production-only elements above (the `p.bay__note` in `.bays__head`, the Little Rose lines in
`.places__foot`, chips and units without a door) reuse prototype classes only, because the parity routes
never load `wp.css` and the static check refuses a class without a rule. The production assertions and
tastegate on `scratch-prod` judge them (gate 7.7).

In every mode: a protected center never prints a location, never says `visitCenter`, never carries
`--spill` in its unit colour, never carries `data-door="open"`, and no light near it uses the pink or red
unit colour. The world's own rules (`journey.js readBeats`, `door.js`) stay as a further lock.

If HCWA approves the shut door (Q1), the safeguarding lead fills the approval pair on production and
every row above switches to the left column at once. There is no state where some places show the door
and others do not.

**Traces.** `data-traces` is printed on `#centers` only when `hcwa_traces_on()`: off production when
enabled (as the prototype's staging). Never on production.

**Impact figures.** Home plaques follow the prototype: the row shows only with ≥ 2 visible organization
figures, in every environment; production shows verified figures only. Center and program counts follow
CONTRACT rule 1 (production needs 2 verified; staging shows from 1, as the prototype's `figuresFor`).

**Partners.** The home section renders only with ≥ 4 logos that pass `hcwa_partner_logo_allowed()`
(`MIN_PARTNER_LOGOS`, exported); staging shows the pending strip below that; production prints nothing.
CONTRACT rule 5 gains this minimum.

**Featured story (H6).** Off production: the latest story post, marked `data-story-placeholder
data-draft` when its text has a draft marker, its picture the `story` still (never a photo). Production:
only a story post that has no draft text and whose image passes rule 2; otherwise none.

**Donation details.** Unchanged rule 3: production prints no slip rows, QR or PayPal until finance
confirms; staging prints the labelled placeholders with `data-donation-placeholder`.

### 3.5 Forms, and what Contact and Donate show on production

`forms.js` fakes sending. WordPress has no handler until task 2.7. Off production the forms render as in
the prototype (with `form__note` saying nothing left the browser), with `method="post"` and the control
names of 2.6. On production, while `hcwa_forms_enabled()` is false, no `form[data-form]` prints:
`section.tell` and `section.reach__write` print their heading and a `dl.office__lines` with the contact
lines that rule 12 allows; a section with no line left is left out.

With today's seeded content on production (phone, email and address all draft, no confirmed donation
details), that means:

- **Contact:** lit header (title, intro if not draft; no `lit-head__actions`), no `reach__write`, no
  `office`, the units list (names, Little Rose in words), the footer (no Contact column).
- **Donate:** sun head (title, intro if not draft; no `sun-head__go` and no jump links, because no panel
  or tell section is printed), no slip, no `tell`, `nav.trust`, and the dusk band with its title and the
  Contact button.

Both pages stay complete and honest; nothing suggests a message or a gift went through. Whether HCWA
wants these pages public in that state is Q5.

## 4. Templates, patterns and blocks

### 4.1 Blocks (hcwa-core)

All dynamic, `apiVersion 3`, no `save` markup, registered from `blocks/blocks-manifest.php`. Editable
text attributes carry `"role": "content"`.

**Attribute format.** Text attributes are plain text. The editor edits them in the canvas with one
shared helper added to `blocks/editor.js`: `RichText` with `allowedFormats: []`, `disableLineBreaks`
and `withoutInteractiveFormatting`, plus the existing `ServerSideRender` preview. Even so, stored values
can carry `&amp;`, `&nbsp;` or a `<br>` (pasted text, kses normalising entities for users without
`unfiltered_html`), so every render path passes each value through `hcwa_attr_text()` before
`hcwa_draft_text()`, the text helpers and escaping. A unit case covers `&`, `<`, a non-breaking space
and a soft break, and checks no `&amp;amp;` or literal tag reaches the output.

| Block | Status | Prints |
|---|---|---|
| `hcwa/sections` | new | only its rendered inner blocks (no wrapper, no literal HTML between them; 2.1 rule 4) |
| `hcwa/day` | new | `div.day.day--{variant}` (+ `hub` class and `data-hub` for `hub`) around its rendered inner blocks only; `variant` enum hub, involve, donate, contact, gap |
| `hcwa/lit-header` | new | 2.5 lit header; attrs `still`, `size`, `intro`, `title` (default: page title), `actions` (`reach` for Contact); trail from page ancestors |
| `hcwa/sun-head` | new | Donate header; attrs `intro`, `go`, `jumpAbroad`, `jumpTell` |
| `hcwa/dusk-band` | new | 2.6 dusk band; attrs `anchor`, `titleId`, `title`, `lines` (phone, email, sponsorship), `actions` (donate, volunteer, partner, contact), `note` |
| `hcwa/hero`, `hcwa/passage`, `hcwa/corridor`, `hcwa/work-sheet`, `hcwa/shutters`, `hcwa/help` | new | H2, H3, H4, H5, H7, H8 |
| `hcwa/work-hub` | new | P1 filters, places, times |
| `hcwa/louvers`, `hcwa/faq` | new | P4 |
| `hcwa/gifts` | new | P5 gift examples, off production only |
| `hcwa/contact-reach`, `hcwa/contact-units` | new | P6 |
| `hcwa/news-latest` | rewrite | H6 |
| `hcwa/partner-wall` | rewrite | H9 (min 4) |
| `hcwa/donation-methods` | rewrite | P5 `.give-grid` (slip + tell) and `nav.trust`; rule 3 unchanged |
| `hcwa/impact-strip` | rewrite | `section.counts` (2.6) |
| `hcwa/program-list` | rewrite | `ul.program-list` of program rows |
| `hcwa/report-list` | rewrite | `ul.docs__list` of `li.doc` |
| `hcwa/announcement-bar` | rewrite | `.announce[data-announce]` with `[data-dismiss-announce]` (handled by `ui/en-bar.js`, remembered by `wp-bars.js`) |
| `hcwa/language-notice` | keep | unchanged |
| `hcwa/center-grid` | delete | replaced by `hcwa/corridor` and `hcwa/work-hub` |

### 4.2 Locking

contentOnly is the riskiest unknown of the port: in WordPress 7.1.4 a block inside a contentOnly
container becomes "contentOnly" only if it has an attribute with `"role": "content"`, otherwise
"disabled"; whether a RichText canvas and a `ServerSideRender` preview can live together there, and
whether array attributes (`ways[]`, `faq[]`, `lines`) can be edited, is unproven. So:

1. **Spike first** (templates stream, before the other blocks): build `hcwa/sections`, `hcwa/hero` and
   `hcwa/faq` (an array block) and test them in the editor of a scratch instance with Playwright: a
   section cannot be inserted between others, moved or removed; text attributes, including one item of
   `faq[]`, can be edited and saved; the saved page renders the edit; no invalid-block warning. Record
   the result in CONTRACT.md.
2. **A (spike passes):** `hcwa/sections` has `templateLock: "contentOnly"`.
3. **B (spike fails):** `hcwa/sections` has `templateLock: "all"`, which blocks inserting, moving and
   removing inner blocks while their attributes stay editable (in the canvas or, where the canvas fails,
   in the block sidebar). Per-block `lock: {move, remove}` alone is not used: it lets editors insert
   blocks between sections, which breaks the CSS sibling pairs.
4. In every pattern the `hcwa/sections` block itself carries `"lock": {"move": true, "remove": true}`.
5. Unlocking a section through the editor's "edit section" control stays possible; 2.1 rule 4 makes the
   front end safe whatever staff insert. The open question in CONTRACT.md on that control stays open
   for HCWA (Q8).

### 4.3 Theme files

| File | Action |
|---|---|
| `header.php`, `footer.php` | rewrite to 2.2 and 2.3 |
| `front-page.php` | rewrite: `hcwa_render_sections()` of the front page; adds an sr-only H1 only if none was printed |
| `page.php` | rewrite: a page whose content holds an `hcwa/sections` block prints `hcwa_render_sections()`; any other page prints `hcwa_sun_head_html()` + `div.day.day--donate > div.entry-content.container` with `the_content` (2.7) |
| `single-hcwa_center.php`, `single-hcwa_program.php` | rewrite to P2, P3 |
| `404.php`, `searchform.php` | rewrite to P7 |
| `home.php`, `archive.php`, `index.php`, `single.php`, `search.php`, `archive-hcwa_report.php`, `single-hcwa_report.php`, `attachment.php` | restyle to 2.7 (logic stays: gates, language notice, pagination, search post types) |
| `template-parts/breadcrumb.php`, `pagination.php`, `share.php` | keep |
| `template-parts/news-archive.php`, `related-news.php`, `report-row.php` | rewrite to dated list, `.related`, `li.doc` |
| `template-parts/card.php`, `cta-band.php`, `page-header.php` | delete |
| `template-parts/center/{plate,story,photos,hang,here,give}.php`, `template-parts/program/{brief,span,aims,where,docs}.php` | add |
| `inc/template-tags.php`, `inc/navigation.php`, `inc/setup.php`, `inc/strings.php`, `inc/core-bridge.php` | update (icons, buttons `on-dusk`, pause toggle, lang switch, walker, page context, body class, title parts, bridge to the new core functions); `inc/setup.php` loses its `add_editor_style()` call |
| `inc/assets.php` | rewrite (manifest enqueue, no query strings, dequeues, editor styles, WordPress-only scripts) |
| `inc/head.php`, `inc/art.php` | add |
| `assets/css/wp.css` | add (2.7) |
| `assets/js/wp-bars.js`, `assets/js/wp-share.js` | add (1.2) |
| `assets/css/site.css`, `assets/css/patterns.css`, `assets/js/site.js`, `assets/js/bars.js`, `assets/fonts/*`, `assets/img/*` | delete |
| `assets/css/editor.css`, `theme.json`, `style.css`, `functions.php` | update |

### 4.4 Patterns (VI/EN pairs)

| Pattern | Action | Body |
|---|---|---|
| `page-home-vi`, `-en` | rewrite | `hcwa/sections` > hero, passage, corridor, work-sheet, news-latest, shutters, help, partner-wall, dusk-band (H10) |
| `page-our-work-vi`, `-en` | rewrite | `hcwa/sections` > lit-header, day(hub) > work-hub, dusk-band |
| `page-get-involved-vi`, `-en` | rewrite | `hcwa/sections` > lit-header, day(involve) > louvers + faq, dusk-band |
| `page-donate-vi`, `-en` | rewrite | `hcwa/sections` > sun-head, day(donate) > gifts + donation-methods, dusk-band |
| `page-contact-vi`, `-en` | rewrite | `hcwa/sections` > lit-header, day(contact) > contact-reach + contact-units |
| `hero-split`, `focus-areas`, `cta-band`, `ways-to-help`, `faq`, `contact-band`, `page-header` (14 files) | delete | replaced by blocks |

Every other page (About, Team, Policies and the rest) is a normal page: `page.php`'s non-sections branch
prints it through `the_content` (2.7), and the seed keeps its existing core-block bodies. A sections
starter for them would add nothing: under the lock staff could not add paragraphs or images anyway.

The ten page patterns are generated, not hand-written: `scripts/build-wp-patterns.py` (templates
stream) fills each pattern's block attributes from `content/draft/*.json`, wrapping `draft: true` fields
in `[DRAFT: …]`, so the starter copy always equals the prototype's. `wp-static-check.py` fails when a
pattern is stale.

## 5. Parity driver and runtime checks

### 5.1 What it is and how it runs

`scripts/wp-parity/` (Python, the repo's `.venv` Playwright and Pillow):

| File | Job |
|---|---|
| `run.py` | the driver: serves `prototype/dist` with `npx vite preview --port 4173 --strictPort` (next free port if busy; killed by PID on exit), loads both sides, runs 5.2 to 5.5, writes the report, exits 1 on any gate failure |
| `skeleton.js` | the in-page extractor, injected identically into both sides |
| `routes.json` | the route pairs (5.2) |
| `allow-assets.json`, `allow-templates.json` | accepted differences, each with selector, kind, reason and owner (one file per stream) |
| `lcp.cjs` | the conditions of `prototype/scripts/lcp.cjs` against any base URL |
| `gzip-proxy.mjs` | a local gzip front for WordPress during LCP runs (PHP's server sends no compression; the prototype's LCP server gzips), killed by PID |
| `three-chunks.py` | finds the three.js chunk on each side by content (`WebGLRenderer`): `prototype/dist/assets` and `wp/themes/hcwa-2026/assets/dist/assets` |

Command: `HCWA_WP_LOCAL_OK=1 scripts/wp-local.sh parity main [-- --quick|--full|--only home]`. It reads
the instance's base URL from `.wp-local/main/`, never changes the instance, and writes to
`qa/wp-port/parity/<date>-<time>/` (`report.json`, `report.html` with side-by-side images and diff maps
per section, `strips/`, `logs/`). At most 2 headless browsers at a time: the driver closes its own
browser before it runs the prototype's node checks one after another.

### 5.2 Matrix

Routes (identical paths on both sides): `/`, `/en/`, `/hoat-dong/`, `/en/our-work/`,
`/hoat-dong/co-so/tre-xanh/`, `/en/our-work/centers/green-bamboo/`, `/hoat-dong/du-an/cung-vuon-len/`,
`/en/our-work/programs/thrive-together/`, `/chung-tay/`, `/en/get-involved/`, `/chung-tay/ung-ho/`,
`/en/get-involved/donate/`, `/lien-he/`, `/en/contact/`, `/404.html`, `/en/404.html` (both sides answer
404 for these two paths by design; the document response is exempt from the failed-request check, its
subresources are not). 16 routes × 2 widths (390×844, 1440×900).

| Mode | How | Checks |
|---|---|---|
| reduced | `reducedMotion: "reduce"` | structure, text, links, screenshots, one H1, overflow, console, requests (no three.js chunk) |
| no JS | `javaScriptEnabled: false` | structure, text, links, one H1, overflow, every still present |
| no WebGL | Chromium with WebGL disabled (`--disable-webgl --disable-3d-apis`, as `check-hero.cjs`'s `nowebgl` browser) | `html.world-off` reached on home, stills present, no three.js render, structure and screenshot parity |
| Tier 0 | `?tier=0` | console, requests (no three.js chunk), stills shown |
| Tier 1, Tier 2 | `?tier=1`, `?tier=2` (home) | world reaches `html.world-live` (delegated to `check-hero.cjs`), console |
| paused | after `world-live`, click `[data-motion-toggle]` | frame strips (5.5) |
| 320 | 320×640 | no sideways scroll |

### 5.3 How parity is judged

1. **Structure.** `skeleton.js` walks `body` and records, per element: tag, classes (sorted; `wp-*`
   classes and body classes dropped), `id`, `role`, every `data-*` name (values kept for `data-world`,
   `data-stage`, `data-beat`, `data-unit`, `data-door`, `data-emblem`, `data-status`, `data-areas`,
   `data-way`, `data-span`, `data-still`, `data-reveal`, `data-traces`, `data-draft`, `data-pending`),
   `aria-*` names (values for `hidden`, `current`, `expanded`), `hidden`, `loading`, `fetchpriority`,
   `width`/`height`, `<source>` `media`/`type`/`sizes` and the number of srcset candidates, and custom
   property names in `style` (values for `--n`, `--i`, `--w`). `<script>` elements in `body` are skipped
   (2.8). URLs and text are left out here. The tree is cut into sections: `body > *` order, header,
   footer, the menu sheet, and each `main > *` root (inner pages: `.lit-head`, each `.day > *`,
   `.dusk`). A section passes when its diff is empty after the allow lists.
2. **Text.** Visible text per section (NFC, whitespace collapsed, sr-only text included), diffed the
   same way. The seed loads the same JSON, so text must match.
3. **Links.** Per section, the ordered list of `a[href]` values: same-origin values with the origin
   stripped, `assets/dist/` prefixes normalised to the prototype's root paths, and `?ver` never present
   (1.2). External news links are the allowed 2.8 difference. This catches wrong menu targets, the
   visible VI/EN switch, breadcrumbs, the `#co-so`/`#centers` and `#du-an`/`#programs` anchors and the
   sun-head jump links.
4. **Screenshots.** Reduced mode, `?tier=0`, fonts ready, lazy images forced in by scrolling each
   section into view once, then an element screenshot of each section root on both sides. A section
   passes when the heights differ by ≤ 2 px and ≤ 1.0% of pixels differ by more than 24/255 in any
   channel. Failures go into `report.html` with both images and a diff map.
5. **Behaviour.** The prototype's own checks run unchanged against both bases:
   `check-page.cjs`, `check-hero.cjs`, `check-bays.cjs`, `check-tiles.cjs`, `check-sunin.cjs` and
   `light-contrast.mjs` (contrast probes; its route list matches WordPress paths). Every line that passes
   on the prototype must pass on WordPress; the driver diffs the PASS/FAIL lines. `check-hero.cjs` finds
   the three.js chunk by reading `prototype/dist/assets`, so on WordPress its "three.js not requested"
   lines would pass without testing anything: those lines are marked prototype-only in the diff, and the
   driver asserts them itself on the WordPress request log with `three-chunks.py` (t0, reduced,
   no-WebGL, paused). Lines that 05-DESIGN 0.6.7 notes as timing-dependent (the 390 Tier 1 ambient-motion
   line) may be re-run once; such lines are listed by name in the allow files with `"kind": "timing"`.
6. **Console and network.** 0 console errors, 0 page errors, 0 failed requests (4xx/5xx) on both sides,
   except the 404 documents of 5.2.
7. **debug.log.** The driver notes the size of `.wp-local/main/logs/debug.log` before and after the run
   and fails on any new warning, notice or deprecation that names `themes/hcwa-2026` or
   `plugins/hcwa-core`. (The log already holds a core `translations_api()` warning from the language
   pack download; it is not ours.)

### 5.4 Allow lists

An entry is `{"route": "*" or a path, "section": "#news", "kind": "structure|text|links|pixels|timing",
"match": …, "reason": "…", "owner": "assets|templates"}`. Only the owning stream edits its file. The lead
reviews every entry; 2.8 lists the expected ones.

### 5.5 Frame strips, LCP, budgets

- **Frame strips:** home, VI and EN at 390 Tier 1 and 1440 Tier 2: 24 scroll positions from the hero to
  the footer, captured on both sides after `world-live` and pause, written as one side-by-side strip per
  combination. Automated part: both sides reach `world-live`; no frame between the last door and the
  `#work` H2 is under 15% non-background (05-DESIGN 0.6.7). A person looks at the strips before sign-off.
- **LCP:** `lcp.cjs` conditions (Moto G Power viewport, Slow 4G, CPU ×4, cold cache, median of 3) on `/`
  and `/hoat-dong/co-so/tre-xanh/`, prototype through its own server and WordPress through the gzip
  proxy, with `?tier=0`, `?tier=1` and default.
- **Budgets** (requests and bytes from the network log, home at 1440, Tier 2, cold cache): gate 7.10.

### 5.6 Seed extensions (`scripts/wp-seed.php`)

- Centers: `hcwa_accent` (unit), `hcwa_short_name`, `hcwa_header_still` (`header-green-bamboo` for Green
  Bamboo, '' for the others), every Green Bamboo `centerPages` field (3.2), story and history as post
  content (paragraph blocks, `<!--more-->` between), `menu_order` 0, 1, 2 as in `work.json`.
- Programs: `hcwa_center_ids` (Thrive Together: Green Bamboo, Little Rose, Cau Han; Charity Class: Cau
  Han, Green Bamboo; the disability pilot: none), `hcwa_full_name`, `hcwa_years_label` (the tokens),
  `hcwa_header_still` (`header-thrive-together`), the Thrive Together `programPages` fields; its two
  `reports[]` as draft `hcwa_report` posts with `hcwa_program_id`.
- Settings: `short_name_*`, `little_rose_note_*`, `world_traces_enabled = true`, `contact_confirmed`
  only when neither site.json `phone` nor `email` is marked draft (so: off). Emails stay plain addresses
  (the field is sanitized as addresses); the phone keeps today's `[DRAFT: …]` value where it exists.
  Never any approval field, never any `donation_*` value.
- Pages: bodies from the regenerated patterns (the existing `hcwa_seed_pattern()` path); re-running the
  seed refreshes seeded sections-page bodies. Other pages keep their existing bodies.
- `seed-urls.json`: add `kind` `parity` for the 16 routes, the center and program unit keys, and the
  expected beat count.
- Still idempotent; still refuses to run on production.

### 5.7 Smoke extensions (`scripts/wp-smoke.py`)

The production scans read `assets/generated/guards.json` and `generated/stills.json`; nothing is
re-typed in Python. Under `--production`, the `BLOCKERS` scan, the Little Rose scans and the still-name
scan always run, whatever `--allow-draft` says; `--allow-draft` only skips the older content checks
(`DRAFT`, `DRAFT_TOKEN`, `HCWA_NOTE` on the rendered text). New checks on every seeded view (both
widths, both languages):

- Head: the first `<script>` is the head-inline (it sets `data-tier`), the second is `pageReveal`;
  `#i18n` parses with the 11 non-empty keys; the main CSS and module come from `assets/dist/` with 200;
  no `?ver` on any `assets/dist/` URL; the module `src` equals the URL each loaded chunk imports the
  entry from; no `hcwaI18n`, no `fraunces`, no jQuery, no `wp-embed`, no emoji script; on sections
  pages no `global-styles` or `wp-block-library` style.
- `<html>`: `lang` starts with the page language; `data-tier` and `data-motion` after load; `data-lit`
  exactly on the lit pages (2.5).
- `body` children in the 2.2 order, nothing between them from our code.
- `.lit-head + .day` on lit pages; `main:has(> :last-child.dusk)` where the page ends on a dusk band.
- Links: every `[data-lang-switch] a[hreflang=X]` equals the page's `<link rel=alternate hreflang=X>`;
  every header, sheet and footer menu link answers 200 and its page's `lang` matches the current page's.
- `[data-beat]` only inside `#centers`, count = seeded centers (production before approval: minus the
  protected ones).
- Little Rose: no `bay__where`/`door__where`, no `visitCenter` text near a protected center, no
  unit-colour `--spill`; on `--production` before approval: none of the `LITTLE_ROSE_DOOR` patterns, no
  `li.door-chip[data-unit=<protected unit>]` holding a `.door-chip__door` or `--unit`, and no listed
  still name in the HTML or the request log.
- `data-traces` on `#centers` off production, never on `--production`.
- Numbers: the `textContent` of every `.num`, `.plaque__value` and `.count__value` is the same at
  `DOMContentLoaded` and 3 s later, and their computed `animation-name` is `none` and
  `transition-duration` is `0s` (impact numbers never count up).
- Language: on EN pages, no Vietnamese-only letter (any of `ăâđêôơư` or a combining tone mark after
  NFD) in visible text outside `[lang=vi]`, except words that occur in the EN values of
  `content/draft/*.json` and the dictionary (proper names); on VI pages, no EN dictionary value that
  differs from its VI value and is at least two words long. Form labels, buttons and error texts are
  included (no mixed-language form).
- `--production`: the exported `BLOCKERS` on the HTML with scripts and styles removed (footnote exception
  only); no `form[data-form]` while forms are off; no `section.partners` below 4 allowed logos; no
  `a[href^="#"]` whose target id is missing; no `tel:`/`mailto:` link with empty text.
- Staging, no JS: filling and submitting the Contact form answers 200 on the same path, and the final URL
  has no query string.
- `--unpublished <file>`: the listed URLs must answer 404 (5.9).
- Every `posters/`, `svg/`, `fonts/`, `img/` request answers 200.
- Probes for the refused attachment (the bug in 6.3): `/?attachment_id=<id>`, `/?p=<id>` and
  `/?page_id=<id>` each answer 404 with no `Location` header (today all three answer 301 to the Little
  Rose attachment path).

### 5.8 The stub render harness: not built

A stub renderer for whole templates would need about 78 more WordPress functions, `WP_Query`, a block
parser and registry and a nav walker, and it would still not prove what the real WordPress prints. Real
WordPress runs locally, so it is not worth it. What stays without WordPress, cheap and useful in CI (both
run in a fresh clone because their inputs are committed, 1.1):

- `php wp/tests/run.php` unit tests, including **golden tests**: `export-wp.mjs` writes
  `wp/tests/fixtures/golden.json` with input and output pairs from the prototype's own functions
  (`splitWords`, `viKeep`, `keepHyphens`, `gachStrip`, `stillPicture`, `poster`, `litStill`,
  `quatrefoil`, the icon SVGs, date splits, head-inline). PHP must reproduce each output exactly.
- `python3 -I scripts/wp-static-check.py`.

### 5.9 Instance rules

- `main` (8881): read only for parity and smoke; edits to the symlinked theme and plugin show on the
  next request; re-seed with `seed main` after a content-model change (idempotent). Never `stop`,
  `reset`, `setup` or `env` on `main`.
- **Production pass**, on a throwaway instance:
  1. `setup scratch-prod 8890`, `seed scratch-prod`, `env scratch-prod production`, `start scratch-prod`.
  2. CONTRACT rule 4's procedure after a database push: first
     `wp hcwa draft-scan --printed=raw --format=urls > qa/wp-port/scratch-prod/unpublished.json` (the
     public URLs of those posts, written while they are still published: a draft's permalink is
     `?p=N`), then `wp hcwa draft-scan --printed=raw --format=ids` and set each to draft with
     `wp post update <ids> --post_status=draft`. `guarded` rows stay published: that their
     draft text never renders on production is exactly what the pass proves.
  3. `smoke scratch-prod -- --unpublished qa/wp-port/scratch-prod/unpublished.json` (production mode,
     without `--allow-draft`).
  4. The driver's production assertions (`run.py --production-asserts`, gate 7.7).
  5. `wp hcwa draft-scan --printed=raw` lists nothing; `wp hcwa coverage` has no blocking row.
  6. `stop scratch-prod`.
- Editor checks (4.2, 7.12) run on a scratch instance (`scratch-editor`, another port), never on `main`.
- Never install another plugin or theme from the internet.

## 6. Build streams and file ownership

### 6.0 Work order

1. The lead updates CONTRACT.md from 6.4 and asks Maxwell Q1 (Little Rose door) and Q2 (traces route).
   Until answered, this spec's defaults hold.
2. The harness agent builds the harness (6.1).
3. The assets stream delivers **milestone A first**: the build mode and export (1.1), every generated file
   of 1.3 (`prototype-data.php`, `golden.json`, `stills.json` with `showsLittleRoseDoor`, `guards.json`,
   `classes.json`, `sources.json`, head-inline, page-reveal, SVG). The templates stream may start the
   content model (3.2), the strings and the locking spike (4.2) meanwhile, but not the text helpers,
   the art calls or 3.4 until milestone A is in the tree.
4. Both streams run in parallel; then the lead integrates and runs section 7.

A stream that needs a file it does not own lists it under "needs" in its report.

### 6.1 Harness agent (first)

Owns: `scripts/wp-parity/**` (run.py, skeleton.js, routes.json, lcp.cjs, gzip-proxy.mjs,
three-chunks.py, the production assertions, and the first empty `allow-assets.json` and
`allow-templates.json`), the `parity` subcommand in `scripts/wp-local.sh`, the 5.6 changes to
`scripts/wp-seed.php`, the 5.7 changes to `scripts/wp-smoke.py`, `scripts/wp-static-check.py` (classes
from `assets/generated/classes.json` and `wp.css`; generated freshness from `sources.json`; pattern
freshness; drop the `patterns.css` hide-wrapper logic; the new block list; when a committed generated
file is missing it fails with the export command), `wp/tests/bootstrap.php`, `wp/tests/run.php`,
`wp/tests/runtime-scripts-test.php`, and moving every asset assertion out of `theme-gates-test.php`,
`theme-helpers-test.php` and `integration-test.php` into a new `wp/tests/theme-assets-test.php` so the
streams start with clean ownership. Done when the driver runs end to end against today's WordPress and
prototype and produces a report (it will show many differences; that is the point), and the new static
checks pass on fixture inputs. Until milestone A lands, the static check reports the missing generated
files as one error; that is expected.

After delivery: `wp-seed.php` and `allow-templates.json` pass to the templates stream,
`allow-assets.json` to the assets stream; the rest stays with the lead.

### 6.2 Stream "assets"

Owns:

- `prototype/vite.wp.config.js` (new), `prototype/scripts/export-wp.mjs` (new),
  `prototype/test/export-wp.test.mjs` (optional, new), the two scripts in `prototype/package.json`.
- `wp/themes/hcwa-2026/assets/dist/**` (generated, ignored), `assets/generated/**` (generated,
  committed: `head-inline.*.js`, `page-reveal.js`, `svg/**`, `stills.json`, `guards.json`,
  `classes.json`, `sources.json`), `wp/plugins/hcwa-core/includes/prototype-data.php` (generated,
  committed; hcwa-core `require`s it) and `wp/tests/fixtures/golden.json` (generated, committed).
- `wp/themes/hcwa-2026/theme.json`, `style.css`, `functions.php`, `inc/assets.php` (including
  `add_editor_style()`), `inc/head.php` (new), `inc/art.php` (new), `assets/css/editor.css`,
  `assets/js/wp-bars.js` (new), `assets/js/wp-share.js` (new).
- Deletes `assets/css/site.css`, `assets/js/site.js`, `assets/js/bars.js`, `assets/fonts/*`,
  `assets/img/*`.
- `wp/tests/theme-assets-test.php`, `wp/tests/assets-stubs.php` (new).
- `scripts/wp-parity/allow-assets.json`.

Tasks: milestone A (6.0); 1.2 head-inline, `pageReveal` and `#i18n` emission, manifest enqueue (draft CSS
first on staging, module in head, modulepreload, font preloads, no query strings, dequeues),
`hcwa_theme_bar_script()`, `wp-share.js`; `HCWA_Theme_Art` behind `interface_exists` with a no-plugin
test (stills, posters, sprite once per request, gạch art and strip, screen art, block icons, asset URLs,
`shows_little_rose_door()`); `theme.json` palette and fonts from tokens; editor styles; world and motion
integration (the worker and lazy chunks load from `assets/dist/` on the front page only,
`html.world-live` reached at Tier 1 and 2, three.js never requested at Tier 0, under reduced motion,
without WebGL or without the canvas); fix `debug.log` lines from its files; the asset side of gates 7.1,
7.2, 7.9, 7.10 and 7.11.

### 6.3 Stream "templates"

Owns:

- Theme: `header.php`, `footer.php`, `front-page.php`, `page.php`, `home.php`, `index.php`,
  `archive.php`, `archive-hcwa_report.php`, `single.php`, `single-hcwa_center.php`,
  `single-hcwa_program.php`, `single-hcwa_report.php`, `search.php`, `searchform.php`, `attachment.php`,
  `404.php`, all of `template-parts/**`, all of `patterns/**`, `inc/template-tags.php`,
  `inc/navigation.php`, `inc/setup.php` (minus `add_editor_style()`), `inc/strings.php`,
  `inc/core-bridge.php`, `inc/admin.php`, `assets/css/wp.css` (new); deletes `assets/css/patterns.css`.
- Plugin: `hcwa-core.php`, `includes/*.php` except the generated `prototype-data.php` (including the new
  `text.php`, `art.php` and `sections.php`, the regenerated `strings-data.php`, `meta.php`,
  `settings.php`, `media-approval.php`, `publish-guard.php`, `cli.php`), `blocks/**` (block.json,
  render.php, index.js, index.asset.php, `editor.js`, `blocks-manifest.php`), `languages/**`,
  `assets/**`.
- Scripts: `scripts/build-wp-strings.py`, `scripts/build-wp-patterns.py` (new), and after the harness
  delivers, `scripts/wp-seed.php` and `scripts/wp-parity/allow-templates.json`.
- Tests: every other `wp/tests/*-test.php` and `*-stubs.php` (blocks, core, patterns, theme gates and
  helpers, integration), `blocks-manifest-build.php`, and a new `theme-markup-test.php` (golden text
  helpers, `hcwa_attr_text`, `hcwa_center_body`, door, chip and Little Rose rules, draft rendering and
  the bracket rule, `hcwa_render_sections` cleaning and gating, contact lines).

Tasks, in this order: the locking spike (4.2); 3.2 content model and settings, strings (ui.json +
ui-world.json); after milestone A, 2.1 to 2.8 markup, 3.3 to 3.5 rules, 4.1 to 4.4 blocks, templates and
patterns; `wp hcwa draft-scan` `printed` column and filters, `wp hcwa coverage` for the new fields; fix
the attachment redirect (below); the template side of gates 7.3, 7.4, 7.6, 7.7, 7.8, 7.12 and 7.13.

**The attachment bug (found 9 Nov, fix in `includes/media-approval.php`).** `/?attachment_id=40`,
`/?p=40` and `/?page_id=40` all answer 301 to `/hoat-dong/co-so/hoa-hong-nho/hcwa-20261109-v1lzeuoz/`,
which exposes the Little Rose page path (slug and base variants already answer 404 and leak nothing).
Cause, checked in the reference source: `hcwa_refuse_attachment_page()` runs on `wp` (priority 5) and
calls `set_404()`; core `redirect_canonical()` (template_redirect, priority 10) then takes its `is_404()`
branch (`canonical.php` lines 146-167), which turns the id query into `get_permalink( N )` and redirects.
Fix: a `redirect_canonical` filter that returns false when the request is a 404 whose `attachment_id`,
`p`, `page_id` or `attachment` query var (or the guessed redirect target) is an attachment
`hcwa_attachment_page_refused()` refuses; a stub test for each of the three query vars; the smoke probes
of 5.7 must then report 404 with no `Location` header.

### 6.4 Interfaces between the streams (the lead copies these into CONTRACT.md)

| Provider | Function or file | Caller |
|---|---|---|
| templates (hcwa-core) | `HCWA_Art` interface, `HCWA_Art_Guard`, `hcwa_art()`, the filter `hcwa_art_provider` | assets implements, templates calls |
| assets (theme) | `hcwa_theme_print_head_first(): void` | `header.php` |
| assets (theme) | `hcwa_theme_bar_script( string $which ): string` (`en` or `announce`) | `header.php` |
| assets (theme) | `hcwa_theme_dist_url( string $path ): string`, `hcwa_theme_manifest(): array` | templates (logo, favicons) |
| templates (theme) | `hcwa_theme_page_context(): array` with `lit`, `world`, `header_over`, `body_class` | `inc/head.php` (data-lit via `language_attributes`), `inc/assets.php` (dequeues) |
| templates (hcwa-core) | the functions in 3.2 | both |
| both | `hcwa_theme_t()` and the `hcwa_strings` dictionary | `inc/head.php` reads the 11 `#i18n` keys |
| assets (generated) | `prototype-data.php`, schema below | templates |

**`hcwa_theme_page_context()`** decides before `<html>` prints, once per request (cached), from the
queried object: front page → `page-home`, lit, world, header over; a page holding `hcwa/sections` → read
the first inner blocks (`parse_blocks` of `post_content`): an `hcwa/lit-header` first means lit; the
`variant` of the first `hcwa/day` gives the class (`hub` → `page-work`, `involve` → `page-involve`,
`donate` → `page-donate`, `contact` → `page-contact`), and an `hcwa/sun-head` first means not lit;
center and program singles → `page-center` / `page-program`, lit; 404 → `page-gap`; everything else →
`page-wp`, not lit.

**`prototype-data.php` schema** (generated; `return array( … );`):

| Key | Type | Example |
|---|---|---|
| `version` | string | sha256 over the inputs in `sources.json` |
| `areas` | string[] in display order | `education, protection, family, policy` |
| `statuses` | string[] | `active, completed` |
| `units` | string[] | `green-bamboo, little-rose, cau-han` |
| `emblems` | unit ⇒ emblem | `green-bamboo ⇒ dotTre`, `cau-han ⇒ vomCau`, `little-rose ⇒ hoaThi` |
| `area_block` | area ⇒ block kind | `education ⇒ hoaThi`, `protection ⇒ matCao`, `family ⇒ vongTron`, `policy ⇒ bacThang` |
| `area_patch` | area ⇒ int[2] | `education ⇒ [2,2]`, `protection ⇒ [2,1]`, `family ⇒ [1,1]`, `policy ⇒ [1,1]` |
| `tile`, `grout_px` | int, float | as `blocks.mjs` |
| `hole_masks` | emblem ⇒ dist path | `dotTre ⇒ svg/hole-dot-tre.svg`, `vomCau ⇒ svg/hole-vom-cau.svg` |
| `vi_keep` | string[] longest first | the 27 glossary entries |
| `icons` | name ⇒ inner SVG markup | the 16 Lucide paths |
| `quatrefoil` | inner SVG markup | the 191 B shape |
| `guards` | `blockers` (list of `pattern` as PCRE with `u` flag, `why`), `little_rose_door` (PCRE), `footnote` (PCRE `^\[\d+\]$`), `min_partner_logos` (int) | from `render.mjs` |

Also for the lead: CONTRACT.md's "Never download or execute WordPress" line and the README permission
note must change to Maxwell's 9 Nov permission and the instance rules in 5.9.

## 7. Acceptance gates

Every gate is measured on the final tree. "WordPress" means the seeded `main` instance unless a scratch
instance is named.

| # | Gate | Pass when |
|---|---|---|
| 7.1 | Prototype unchanged | `git diff --stat prototype/` lists only the files in 1.1; `npm test` passes (11 test files, or 12 with `export-wp.test.mjs`); `npm run build` and `npm run build:pages` pass; `PROD=1 npm run build` fails with exactly 59 problems, the same lines as `qa/wp-port/prototype-map/prod-guards.log` |
| 7.2 | Export | `npm --prefix prototype run export:wp` succeeds twice in a row with identical `assets/generated/`, `prototype-data.php` and `golden.json`; no root-absolute `/assets/`, `/svg/`, `/fonts/`, `/posters/`, `/img/` reference left in any dist JS or CSS; gzip sums within 2% of 1.1: main + draft CSS against 28.6 KB, JS entry + its static imports against 16.0 KB, the motion chunk against 55.3 KB, the world set against 183.8 KB; `showsLittleRoseDoor` holds the 9 stills of 1.3 |
| 7.3 | Unit tests | `php wp/tests/run.php`: 0 failed (the strings-data failure is fixed by regeneration); every golden case passes; the no-plugin theme load, `hcwa_attr_text`, bracket-rule, chip, `hcwa_render_sections` and attachment-redirect cases pass |
| 7.4 | Static check | in a fresh clone (no dist), `python3 -I scripts/wp-static-check.py`: 0 errors; warnings only from the existing shared-Vietnamese-source list or new ones the lead accepts in writing |
| 7.5 | Lint | the CONTRACT.md lint commands print nothing |
| 7.6 | Every seeded URL renders | `scripts/wp-local.sh smoke main`: every view passes (129 today, plus the new parity URLs), including `gate:refused-attachment-page` and the `?attachment_id`, `?p` and `?page_id` probes (404, no `Location`); `scripts/wp-local.sh wp main -- hcwa coverage` reports no blocking row and reports the new fields (unit key, short name, header still, `hcwa_center_ids`); 0 new lines in `debug.log` naming our files |
| 7.7 | Production variants | the 5.9 production pass on `scratch-prod`: smoke without `--allow-draft` passes; `draft-scan --printed=raw` lists nothing after step 2; 0 `BLOCKERS` hits (scripts and styles removed); explicit assertions, per production route: beat count and `--n` = seeded centers − protected; no `LITTLE_ROSE_DOOR` match; no protected chip with a door or `--unit`; no listed still name in the HTML or the requests; no `data-traces`; no `form[data-form]`; no partners section; no slip values, QR or PayPal; no `data-draft`, `data-pending` or `data-donation-placeholder`; no pending chips; no dangling `aria-labelledby` or `#` link; no empty `tel:`/`mailto:` link; no `noindex`; Contact and Donate as described in 3.5; tastegate at 390 and 1440 on home, Our Work, Contact and Donate |
| 7.8 | Parity, structure, text and links | 16 routes × 2 widths × reduced, no-JS and no-WebGL: every section's structure, text and link diff is empty after the allow lists |
| 7.9 | Parity, pixels | every section at 390 and 1440, reduced, Tier 0 and no-WebGL, VI and EN: height within 2 px and ≤ 1.0% differing pixels, or an allow entry with a reason the lead accepted |
| 7.10 | Parity, behaviour and budgets | every PASS line of the prototype checks (5.3 item 5) passes on WordPress (prototype-only lines replaced by the driver's own three.js assertions; a listed timing line may pass on its one re-run); `world-live` on home at Tier 1 and 2 on both sides; 0 console errors and 0 failed requests in every mode (404 documents exempt); one H1 per view; no sideways scroll at 320, 390 and 1440. No `?ver` on any dist URL and `main.js` evaluated once (one `window.hcwa` bus construction, counted by the driver). Home, 1440, Tier 2, cold: before `load`, ≤ 15 requests excluding images and ≤ 300 KB transferred excluding images (the prototype is at about 236 KB: HTML, CSS, the main and common chunks, the motion chunk, four fonts); the home HTML ≤ 15.5 KB gzip (prototype 13.5 KB) with ≤ 1 KB of inline `<style>` in `<head>` on every parity route; the world chunk set ≤ 190 KB gzip and only after `load` + idle; the whole home after `world-live` with no scroll ≤ 1 MB. Inner pages: ≤ 12 requests excluding images before `load`, and never the motion or world chunks. On the parity routes WordPress makes the same script and style requests as the prototype (plus the draft CSS on staging), 0 more, and loads no jQuery, Elementor, emoji, embed, global-styles or block-library assets |
| 7.11 | Mobile LCP | `/` and the Green Bamboo page, `lcp.cjs` conditions, median of 3, through the gzip proxy: ≤ 2.5 s on every path (target 1.8 s on home), CLS < 0.1, no long task over 200 ms in the first 10 s on the stills path, three.js requested only after LCP, and WordPress no more than 300 ms slower than the prototype on the same machine |
| 7.12 | Editor | on `scratch-editor`: the 4.2 spike result recorded; each page pattern inserts without an invalid-block warning; sections cannot be inserted, moved or removed, and `hcwa/sections` cannot be removed; text attributes (including an array item) edit and save; the publish guard refuses a production page with a `[DRAFT` attribute. Negative case: unlock a section, insert a `core/html` block with an `<img>` of an unapproved upload and a paragraph with that image inline, save; the front end prints neither image and no `core/html` output |
| 7.13 | Safeguarding | no photo of a child, name, school or location of a child added to the repo (`git diff --stat` reviewed; no new binary under `wp/` except generated SVG); Little Rose rules in 3.4 hold in both environments (smoke, 7.7); no live social feed embed; the 5.7 language checks pass on every view (every visible string bilingual, no mixed-language form, button or error); no `text-transform: uppercase` in our CSS and the existing no-uppercase-Vietnamese smoke check passes; running text line-height ≥ 1.6; reduced-motion, no-JS and no-WebGL views complete (7.8); the 5.7 number check passes on every view with figures (impact numbers never count up) |
| 7.14 | Look | tastegate at 390 and 1440 on every route and on the WordPress-only pages (2.7), VI and EN: exit 0; frame strips looked at by a person; no hard line between sections (05-DESIGN 0.5) |

## 8. Open questions and what is out of scope

### Open questions for Maxwell or HCWA

1. **Little Rose on production before the safeguarding lead signs off** (ask before the templates stream
   builds 3.4). This port decides: no door in any form anywhere, N−1 doors in the corridor, Little Rose in
   words only, and every still that shows the door withheld (3.4). The alternative is that HCWA approves
   the shut door now, so production matches staging everywhere. There is no middle option (for example,
   the door in background stills but not in the corridor): one switch governs all of it.
2. **Traces route.** The port keeps traces staging-only, as its brief says. 05-DESIGN 6.8 describes an
   approval that would allow them on production. If Maxwell wants that route, the lead records it in
   CONTRACT.md before the templates stream starts.
3. **Who holds `hcwa_approve_safeguarding`** (the Little Rose door), and who may tick "since year
   verified" and "contact confirmed"? Proposed: HCWA's safeguarding lead's account only for the first;
   Administrators by default until then.
4. **Forms on production** until task 2.7: only the confirmed contact lines instead of a form (3.5).
   Acceptable, or should the forms be hidden on staging too?
5. **Contact and Donate on production with nothing confirmed** (3.5): publish them in that reduced state,
   or keep them unpublished until HCWA confirms the phone, email and donation details?
6. **Header stills for the other centers.** Only Green Bamboo and Thrive Together have one; Cau Han,
   Little Rose and the other programs get the plain placeholder. Render more stills later (Little Rose:
   never a door)?
7. **Deploys:** `assets/dist/` (posters 14 MB) is not committed. Build it in CI and ship a theme zip, or
   commit it on a release branch?
8. **Locked sections (CONTRACT Q12):** the lock lets staff edit words only, and the editor's "edit
   section" control can still unlock a section (made safe by 2.1 rule 4). Is that the level of freedom
   HCWA's team wants?
9. **Plaques versus counts:** the home plaques need 2 visible figures everywhere (prototype), the center
   and program counts follow rule 1 (staging from 1). Keep this split?

### Out of scope

- Anything that needs HCWA's own WordPress or host: importing the live site (2.2), Yoast at HCWA's
  version, host caching, compression and HTTP/2, real email delivery, redirects on the live server.
- The form handler and plugin (2.7), until Q9 of 03-STRATEGY is answered.
- New stills (including door-free versions of the listed stills), world changes and any prototype change
  beyond the build mode in 1.1.
- Device measurements on a real Galaxy A15-class phone (05-DESIGN 13.3); the lab LCP in 7.11 stands in.
- A Content Security Policy (the hashes are recorded for later).
- HCWA's approval of wording, numbers, photos and donation details: nothing here makes any of them
  final.

## 9. Review decisions

An adversarial reviewer read the first draft on 9 Nov 2026. Each finding was checked against the code
and the running instance before it was taken. All 6 musts are fixed; all 16 shoulds are taken, three of
them with a stated variation.

| Finding | Decision | Where |
|---|---|---|
| Must: Little Rose door reaches production through stills not named for it (`passage-corridor`, `bay-green-bamboo`, `bay-cau-han`) | Fixed. `showsLittleRoseDoor` is a generated field (fails closed, 9 stills today) and an input to the templates stream; `HCWA_Art_Guard` refuses those stills before approval; passage exit prints empty, bay views use the single-door `door-{unit}` cards; smoke scans names and requests. Q1 is asked before 3.4 is built; one switch, no partial state. | 0 #6, 1.3, 1.4, 3.4, 5.7, 6.0, 7.7, 8 Q1 |
| Must: program-row door chips paint the Little Rose door | Fixed. 3.4 row for chips; every chip carries `data-unit`; smoke and production assertions test it; unit case in `theme-markup-test.php`. | 2.6, 2.8, 3.4, 5.7, 6.3, 7.7 |
| Must: `do_blocks()` bypasses the classic-HTML half of the photo gate and the password check | Fixed with `hcwa_render_sections()`: password check, tree cleaning (no `core/html`, `core/freeform`, `core/shortcode`, classic HTML, literal HTML between sections), and `wp_filter_content_tags()` + `hcwa_content_strip_refused()` on every non-`hcwa/*` block's output. It does not run them on our own output, because the gate would refuse the theme stills as "not in the library" in gated page contexts. page-basic dropped: other pages are normal pages through `the_content`. Negative case in 7.12. | 0 #3, 2.1 rule 4, 2.7, 4.3, 4.4, 7.12 |
| Must: gate 7.7 cannot pass as written | Fixed. 5.9 runs the rule 4 procedure (unpublish the `raw` draft-scan rows, which needed a `printed` column and filters), then smoke without `--allow-draft`, with the unpublished URLs expected to 404. The guards.json scans always run under `--production`. `data-draft` on `ul.foot-contact`, `p.lit-head__actions` and the other unconditional containers is now conditional and dropped on production. | 2.1 rule 7, 2.3, 2.5, 3.3, 5.7, 5.9, 7.7 |
| Must: traces got a production path the hard limits forbid | Fixed. `hcwa_traces_on()` = enabled and not production; no traces approval fields; Q2 records how Maxwell could choose 05-DESIGN 6.8 instead. | 0 #7, 3.2, 3.4, 8 Q2 |
| Must: versioned URLs would evaluate `main.js` twice and duplicate the main CSS | Fixed. No query string on any dist URL (version `null`, hand-printed module and modulepreload); tested in the theme assets test, the smoke and gate 7.10. | 1.2, 5.7, 7.10 |
| Should: `check-hero.cjs` is not base-agnostic | Taken. The driver's `three-chunks.py`; check-hero's three.js lines marked prototype-only. | 5.1, 5.3 item 5 |
| Should: links are never checked | Taken. Per-section link diff; smoke checks the language switch against hreflang and every menu link. | 5.3 item 3, 5.7, 7.8 |
| Should: WordPress-only behaviours vanish with `site.js` and `bars.js` | Taken, with a variation: kept as two small WordPress-only scripts (`wp-bars.js` inline before paint, `wp-share.js`), rather than dropping native share. The EN bar also stays decided before paint, which keeps the CONTRACT round-1 decision for both bars; the end state equals the prototype's. | 1.2, 2.2, 2.8 |
| Should: RichText values are HTML | Taken. Plain-text attributes, no formats, no line breaks; `hcwa_attr_text()` before every helper; unit case. | 2.1 rule 13, 3.2, 4.1 |
| Should: contentOnly unproven, weak fallback | Taken. Spike on hero and faq first; fallback `templateLock: "all"`; lock on `hcwa/sections` in every pattern. | 0 #4, 4.2, 6.3, 7.12 |
| Should: center and program body text has no rendering path | Taken. `hcwa_center_body()` with no `the_content` filters; program post content not printed. | 2.5, 3.2 |
| Should: two definitions of draft | Taken, with a variation: the prototype's `BLOCKERS` are exported and used by PHP and Python alike, but digit-only brackets (`[1]`) stay allowed as footnotes, because posts migrated from the live site may carry them and CONTRACT rule 4 already allows them; `content/draft/*.json` has none, and a test covers every bracket string in it. | 1.3, 3.3, 5.7 |
| Should: production leaves holes | Taken. Rules 10 to 12, the hero's sr-only H1, `contact_confirmed` because `emails` cannot carry a draft wrapper, and what Contact and Donate show. | 2.1, 2.3 to 2.5, 3.1, 3.2, 3.5, 5.6 |
| Should: templates depend on assets outputs | Taken. Milestone A first; `prototype-data.php` schema in 6.4; `HCWA_Theme_Art` behind `interface_exists` with a no-plugin test; editor styles move to `inc/assets.php`. | 1.4, 1.5, 6.0, 6.2, 6.3, 6.4 |
| Should: gates 7.1 and 7.2 cannot match as worded | Taken. Main + draft CSS against 28.6 KB; JS compared as sums; 11 or 12 test files. | 1.1, 7.1, 7.2 |
| Should: parity matrix gaps | Taken. No-WebGL mode; explicit production assertions instead of production parity; 404 documents exempt; one re-run for listed timing lines. | 5.2, 5.3, 5.9, 7.7 to 7.10 |
| Should: WP-CLI in no gate; only `?attachment_id` probed | Taken. `wp hcwa coverage` in 7.6, draft-scan in 5.9 and 7.7; `?p` and `?page_id` probes and stub tests. | 5.7, 5.9, 6.3, 7.6, 7.7 |
| Should: global styles grow on every page | Taken, both parts: global styles and block CSS dequeued on sections pages, and the theme.json palette kept to 8 editor colours; HTML bytes and inline style counted in 7.10. | 1.2, 1.5, 7.10 |
| Should: static check and CI need the gitignored dist | Taken. `classes.json` committed; freshness from `sources.json` against `prototype/` sources; fresh-clone run in 7.4. | 1.1, 1.3, 6.1, 7.4 |
| Should: contact field names clash with query vars | Taken. `c-{id}` names, `method="post"`, ids unchanged (`forms.js` reads ids and `[data-field]`, checked); no-JS submit check on staging. | 2.6, 2.8, 5.7 |
| Should: some 7.13 items are not mechanical | Taken. Smoke checks for static numbers and for mixed language. | 5.7, 7.13 |
| Should: smaller errors (`en-GB`, page context, P2 gallery, filter counts) | Taken. `en-GB` is listed in 2.8 rather than filtered, because nothing in the prototype reads the exact value (checked) and it is Polylang's correct locale. Page context derived from the first blocks; approved gallery markup defined; filter notes and counts from the doors printed. | 2.2, 2.5, 2.8, 3.4, 6.4 |
