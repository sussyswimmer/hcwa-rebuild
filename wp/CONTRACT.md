# Phase 2 build contract (hcwa-core + hcwa-2026)

The plugin and theme are built in parallel by several people/agents. This file is the
agreement between them: names, file ownership, function signatures, gating rules. If you
need to change something here, change this file in the same commit and say why.

Source of truth for design and copy: the Phase 1 prototype (`prototype/`) and
`content/draft/*.json`. Specs: `docs/plan/04` (content model), `05` (design), `06` (pages),
`07` (bilingual), `09` (QA). Hard rules: `CLAUDE.md`.

Reference source, read-only, never executed: WordPress 7.1.4 and Polylang 3.9.1 under
`.wp-local/ref/src/` (gitignored). Grep it to confirm hook names and signatures instead of
relying on memory.

## Decisions (task 2.1)

- **Hybrid theme.** PHP header, footer and templates; `theme.json` for tokens; page bodies
  are blocks and patterns. Reason: Polylang free does not translate block-theme template
  parts or CPT/taxonomy URL bases; PHP templates with Polylang menus and strings work.
- **Per-language URL bases are ours.** Polylang free cannot give `hcwa_center` a VI base
  `hoat-dong/co-so` and an EN base `our-work/centers`. So CPTs register with
  `'rewrite' => false` and `hcwa-core/includes/localized-urls.php` adds explicit rules
  with `add_rewrite_rule()` plus `post_type_link` / `term_link` filters. Checked in
  `polylang/src/links-directory.php`: rules from `add_rewrite_rule()` only pass through
  `rewrite_rules_array`, where Polylang only touches rules containing `post_type=`, so our
  rules (which carry `lang=vi|en`) survive unchanged. Build links from
  `get_option( 'home' )`, not `home_url()`, so Polylang never adds a second `/en/`.
- **Patterns ship as VI/EN pairs** (`hero-split-vi`, `hero-split-en`). Patterns load once
  at startup and cannot know the language of the post being edited. Starter text is
  `[DRAFT: …]` in the pattern's language; the production publish guard catches leftovers.
- **Forms (2.7) are not built yet**: needs HCWA's answer to Q9 and the form plugin choice.
- **Not run yet**: WordPress itself. Running downloaded WordPress needs Maxwell's
  permission in this environment. Until then: `php -l`, `node --check`, `php wp/tests/run.php`.

## Layout and ownership

```
wp/
  CONTRACT.md                         this file (integration agent may edit)
  README.md                           how to run locally (dev-runtime agent)
  tests/bootstrap.php, run.php        minimal WP stubs + runner (core agent)
  tests/<area>-*-test.php             each agent adds its own
  tests/integration-test.php          integration fixes between the parts (integration agent)
  plugins/hcwa-core/                  CORE agent, except blocks
    hcwa-core.php                     header, constants, requires
    includes/helpers.php              hcwa_is_production, hcwa_lang, hcwa_t, formatting
    includes/strings.php              string dictionary + Polylang registration
    includes/post-types.php           CPTs + hcwa_focus_area taxonomy
    includes/meta.php                 register_post_meta + meta boxes
    includes/media-approval.php       attachment approval fields + hcwa_image()
    includes/localized-urls.php       per-language bases (see above)
    includes/polylang.php             translatable types, meta sync, language links
    includes/settings.php             Settings → HCWA (announcement, contact, donation, page map)
    includes/impact.php               hcwa_get_impact_figures() with the verified gate
    includes/publish-guard.php        production refuses to publish [DRAFT text
    includes/cli.php                  wp hcwa coverage | draft-scan
    languages/                        admin strings, Vietnamese (.l10n.php)
    includes/blocks.php               BLOCKS agent
    blocks/<name>/                    BLOCKS agent (block.json, index.js, index.asset.php, render.php)
  themes/hcwa-2026/                   THEME agent, except patterns/
    style.css, theme.json, functions.php, header.php, footer.php, templates, inc/, template-parts/
    assets/css/site.css               port of prototype CSS
    assets/css/patterns.css           PATTERNS agent (theme enqueues it front + editor if present)
    assets/js/site.js, assets/fonts/, assets/img/
    patterns/*.php                    PATTERNS agent
scripts/wp-local.sh, wp-router.php, wp-seed.php, wp-smoke.py   DEV-RUNTIME agent
scripts/wp-static-check.py            cross-part static check (integration agent)
```

