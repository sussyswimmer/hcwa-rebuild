# QA checklist

Run on the prototype at the end of Phase 1 and on staging before launch. Save every report
in `qa/{date}/`.

Automated for WordPress (Phase 2): `wp hcwa audit` (or Tools → HCWA checks) covers the
content and safety blockers; `wp hcwa coverage` the bilingual counts;
`wp/scripts/check-pages.mjs` (`--base=<staging URL> --production` for the live rules) the
per-template checks at 390 and 1440. The items they don't cover still need a person.

## Content and safety (blockers)
- [ ] No impact figure renders with `verified = no` on production
- [ ] No `[DRAFT` text anywhere in production HTML (grep the built pages / crawl staging)
- [ ] Donation details match `content/verified/donation.json`, confirmed by HCWA finance with a date
- [ ] No image with `approved_for_web = no` renders; Little Rose shows no identifiable child
- [ ] Stories carry the "names changed" line
- [ ] No social media auto-feed embeds
- [ ] Privacy notice and safeguarding policy linked in the footer in both languages

## Bilingual
- [ ] `wp hcwa coverage` reports 0 missing pages/CPTs/menu items/strings
- [ ] Walk EN: home → a center → a program → donate → contact form submit. No Vietnamese UI on the way (only proper names)
- [ ] Walk VI: same path. No English UI on the way
- [ ] Switch stays on the same page everywhere; untranslated news shows the language notice
- [ ] `lang` on `<html>`, `hreflang` pairs + `x-default` on every template
- [ ] Dates and numbers formatted per locale
- [ ] Form errors, success messages and visitor notification emails in the right language
- [ ] Every PDF link shows its language

## Design (tastegate)
- [ ] `python3 skills/tastegate/scripts/gate.py <url> --out qa/tastegate-<page>` exits 0 for every template at 390×844 and 1440×900
- [ ] Hero headline + both buttons visible in the first screen on both sizes
- [ ] No horizontal scroll at 320px
- [ ] Looked at the screenshots: signature detail (unit color bands) visible, nothing reads as a default
- [ ] No ALL CAPS Vietnamese, no emoji in UI

## Accessibility (WCAG 2.2 AA)
- [ ] Contrast: all text ≥ 4.5:1 (large ≥ 3:1), focus ring ≥ 3:1
- [ ] Keyboard only: menu, mobile menu, language switch, accordions, tabs, gallery lightbox, forms all usable; focus visible; Escape closes overlays
- [ ] Skip link, landmarks, one H1 per page, logical heading order
- [ ] Every image has alt text (or `alt=""` if decorative), in the page language
- [ ] Form labels, `aria-describedby` for errors, error summary on submit
- [ ] Touch targets ≥ 44×44px
- [ ] `prefers-reduced-motion` removes the fade-ups
- [ ] Automated: axe-core run on every template, 0 serious/critical

## Performance (mid-range Android, 4G throttling, Lighthouse mobile)
- [ ] LCP < 2.5s, CLS < 0.1, INP < 200ms on home, a center page, news archive
- [ ] Homepage transfer < 1 MB; fonts ≤ 4 files
- [ ] Images: AVIF/WebP, `srcset`, `loading="lazy"` below the fold, hero `fetchpriority="high"`
- [ ] No unused plugin assets (Elementor, PayPal pixel, social bar) on any page
- [ ] Lighthouse: Performance ≥ 90, Accessibility ≥ 95, Best Practices ≥ 95, SEO ≥ 95

## SEO (`frontend-seo`)
- [ ] Unique title + meta description per page per language
- [ ] Open Graph image 1200×630 per page type, per language
- [ ] XML sitemaps per language submitted to Search Console
- [ ] `Organization` / `NGO` schema.org JSON-LD on home with name in both languages, logo, address, sameAs (social)
- [ ] All old URLs 301 to their new home (redirect test passes)
- [ ] Staging is `noindex`; production is not

## Function
- [ ] Each form submits, routes to the right inbox, shows success, sends visitor confirmation; consent is required
- [ ] Copy buttons on donation details work and announce "Copied"
- [ ] Announcement bar can be turned on/off by an editor
- [ ] 404 page works in both languages
- [ ] Console: 0 errors on every template
- [ ] Backups scheduled and one restore tested on staging
