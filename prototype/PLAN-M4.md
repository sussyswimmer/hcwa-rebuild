# M4 plan: every page, no dead links, two 3D openings, real walls (PLAN-M4.md)

Written 11 Nov 2026 (planning step; nothing here is built yet). Read with `CLAUDE.md` (hard rules),
`docs/plan/05-DESIGN.md` (0.5, 0.6, 0.6.7, 5 and 7), `docs/plan/06-PAGES.md`,
`docs/plan/04-SITEMAP-AND-CONTENT-MODEL.md` and `PLAN.md` (section 4 for the inner-page pattern,
section 9 for what was built differently). Every stream logs its own departures in PLAN.md
section 9 under its own heading.

Maxwell, 11 Nov 2026: "finish the rest of the pages; at the top of the contact and news pages there
should be clean 3D scroll animations; make sure there are no more 'page can't be found'."

Maxwell, same day, on the walls: "for all of these walls they look too perfect, we need some more
character, more realistic; have Higgsfield generate some more realistic walls and use that for
context, maybe some scratches or just a real wall, not look like it's made out of play-doh."
That request is covered in section 6 (stream 3, "walls").

## 1. What the crawl found

**Method.** `npm run build` (and `npm run build:pages`, base `/hcwa-rebuild/`), then
`node ../qa/m4/crawl.mjs dist [out.json] [base]`. The crawler starts from `/`, `/en/`, both
components pages and both 404 pages. It follows every `<a href>` and form `action` in the built HTML.
That covers the desktop dropdowns, the mobile menu sheet, the footer, cards, chips, buttons, the
corridor bays, the help louvers and in-page anchors, because every one of them is in the
server-rendered HTML. No link is created by script except the form error summary's `#field` links,
which point at ids on the same page. For each link it checks three things: the target page exists in
`dist/`; the target is not the 404 page with its "not part of the prototype yet" note; and the
`#anchor` exists as an `id` on the target.

**Result (both builds give the same list).** 18 pages crawled (9 VI, 9 EN), **36 dead targets (18 VI,
18 EN), 638 dead link instances**. Every dead target is a missing page. There are no other kinds:
no links to existing pages with a missing anchor, and no missing assets. Each anchor link below is
dead only because its page is missing. 8 external links (3 VI + 3 EN live news posts, Google Maps,
Facebook) are out of scope.