Do not edit files another agent owns. If you need something from them, use the contract
name below and list it under "needs" in your report.

## Names

- PHP prefix `hcwa_` (functions), `HCWA_` (classes, constants). Text domain `hcwa`
  (admin UI only; front-end strings go through `hcwa_t()`).
- Post types: `hcwa_center`, `hcwa_program`, `hcwa_report` (public singles);
  `hcwa_impact`, `hcwa_partner` (admin only, `public => false`, `show_ui => true`).
- Taxonomy: `hcwa_focus_area` on centers, programs and posts. Terms (slugs, VI terms get
  `-vi` only if Polylang needs distinct slugs): education, protection, family, policy.
- Post categories (seeded): news, stories, events, announcements (VI: tin-tuc,
  cau-chuyen, su-kien, thong-bao).
- Blocks: `hcwa/impact-strip`, `hcwa/center-grid`, `hcwa/program-list`,
  `hcwa/report-list`, `hcwa/partner-wall`, `hcwa/news-latest`, `hcwa/donation-methods`,
  `hcwa/language-notice`, `hcwa/announcement-bar`. Category "HCWA".
- Block attributes (patterns may set these; anything else stays default):
  impact-strip `scopeType` (organization | center | program, default organization),
  `scopeId` (0 = current post), `limit` (4), `showLink` (true) ·
  center-grid `limit` (3) · program-list `status` (any | active | completed),
  `limit` (4), `centerId` (0 = all; on a center page, 0 means "this center") ·
  report-list `reportType` (any), `limit` (6) · partner-wall `homeOnly` (true),
  `limit` (12) · news-latest `featured` (true), `limit` (3) · language-notice
  `forceLang` ("" | vi | en) · donation-methods and announcement-bar: none.
  All blocks are dynamic (`render.php`, saved as self-closing comments). block.json adds
  enums matching these values (report-list `reportType`: any | annual | financial |
  program | policy); limits are clamped in render.php (impact-strip 1–12, center-grid
  1–24, program-list and report-list 1–50, partner-wall 1–48, news-latest 1–12).
  announcement-bar is not in the inserter (header.php prints it on every page) and
  donation-methods may appear once per page (its anchors and live region are unique).
  impact-strip, program-list and language-notice use the `postId` context for "0 = this
  post"; the theme renders them with `hcwa_theme_block()` (render_block() with the global
  post as context).
- Pattern slugs: `hcwa-2026/<name>-vi|en`, category `hcwa`. Names: hero-split,
  focus-areas, cta-band, ways-to-help, faq, contact-band, page-header, plus page
  compositions `page-home`, `page-our-work`, `page-get-involved`, `page-donate`,
  `page-contact`.
- Menu locations (theme): `primary`, `footer-explore`, `footer-trust`. Polylang creates a
  per-language assignment for each.

## URLs (localized-urls.php)

| Object | VI base | EN base |
|---|---|---|
| `hcwa_center` | `hoat-dong/co-so/{slug}/` | `en/our-work/centers/{slug}/` |
| `hcwa_program` | `hoat-dong/du-an/{slug}/` | `en/our-work/programs/{slug}/` |
| `hcwa_report` | `tac-dong/bao-cao/{slug}/` | `en/impact/reports/{slug}/` |
| category | `tin-tuc/{slug}/` | `en/news/{slug}/` |
| post | `{slug}/` | `en/{slug}/` (Polylang default) |

Pages keep normal hierarchical slugs per language (`/hoat-dong/`, `/en/our-work/`).
Logical page keys and their default paths mirror `prototype/src/routes.mjs` (home,
ourWork, impact, news, getInvolved, donate, volunteer, partner, inKind, contact, about,
ourStory, team, partners, policies).

## Meta keys (all registered with `register_post_meta`, `show_in_rest`, sanitize + auth callbacks)

"sync" = copied between translations via `pll_copy_post_metas`; others are per language.

