# Audit of the current site (hcwa.org.vn)

Reviewed 29 Oct 2026 in a desktop browser plus page-by-page HTML reads. Mobile layout was
not checked in a real phone viewport yet; that is task 0.1 in `08-BUILD-PLAN.md`.

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
