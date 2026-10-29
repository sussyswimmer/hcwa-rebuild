# Page specs

Every page exists in VI and EN with identical structure. Copy in `[DRAFT: ...]` is
placeholder until HCWA approves. Section order follows the brief's suggested flow, with
two changes explained inline.

## Global: header

Desktop (≥1024px), 80px tall, shrinks to 64px after 120px of scroll:
`[logo] ··· About Us · Our Work · Impact & Reports · News · Get Involved · Contact ··· VI / EN  [Donate]`

Mobile (<1024px), 64px: `[logo]  ···  VI / EN  [Donate]  [☰]`
- The language switch and Donate stay visible outside the menu. These are the two
  things people look for on a phone.
- The menu opens as a full-height sheet with accordions for sub-items, focus trapped,
  closes on Escape and on link tap.

Announcement bar (optional, above header): one line, dismissible, e.g.
`[DRAFT: Tết 2027 charity fair: donate items before 15 Jan →]`.

## Global: footer

Four columns on desktop, stacked accordions on mobile:
1. Logo, one-line description, registration line `[DRAFT: legal status / decision number]`
2. Contact: address (post-2025 format, verified), phone (one format: +84 28 3840 1406),
   email(s), Zalo, map link
3. Explore: About, Our Work, Impact & Reports, News, Get Involved
4. Trust: Annual reports · Child safeguarding policy · Privacy notice · Partners
Bottom row: © {current year} HCWA · social icons (Facebook, Instagram, YouTube if any) ·
VI / EN switch again.

## Homepage

Brief's flow: hero → impact numbers → what we do → centers & projects → stories → partners
→ ways to help → contact. Kept, with one change: "Ways to help" comes before "Partners",
because a visitor who scrolled that far is ready to act and partners are proof that
supports the ask, so partners sit right under it.