- center: `hcwa_summary`, `hcwa_who_we_serve`, `hcwa_what_we_do` (one item per line),
  `hcwa_location`, `hcwa_help_text`, `hcwa_contact_name`; sync: `hcwa_since_year` (int),
  `hcwa_accent` (green-bamboo | little-rose | cau-han | blue), `hcwa_logo_id`,
  `hcwa_gallery_ids` (int[]), `hcwa_contact_approved` (bool), `hcwa_phone`, `hcwa_email`,
  `hcwa_news_tag` (term slug), `hcwa_no_identifiable_children` (bool, true for Little Rose).
- program: `hcwa_summary`, `hcwa_goals`, `hcwa_activities` (one per line); sync:
  `hcwa_status` (active | completed | planned), `hcwa_start_year`, `hcwa_end_year`,
  `hcwa_partner_ids` (int[]), `hcwa_center_id`, `hcwa_news_tag`.
- impact (title = label, per language): sync: `hcwa_value` (number|null), `hcwa_suffix`,
  `hcwa_year`, `hcwa_verified` (bool), `hcwa_verified_by`, `hcwa_verified_date`
  (Y-m-d), `hcwa_order`, `hcwa_scope_type` (organization | center | program),
  `hcwa_scope_id`; internal: `hcwa_source_note`.
- report: `hcwa_summary`; sync: `hcwa_year`, `hcwa_report_type` (annual | financial |
  program | policy), `hcwa_doc_language` (vi | en | both), `hcwa_file_id`.
- partner (featured image = logo): sync: `hcwa_website`, `hcwa_partner_type`
  (corporate | ngo | government | foundation | academic), `hcwa_active_years`,
  `hcwa_show_on_home` (bool), `hcwa_logo_permission` (bool).
- attachment: `hcwa_approved_for_web` (bool), `hcwa_identifiable_child` (bool).

IDs stored in sync meta point at the VI original; resolve with
`pll_get_post( $id, hcwa_lang() )` when rendering.

## Functions the core plugin provides (theme and blocks may call these)

```php
hcwa_is_production(): bool                 // wp_get_environment_type() === 'production'
hcwa_lang(): string                        // 'vi' | 'en'; 'vi' if Polylang is inactive
hcwa_t( string $key, array $vars = [] ): string        // current language, unescaped; {name} vars
hcwa_t_in( string $key, string $lang, array $vars = [] ): string
hcwa_number( float|int $n, int $decimals = 0 ): string // 300.000 (vi) / 300,000 (en)
hcwa_date( string $ymd ): string           // 08/02/2026 (vi) / 8 Feb 2026 (en)
hcwa_page_url( string $key, ?string $lang = null ): string
hcwa_language_links(): array               // ['vi' => [url, name, current, has_translation], 'en' => …]
hcwa_setting( string $key, $default = null )            // raw option value
hcwa_setting_l( string $key, ?string $lang = null ): string  // per-language variant (key_vi / key_en)
hcwa_get_impact_figures( array $args ): array           // args: scope_type, scope_id, limit
    // each: [ 'value' => ?float, 'suffix', 'label', 'year', 'verified' => bool, 'id' ]
    // production: unverified figures are never returned
hcwa_image_allowed( int $attachment_id, $context = null ): bool
hcwa_image( int $attachment_id, string $size, array $attr = [], $context = null ): string
    // '' when refused (not approved for web; or identifiable child in a no-identifiable context)
hcwa_strip_leading_emoji( string $s ): string
hcwa_donation_details(): ?array            // null unless finance-confirmed (confirmed_by + date set)

// Also provided by hcwa-core and used by the blocks, the theme or the seed (integration):
hcwa_number_in( float|int $n, string $lang, int $decimals = 0 ): string
hcwa_date_in( string $ymd, string $lang ): string
hcwa_language_name( string $lang ): string // endonym: 'Tiếng Việt' | 'English'
hcwa_page_keys(): array                    // the 15 page keys staff map in Settings → HCWA
hcwa_page_paths(): array                   // default paths, plus anchor keys with 'base'
hcwa_page_path( string $key, string $lang ): ?string
hcwa_lang_notice_request(): string         // '' | 'vi' | 'en' from ?hcwa_lang_notice=
hcwa_current_url(): string
hcwa_translation_id( int $post_id, string $lang ): int
hcwa_original_id( int $post_id ): int      // the VI original (what sync meta stores)
hcwa_original_term_slug( WP_Term $term ): string
hcwa_contact_emails(): array
hcwa_announcement(): ?array                // ['text', 'url'] or null
hcwa_partner_logo_allowed( int $partner_id, ?bool $production = null ): bool  // rule 5
hcwa_impact_strip_visible( array $figures, bool $production ): bool           // rule 1
hcwa_meta( int $post_id, string $key )     // typed read of a registered meta key
hcwa_meta_lines( int $post_id, string $key ): array
hcwa_id_list( $value ): array
hcwa_contains_draft( string $s ): bool     // rule 4
hcwa_content_images_gated( $post = null ): bool  // rule 2 for photos placed in content
hcwa_strings(): array                      // the whole dictionary (after the filters)
```