| Dead target (VI / EN) | Route key | Linked from | Link text |
|---|---|---|---|
| `/ve-chung-toi/` / `/en/about/` | `about` | header nav + dropdown, menu sheet, footer Explore: every page | Về chúng tôi, Chúng tôi là ai / About Us, Who we are |
| `/ve-chung-toi/cau-chuyen/` / `/en/about/our-story/` | `ourStory` | About dropdown, menu sheet: every page | Câu chuyện của Hội / Our story |
| `/ve-chung-toi/doi-ngu/` / `/en/about/team/` | `team` | About dropdown, menu sheet: every page | Đội ngũ / Team |
| `/ve-chung-toi/doi-tac/` / `/en/about/partners/` | `partners` | About dropdown, menu sheet, footer Trust: every page | Đối tác & nhà tài trợ / Partners & sponsors |
| `/ve-chung-toi/chinh-sach/` / `/en/about/policies/` | `policies` | About dropdown, menu sheet: every page | Chính sách / Policies |
| `…/chinh-sach/#bao-ve-tre-em` / `…/policies/#safeguarding` | `safeguarding` | footer Trust: every page | Chính sách bảo vệ trẻ em / Child safeguarding policy |
| `…/chinh-sach/#quyen-rieng-tu` / `…/policies/#privacy` | `privacy` | footer Trust: every page (and Donate's questions nav) | Thông báo quyền riêng tư / Privacy notice |
| `/tac-dong/` / `/en/impact/` | `impact` | header nav, menu sheet, footer Explore, home work sheet "See our impact" | Tác động & Báo cáo / Impact & Reports |
| `/tac-dong/#bao-cao` / `/en/impact/#reports` | `reports` | footer Trust: every page | Báo cáo thường niên / Annual reports |
| `/tin-tuc/` / `/en/news/` | `news` | header nav, menu sheet, footer Explore, home news "All news", home featured-story placeholder | Tin tức, Tất cả tin tức, [Tên câu chuyện] / News, All news, [Story title] |
| `/chung-tay/tinh-nguyen/` / `/en/get-involved/volunteer/` | `volunteer` | Get Involved dropdown, menu sheet, help louvers (home, Get Involved) | Tình nguyện / Volunteer |
| `/chung-tay/doi-tac-doanh-nghiep/` / `/en/get-involved/partner/` | `partner` | Get Involved dropdown, help louvers, home partners "Become a partner", Thrive Together CTA | Hợp tác, Trở thành đối tác, Hợp tác trong một dự án / Partner with us, Become a partner, Partner on a program |
| `/chung-tay/hien-vat/` / `/en/get-involved/in-kind/` | `inKind` | Get Involved dropdown, help louvers | Hiện vật (và Tiệm bánh Hoa Hồng Nhỏ) / In-kind gifts (and the Little Rose Bakery) |
| `/chung-tay/hien-vat/#tiem-banh` / `/en/get-involved/in-kind/#bakery` | `bakery` | Get Involved dropdown, menu sheet: every page | Tiệm bánh Hoa Hồng Nhỏ / Little Rose Bakery |
| `/hoat-dong/co-so/hoa-hong-nho/` / `/en/our-work/centers/little-rose/` | (none yet; `work.centers[little-rose].path`) | home corridor bay, Our Work door wall, components | Tìm hiểu về mái ấm, Mái ấm Hoa Hồng Nhỏ / About the shelter, Little Rose Warm Shelter |
| `/hoat-dong/co-so/cau-han/` / `/en/our-work/centers/cau-han/` | (none yet; `work.centers[cau-han].path`) | home corridor bay, Our Work door wall, components | Xem cơ sở, Dự án Cầu Hàn / Visit center, Cau Han Project |
| `/hoat-dong/du-an/lop-hoc-tinh-thuong/` / `/en/our-work/programs/charity-class/` | (none yet; `work.programs[charity-class].path`) | home and Our Work program rows, Green Bamboo "programs here", components | Lớp học tình thương / The Charity Class |
| `/hoat-dong/du-an/quyen-tre-em-khuyet-tat/` / `/en/our-work/programs/disability-rights-pilot/` | (none yet; `work.programs[disability-pilot].path`) | home and Our Work program rows | Dự án thí điểm thúc đẩy quyền trẻ em khuyết tật / Pilot project promoting the rights of children with disabilities |

Full output: `qa/m4/crawl-dist.json`, `qa/m4/crawl-pages.json`. Baselines taken before any M4 work:
`npm test` 110/110; `PROD=1` render blocked on 59 problems (16 data-draft, 16 [DRAFT, 16 bracket
tokens, 8 Little Rose door, 2 donation placeholder, 1 traces).

## 2. Decisions taken in this plan

1. **18 new page pairs are built (section 3); no internal link may point anywhere else.** A link
   only goes in once its target exists. The crawl in section 8 is the gate.
2. **News posts stay on the live site in M4.** We only have titles and dates (`news.json`), no
   bodies, and writing a body would be inventing content. The news hub cards link to the live posts
   with the existing external marker (`ui.externalPost`). Article pages (`/{slug}/`,
   `/en/{slug}/`) come with the WordPress migration, which keeps the real posts and URLs.
3. **News categories are in-page filters, not archive URLs.** The All / News / Stories / Events chips
   use the Our Work pattern: anchors plus `:target`, with JS filtering when JS is on. They do not link
   to `/tin-tuc/cau-chuyen/` and the other category URLs, which are not built in the prototype. Stories
   has no posts yet, so it shows its empty state.
4. **The Cầu Hàn English URL becomes `/en/our-work/centers/cau-han-project/`.** This matches
   04-SITEMAP's redirect table and `wp/CONTRACT.md` (Polylang free cannot reuse the VI slug
   `cau-han`). `work.json` currently says `/en/our-work/centers/cau-han/`; the pages stream changes
   it. The prototype has never linked the old URL to a built page, so it needs no redirect.
5. **Little Rose's page is words and an abstract illustration only.** There is no world still, no
   door in any form and no location (section 4.11). Its header is the plain sun-band header that
   Donate uses, not a lit header. `test/little-rose.test.mjs` already says Little Rose has no
   header still, and that stays true.
6. **The openings are CSS 3D on prerendered layers, not live WebGL.** PLAN section 4 says inner
   pages run no live WebGL and never load the GSAP/Lenis motion chunk. The openings stay inside
   that: layered stills and SVG/HTML planes in a CSS `perspective` space, driven by native scroll
   (section 5).
7. **The 404 page stays, without its staging note.** Once the crawl is clean, the
   "not part of the prototype yet" line (`ui.notInPrototype`, `.gap__proto`) is removed from
   `notFound()`. The 404 page then only appears for mistyped URLs. The comment in `routes.mjs` that
   describes unbuilt routes is updated to match.
8. **Header stills for the new pages.** Programs share the program template's still
   (`header-thrive-together`, lam stripes; the still is a pattern, not a unit). Cầu Hàn, About, Impact
   and (if the openings stream wants one) News get new stills from stream 3 (section 6.4). Until a
   still exists, `litHeader()` shows its shade fill, so no stream waits for one. The other new pages
   use the image-free sun-band header with CSS/SVG block art.
9. **`npm test` and the WordPress export.** `test/export-wp.test.mjs` › "the committed generated
   files are what the export makes now" compares `wp/**/generated`, `prototype-data.php` and
   `wp/tests/fixtures/golden.json` with what the prototype makes now. Every new content string
   makes them stale. The streams must not touch `wp/`, so **that one assertion is the only red test
   allowed during the streams.** The lead clears it at integration with `npm run export:wp`, which
   writes generated files only. Every other test stays green throughout.

## 3. The pages

"Stream" is who builds it (section 7). "Content now" means what is already in `content/draft/`.
Every new string goes in a stream-owned file (section 7.2). Every page exists in VI and EN with the
same structure.

| # | Page | Route key | VI URL | EN URL | Stream | Spec | Content now |
|---|---|---|---|---|---|---|---|
| 1 | Who we are (About hub) | `about` | `/ve-chung-toi/` | `/en/about/` | pages | 06 "About pages: Who we are"; 04 main menu 1 | `site.description`, `site.registration` (draft), `site.whatWeDo`, `work.focusAreas`. Mission, vision and values: none |
| 2 | Our story | `ourStory` | `/ve-chung-toi/cau-chuyen/` | `/en/about/our-story/` | pages | 06 "About pages" timeline; 04 Center table (since) | `work.centers[].sinceToken` (tokens only) |
| 3 | Team | `team` | `/ve-chung-toi/doi-ngu/` | `/en/about/team/` | pages | 06 "About pages: Team" | none |
| 4 | Partners & sponsors | `partners` | `/ve-chung-toi/doi-tac/` | `/en/about/partners/` | pages | 06 "About pages: Partners & sponsors"; 04 Partner type | `site.partnerLogos: []`; `work.programs[thrive-together].partners` ("CapitaLand Vietnam", text) |
| 5 | Policies, with `#bao-ve-tre-em`/`#safeguarding`, `#quyen-rieng-tu`/`#privacy`, `#dieu-khoan`/`#terms` | `policies` (+ `safeguarding`, `privacy`) | `/ve-chung-toi/chinh-sach/` | `/en/about/policies/` | pages | 06 "About pages: Policies"; 06 footer column 4 | none |
| 6 | Impact & Reports, with `#bao-cao`/`#reports` | `impact` (+ `reports`) | `/tac-dong/` | `/en/impact/` | pages | 06 "Impact & Reports"; 04 Impact figure, Report | `impact.figures` (8, all `verified: false`); no reports |
| 7 | News hub with 3D opening | `news` | `/tin-tuc/` | `/en/news/` | **openings** | 06 "News archive and post"; 04 page tree `tin-tuc/` | `news.posts` (3 real posts, external), `news.featuredStory` (placeholder); `ui.cat_*`, `ui.namesChanged`, `ui.externalPost` |
| 8 | Contact: 3D opening at the top of the existing page | `contact` | `/lien-he/` | `/en/contact/` | **openings** | 06 "Contact"; PLAN P6 | page exists; `involve.contactPage`; still `header-contact` (3 layers) |
| 9 | Volunteer | `volunteer` | `/chung-tay/tinh-nguyen/` | `/en/get-involved/volunteer/` | pages | 06 "Get Involved hub + subpages: Volunteer" | `involve.ways[volunteer]`, `involve.faq` (child-safety question), form field helpers |
| 10 | Partner with us | `partner` | `/chung-tay/doi-tac-doanh-nghiep/` | `/en/get-involved/partner/` | pages | 06 "…: Partner with us" | `involve.ways[partner]`, `site.email.sponsorship` (draft) |
| 11 | In-kind gifts, with `#tiem-banh`/`#bakery` | `inKind` (+ `bakery`) | `/chung-tay/hien-vat/` | `/en/get-involved/in-kind/` | pages | 06 "…: In-kind gifts"; 04 Get Involved dropdown | `involve.ways[in-kind]`, `involve.faq` (in-kind question), `site.address` (draft) |
| 12 | Little Rose Warm Shelter | new `littleRose` | `/hoat-dong/co-so/hoa-hong-nho/` | `/en/our-work/centers/little-rose/` | pages | 06 "Center page template" (illustration variant); 04 Center | `work.centers[little-rose]`, `work.littleRose.privacyNote` |
| 13 | Cầu Hàn Project | new `cauHan` | `/hoat-dong/co-so/cau-han/` | `/en/our-work/centers/cau-han-project/` (decision 4) | pages | 06 "Center page template"; PLAN P2 | `work.centers[cau-han]`; stills `door-cau-han`, `hub-door-cau-han`, `bay-cau-han`, `gallery-wall` |
| 14 | The Charity Class | new `charityClass` | `/hoat-dong/du-an/lop-hoc-tinh-thuong/` | `/en/our-work/programs/charity-class/` | pages | 06 "Program page template"; PLAN P3 | `work.programs[charity-class]` |
| 15 | Disability rights pilot (completed) | new `disabilityPilot` | `/hoat-dong/du-an/quyen-tre-em-khuyet-tat/` | `/en/our-work/programs/disability-rights-pilot/` | pages | 06 "Program page template" | `work.programs[disability-pilot]`; 2 real related posts in `news.posts` |
| 16 | 404 (exists): drop the staging note | `notFound` | `/404.html` | `/en/404.html` | openings (owns `involve.mjs`) | 06 "404"; PLAN P7 | exists |

Not built in M4, and not linked anywhere: news article pages, news category archives, report detail
pages (`tac-dong/bao-cao/{slug}/`). Each would need content HCWA has not supplied.

## 4. Page notes (pages stream unless marked)

The shared pattern is PLAN section 4. Every page has a header that feathers into a `.day` gradient
with no bottom edge (05-DESIGN 0.5). The gradient's position follows the page's place in the day:
About pages are morning (lime), Impact is midday, the Get Involved subpages are late afternoon (sun),
and Policies is plain and calm. Reveals follow 05-DESIGN 5.4. Nothing animates the H1, figures,
amounts or form values. Placeholder copy is short, plain and marked `data-draft` / `[DRAFT: …]`. Each
new English draft goes through `anti-slop`, and HCWA approves all wording.

### 4.1 Who we are (`about`)
Lit header (still `header-about` from stream 3; shade fill until then). Sections: mission, vision and
values; what HCWA is legally; the four focus areas (reuse the gạch chips/strip from Our Work, linking
to `ourWork` with the area anchor); a teaser for Our story; links to Team, Partners, Policies.
Mission, vision and values may be condensed from the current About page (VI, plus an EN
translation). Mark them draft with a `source` note, as `work.json` does. Legal status is a
`[DRAFT]` token. **No founding year and no numbers.**

### 4.2 Our story (`ourStory`)
A timeline in the house's light: each step is a Hoa thị patch moving along the wall as you scroll
(CSS `view()`, follows the scroll). Steps: the founding, Little Rose, Green Bamboo (as the Cầu Muối
club), Cầu Hàn, Green Bamboo's 2020 model change, today. **Every year is a token** (`[năm]` /
`[year]`, `data-draft`) until HCWA verifies it; PROD leaves the years out. It never prints 1992, 1993,
1997 or 2020 (04 lists them from the current site; CLAUDE.md forbids unverified years). Each step has
one line of words. Little Rose's line says what the shelter is and never where it is.

