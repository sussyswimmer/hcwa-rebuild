# Sitemap, menus, URLs and content model

All labels are proposals. HCWA approves final wording (see glossary in `07-BILINGUAL.md`).

## Main menu (6 items + switch + Donate)

| # | EN label | VI label | EN URL | VI URL |
|---|---|---|---|---|
| 1 | About Us | Về chúng tôi | `/en/about/` | `/ve-chung-toi/` |
| 2 | Our Work | Hoạt động | `/en/our-work/` | `/hoat-dong/` |
| 3 | Impact & Reports | Tác động & Báo cáo | `/en/impact/` | `/tac-dong/` |
| 4 | News | Tin tức | `/en/news/` | `/tin-tuc/` |
| 5 | Get Involved | Chung tay | `/en/get-involved/` | `/chung-tay/` |
| 6 | Contact | Liên hệ | `/en/contact/` | `/lien-he/` |
| | Switch | VI / EN | | |
| | Button | Donate | Ủng hộ | `/en/get-involved/donate/` | `/chung-tay/ung-ho/` |

Dropdowns (desktop) / accordion (mobile):
- About Us: Who we are · Our story · Team · Partners & sponsors · Policies
- Our Work: Centers (each listed) · Projects & programs (each active one listed) · All work
- Get Involved: Donate · Volunteer · Partner with us · In-kind gifts · Little Rose Bakery

## Full page tree

```
/ (VI home)                         /en/ (EN home)
├── ve-chung-toi/                   ├── about/
│   ├── cau-chuyen/                 │   ├── our-story/
│   ├── doi-ngu/                    │   ├── team/
│   ├── doi-tac/                    │   ├── partners/
│   └── chinh-sach/                 │   └── policies/   (safeguarding, privacy, terms)
├── hoat-dong/                      ├── our-work/
│   ├── co-so/{slug}/               │   ├── centers/{slug}/
│   └── du-an/{slug}/               │   └── programs/{slug}/
├── tac-dong/                       ├── impact/
│   └── bao-cao/{slug}/             │   └── reports/{slug}/
├── tin-tuc/                        ├── news/
│   ├── cau-chuyen/ (category)      │   ├── stories/
│   ├── su-kien/    (category)      │   ├── events/
│   └── {post-slug}/                │   └── {post-slug}/
├── chung-tay/                      ├── get-involved/
│   ├── ung-ho/                     │   ├── donate/
│   ├── tinh-nguyen/                │   ├── volunteer/
│   ├── doi-tac-doanh-nghiep/       │   ├── partner/
│   └── hien-vat/                   │   └── in-kind/
├── lien-he/                        ├── contact/
└── 404                             └── 404
```

Note on post URLs: keep existing news post slugs exactly as they are today (only the
`/en/` prefix is added on English). Polylang setting: "The language is set from the
directory name in pretty permalinks", hide the default language (VI) prefix.

## Content types (registered in the `hcwa-core` plugin)

### Center (`hcwa_center`)
Permanent units that HCWA runs. Proposed members, HCWA to confirm:

| VI name | EN name | Since | Who it serves (from current site) |
|---|---|---|---|
| Cơ sở Bảo trợ Xã hội Tre Xanh | Green Bamboo Social Protection Center | 1993 (as Cầu Muối club; community social work model since July 2020) | Disadvantaged children and families, community-based |
| Mái ấm Hoa Hồng Nhỏ | Little Rose Warm Shelter | 1992 | Girls aged 12 to 18 who survived or are at risk of sexual abuse |
| Dự án Cầu Hàn | Cau Han Project | 1997 | Children and families in District 7 affected by HIV/AIDS, migrant families, children who left school |