`scripts/wp-static-check.py` compares every signature in this block with the code.

`hcwa_t()` keys come from one dictionary, assembled by `hcwa_strings()`:

- `includes/strings-data.php`: `content/draft/ui.json`, generated by
  `scripts/build-wp-strings.py`. Never edited by hand; change ui.json and regenerate.
- core keys (`pay_*`) in `includes/strings.php`;
- block keys (`blk_*`) added by `includes/blocks.php` through the `hcwa_strings` filter;
- theme keys added by `inc/strings.php` through the same filter.

Filters are added before `init` and existing keys always win (array union), so a part can
never reword another part's key. The plugin's blocks never use theme keys (the plugin must
work with any theme), which is why `blk_type_*` / `rt_*` and `blk_catAnnouncement` /
`cat_announcement` exist side by side with the same text.

A missing key returns the key itself in non-production and '' on production, and is
logged once per request. Each key is registered with Polylang (`pll_register_string`,
group "HCWA UI") using the VI text as the source string; EN falls back to our dictionary
when Polylang has no EN translation saved, so the site is fully bilingual out of the box
and staff can still edit wording in Languages → Translations. Polylang keeps one
translation per source string, so when several keys share a Vietnamese text but differ in
English ('Hoạt động' is "Our Work", "Activities" and "What we do"), `hcwa_t()` always takes
their English from the dictionary (`hcwa_ambiguous_sources()`); `wp-static-check.py`
lists them as warnings. Give such keys distinct Vietnamese wording to make their English
editable in Polylang.

## Gating rules (CLAUDE.md, enforced in code)

1. Impact figures: unverified never render on production; non-production shows them with
   the dashed "Đang xác minh / Pending verification" chip. A strip with fewer than 2
   verified figures hides on production, on every scope (organization, center, program).
   "Verified" means the tick plus a "verified by" name and a valid date. Non-production
   shows a strip from 1 figure, with a note that the live site needs 2.
2. Images in center, program and story contexts render only if `hcwa_approved_for_web`.
   Little Rose context (`hcwa_no_identifiable_children`) also refuses
   `hcwa_identifiable_child`. Refusals leave no broken markup and log in admin.
   Templates and blocks use `hcwa_image()`. Photos placed in post content (core/image
   blocks, the patterns' image slots, classic HTML, cover and media-text) are gated by
   `hcwa_gate_image_block()` (`render_block_core/image`) and `hcwa_gate_content_img_tag()`
   (`wp_content_img_tag`) when `hcwa_content_images_gated()` is true: pages, centers,
   programs, reports, story posts and Little Rose news. Other news posts keep their inline
   photos (open question for HCWA, see Status). Only editors and administrators can tick
   the approval flags (`edit_others_posts`).
3. Donation details render only from finance-confirmed settings. Otherwise: production
   renders nothing (plus an admin notice); non-production renders the labelled
   placeholder panel from the prototype.
4. Production refuses to publish content containing `[DRAFT` (post goes back to draft,
   admin notice explains why). `wp hcwa draft-scan` lists every offender.
5. Partner logos need `hcwa_logo_permission` on production: `hcwa_partner_logo_allowed()`
   is the only gate, used by `hcwa/partner-wall` and the program page alike. Logos and the
   VietQR image are not child photos, so they use `wp_get_attachment_image()`, not the
   approval flag.
6. No social feed embeds. No ALL CAPS or `text-transform: uppercase` on Vietnamese.
7. Non-production pages carry `<meta name="robots" content="noindex, nofollow">` and the
   body class `hcwa-staging`, which turns on `[data-draft]` outlines and pending chips.

## Markup and CSS

Blocks and patterns output the same class names and structure as the prototype
components (`prototype/src/partials/components.mjs`, `layout.mjs`, `pages/*.mjs`), so
the theme's port of `prototype/src/styles/site.css` styles them without extra CSS. New
classes go in the owner's own stylesheet. Every visible string is bilingual. All output
is escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).