### 4.3 Team (`team`)
06: "Only people who agree to be listed." No content exists, so there are **no names, no photos, no
initials and no silhouettes**. On staging: one `[DRAFT]` line explaining that HCWA will list the
people who agree to be named, and 3 empty slots carrying `[Tên] · [Vai trò]` / `[Name] · [Role]`
tokens with `data-team-pending`. Add `data-team-pending` to `render.mjs` BLOCKERS and a case to
`prod-guard.test.mjs`. In PROD the slots are left out and the page shows its intro plus links to
Volunteer and Contact.

### 4.4 Partners & sponsors (`partners`)
Grouped by type (corporate, NGO, government, foundation, academic; 04 Partner), but a group renders
only when it has approved entries. **No invented partner names.** Logos need ≥ 1 approved entry per
group. The home page's rule of at least 4 approved logos applies to any wall of logos, so staging
shows the existing pending strip (`data-partners-pending`, already a blocker). The one name already
in content is the Thrive Together funder, "CapitaLand Việt Nam" / "CapitaLand Vietnam". It may appear
as text in a "funders of current programs" line that links to the program page, marked draft until
HCWA confirms that a partners page may list it. CTA: Partner with us.

### 4.5 Policies (`policies`)
Three sections, each with its id in the page's language: VI `bao-ve-tre-em`, `quyen-rieng-tu`,
`dieu-khoan`; EN `safeguarding`, `privacy`, `terms`. Follow the `A` map pattern in `work.mjs` so the
VI page never carries the EN ids. Each section has a short HTML summary (`[DRAFT: HCWA to supply]`,
a heading outline only, no invented commitments) and a document row in the empty state
(`ui.noDocuments`). The safeguarding summary may state the site's own rules as draft: changed names
with a visible note, no identifiable photos without approval, Little Rose residents never
identifiable. The privacy summary may describe what the site's forms ask for and that the answers are
used to reply. **The anchors must exist in PROD too**, because the footer links to them on every
page.

