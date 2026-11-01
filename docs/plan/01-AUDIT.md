# Audit of the current site (hcwa.org.vn)

Reviewed 29 Oct 2026 in a desktop browser plus page-by-page HTML reads. On 30 Oct 2026,
10 main pages were measured at phone and desktop size (task 0.1, "Mobile audit" below) and
every public URL was inventoried (task 0.2, "URL inventory" below).

## What they have today

**Platform.** WordPress with the Neve theme, Elementor 3.21.4 as page builder, Polylang
for VI/EN. Plugins seen on the homepage: Elementor, Essential Addons for Elementor,
Content Views, Instagram Feed, Easy Facebook Likebox, Easy PayPal Donation, Ultimate
Social Media Icons, Recent Posts Widget with Thumbnails, Visitors Traffic Real Time
Statistics. The homepage loads 48 scripts and 27 stylesheets.

**Structure (English menu).**
About Us (Who We Are, Our Team, Our Sponsors) · Projects (Green Bamboo Centre → The
Charity Class, Little Rose Warm Shelter, Cau Han Project, Thrive Together Project) ·
News · Support Us · Contact Us · language switcher.

**Structure (Vietnamese menu).**
Về chúng tôi (Chúng tôi là ai, Đội ngũ, Nhà tài trợ) · Dự án (Cơ sở BTXH Tre Xanh →
Lớp học tình thương, Mái ấm Hoa Hồng Nhỏ, Dự án Cầu Hàn, Chương trình Cùng Vươn Lên) ·
Tin mới · Liên hệ · language switcher. There is no Support/Ủng hộ item in the VI menu.

**Homepage sections (EN).** Blue-tinted photo hero with org name, tagline "Helping others
by giving", a paragraph, and a PayPal "Donate Now" image button → "Our Projects" (4 cards)
→ "How can you support our association?" band with "Help now" → embedded Instagram feed.
The VI homepage adds a Vision block, the 3 latest news posts and a contact form.

**Content that is in good shape.**
- News is active and bilingual for recent posts: early-2026 items (disability-rights
  forum, policy workshop, Tết fundraising fair, Philanthropy Asia Summit) exist in both
  languages. 16 pages of English news.
- Mission, vision and values are written down (About page, VI).
- Each center/project page has a story, goals, activities and a named contact.
- Support Us lists concrete giving amounts and three ways to help (donate, partner,
  volunteer).
- Logo, name and brand blue are recognizable and worth keeping.

## Problems, grouped

### 1. First impression and clarity
- The hero leads with the org name and a dense paragraph in small, letter-spaced white
  text over a busy photo. A first-time visitor learns "who" but not "what you do for
  children" or "what changed because of it".
- The EN hero's only button is a PayPal image. Asking for money before explaining the
  work is the wrong order, and the PayPal art looks dated.
- The only impact claim ("30+ years, 300,000+ children") sits in a paragraph. The VI About
  page says "gần 30 năm" (nearly 30 years), so the two pages disagree.

### 2. Centers vs projects are mixed together (the brief's main ask)
- "Projects" contains both permanent units (Green Bamboo center, Little Rose shelter) and
  time-bound programs (Thrive Together, funded under CapitaLand's community resilience
  initiative). Cau Han sits in between.
- The cards use logos instead of photos, and the Thrive Together card shows the funder's
  logo (CapitaLand), so it reads as a sponsor ad.
- A program visible in the news (the pilot project on the rights of children with
  disabilities) is missing from the menu entirely.

### 3. Bilingual experience
- English is the root homepage (`/`), while Vietnamese lives at `/hoi-bao-tro-tre-em/`
  and that page's title tag is "Đội Ngũ - HCWA" ("Team"), so Google and browser tabs show
  the wrong name for the main VI page.
- Translations are missing in places, so the switcher dead-ends: the VI Cau Han page has
  no EN link, and the EN Support Us page has no VI version.
- Mixed language inside one page: the VI contact form's field is labelled "Message"; the
  footer on VI pages is English ("Contact Us", "Powered by").
- Same project, several URLs: Thrive Together is linked as `/dva`, `/dva/` and
  `/dva-child-abuse-prevention-project/` in different places; Cau Han EN is `/chp-2/`.
- Flag icons as the language switch (flags are countries, not languages).

### 4. Out-of-date content
- Little Rose (EN) asks for help with "goals for 2021-2022" and cites Covid-19.
- About (VI) last edited 2022; Support Us last edited May 2022.
- Footer: "© HCWA, 2019" and "Neve | Powered by WordPress".
- The address appears in three forms. Footer: "Ward 19, Thanh My Tay Ward" (old and new
  ward mixed). About: "Phường 19, Quận Bình Thạnh". Homepage: "Phường Thạnh Mỹ Tây".
  Since 1 July 2025, Wards 19, 22 and 25 of Bình Thạnh were merged into Thạnh Mỹ Tây
  ward and districts no longer exist, so only the new form is current. Center addresses
  in District 7 likely need the same update.
- Phone written four different ways across pages.
- The About page says HCWA is managed by the city's Department of Labour, Invalids and
  Social Affairs. Government departments were restructured in 2025; HCWA should confirm
  the current name.

