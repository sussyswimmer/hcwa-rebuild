# HCWA website rebuild: context for Claude Code

You are helping rebuild the website of the Ho Chi Minh City Child Welfare Association
(Hội Bảo trợ Trẻ em TP. Hồ Chí Minh, "HCWA"), a voluntary social organization that has
supported disadvantaged children in Ho Chi Minh City for 30+ years. Current site:
https://hcwa.org.vn (WordPress + Neve theme + Elementor + Polylang).

The work is volunteer work by Maxwell for HCWA. HCWA approves all wording, numbers, photos
and donation details before anything goes live.

## Read these first, in order

1. `docs/plan/00-README.md`: what each file is for
2. `docs/plan/03-STRATEGY-AND-DECISIONS.md`: the approach and what is still undecided
3. `docs/plan/08-BUILD-PLAN.md`: the phase you are working on and its acceptance criteria
4. Then the spec files the current phase points to

## Hard rules (never break these)

- Never publish or hard-code an impact number, founding year, statistic, outcome or quote
  that HCWA has not verified. Use the placeholder system in `docs/plan/05-DESIGN.md` and
  `docs/plan/06-PAGES.md`. Unverified figures must not render on production.
- Never publish bank account numbers, QR codes, account-holder names or PayPal addresses
  that HCWA finance has not re-confirmed in writing. Copy them only from the
  `content/verified/` folder once it exists.
- Child safeguarding: no child's full name, school, home location or identifiable face
  on any page unless HCWA has marked that photo/story as approved. Residents of Little Rose
  Warm Shelter (a shelter for girls who survived or are at risk of sexual abuse) are never
  identifiable, full stop. Use illustrations or non-identifying photos for that page.
  Story subjects get changed names plus a visible note saying so.
- Do not embed live social feeds (Instagram/Facebook auto-feeds). They pull unreviewed
  photos of children onto the site and break when the API changes.
- Every UI string exists in Vietnamese and English. No mixed-language forms, buttons or
  error messages. See `docs/plan/07-BILINGUAL.md`.
- Vietnamese is the primary language. Never set Vietnamese text in ALL CAPS (it breaks
  diacritic readability). Body line-height 1.6 or more.
- Keep the current WordPress install, its posts and its URLs. We rebuild the theme and
  page layouts, not the CMS. Every old URL that changes gets a 301 redirect.
- Clarity over effects. No 3D, no scroll-jacking, no parallax, no animated counters on
  impact numbers. Motion rules are in `docs/plan/05-DESIGN.md`.

## Skills to use

- `frontend-design` and `god-site-skill:design-taste-frontend` for visual decisions
  (with dials set to variance 4, motion 2, density 4: this is a trust-first site)
- `god-site-skill:tastegate` to render and check every page at 390px and 1440px
- `god-site-skill:design-token-contrast` on the palette before building components
- `god-site-skill:frontend-seo` and `god-site-skill:frontend-lighthouse` before launch
- `anti-slop` for any English copy you draft (HCWA will still approve the final wording)
- Do NOT use the 3D, shader, cinematic or scroll-story skills from god-site-skill here

## Writing placeholder copy

When you need text HCWA has not supplied, write it short and plain, and wrap it so it
is obvious: `[DRAFT: ...]` in markdown and a `data-draft` attribute plus a visible dashed
outline in the prototype. Never invent stats, partner names, testimonials or awards.

## Tech defaults (see `docs/plan/03-STRATEGY-AND-DECISIONS.md` for the reasoning)

- Phase 1 prototype: Vite + plain HTML/CSS/vanilla JS, two locales (`/` = vi, `/en/`),
  deployed to Netlify for HCWA review
- Phase 2+: WordPress hybrid theme `hcwa-2026` (PHP header/footer, `theme.json` tokens,
  Gutenberg block patterns for page bodies) + small plugin `hcwa-core` for content types
- Local WordPress: WordPress Playground via `cd wp && npm run dev` (no Docker or PHP needed;
  seeds the draft content in both languages; `npm run dev:production` for production rules).
  `wp/.wp-env.json` is there for anyone with Docker.
- Polylang stays as the language plugin
- PHP 7.4 compatible code: the live host runs PHP 7.4.33. `npm run lint:php` checks it.

## Working on the WordPress code (`wp/`)

- `plugins/hcwa-core`: content types, fields, photo approval, figures, blocks, checks.
  `themes/hcwa-2026`: layout only. Templates call `hcwa_*` helpers from the plugin.
- Design system: edit `prototype/src/styles/*.css` and `prototype/src/main.js`, then run
  `npm run sync` in `wp/`; never edit the copies in the theme. WordPress-only CSS goes in
  `themes/hcwa-2026/assets/css/wp.css`.
- UI strings: add them to `content/draft/ui.json` (both languages), then `npm run sync`.
  Templates use `hcwa_t('key')`; never hard-code a visible string.
- Review vs production: `wp_get_environment_type()`. Anything not `production` shows drafts
  outlined and unverified figures with a "Pending verification" chip; production hides them.
  Blocks have a "Draft" switch; centers, programs and reports a "wording is a draft" field.
- Before committing: `npm test`, `npm run lint:php`, and with `npm run dev` running,
  `npm run check:pages`, `npm run check:editor` (and `check:production` against
  `dev:production`). Set `CHROME_PATH` if playwright-core's own Chromium isn't installed.
