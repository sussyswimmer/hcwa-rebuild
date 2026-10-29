# Bilingual spec (VI / EN)

HCWA's brief: the English site should "look and feel as complete and welcoming as the
Vietnamese one, not like an afterthought". An English visitor must be able to go home →
project page → contact/support without landing on Vietnamese-only content.

## Polylang configuration

- Languages: Tiếng Việt (`vi`, default), English (`en`).
- URL mode: language from directory; hide the directory for the default language. So
  VI = `/…`, EN = `/en/…`.
- "Detect browser language": OFF. Instead show the dismissible "View this page in
  English →" bar on VI pages when `navigator.language` starts with `en` and the visitor
  hasn't dismissed it (remember in `localStorage`, wrapped in try/catch).
- Media: enable translation for media so alt text and captions exist in both languages.
- Synchronization: sync featured image, page parent, taxonomies and custom fields that
  aren't text (years, status, verified flag, PDF file) between translations, so staff
  only enter them once.
- `hreflang`: Polylang outputs `vi`, `en` and `x-default` (= VI). Check it's present on
  every template, including archives and CPT singles.
- `<html lang>` correct on every page; any inline foreign-language phrase gets its own
  `lang` attribute.

## The switch

- Text, not flags: `VI / EN`. Accessible names: "Tiếng Việt" and "English".
- Always links to the translation of the current page.
- If there's no translation:
  - Pages and CPTs: this should not happen at launch (see coverage check below).
  - News posts: link to the post's language notice. On an untranslated VI post viewed
    by an English visitor, show the panel "This article is only available in Vietnamese"
    with links to "Read it in Vietnamese" and "See English news". Never silently drop
    the visitor on the VI homepage.

## What must be translated (all of it)

- Menus (one menu per language per location)
- Theme strings in PHP templates: register with `pll_register_string()` in `hcwa-core`,
  group "HCWA UI", output with `pll__()`. Never hard-code a visible string.
- Block patterns: one pattern per language, or patterns that contain only placeholders
  editors fill per language. Decide in task 2.1.
- Forms: field labels, placeholders, helper text, validation errors, success messages,
  email notifications to the visitor, topic dropdown options.
- Dates and numbers: VI `08/02/2026` and `300.000`; EN `8 Feb 2026` and `300,000`. Use
  `date_i18n()` and `number_format_i18n()` with the current locale, never manual strings.
- SEO title + meta description + social image text per language.
- 404, search results, empty states, pagination labels ("Trang sau / Next page").
- PDF labels: language badge on every document link.

## Translation coverage check (run before every review and before launch)

A WP-CLI command in `hcwa-core`:
`wp hcwa coverage` → lists every published page, center, program, report, partner, menu
item and registered string that is missing an EN or VI counterpart. Launch rule: zero
missing for pages and CPTs; news posts may be VI-only but must have the language notice.

## Writing English for HCWA

- Short sentences, plain words, no idioms that don't travel (many readers speak English
  as a second language too).
- Translate meaning, not word order. "Cơ sở Bảo trợ Xã hội" literally is "Social
  Protection Facility", but "Green Bamboo Social Protection Center" reads naturally.
- Keep Vietnamese proper names with diacritics in brackets the first time on EN pages:
  "Little Rose Warm Shelter (Mái ấm Hoa Hồng Nhỏ)".
- HCWA approves all final wording. Claude Code drafts with the `anti-slop` skill and
  marks drafts.

## Glossary (proposed; HCWA confirms)

| Vietnamese | English | Note |
|---|---|---|
| Hội Bảo trợ Trẻ em TP. Hồ Chí Minh | Ho Chi Minh City Child Welfare Association (HCWA) | Use "HCWA" after first mention |
| Về chúng tôi | About Us | |
| Hoạt động | Our Work | Alternative VI: "Chương trình & Dự án" |
| Tác động & Báo cáo | Impact & Reports | |
| Tin tức | News | Replaces "Tin mới" |
| Câu chuyện | Stories | |
| Sự kiện | Events | |
| Chung tay | Get Involved | Alternative VI: "Tham gia" |
| Ủng hộ | Donate | Button label |
| Tình nguyện | Volunteer | |
| Đối tác / Hợp tác | Partner with us | |
| Hiện vật | In-kind gifts | |
| Liên hệ | Contact | |
| Cơ sở / Đơn vị trực thuộc | Centers | Or "Centres": decision Q11 |
| Dự án & chương trình | Projects & programs | |
| Đang hoạt động / Đã hoàn thành | Active / Completed | Status pills |
| Đang xác minh | Pending verification | Staging-only chip |
| Cơ sở Bảo trợ Xã hội Tre Xanh | Green Bamboo Social Protection Center | |
| Lớp học tình thương | The Charity Class | |
| Mái ấm Hoa Hồng Nhỏ | Little Rose Warm Shelter | |
| Tiệm bánh Hoa Hồng Nhỏ | Little Rose Bakery | |
| Dự án Cầu Hàn | Cau Han Project | |
| Chương trình Cùng Vươn Lên | Thrive Together | |
| Chính sách bảo vệ trẻ em | Child safeguarding policy | |
| Thông báo quyền riêng tư | Privacy notice | |
| Xem tất cả | See all | |
| Đọc thêm | Read more | |
| Gửi | Send | Replaces "Gửi đi" |