### 5. Trust, safety and compliance
- The embedded Instagram feed on the EN homepage shows placeholder images (broken) and
  posts from 2023 to 2024. One embedded post names a Little Rose resident and her school
  achievement. Little Rose serves girls who survived or are at risk of sexual abuse, so
  this is a safeguarding risk and should come down now, regardless of the redesign.
- No child safeguarding policy, privacy policy or annual report is linked anywhere.
  Peer NGOs put all three in the footer.
- Bank details on Support Us include a personal name as the account holder. Fine if
  correct, but HCWA finance should re-confirm before we carry them over.
- Contact forms collect name, phone and email with no consent line. Vietnam's Personal
  Data Protection Law (Law 91/2025/QH15) has applied since 1 Jan 2026 and is consent-based.
  (Not legal advice; HCWA should confirm what they need.)

### 6. Technical
- 17 of 32 images on the homepage have no alt text.
- Social share image is a file named `Test-Logo-200x200-1.png` (200×200), so links shared
  on Facebook/Zalo look broken.
- ALL CAPS headings and menus in Vietnamese ("CƠ SỞ BẢO TRỢ XÃ HỘI TRE XANH") hurt
  readability with stacked diacritics.
- A floating social icon bar sits over the content on every page.
- PayPal tracking pixels load on every page from the donation plugin.
- Elementor 3.21.4 dates from 2024; plugin updates are behind.
- The server runs PHP 7.4.33 (response header, checked 1 Nov 2026), which stopped getting
  security fixes in November 2022. WordPress itself is current (7.1.4) and the host uses
  LiteSpeed with its page cache. Ask the host to move to PHP 8.2 or newer; the new theme
  and plugin work on both.

## Mobile audit (task 0.1, 30 Oct 2026)

`scripts/live-audit.py` rendered 10 pages at 390×844 (iPhone user agent, touch) and
1440×900: home, About, Green Bamboo, Thrive Together, and Support Us (EN) / Liên hệ (VI),
in both languages. Full-page screenshots are in `qa/before/`, measurements in
`qa/before/report.json`. Photos are blurred and bank details greyed out in the screenshots
because this repository is public (see `qa/before/README.md`). Little Rose was left out on
purpose. This is a phone-sized browser, not a real handset; check the worst items on a real
phone before reporting them to HCWA.

Worst first:

1. **Both homepages scroll sideways, on phones and on desktop.** The Easy PayPal Donation
   form (`form.wpedon-form` inside an Elementor shortcode) is wider than the screen: 75px
   too wide on the EN home and 66px on the VI home at 390px, and 416px / 431px too wide at
   1440px. On a phone the whole page can be dragged sideways. The EN hero title also
   breaks out of the screen ("ASSOCIATION" is wider than 390px at that size). The inner
   pages measured have no sideways scroll.
2. **Nearly every tap target on a phone is under 44px** (the bar in `09-QA-CHECKLIST.md`):
   83 of 106 links and buttons across the 10 pages, and all 6 on most inner pages.
3. **The EN homepage has no button in the first phone screen.** The first 844px is the name
   in five lines of capitals, the tagline and a seven-line paragraph; the only button (the
   PayPal image) starts below it. The VI home does show "Tìm hiểu thêm về CHÚNG TÔI".
4. **The floating social bar covers content.** On a phone it sits on top of the Liên hệ
   form fields; on desktop it overlaps the right edge of the page content.
5. **Phone menu.** It opens as a full-height side panel and works, with every sub-item
   already expanded (11 links in VI, 14 in EN). The EN menu ends with "English" and
   "Tiếng Việt" plus flags; the VI menu shows flags only, no words. Every VI item is typed
   in capitals with wide letter-spacing, and "CƠ SỞ BẢO TRỢ XÃ HỘI TRE XANH" runs into its
   arrow. The VI menu ends with an English "Donate with PayPal" button and has no Ủng hộ
   item. The same PayPal image sits in the header of every page in both languages.
6. **ALL CAPS Vietnamese on every VI page measured**, set by CSS: 15 elements on the home
   page, 7 on About, 3 on Green Bamboo, 2 on Thrive Together, 3 on Liên hệ, on top of the
   menu items typed in capitals.
7. **Headings.** The EN home's only H1 is the tagline "Helping others by giving". Support Us
   and Liên hệ have no H1. Green Bamboo (EN) has four H1s ("Past:", "Present:", "Future:").
8. **Alt text is missing on 104 of 207 images** across the 10 pages. Worst: Green Bamboo
   and Thrive Together (EN) 13 of 16 each, Support Us 11 of 14, EN home 17 of 32.
9. **Forms on phones.** Contact form placeholders render at about 6px, and the VI form's
   "Message" label is in English.
10. **Footer.** Footer text is 14.4px with a 1.33 line height (the rule for Vietnamese is
    1.6 or more). Support Us prints "© HCWA, 2019. All rights reserved." twice. The VI
    footer reads "Neve | Mã nguồn WordPress", the EN one "Neve | Powered by WordPress".