### 4.6 Impact & Reports (`impact`)
06 order: H1 + one line on how numbers are counted (`[DRAFT, HCWA to write]`); headline figures
(organization scope); "by year" as a plain table of tokens (staging only); stories of change (the
featured-story placeholder, `data-story-placeholder`, already a blocker); the reports library
`#bao-cao` / `#reports`; transparency with a link to `safeguarding`. **Figures follow the verified
rule.** They render from `impact.figures` through `visibleFigures()` with pending chips; PROD shows
none, and a section with zero verified figures hides itself. **Numbers never count up.** The reports
section and its id render in PROD with the empty state, because the footer links to it. Lit header
`header-impact` (stream 3).

### 4.7 Volunteer (`volunteer`)
Who we look for (`involve.ways[volunteer]`, which is taken from the current page: activities with
children, communications, fundraising, sponsor relations); what to expect `[DRAFT]`; a child-safety
note `[DRAFT: HCWA's checks and code of conduct]`, with no invented checks; a form with name, email,
phone (optional), area of interest (choices), availability and message, consent with the privacy
link, every label and error in the page language, and `f_prototypeSent` on submit. Reuse
`field`/`choiceField`/`consentField`/`formSent` from `partials/components.mjs` and `ui/forms.js`.

### 4.8 Partner with us (`partner`)
Ways to partner (program funding, employee volunteering, in-kind, events; 06); examples only from
approved logos (the pending strip until then); contact by `site.email.sponsorship` (draft) plus a
short form or mailto. This page answers Thrive Together's "Partner on a program" CTA.

### 4.9 In-kind gifts (`inKind`) and the bakery (`#tiem-banh` / `#bakery`)
What is needed (a `[DRAFT]` list); "contact us before sending"; drop-off at the office
(`site.address`, draft, the same line as the footer). The bakery section: what the Little Rose Bakery
is `[DRAFT]`, and how to order **through the office** (phone, email, the contact form). **No photos of
residents, no residents' names, no shelter location, nothing in it that implies the bakery's
address.** Illustrate it with block art or a bakery motif, never people. It links to the Little Rose
page with `ui.aboutShelter`. Both ids exist in PROD.

### 4.10 Cầu Hàn (`cauHan`)
`center()` generalised from Green Bamboo (see 7.3 for `work.mjs`): unit band `cau-han`, emblem
`vomCau`, since token, location (draft, ward/city only), at a glance, our story `[DRAFT]`, figures (no
`cau-han` scope exists, so the section hides), the gallery as `gallery-wall` slots, programs here
(Charity Class, Thrive Together), how to help, related news (none tagged), contact (office). Header
still `header-cau-han` from stream 3 (blue door open, Vòm cầu light), shade fill until it lands. **Extra
care:** the project serves families affected by HIV/AIDS. No photo slot, caption or story line may
suggest any child's or family's health status. Gallery labels stay generic.
`work.centerPages["cau-han"]` gets draft entries keyed like `green-bamboo`.

