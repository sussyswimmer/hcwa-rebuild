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
  All blocks are dynamic (`render.php`, saved as self-closing comments).
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
```

`hcwa_t()` keys come from `content/draft/ui.json` (the prototype's dictionary) plus any new
keys the theme or blocks need. A missing key returns the key itself in non-production and
logs it. Each key is registered with Polylang (`pll_register_string`, group "HCWA UI")
using the VI text as the source string; EN falls back to our dictionary when Polylang has
no EN translation saved, so the site is fully bilingual out of the box and staff can still
edit wording in Languages → Translations.

## Gating rules (CLAUDE.md, enforced in code)

1. Impact figures: unverified never render on production; non-production shows them with
   the dashed "Đang xác minh / Pending verification" chip. A strip with fewer than 2
   verified figures hides on production.
2. Images in center, program and story contexts render only if `hcwa_approved_for_web`.
   Little Rose context (`hcwa_no_identifiable_children`) also refuses
   `hcwa_identifiable_child`. Refusals leave no broken markup and log in admin.
3. Donation details render only from finance-confirmed settings. Otherwise: production
   renders nothing (plus an admin notice); non-production renders the labelled
   placeholder panel from the prototype.
4. Production refuses to publish content containing `[DRAFT` (post goes back to draft,
   admin notice explains why). `wp hcwa draft-scan` lists every offender.
5. Partner logos need `hcwa_logo_permission` on production.
6. No social feed embeds. No ALL CAPS or `text-transform: uppercase` on Vietnamese.
7. Non-production pages carry `<meta name="robots" content="noindex, nofollow">` and the
   body class `hcwa-staging`, which turns on `[data-draft]` outlines and pending chips.

## Markup and CSS

Blocks and patterns output the same class names and structure as the prototype
components (`prototype/src/partials/components.mjs`, `layout.mjs`, `pages/*.mjs`), so
the theme's port of `prototype/src/styles/site.css` styles them without extra CSS. New
classes go in the owner's own stylesheet. Every visible string is bilingual. All output
is escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).

## Checks every agent runs before reporting

```
find wp -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v 'No syntax errors'
find wp -name '*.js' -print0 | xargs -0 -n1 node --check
php wp/tests/run.php
```
Never download or execute WordPress, WP-CLI, Composer packages or other external code.
Never `git commit` or push; the orchestrator commits.