11. **Body text is fine**: 17.6px at a 1.7 line height on most pages, and nothing readable
    under 12px apart from the hidden skip link.
12. **Weight and length.** Every page loads 26 to 28 external script files and 26 to 30
    stylesheets (inline scripts not counted). On a phone the VI home is 8,573px long, about
    ten screens; the EN home is 4,404px.
13. **Language detection.** `/` sends browsers set to Vietnamese a 302 to
    `/hoi-bao-tro-tre-em/`; everyone else, Google included, gets the English home. So
    "English is the root homepage" holds for search, not for every visitor.

## URL inventory (task 0.2, 30 Oct 2026)

`scripts/url-inventory.py` read the five Yoast sitemaps, followed every internal link and
recorded redirects without following them. Result: 260 URLs in `scripts/urls-live.csv`
(url, language, title, status, translation, type, WordPress ID, Elementor flag, redirect
target, canonical, last modified, source, page it was found on). This seeds the 301 map in
task 3.4.

- **Content:** 23 pages, 162 posts (83 VI, 79 EN), 5 categories, 29 tags, author archives,
  and their paginated lists.
- **Elementor everywhere:** all 23 pages and 123 of 162 posts are built with Elementor.
  Task 3.3 has to check or convert about 120 posts, not a handful. Plan time for it.
- **Missing translations:** 40 posts have no other-language version (22 VI-only, 18
  EN-only). Pages without one: Cau Han (both `/chp-2/` and `/du-an-cau-han/`) and Support
  Us.
- **Broken internal links:** `/gwbs-gallery/` returns 404 (linked from the Green Bamboo
  restaurant opening post); a `_wp_link_placeholder` link on `/cho-mot-ngay-moi/` returns
  404; `/chp/` (linked from `/up-coming-event/`) redirects to a JPG file.
- **Existing redirects to keep:** `/dva` and `/dva/` → `/dva-child-abuse-prevention-project/`.
  In the new map they should go straight to the new URL in one hop.
- **Fragile slugs:** two posts use decorative Unicode letters in the URL ("𝕊𝔸ℕ𝕋𝔸"). The
  redirect test has to handle the encoded and the raw form.
- **Little Rose posts need a safeguarding review now, before migration.** Several news posts
  are about Little Rose residents, for example
  `/good-new-our-talented-girls-were-awarded-hcm-clubs-scholarships/`,
  `/20-10-so-much-love-for-our-young-little-girls/`,
  `/viet-vu-dao-martial-arts-is-coming-to-the-litte-rose-warm-shelter-lrws/`,
  `/lop-viet-vu-dao-tai-mai-am-hoa-hong-nho/` and `/for-a-future-to-be-brighter-lrws-bakery/`,
  plus the `/tag/lrws/` and `/tag/hoa-hong-nho/` archives. These were not opened for this
  audit. HCWA should check them for identifiable faces or names and unpublish or edit any
  that show a resident; none migrate unless HCWA marks them approved.
- **Author archives are public** under `/author/` and `/vi/author/` and expose login names.
  Turn them off (new task 0.13).
- **News went quiet:** by last-modified date, 92 posts are from 2020, 42 from 2021, 12 from
  2022, 3 from 2023 to 2024, and 13 from 2026.
- **Titles:** the VI home is still "Đội Ngũ - HCWA"; the VI news hub is "tin moi - HCWA"
  (no diacritics); VI Thrive Together is just "DỰ ÁN - HCWA". Most other titles are in
  capitals. The EN pages declare `lang="en-GB"`.

## Top 5 improvements (what to send HCWA as step 1 of their brief)

1. **A homepage that answers four questions in one screen:** who you are, what you do for
   children, what changed (verified numbers), how to help. Photo, one headline, short
   intro, "Explore our work" + "Get involved" buttons, impact strip right below.
2. **Split "Our Work" into Centers (places) and Projects & programs (time-bound work)**,
   with photo cards for centers and a clear list for programs showing status, years and
   partners.
3. **A complete English twin.** Every page, menu, button, form and message in both
   languages, a visible VI | EN switch that stays on the same page, Vietnamese as the
   default at `/` and English at `/en/`, old URLs redirected.
4. **An "Impact & Reports" page** with verified headline numbers, outcomes by year and
   annual reports (with a language label on each PDF), plus safeguarding and privacy
   policies in the footer.
5. **One easy "Get involved" path:** donate (bank transfer with VietQR for local donors,
   PayPal for international), volunteer, partner, in-kind gifts, and the Little Rose
   bakery, each with a short form in the right language.

## Quick wins inside the existing site (no redesign needed)

These can ship this week on the current WordPress, with HCWA's OK. Full list with steps
is Phase 0 in `08-BUILD-PLAN.md`.

- Remove the Instagram feed from the EN homepage.
- Fix the VI homepage title ("Đội Ngũ" → "Hội Bảo trợ Trẻ em TP.HCM").
- Add a VI "Ủng hộ" page and menu item; link the missing translations.
- Make the address, phone and footer year consistent; remove the theme credit.
- Replace the test-logo share image with a proper 1200×630 image.
- Add alt text to homepage images.
- Update the Little Rose page's 2021-2022 appeal text.