### 4.11 Little Rose (`littleRose`): safeguarding first
05-DESIGN D6.5, 0.6.4 and section 8; CLAUDE.md child safeguarding.
- **Words and an abstract illustration only.** The illustration is CSS/SVG from `partials/blocks.mjs`:
  the house's Hoa thị block letting plain, untinted daylight onto a lime wall. It shows no door (open
  or shut), no building, facade, street or window view, and no figures or silhouettes. No light leans
  pink or red.
- **No world still of any kind.** Not `door-little-rose`, `bay-little-rose`, `hub-door-little-rose` or
  `hub-doors-wide`, and no new still (the test pins the Little Rose still list). No element carries
  `data-unit="little-rose"` together with `data-door`, so the page never trips the
  `LITTLE_ROSE_DOOR` guard, and it adds no new line to that guard's PROD count.
- **No location of any kind** (no ward, no city line, no map, no address), no contact person, no phone
  of its own (contact goes through the office), no gallery, no figures section (no `little-rose`
  scope exists), no related news (Little Rose news is a no-identifiable context in the WP contract),
  no share links, and never the word "visit" (`ui.visitCenter`).
- Content: who it serves (`serves`, with its `[độ tuổi]` / `[age range]` token), what the shelter
  offers `[DRAFT, condensed from the current page once HCWA confirms; drop the 2021-2022 appeal]`,
  the privacy note shown prominently, and how to help: donate (general; whether Little Rose keeps
  its own account is HCWA's decision, and no account details appear), the bakery link and in-kind
  gifts.
- Header: the plain sun-band header (as Donate), with the unit's 6 px band in its own pink as paint,
  not light.
- New test `test/little-rose-page.test.mjs`, VI and EN, staging and PROD: no `data-door`, no
  `door-little-rose-` / `bay-little-rose-` / `hub-doors-wide` strings, no `<img>` or `<picture>` in
  the page body, no `location` text, no `visitCenter` string, the privacy note present, and no
  `tel:` link other than the office's.

### 4.12 Charity Class (`charityClass`) and the disability pilot (`disabilityPilot`)
`program()` generalised from Thrive Together. Status pill (active / completed), years tokens, summary
(draft), goals and activities `[DRAFT]` lists, results (no scoped figures, so the section hides),
where it runs (door chips; Little Rose's chip is name only), documents (empty state), related news,
CTA. The pilot's related news is its 2 real posts (external links, real dates). Its CTA says "Support
work like this", because a completed project takes no new partners. `work.programPages` gets entries
keyed like `thrive-together`. Both use the `header-thrive-together` still (decision 8).

### 4.13 News hub (`news`, openings stream)
The 3D opening (section 5.2), then the archive: category chips (decision 3), the cards (category,
localized real date, title, external link to the live post, no images), the Stories empty state and
the featured-story placeholder (staging only, guarded). There is no pagination because there are 3
posts; the pagination component appears only past 12. Story cards will carry `ui.namesChanged` once
stories exist. **No live social feeds, no embeds, no photos of children.**

### 4.14 Contact (`contact`, openings stream)
The 3D opening replaces the lit header at the top (section 5.1). Everything below stays: the form,
the office card and the unit lines. Little Rose's contact line stays "through the office", with its
privacy note and no link. Cầu Hàn's name becomes a link once `work.json` marks it `built`; this
happens on its own in `unitReach()`.

## 5. The two 3D openings (openings stream)

Load and follow `god-site-skill:ui-motion` (UI chrome stays *Silk*, 05-DESIGN 5.1) and
`god-site-skill:scroll-experience`. Read `god-site-skill:pro-motion` for timing and easing craft
only; it is about films. Use `god-site-skill:anti-ui-slop` for every visual decision. Rules for both
openings:

- **Native scroll, never hijacked.** No Lenis, no GSAP, no snap, no scroll-jacking. Progress comes
  from CSS scroll-driven animations (`animation-timeline: view()` / named `scroll-timeline`,
  `animation-range`) inside `@supports (animation-timeline: view())`. Where that is unsupported
  (Firefox today), a small module (`src/motion/scroll3d.js`, under 2 KB gzip) writes one custom
  property `--p` (0 → 1) per stage. It uses a passive scroll listener with one rAF, sleeps under an
  IntersectionObserver when the stage is off screen, and never imports `motion/index.js`.
- **The 3D is CSS 3D:** `perspective` on the stage, layers at `translateZ`, rotations of at most
  about 12°. It moves only `transform`, `opacity`, `clip-path`, `mask-position` and custom
  properties (05-DESIGN 5.1). Blur is at most 8 px, and at most 4 promoted layers per stage.
- **Text is HTML and stays readable.** The H1 never animates; the stage moves behind it. Every
  readable item is flat and face-on wherever it is meant to be read. No text lives inside an image.
- **Fallbacks are the same page at rest.** Reduced motion, `data-motion="off"` (pause), `?tier=0`,
  no-JS and no WebGL all show the composed rest frame. That frame is the stage unpinned with its
  layers flat-stacked (or the contact lit header as it is today), with all text and links present
  and nothing hidden. Tier only changes richness (number of layers, light drift), never content.
- **No hard lines (0.5).** The stage's foot feathers into the `.day` gradient over at least 24svh
  (16svh on phones). The stage has no box edge, and the light carries the seam.
- **Budget.** At most about 1.2 extra screens of scroll on desktop and 1 on phones, before the first
  form field (Contact) or the first card (News). "Skip to the form" / "skip to the news" links sit in
  the first screen. Mobile LCP stays under 2.5 s: the base layer is the LCP image (eager,
  `fetchpriority="high"`) and the other layers decode after it. CLS stays under 0.1.
- **Timing craft (taken from pro-motion's principles).** Scroll maps linearly to progress, so the
  scene follows the finger. Each layer gets its own spatial easing over that progress: the camera on
  `--ease-in-out`, light arriving on `--ease-sun`, nothing with bounce. Start motion the moment the
  stage is entered, so there is no dead first screen. Hold the last 12-15% of the range so the frame
  comes to rest before the reading starts. Parallax ratios stay small (the inner pages' 0.12× drift
  is the reference). One idea per opening.

### 5.1 Contact: "through the screen to the street"
The existing `header-contact` still already has three layers: base, the room's light (`lightroom`)
and the screen's light (`lightscreen`). Set them in depth. As the visitor scrolls, the camera
dollies toward the bông gió screen: the screen layer grows past the frame edges, the light layer
slides as if the sun moved toward 16:30, and the street side opens out. The page then settles onto
the office address in the late-afternoon gradient. The call and write buttons and the H1 stay in the
first screen. The fallback is today's lit header. Read layer geometry from the still's own data
(`lib/posters.mjs`, `world/poster-frames.js`), never hard-coded pixels, so stream 3's re-render does
not break it.

### 5.2 News: "notices on the wall"
A stretch of the house's lime wall, with the real wall texture from stream 3 once it lands. The
latest posts are pinned to it as paper notices; each one is an HTML card with category, date and
title. The camera starts close on the newest notice and pulls back along the wall as you scroll. Each
notice turns from a slight angle to flat as it reaches its reading position, and a quatrefoil of sun
passes over each in turn. The fallback is the same notices as a flat list. The opening ends where the
archive's chips begin, and the cards in the opening are the archive's first cards, not a copy (one
list in the DOM, no duplicate links). If a still helps (the wall, out of focus), ask stream 3 for
`header-news`; otherwise build the wall from CSS and the texture.

### 5.3 Shared module
`src/partials/scroll3d.mjs` (stage markup: `data-scroll3d`, layers, rest frame),
`src/motion/scroll3d.js` (the fallback driver above) and `src/styles/openings.css`. Contact and News
both use them, and nothing else does.

## 6. Walls: real plaster instead of play-doh (stream 3, from Maxwell's 11 Nov request)

**Problem.** The world's walls come from procedural noise (`world/textures.js` `bakeLimeWash`, the
house shader in `world/materials/still-surface.js` and `surface.js`). They read as smooth and soft
(Maxwell: "play-doh"). Wanted: real wall character, such as scratches, hairline cracks, patched
plaster, rain streaks, chipped paint at the dado and worn door edges.

### 6.1 Generate (Higgsfield)
- Use the Higgsfield tools (`generate_image`; first `models_explore` with `action: "recommend"` for
  a photoreal, tileable texture model). Generation spends credits, so get Maxwell's or the lead's OK
  on the budget. Generate a first batch of 6-8, pick, and refine. Never re-run blindly.
- Sources to generate: (a) old Saigon lime-washed plaster, cream to pale yellow; (b) the mint
  painted dado band with chipped edges and scuffs at knee height; (c) painted wooden door leaf wear
  (edges, kick zone, handle); (d) cast-concrete breeze-block surface with pores and flaking lime
  wash; (e) worn cement-tile floor and grout; (f) ceiling plaster.
- Every prompt asks for: straight-on, orthographic, flat even overcast light (the world adds its own
  sun), seamless/tileable, about 2048 px, no vignette. **No text, letters, graffiti, posters, signs or
  house numbers; no people, children, faces or hands; no plants, windows or object shadows.** This
  keeps safeguarding and makes sure no real place can be recognised.
- Record each prompt, model and pick in `public/textures/SOURCES.md`, so HCWA knows these are
  generated.

### 6.2 Process
`scripts/build-wall-textures.py` (Pillow, run with `python3 -I`). It high-passes away any baked-in
lighting, makes each image seamless (offset and blend, checked by tiling 2×2), and desaturates to
neutral detail: **colour still comes from the tokens**, and the textures only modulate albedo by
about ±10%, plus roughness and a derived normal. It packs one map per surface (R albedo detail, G
crack and scratch mask, B height, A stains/loang) at 1024² (Tier 2) and 512² (Tier 1), as WebP. The
budget is at most 120 KB per map and 360 KB in all. The maps load with the world chunk after the
text, never before LCP.

### 6.3 Use
`world/textures.js` combines the maps with the existing loang. The house shader applies them to the
walls, dado, doors (wear only where 0.6.7 allows: edges, kick zone, handle), the screen blocks, the
floor and the ceiling, at real scale: hairlines are a few millimetres wide, patches tens of
centimetres, seen from the 1.10 m eye. Little Rose's door and bay keep their rules (clean paint, no
wear, plain untinted light). `test/little-rose.test.mjs` must still pass, including the g ≥ r
check on every light colour. Optionally, a CSS grain from the same plaster map goes on the lit
headers' plain walls and the openings' wall at an opacity of 6% or less, never under running text
that would fall below 7:1.

### 6.4 Re-render every still
Run `npm run posters` (hero buckets) and `npm run posters -- --still all`. This includes the new
shots requested here: `header-cau-han` (blue door open, Vòm cầu light, Cầu Hàn's band),
`header-about` (the house's front screen in early-morning light), `header-impact` (midday light
patches on the wall, no numbers in the image) and `header-news` if the openings stream asks.
`encode-posters.py` budgets hold. Then run `check-sun-crisp`, `check-hero`, `light-contrast` and
`lcp.cjs`. HCWA approves the new wall look with the 0.6.4 items, and the safeguarding lead sees
`bay-little-rose` and `hub-door-little-rose` re-rendered.

## 7. Who owns what

Three streams. None edits another's files. A stream that needs a change in someone else's file
writes it under "needs" in its PLAN section 9 heading and tells the lead. At most one headless
browser per stream while they run together (the 4-CPU box also runs another agent). Each stream kills
every server it starts, by PID.

### 7.1 Files

| File / folder | pages | openings | walls | Notes |
|---|---|---|---|---|
| `src/routes.mjs` | **owns** | | | adds every new route key (`littleRose`, `cauHan`, `charityClass`, `disabilityPilot`, and `page` on `about`, `ourStory`, `team`, `partners`, `policies`, `impact`, `volunteer`, `partner`, `inKind`); changes the Cầu Hàn EN path; adds `news: { …, page: "news" }` **once `src/pages/news.mjs` exists** |
| `src/pages/index.mjs` | **owns** | | | registers `news` → `./news.mjs` (made by openings) and the new page modules |
| `src/pages/work.mjs` | **owns** | | | generalises `center()` / `program()` by route (e.g. `routes[key].unit` / `.program`); Our Work and home output unchanged |
| new `src/pages/about.mjs`, `impact.mjs`, `involve-more.mjs`, `little-rose.mjs` | **owns** | | | page modules (names are the stream's choice) |
| `src/pages/involve.mjs` | read only | **owns** | | `contact()` opening, `notFound()` note removal; Get Involved and Donate unchanged |
| new `src/pages/news.mjs` | | **owns** | | **first step:** a stub exporting `news(c)` so the pages stream can register the route |
| `src/pages/home/**`, `src/pages/home.mjs` | | | | nobody (only the `built` flags and paths in `work.json` change what the home page links to) |
| `src/partials/layout.mjs` | **owns** | | | `isCurrent()` for the new children (About, Our Work centers and programs, Get Involved subpages) |
| `src/partials/lit-header.mjs`, `components.mjs`, `blocks.mjs`, `louvers.mjs` | **owns** | read only | | openings asks via "needs" for a new icon or helper, or keeps it in `scroll3d.mjs` |
| new `src/partials/scroll3d.mjs` | | **owns** | | |
| `src/partials/still.mjs`, `src/lib/posters.mjs` | read only | read only | **owns** | |
| `src/lib/content.mjs` | **owns** | | | **first step (before anything else):** merge every `content/draft/ui-*.json` into `ui` (a key in two files is a build error, as now) and load the new optional files below |
| `src/lib/ctx.mjs`, `html.mjs`, `vikeep.mjs`, `split-words.mjs`, `head-inline.mjs`, `src/head-inline.js` | | | | frozen |
| `src/render.mjs` | **owns** | | | the `data-team-pending` blocker; any guard openings needs goes through "needs" |
| `src/main.js` | | **owns** | | adds `import "./styles/openings.css"` after `pages.css` and `initScroll3d()`; stream 3's CSS import (if any) through "needs" |
| `src/ui/index.js`, `src/ui/*.js` | **owns** | | | news chips reuse `ui/filters.js`; changes to it through "needs" |
| new `src/motion/scroll3d.js` | | **owns** | | |
| `src/motion/*` (others) | | | | frozen (home) |
| `src/styles/pages.css` | **owns** | | | the pages stream may append here or `@import` a new `pages-m4.css` at its top |
| new `src/styles/openings.css` | | **owns** | | contact and news overrides live here, never in `pages.css` |
| `src/styles/tokens.css`, `base.css`, `layout.css`, `components.css`, `light.css`, `motion.css`, `world.css`, `stages.css`, `draft.css` | | | | frozen (a token change goes to the lead) |
| `src/world/**` | | | **owns** | textures, materials, new still shots (`still-shots.js`, `sets/stills.js`) |
| `public/posters/**`, new `public/textures/**` | | | **owns** | |
| `scripts/render-posters.mjs`, `encode-posters.py`, new `build-wall-textures.py` | | | **owns** | |
| new `scripts/check-links.mjs` + `npm run check:links` | **owns** | | | port of `qa/m4/crawl.mjs` (base-path aware); `package.json` scripts: pages owns |
| new `scripts/check-openings.cjs` | | **owns** | | frame strips at scroll positions, 390 and 1440 |
| `content/draft/ui.json` | | | | **frozen**: no edits; new strings go in the files below |
| new `content/draft/ui-pages.json` | **owns** | | | |
| new `content/draft/ui-openings.json` | | **owns** | | |
| `content/draft/ui-world.json` | | | | frozen |
| `content/draft/work.json` | **owns** | | | `built: true` flags, Cầu Hàn EN path, new `centerPages` / `programPages` entries |
| `content/draft/impact.json` | **owns** | | | add `page` strings and `reports: []`; figures stay `verified: false` |
| new `content/draft/about.json` | **owns** | | | who we are, story, team, partners, policies |
| new `content/draft/involve-pages.json` | **owns** | | | volunteer, partner, in-kind, bakery |
| `content/draft/involve.json` | read only | **owns** | | `contactPage` opening lines |
| `content/draft/news.json` | read only | **owns** | | hub intro, empty-state lines |
| `content/draft/site.json`, `world.json`, `kit.json` | | | | frozen (`kit.json`: pages may add components-page entries through "needs") |
| `test/work-pages.test.mjs`, `prod-guard.test.mjs`, new `about-pages.test.mjs`, `little-rose-page.test.mjs`, `links.test.mjs` | **owns** | | | `links.test.mjs` renders every page in memory and checks that every internal href and hash resolves |
| `test/involve-pages.test.mjs`, new `openings.test.mjs` | | **owns** | | |
| `test/stills.test.mjs`, `little-rose.test.mjs`, `world.test.mjs`, `beats.test.mjs`, `shutters.test.mjs` | | | **owns** | |
| `test/export-wp.test.mjs`, `wp/**` | | | | nobody; the lead runs `npm run export:wp` at integration (decision 9) |
| `PLAN.md` section 9 | own heading | own heading | own heading | append only, under "M4 pages", "M4 openings", "M4 walls" |

### 7.2 Handshakes and order
1. **pages, first:** the `content.mjs` merge (`ui-*.json` + optional `about.json` and
   `involve-pages.json`), with `npm test` and the build still green. Tell the lead when it is done.
2. **openings, first:** the `src/pages/news.mjs` stub, empty `ui-openings.json` and `openings.css`,
   and the `main.js` imports. Tell the lead.
3. **pages:** registers `news` in `routes.mjs` and `index.mjs`. From here on `/tin-tuc/` renders the
   openings stream's module.
4. Then in parallel: pages builds sections 4.1-4.12; openings builds 5.1, 5.2 and 4.13 on top of the
   stub; walls runs 6.1-6.3.
5. **walls, last:** renders the stills (6.4) once the other two say which headers they use. Pages
   and openings do not wait for this; they ship on the shade fill.
6. **lead:** `npm run export:wp`, the integration gates (section 8), then commits.

Both streams load the god-site-skill set named in `CLAUDE.md` for visual work (`anti-ui-slop`,
`design-taste-frontend` dials 8/8/4, `tastegate`), plus `scroll-experience` and `ui-motion` for the
openings.

## 8. Acceptance

A page is done when all of these hold, in VI and EN:

1. **0 dead links.** `node ../qa/m4/crawl.mjs dist` after `npm run build`, and with base
   `/hcwa-rebuild/` after `npm run build:pages`: **0 dead targets in VI and in EN.** That means no
   404, no "not part of the prototype" notice and no missing anchor, including `#bao-cao`/`#reports`,
   `#tiem-banh`/`#bakery`, `#bao-ve-tre-em`/`#safeguarding` and `#quyen-rieng-tu`/`#privacy`. The same
   check runs as `npm run check:links` and `test/links.test.mjs`, and also on the PROD render for
   every anchor the footer uses.
2. **Builds.** `npm test` is green (the one export assertion in decision 9 is the only exception
   until the lead regenerates). `npm run build` and `npm run build:pages` pass. `PROD=1 npm run build`
   still fails only on its draft and safeguarding guards. Each stream reports the new count against
   the baseline of 59: new lines may only be data-draft, [DRAFT, bracket tokens or the team pending
   marker, never a new Little Rose door, traces or donation line.
3. **Complete at 390 × 844 and 1440 × 900** with JS on, JS off, `prefers-reduced-motion: reduce`,
   `?tier=0` and pause on. All text and links are present, no hidden text, no horizontal scroll at
   320, one H1, 0 console errors, 0 4xx. Captures go to `qa/m4/<stream>/`.
4. **Tastegate passes** on every new or changed page at both sizes (exit 0; warnings listed), and a
   person looks at the screenshots: no hard line anywhere (0.5), no AI-slop defaults.
5. **Contrast bars hold:** H1 at least 4.5:1, running text at least 7:1 over the real background,
   including over the opening stages at every scroll position the strip captures. Run
   `npm run check:contrast` (light-contrast.mjs) and add the new pairs to `qa/contrast-pairs.json`.
6. **Motion:** the openings follow native scroll (no Lenis or GSAP on inner pages); the H1, figures
   and form values never animate; no number counts up; Vietnamese is never uppercased
   (`vi-text.test.mjs`); line-height is at least 1.6.
7. **Safeguarding:** `little-rose-page.test.mjs` passes; no names, photos or invented partners or
   staff; every unverified figure carries `data-pending`; no donation details.
8. **Performance:** mobile LCP under 2.5 s on Contact and News (`lcp.cjs`, median of 3); CLS under
   0.1; the openings' JS chunk under 2 KB gzip; main CSS growth reported.
9. **Walls (stream 3):** Maxwell signs off a before/after strip (hero, corridor bay, a header still)
   showing real plaster character; poster budgets hold; `light-contrast` and `check-sun-crisp` pass;
   the Little Rose tests pass.

## 9. Open questions for HCWA (none block the build)

Mission, vision and values wording; the legal status line; every year in Our story; who agrees to be
on the Team page; whether CapitaLand Vietnam may be listed on a partners page; the safeguarding,
privacy and terms texts and PDFs; what Little Rose's page may say about the shelter's work, and
whether gifts to it use a separate account; how bakery orders are taken; the in-kind needs list; the
Cầu Hàn EN slug (`cau-han-project` or another); the generated wall look (6.4).
