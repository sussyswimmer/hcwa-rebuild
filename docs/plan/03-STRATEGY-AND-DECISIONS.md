# Strategy and decisions

## The approach in one paragraph

Rebuild the front end, keep the engine. HCWA's brief says to keep the current site where
practical and to agree which ideas fit the current WordPress setup. So we keep WordPress,
its posts, media library, Polylang and URLs, and replace the Neve theme + Elementor page
layouts with a lean custom theme built for this site. HCWA's small team keeps editing in
the normal WordPress editor, now with ready-made blocks for every section. Before touching
WordPress, we build a clickable prototype so HCWA can approve the direction cheaply.

## Why not the alternatives

| Option | Verdict |
|---|---|
| Restyle inside Elementor + Neve | Cheapest start, but keeps 9+ plugins, 48 scripts per page, an old Elementor, and layouts that are hard for staff to edit without breaking. Fine for Phase 0 quick wins only. |
| New custom theme on the same WordPress (recommended) | Keeps content, URLs, logins and Polylang. Fewer plugins, faster pages, editing with locked block patterns so staff can't break layouts. |
| Static site (Astro/Next) on Netlify | Fast and cheap to host, but HCWA would lose the editor they know. Goes against "easy for our small team to update". Use it only for the prototype. |

## Stack

**Phase 1 prototype:** Vite + plain HTML/CSS/vanilla JS. Two locales (`/` Vietnamese,
`/en/` English). Content in JSON files. Deployed to Netlify with a private-ish URL.
Plain HTML on purpose: every section maps 1:1 to a WordPress block pattern later.

**Phase 2 onward: WordPress.**
- Theme `hcwa-2026`: a hybrid theme. `theme.json` holds all design tokens and the block
  editor palette/type scale; PHP templates render header, footer and archive layouts;
  page bodies are Gutenberg block patterns (locked where structure matters).
  Why hybrid instead of a full-site-editing block theme: Polylang free handles menus and
  strings per language reliably in PHP templates, while translated template parts in
  block themes may need Polylang Pro. Task 2.1 in the build plan checks this; if Polylang
  free handles it cleanly, a full block theme is fine.
- Plugin `hcwa-core`: registers content types (centers, programs, reports, impact
  figures, partners), their fields and the custom blocks that render them. Keeping these
  in a plugin means a future theme change does not lose content.
- Keep: Polylang, a single form plugin (whatever HCWA already uses or Fluent Forms /
  WPForms Lite), an SEO plugin (Yoast or Rank Math, both work with Polylang), a backup
  plugin, a security plugin if the host doesn't provide one.
- Remove after migration: Elementor, Essential Addons, Neve, Instagram Feed, Easy
  Facebook Likebox, Easy PayPal Donation, Ultimate Social Media Icons, Recent Posts
  Widget, Visitors Traffic stats, Content Views. Target: 8 plugins or fewer.
- Fonts self-hosted in the theme (no Google Fonts CDN call).
- Local dev: `@wordpress/env` (needs Docker) or the WordPress Studio app.

## Phases

| Phase | Output | HCWA involvement |
|---|---|---|
| 0. Quick wins + audit check | Fixes on the live site, mobile audit screenshots | Approve each fix |
| 1. Prototype | Clickable homepage (desktop + mobile), Our Work hub, one center page, one program page, Get Involved | Review link, give feedback, approve direction |
| 2. Theme + content model | `hcwa-2026` theme and `hcwa-core` plugin running locally with a copy of the real content | Supply verified text, numbers, photos |
| 3. Migration on staging | All pages rebuilt in patterns, translations complete, redirects in place | Content review page by page |
| 4. QA + launch | Passes `09-QA-CHECKLIST.md`, goes live, old plugins removed | Final sign-off |
| 5. Handover | 1-page editing guide (VI/EN) + 30-minute walkthrough | Staff training |

## Decisions HCWA needs to make

Ask these in the reply email. Q1 to Q4 block Phase 2.

1. **Theme change.** OK to replace Neve/Elementor with a new theme on the same WordPress,
   tested on a staging copy first? (Alternative: only restyle within Elementor.)
2. **Default language.** Vietnamese at `hcwa.org.vn/` and English at `hcwa.org.vn/en/`,
   with every old URL redirected? Today it's the reverse, and the VI homepage is at
   `/hoi-bao-tro-tre-em/`.
3. **Centers vs programs.** Please confirm this split (see `04-SITEMAP-AND-CONTENT-MODEL.md`):
   Centers = Green Bamboo, Little Rose, Cau Han. Programs = Thrive Together, Charity
   Class, the disability-rights pilot. Is Cau Han a center or a project? Does the Charity
   Class belong to Green Bamboo, Cau Han, or both?
4. **Access.** Admin account, hosting provider, and can we get a staging copy
   (e.g. `staging.hcwa.org.vn`) with backups?
5. **Verified numbers.** Founding year; total children supported; children per year;
   any outcome numbers per center (for example scholarships, school retention); which
   annual reports exist and in which language.
6. **Donations.** Which methods to show: VND bank transfer, VietQR codes, USD/EUR
   transfer, PayPal? Please re-confirm all account details and the account-holder name.
   Should Little Rose keep its own account on the site?
7. **Photos and stories.** Which existing photos have consent? Any photo policy we should
   follow? We propose no identifiable faces for Little Rose at all.
8. **Policies.** Do HCWA have a child safeguarding policy and a privacy policy we can
   publish (VI and EN)? If not, we can draft a short privacy notice for the forms for
   HCWA to check.
9. **Forms.** Which email receives each form: donations, partnerships, volunteers,
   media, general?
10. **Analytics.** Do they want visitor stats? If yes, a privacy-friendly option with
    consent.
11. **Spelling.** "Center" (US) or "Centre" (UK)? The current menu uses "Centre", the
    brief uses "centers".
12. **Who updates the site** after launch, and how comfortable are they with WordPress?
    This decides how locked the patterns should be.

## Risks

| Risk | Mitigation |
|---|---|
| Removing Elementor breaks old page layouts | Rebuild every Elementor page as patterns on staging before deactivating; old news posts are checked by script |
| Old links (partners, Google, CNCF) break after URL change | Full URL inventory + 301 map, tested with a script before launch |
| Unverified numbers reach production | Impact figures have a "verified" flag; unverified ones never render on production (build check) |
| A child is identifiable | Photo approval field on every media item; Little Rose uses illustrations only |
| Staff can't maintain it | Locked patterns, short editing guide, fewer plugins, no custom code they need to touch |
| Volunteer time runs out mid-project | Each phase leaves the site in a working state; Phase 0 and 1 already deliver value alone |