Fields: name, short summary (max 160 chars), since (year), who we serve, what we do
(list), location (ward/city only by default; full address only if HCWA approves), contact
person (optional, approval flag), phone, email, accent color (from the unit's own logo),
unit logo, cover photo, gallery, "how to help this center" text, related program(s),
news tag.

### Program (`hcwa_program`)
Time-bound or funded work. Proposed members, HCWA to confirm:

| VI name | EN name | Notes |
|---|---|---|
| Chương trình Cùng Vươn Lên | Thrive Together: Advancing Child Resilience in HCMC | Under CapitaLand Community Resilience Initiative (CCRI) |
| Lớp học tình thương | The Charity Class | Currently under Green Bamboo in the menu; Cau Han page also runs one |
| Dự án thí điểm thúc đẩy quyền trẻ em khuyết tật | Pilot project promoting the rights of children with disabilities | Closing conference held early 2026, so status = completed |

Fields: name, summary, status (active / completed / planned), start and end year, focus
area(s), partners/funders (relation to Partner), linked center (optional), goals,
activities, results (each result is an impact figure, so it follows the verified rule),
documents, cover photo, news tag.

### Impact figure (`hcwa_impact`)
One row per number. Fields: value (number), unit/suffix ("+", "%"), label, source note
(internal), year it refers to, `verified` (yes/no), verified by + date, display order,
scope (organization / center X / program Y).
Rendering rule: on production, figures with `verified = no` do not render, and a section
with zero verified figures hides itself. On staging/prototype they render with a dashed
"Pending verification / Đang xác minh" chip.

### Report (`hcwa_report`)
Fields: title, year, type (annual report / financial statement / program report /
policy), document language (vi / en / both), PDF file, cover thumbnail, 2-line summary.
The listing always shows a language badge, e.g. "PDF · Tiếng Việt" on the EN site, as the
brief asks.

### Partner (`hcwa_partner`)
Fields: name, logo (SVG/PNG), website, type (corporate / NGO / government / foundation /
academic), active years, show on homepage (yes/no). HCWA confirms permission to show
each logo.

### Taxonomies
- Focus area (shared by centers, programs, posts): proposed four, taken from the "main
  activities" on the current About page: Education · Child protection · Family &
  community support · Policy & advocacy. HCWA to confirm.
- Post categories: News (Tin tức), Stories (Câu chuyện), Events (Sự kiện), Announcements
  (Thông báo).

### Photo approval
Add two media fields: `approved_for_web` (yes/no) and `identifiable_child` (yes/no). The
center/program/story templates refuse to output an image where `approved_for_web` is no
(log a warning in admin). Little Rose templates refuse `identifiable_child = yes`.

## Redirect map (seed list; build the full list in task 3.4)

| Old URL | New URL |
|---|---|
| `/hoi-bao-tro-tre-em/` | `/` |
| `/about-us/` | `/en/about/` |
| `/our-team/` | `/en/about/team/` |
| `/our-sponsors/` | `/en/about/partners/` |
| `/gbws/` | `/en/our-work/centers/green-bamboo/` |
| `/charity-classes/` | `/en/our-work/programs/charity-class/` |
| `/lrws/` | `/en/our-work/centers/little-rose/` |
| `/chp-2/` | `/en/our-work/centers/cau-han/` |
| `/dva/`, `/dva-child-abuse-prevention-project/` | `/en/our-work/programs/thrive-together/` |
| `/our-news/` | `/en/news/` |
| `/support-us/` | `/en/get-involved/donate/` |
| `/contact-us/` | `/en/contact/` |
| `/doi-ngu/` | `/ve-chung-toi/doi-ngu/` |
| `/nha-tai-tro/` | `/ve-chung-toi/doi-tac/` |
| `/mai-am-tre-xanh/` | `/hoat-dong/co-so/tre-xanh/` |
| `/lop-hoc-tinh-thuong/` | `/hoat-dong/du-an/lop-hoc-tinh-thuong/` |
| `/mai-am-hoa-hong-nho/` | `/hoat-dong/co-so/hoa-hong-nho/` |
| `/du-an-cau-han/` | `/hoat-dong/co-so/cau-han/` |
| `/dva2/` | `/hoat-dong/du-an/cung-vuon-len/` |
| `/tin-moi-2/` | `/tin-tuc/` |
| `/ve-chung-toi/`, `/lien-he/` | unchanged |
| English news posts `/{slug}/` | `/en/{slug}/` |
| Vietnamese news posts `/{slug}/` | unchanged |

The root `/` changing from English to Vietnamese is the one tricky case: visitors whose
browser language is English and who land on `/` should see a small, dismissible bar
"View this page in English →" linking to `/en/`. No automatic redirect by browser
language (it confuses search engines and returning visitors).
