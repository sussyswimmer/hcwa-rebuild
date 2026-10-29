# Build plan for Claude Code

Work one phase at a time. At the end of each phase, stop, summarize what changed, list
anything still `[DRAFT]` or pending verification, and wait for Maxwell.

Repo layout to create:

```
hcwa-site/
├── CLAUDE.md, 00..10 plan files (this folder)
├── content/
│   ├── draft/            # placeholder copy, clearly marked
│   └── verified/         # ONLY text/numbers/donation details HCWA approved, with date
├── prototype/            # Phase 1 (Vite)
├── wp/
│   ├── themes/hcwa-2026/
│   └── plugins/hcwa-core/
├── scripts/              # url inventory, redirect tests, coverage, image tools
└── qa/                   # tastegate output, lighthouse reports, screenshots
```

---

## Phase 0: audit check + quick wins on the live site

Goal: fix the worst problems now, inside the current WordPress, and finish the audit.
Everything here needs HCWA admin access and HCWA's OK per item. Take a full backup first.

| # | Task | Done when |
|---|---|---|
| 0.1 | Mobile audit: render the 10 main pages (VI + EN) at 390×844 and 1440×900 with Playwright; save to `qa/before/`. Note overlaps, tiny text, sideways scroll, menu behavior. Add findings to `01-AUDIT.md`. | 20 screenshots + findings list |
| 0.2 | URL inventory: crawl the live site (sitemap.xml + links) and write `scripts/urls-live.csv` (url, language, title, status, translation link). | CSV lists every public URL |
| 0.3 | Remove the Instagram feed block from the EN homepage. | Not on any page |
| 0.4 | VI homepage SEO title → "Hội Bảo trợ Trẻ em TP. Hồ Chí Minh (HCWA)"; EN → "Ho Chi Minh City Child Welfare Association (HCWA)"; add meta descriptions. | Titles correct in page source |
| 0.5 | Create VI "Ủng hộ" page (translation of Support Us) and add to VI menu. Link missing translations (Cau Han VI ↔ EN `/chp-2/`). | Switcher works on those pages |
| 0.6 | Footer: dynamic year, remove "Neve / Powered by WordPress", one address format (HCWA-verified post-2025 address), one phone format, VI footer in Vietnamese. | Same footer text on all pages |
| 0.7 | Replace the 200×200 test-logo social image with a 1200×630 image (agency asset A3, or a simple logo-on-paper version for now). | Facebook sharing debugger shows it |
| 0.8 | Alt text on homepage images (VI and EN). | 0 images without alt on home |
| 0.9 | Little Rose EN page: replace the 2021-2022 / Covid appeal with HCWA-approved current text. | No outdated years |
| 0.10 | Update WordPress plugins and Elementor on a backup-first basis. | Up to date, site still renders |

---

## Phase 1: clickable prototype for HCWA review

Goal: the "first homepage design (desktop and mobile)" from HCWA's brief, plus enough
inner pages to judge the system. Static, fast to change.

| # | Task | Done when |
|---|---|---|
| 1.1 | Tokens: sample brand blue and unit accents from the logo files; confirm Vietnamese subsets for Fraunces + Be Vietnam Pro; run `design-token-contrast`. Update `05-DESIGN.md`. | All text pairs ≥ 4.5:1 (≥ 3:1 for large text) |
| 1.2 | Scaffold Vite project in `prototype/` with `/` (vi) and `/en/` builds from shared partials + `content/*.json`. Self-hosted fonts. | `npm run dev` shows both locales |
| 1.3 | Components from `05-DESIGN.md`, on a `/components/` page with every state. | All states visible |
| 1.4 | Homepage per `06-PAGES.md`, both languages. | Hero + CTAs inside first screen at 390×844 and 1440×900 |
| 1.5 | Our Work hub, 1 center page (Green Bamboo), 1 program page (Thrive Together), Get Involved → Donate (with placeholder payment panel, no real numbers), Contact (form UI only). | Pages link to each other in both languages |
| 1.6 | Impact placeholders: `content/impact.json` with `verified:false`; prototype shows "Pending verification" chips. Add a `PROD=1` build mode that omits unverified figures and fails the build if a `[DRAFT` string or a donation placeholder would ship. | `PROD=1 npm run build` fails on drafts |
| 1.7 | Placeholder images: use neutral tinted blocks with the asset code from `10-ASSET-REQUEST.md` printed on them (e.g. "A2 · Hero · 4:5"), so HCWA's agency sees exactly what goes where. No stock photos of children. | Every image slot labeled |
| 1.8 | Run `tastegate` at 390 and 1440 on every prototype page; fix all fail findings. | Gate exits 0 |
| 1.9 | Deploy to Netlify; send Maxwell the link + 6 screenshots (home, center, program × desktop/mobile). | Link works on a phone |