Agreed during integration:

- Every class printed by a block, a pattern or a theme template has a rule in
  `assets/css/site.css` or `patterns.css`, or is listed with its reason in
  `CSS_UNSTYLED` in `scripts/wp-static-check.py` (hooks kept for prototype parity, such
  as `.story-card--featured`, `.figure__year`, `.pay__qr`).
- Blocks print nothing when there is nothing to show on production, and a short
  `.empty-state[data-draft]` note on staging (the announcement bar and the language
  notice print nothing in both). The theme drops the heading around a block that printed
  nothing.
- The Our Work filter: `hcwa/center-grid` and `hcwa/program-list` mark their root
  `[data-filter-group]` and each item `[data-areas]` (focus area slugs without a `-vi` /
  `-en` suffix: education, protection, family, policy). The page pattern's chips are plain
  `?area=` links in `.filters`; `site.js` marks the current one (`aria-current`) and
  adds the "nothing in this area" message itself (`noResults` / `showAll`), because core
  block markup cannot carry `data-*` or `hidden`.
- Patterns carry no `data-draft` (core blocks would fail validation); draft copy is
  marked by its `[DRAFT: …]` text only. Patterns link with root-relative default paths
  from `hcwa_page_paths()`; `wp-static-check.py` checks every link. Changing a page slug
  in WordPress means editing those links in the page content.
- hreflang: hcwa-core adds `x-default` = the Vietnamese URL through
  `pll_rel_hreflang_attributes` (`hcwa_hreflang_x_default()`), as 07-BILINGUAL asks.
- The theme calls hcwa-core only through its `hcwa_theme_*` bridge
  (`inc/core-bridge.php`) or behind `function_exists()`, so it degrades instead of failing
  when the plugin is off. The plugin never calls theme functions.
- Settings → HCWA (option `hcwa_settings`) keys: `announcement_enabled`,
  `announcement_text_vi|en`, `announcement_link_vi|en`, `address_vi|en`, `phone`,
  `phone_tel`, `emails` (one per line), `zalo`, `facebook`, `map_url`,
  `registration_vi|en`, `page_map` (page key => VI page ID), `donation_*` (see
  `includes/settings.php`; `donation_confirmed_by` and `donation_confirmed_date` are only
  ever filled in by HCWA finance, never by a seed or script).

## Checks every agent runs before reporting

```
find wp -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v 'No syntax errors'
find wp -name '*.js' -print0 | xargs -0 -n1 node --check
php wp/tests/run.php
python3 -I scripts/wp-static-check.py
```
Never download or execute WordPress, WP-CLI, Composer packages or other external code.
Never `git commit` or push; the orchestrator commits.

## Status (integration, 5 Nov 2026)

### Built

All five parts are in the repo and fit together statically.

- **hcwa-core** (`wp/plugins/hcwa-core/`): content types and taxonomy, registered meta with
  meta boxes, media approval, localized URLs, Polylang integration (including hreflang
  x-default), Settings → HCWA, verified impact figures, the production publish guard,
  `wp hcwa coverage | draft-scan`, Vietnamese admin strings, and the nine `hcwa/*` blocks
  (`includes/blocks.php`, now required unconditionally from `hcwa-core.php`).
- **hcwa-2026** (`wp/themes/hcwa-2026/`): hybrid theme, `theme.json` tokens and fonts,
  header and footer with the VI / EN switch, templates for pages, posts, archives, centers,
  programs, reports, search, attachments and 404, `site.css` (prototype port), `site.js`.