### 1. Hero (must fit the first screen at 1440×900 and 390×844, buttons visible)
Desktop: 2-column split, 6/6 grid. Left: text. Right: one approved photo, 4:5 crop,
`--r-lg` radius.
- Eyebrow line (small, once only): `Hội Bảo trợ Trẻ em TP. Hồ Chí Minh · since [year]`
- H1 EN: "Helping children build safer, brighter futures." (HCWA's own example, kept)
- H1 VI: `[DRAFT: Cùng trẻ em xây dựng tương lai an toàn và tươi sáng hơn.]`
- Intro EN: "HCWA supports children, young people and families through education, child
  protection and community-based programs."
- Buttons: [Explore our work] (primary) [Get involved] (secondary). HCWA's example uses
  "Partner With Us" as the second button; we suggest "Get involved" because it covers
  donors, volunteers and partners in one tap. HCWA decides.
Mobile: photo first (3:2 crop, max 42vh), then H1, intro, buttons stacked full-width.

### 2. Impact strip (attached to the hero bottom, `--surface` panel)
3 to 4 verified figures in a row (2×2 on mobile). Label each in plain words.
Placeholders until verified, for example:
- `[300,000+]` children and young people supported since `[year]`
- `[N]` centers in Ho Chi Minh City
- `[N]` children supported in `[2025]`
- `[N]` partners in `[2025]`
Link under the strip: "See our impact and reports →".
If fewer than 2 figures are verified at launch, the strip hides and the hero stands alone.

### 3. What we do (focus areas)
Left column: H2 "What we do" + 2-sentence summary. Right column: 4 focus areas as a
2×2 list (icon, name, one sentence, link to Our Work filtered by that area). Not cards.
Focus areas (confirm): Education · Child protection · Family & community support ·
Policy & advocacy.

### 4. Our centers
H2 "Our centers" + one line: `[DRAFT: Places where children learn, heal and grow, every day.]`
3 center cards (unit color band, photo, since-year, who it serves). Desktop: 3 columns of
unequal width is fine; mobile: horizontal scroll-snap row with the next card peeking, plus
a "See all" link.

### 5. Projects & programs
H2 "Projects & programs". Program rows (status pill, years, name, summary, partners).
Show active ones first, max 4, then "All projects →". Visually different from centers on
purpose: rows vs photo cards. This is the brief's "clearly separate centers from
projects" ask.

### 6. Stories & news
H2 "Latest from HCWA". One featured story (large, 3:2 photo) + 3 latest news items (small).
Category label on each (Story / News / Event). "All news →".

### 7. Ways to help
H2 "How you can help". Four options, each icon/illustration + title + one sentence + link:
Donate · Volunteer · Partner with us (companies, schools, NGOs) · Give in kind / buy from
the Little Rose Bakery. On desktop a 4-column row is acceptable here since each item is a
different action; on mobile a 2×2 grid.

### 8. Partners
H2 "With thanks to our partners". One-tone logo wall (max 12, flagged "show on homepage"),
link to Partners page, plus "Become a partner →".

### 9. Contact band
Blue band: "Have a question or want to visit?" + [Contact us] + phone + email inline.
Then footer.

## Our Work hub (`/hoat-dong/`, `/en/our-work/`)
- Page header: H1, 2-line intro.
- Filter chips by focus area (works without JS as links to `?area=`).
- Section "Centers": center cards.
- Section "Projects & programs": tabs or filter Active / Completed, program rows.
- Short note at bottom explaining the difference: centers are long-term places HCWA runs,
  programs are funded projects with a start and end.

## Center page template
1. Header: unit color band, breadcrumb, H1, unit logo, since-year, location (ward/city).
2. Cover photo (or illustration for Little Rose), 16:9 desktop, 4:3 mobile.
3. "At a glance" box: who we serve · what we do (3 to 5 bullets) · since · contact.
4. Our story (short, max ~250 words per language; longer history in an expandable
   "Read the full history" section).
5. Verified figures for this center (same impact component, scoped).
6. Gallery (approved photos only, 3:2, lightbox with keyboard support).
7. Programs run here (program rows).
8. How to help this center (specific needs, link to donate with the center preselected
   in the form; Little Rose links the bakery).
9. Related news (by tag, 3 items).
10. Contact for this center (only details HCWA approved) + CTA band.

## Program page template
1. Header: status pill, years, H1, partner logos (small, one tone).
2. Summary (2 to 3 sentences).
3. Goals (list) · Activities (list) · Results (verified figures only).
4. Where it runs (linked center, if any) and who funds it.
5. Documents (report rows).
6. Related news.
7. CTA: "Support work like this →" or "Partner on a program →".

## Impact & Reports (`/tac-dong/`, `/en/impact/`)
1. H1 + one sentence on how numbers are counted `[DRAFT, HCWA to write]`.
2. Headline figures (organization scope).
3. "By year": a simple table or year tabs with 3 to 5 figures per year. Plain table,
   not a chart, unless HCWA supplies a series of 5+ years (then one bar chart).
4. Stories of change: 2 to 3 stories.
5. Reports library: report rows, filters by type and year, language badge on every PDF.
6. Transparency: who audits/approves accounts `[if HCWA wants to state it]`, link to
   safeguarding policy.

## Get Involved hub + subpages
Hub: 4 options (same as homepage section) + FAQ accordion (5 to 8 questions, e.g. "Can I
visit a center?", "Do you issue receipts?", "Can my company volunteer as a team?").

**Donate.** Order: why it matters (1 line + 2 to 4 verified "what your gift does" examples,
carried over from the current Support Us page once HCWA re-confirms the amounts) →
methods in tabs or an accordion:
- In Vietnam: bank transfer details + VietQR image + copy buttons for account number
  and transfer note. Reference format: `[DRAFT: UNGHO + phone]` so HCWA can reconcile.
- From abroad: USD/EUR transfer + SWIFT, PayPal link (plain button, no PayPal image art).
- Give to a specific center (Little Rose has its own account today; HCWA decides).
- "Tell us you donated" short form so HCWA can thank and receipt.
All details come from `content/verified/donation.json` only.

**Volunteer.** Who we look for (from current page: on projects, or in communications,
fundraising, sponsorship relations), what to expect, child-safety note (background
expectations, code of conduct), form.

**Partner with us.** For companies/schools/NGOs: ways to partner (program funding,
employee volunteering, in-kind, events), 2 to 3 partner examples with logos (approved),
form or direct email to sponsorship relations.

**In-kind gifts.** What's needed (list editable in admin), "contact us before sending",
drop-off address, Little Rose Bakery link.

## News archive and post
- Archive: category filter (All / News / Stories / Events), 12 per page, numbered
  pagination, cards with image, category, date, title.
- Post: breadcrumb, category, date (localized), H1, lead image, body (max 72ch), share
  links (Facebook, Zalo, copy link), related posts, CTA band. If the post has no
  translation, the switcher shows the language notice instead of dead-ending.
- Stories template adds: "Names have been changed to protect the children's privacy." /
  "Tên nhân vật đã được thay đổi để bảo vệ trẻ."

## About pages
- Who we are: mission, vision, values (from current About page), what HCWA is legally
  (voluntary social organization, under which authority: verify), focus areas, timeline
  (founding year → Little Rose 1992 → Green Bamboo 1993 → Cau Han 1997 → 2020 Green
  Bamboo model change → recent; all years verified).
- Team: leadership and staff cards (photo 1:1 optional, name, role). Only people who
  agree to be listed.
- Partners & sponsors: grouped by type, logos one tone, years.
- Policies: safeguarding, privacy notice, terms. PDF + HTML summary.

## Contact
- Form: name, email, phone (optional), topic (Donate / Partner / Volunteer / Media /
  Other: routes to the right inbox), message, consent checkbox
  `[DRAFT: I agree that HCWA may use these details to reply to me. Privacy notice →]`.
  All labels, errors and the success message in the page language.
- Office cards: HQ + each center (only approved details), map link (no embedded Google
  Map iframe by default; it loads tracking and slows the page. A static map image linking
  to Google Maps works.)
- Zalo + Facebook Messenger links.

## 404
Friendly line in the current language, search box, links to Home, Our Work, Contact.