---

## Phase 2: WordPress theme + content model (local)

| # | Task | Done when |
|---|---|---|
| 2.1 | Spike (half a day): in `wp-env`, install Polylang free and test (a) a block theme with translated header/footer template parts and (b) the hybrid approach (PHP header/footer + block patterns). Pick one, write the reason in `03-STRATEGY-AND-DECISIONS.md`. | Decision recorded |
| 2.2 | Import a copy of the live site (full clone via the host's staging or Duplicator / All-in-One WP Migration). | Local site has all posts, media, users |
| 2.3 | `hcwa-core` plugin: register CPTs (center, program, impact, report, partner), taxonomies (focus area), fields (native `register_post_meta` + a simple meta box, or ACF free if HCWA already uses it), media approval fields, Polylang registration for CPTs and strings, `wp hcwa coverage` command. | CPTs translatable; coverage command runs |
| 2.4 | Theme `hcwa-2026`: `theme.json` from tokens (palette, font sizes, spacing, layout widths), self-hosted fonts, header/footer, templates: front-page, page, single-hcwa_center, single-hcwa_program, archive-hcwa_report, home/archive (news), single (post), search, 404. | All templates render with real content |
| 2.5 | Custom blocks (server-rendered, in `hcwa-core`): Impact strip, Center grid, Program list, Report list, Partner wall, Language notice, Announcement bar. Each has a simple sidebar control (scope, count, filters). | Editors insert them from the inserter |
| 2.6 | Block patterns (in theme): Hero split, Focus areas, CTA band, Ways to help, FAQ, Contact band, Page header. Lock structure (`templateLock: "contentOnly"` where layouts must not break). | Editor can change text/images but not break layout |
| 2.7 | Forms: one form plugin, VI and EN forms with consent checkbox, routing by topic, spam protection without Google reCAPTCHA if possible (honeypot or Cloudflare Turnstile). | Test submissions reach the right inbox in both languages |
| 2.8 | Performance baseline: no jQuery on the front end unless a kept plugin needs it, no Elementor assets, images through `srcset`. | Home < 1 MB, < 15 requests excl. images |

---

## Phase 3: migration on staging

| # | Task | Done when |
|---|---|---|
| 3.1 | Staging site (staging.hcwa.org.vn or host's staging) with `noindex` and password. | HCWA can log in |
| 3.2 | Rebuild every Elementor page as blocks/patterns using verified content from `content/verified/`. | Elementor not used by any published page (`wp post list` meta check) |
| 3.3 | Check old news posts render without Elementor (script lists posts with `_elementor_data`). Fix or convert those. | 0 broken posts |
| 3.4 | Redirects: build the full map from `scripts/urls-live.csv` → new URLs (Redirection plugin or server rules). Script `scripts/test-redirects` requests every old URL and expects a single 301 to a 200. | 100% of old URLs resolve |
| 3.5 | Translations: complete EN/VI pairs; `wp hcwa coverage` = 0 for pages and CPTs. | Coverage clean |
| 3.6 | Media: fill approval fields; replace unapproved images; alt text VI + EN. | No unapproved image renders |
| 3.7 | HCWA content review, page by page, both languages. Track in a shared checklist. | HCWA signs off each page |

---

## Phase 4: QA and launch

| # | Task | Done when |
|---|---|---|
| 4.1 | Run every gate in `09-QA-CHECKLIST.md`. | All pass, reports in `qa/` |
| 4.2 | Launch plan: freeze content 24h, backup live, switch theme, activate redirects, remove old plugins, clear caches, submit sitemaps in Google Search Console (both languages). | Live |
| 4.3 | Post-launch: re-run redirect test and tastegate against production; watch 404 log for 2 weeks and add missing redirects. | 404 log clean |

## Phase 5: handover

| # | Task | Done when |
|---|---|---|
| 5.1 | Editing guide (VI + EN, 1 to 2 pages each, screenshots): add a news post in both languages, update an impact number and its verified flag, add a report PDF, change the announcement bar, add a partner logo. | Guide delivered |
| 5.2 | 30-minute walkthrough with HCWA staff. | Done |