- **Patterns**: 24 files, 12 VI/EN pairs, including the five page compositions.
- **Dev runtime**: `scripts/wp-local.sh`, `wp-router.php`, `wp-seed.php`, `wp-smoke.py`,
  `wp/README.md`. The seed now also places the impact strip and the report list on the
  Impact page and the partner wall on the Partners page.
- **Integration**: `scripts/wp-static-check.py` (0 errors) and
  `wp/tests/integration-test.php`. Changes made while fitting the parts together:
  - English for keys that share a Vietnamese source comes from the dictionary
    (`hcwa_ambiguous_sources()`), so a saved Polylang translation cannot relabel another
    key ('Hoạt động', 'Ủng hộ', 'Chính sách', 'Tình nguyện', 'Báo cáo thường niên').
  - Photos placed in post content are gated (rule 2 above). Before this, an unapproved
    photo picked into a pattern image slot or a center/program body would have rendered.
  - hreflang x-default on every template with a translation.
  - Our Work filter: `site.js` reads the pattern's plain `?area=` chips, marks the current
    one and adds the empty message; `noResults` / `showAll` are exported to `hcwaI18n`.
  - The program page gates partner logos with `hcwa_partner_logo_allowed()`, like the
    partner wall, instead of reading the flag and requiring photo approval.

Checks: `php -l` on 112 PHP files and `node --check` on 13 JS files are clean;
`php wp/tests/run.php` passes (blocks, core, theme, patterns and integration suites);
`python3 -I scripts/wp-static-check.py` reports 0 errors. Its warnings are the five
shared-source groups above, the unregistered bookkeeping meta keys (`_hcwa_publish_blocked`,
`hcwa_seed_id`) and nothing else. Each kind of fault the checker looks for was injected
into a scratch copy once and was reported.

### Unverified until WordPress runs

Nothing here has run inside WordPress, WP-CLI or a browser. In particular:

- Rewrite rules and links with Polylang in directory mode: CPT and category URLs in both
  languages, paged archives, canonical redirects, the activation and per-version flush.
- Polylang behaviour: translatable types forced on, meta sync (shared vs per-language
  keys), `pll_register_string` in Languages → Translations, `hcwa_language_links()` on
  every template, hreflang output with our x-default, per-language menus.
- Block registration (`register_block_type` from each folder, `hcwa-blocks-editor`
  dependency), the editor previews with `?lang=`, Vietnamese editor labels, PostSelect.
- Every pattern loading without an "invalid block" warning; contentOnly locking;
  full-width sections in the editor; the `:has()` rules in `patterns.css`.
- The content image gate on real content: core/image, gallery, cover and media-text
  output; legacy classic content; the refusal log.
- The publish guard on production (classic, quick edit, REST, scheduled), the meta box
  save path in both editors, the media modal approval checkboxes, REST protection.
- `theme.json` acceptance and font output, editor styles, sticky header and menus in
  real browsers; tastegate at 390 px and 1440 px.
- The seed and smoke test end to end (`wp-local.sh setup|seed|start|smoke`), then
  `wp hcwa coverage` and `wp hcwa draft-scan`.

### Open questions for HCWA (collected from the part reports)

- Rule 2 scope: should inline photos in ordinary news posts (including posts migrated
  from the current site) also need "Approved for web"? Today only pages, centers,
  programs, reports, stories and Little Rose news do (`hcwa_content_images_gated`).
- Should approving a photo require an explicit yes/no on "identifiable child"?
- Impact strips on center and program pages need 2 verified figures on the live site
  (rule 1), where the prototype showed one. Keep 2?
- Partner logos: permission only (as built), or permission plus photo approval?
- The shared-source strings above: give them distinct Vietnamese wording so staff can
  edit their English in Polylang?
- The English Cau Han URL is `/en/our-work/centers/cau-han-project/` (Polylang free does
  not share slugs across languages); programs run at several centers but store one.
- Donation details: entered in Settings → HCWA with "confirmed by" and date, or imported
  from `content/verified/` once it exists?
- Forms (task 2.7): Q9 and the form plugin.
