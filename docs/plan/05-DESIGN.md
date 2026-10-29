# Design system (DESIGN.md)

Direction line (tastegate step 1):
> Reading this as: a trust-first NGO site for Vietnamese families, local donors and
> international partners, in a warm, plain, editorial language, built on Fraunces +
> Be Vietnam Pro + HCWA blue on warm paper with a sun-yellow action color, and one
> signature detail: each center carries its own logo color as a thin band, so the HCWA
> "family" of units reads at a glance.

Dials: variance 4, motion 2, density 4.

The brief's words to design against: friendly, welcoming, clean, trustworthy, not
crowded, clarity over effects, fast.

## Color tokens

Provisional values. Task 1.1 samples the real brand blue from the official logo SVG and
the unit accents from each unit's logo, then runs `design-token-contrast`.

```css
:root {
  /* neutrals: warm paper, never pure white page / pure black text */
  --paper:      #FBF8F3;  /* page background */
  --surface:    #FFFFFF;  /* cards, header, forms */
  --sunken:     #F3EEE6;  /* alternate section background */
  --line:       #E4DDD2;  /* borders, dividers */
  --ink:        #1C2333;  /* body and headings */
  --ink-2:      #4A5265;  /* secondary text (check ≥ 4.5:1 on --paper and --sunken) */
  --ink-3:      #6B7285;  /* meta text, only at ≥ 15px; check contrast */

  /* brand (sample from logo) */
  --blue-900:   #16275C;
  --blue-700:   #1E3A8A;  /* links, secondary buttons, footer bg */
  --blue-100:   #E6ECF8;
  --on-blue:    #FFFFFF;

  /* action: carried over from the current site's yellow buttons */
  --sun-400:    #FFC42E;  /* primary button bg, text = --ink */
  --sun-500:    #F2B312;  /* hover */
  --sun-100:    #FFF4D6;  /* announcement bar, soft accent bg */

  /* unit accents (sample from each unit logo; bands and labels only) */
  --unit-green-bamboo: #2E8B4A;
  --unit-little-rose:  #C94E73;
  --unit-cau-han:      #2F6FB8;

  /* status */
  --ok:    #2F7D4F;
  --warn:  #8A5A00;
  --error: #B42318;
  --focus: #1E3A8A;  /* 3px outline + 2px offset */
}
```

Rules:
- One action color (sun) for Donate and the primary button only. Everything else is
  blue, ink or outline. If yellow appears in more than two places per screen, cut one.
- Unit accent colors never carry body text. They appear as a 4px top band on center
  cards and page headers, and as the dot in a small label.
- No gradients, no glows, no blue photo overlays (the current hero's blue tint goes).
- Dark mode: not in scope.

## Typography

| Role | Font | Notes |
|---|---|---|
| Display / headings | Fraunces (variable, opsz + SOFT axes) | Warm serif with Vietnamese support. Use SOFT 50, weight 560 to 640. |
| Body / UI | Be Vietnam Pro | Designed for Vietnamese. 400 / 500 / 600 only. |
| Numbers in impact strip | Fraunces, tabular figures | |

Before building, task 1.1 confirms both families ship the `vietnamese` subset and renders
this test line at every size: "Hội Bảo trợ Trẻ em Thành phố Hồ Chí Minh: những đứa trẻ
ở Quận 7 được học, được bảo vệ." If Fraunces stacks diacritics badly at heading sizes,
switch display to Lora or Literata.

Self-host WOFF2 files, `font-display: swap`, preload only the body regular and the
heading weight used in the hero.

Scale (rem, fluid where it matters):

```css
--fs-meta: 0.875rem;                                  /* 14: dates, badges */
--fs-sm:   0.9375rem;                                 /* 15 */
--fs-base: 1.0625rem;                                 /* 17: body */
--fs-lg:   1.25rem;                                   /* 20: lead paragraph */
--fs-h4:   1.375rem;
--fs-h3:   clamp(1.5rem, 1.3rem + 0.8vw, 1.875rem);
--fs-h2:   clamp(1.875rem, 1.5rem + 1.6vw, 2.75rem);
--fs-h1:   clamp(2.25rem, 1.6rem + 2.8vw, 3.75rem);
--fs-stat: clamp(2.5rem, 2rem + 2vw, 3.5rem);
```

- Line height: body 1.65; headings 1.2 (Vietnamese needs the extra room vs the usual 1.1).
- Measure: 60 to 72 characters for body text.
- Sentence case for every heading and menu item in both languages. Never uppercase VI.
- Letter-spacing: 0 for body, -0.01em for h1/h2 only. The current site's wide tracking goes.

## Space, radius, elevation

```css
--s-1: 4px; --s-2: 8px; --s-3: 12px; --s-4: 16px; --s-5: 24px;
--s-6: 32px; --s-7: 48px; --s-8: 64px; --s-9: 96px; --s-10: 128px;
--section-y: clamp(64px, 8vw, 112px);
--container: 1200px;   --gutter: clamp(16px, 4vw, 32px);
--r-sm: 6px;  --r-md: 12px;  --r-lg: 20px;  --r-pill: 999px;
--shadow-1: 0 1px 2px rgba(28,35,51,.06), 0 4px 12px rgba(28,35,51,.06);
```

Spacing inside a group is always tighter than between groups. Cards only where
something is clickable as a unit (centers, stories, reports); lists and dividers
elsewhere.

## Motion

- Hover/press: 150ms, `cubic-bezier(.215,.61,.355,1)`. Buttons darken and move 1px on
  press; cards lift 2px with `--shadow-1`.
- Menus and accordions: 240ms height/opacity.
- Section entrance: one gentle fade-up (8px, 400ms) the first time a section enters the
  viewport. Content is visible by default; the class is added by JS, so no JS = no
  hidden content.
- Impact numbers do NOT count up. Numbers are the payload; they appear instantly.
- No parallax, scroll-jacking, auto-playing carousels or auto-playing video.
- `prefers-reduced-motion: reduce` disables the fade-up and all transitions over 150ms.

## Photo and illustration style

- Natural light, eye level, activity over posing: learning, playing, making, community
  sessions. Hands, backs, groups at a distance and over-the-shoulder shots are good
  defaults.
- Dignity first: no staged sadness, no "poverty" framing.
- Little Rose: illustrations only (see `10-ASSET-REQUEST.md`).
- Photos are never covered by text. The hero is a split layout, so no overlay is needed.
- Every image has real alt text in both languages, written to describe the activity,
  never naming a child.
- Formats: AVIF + WebP with JPEG fallback, `srcset` at 480/768/1200/1600/2400 wide.

## Components (build these once, reuse everywhere)

| Component | Notes |
|---|---|
| Announcement bar | Optional, 1 line, `--sun-100` bg, dismissible, text + link. Editable in admin. |
| Header | Logo left; menu center/right; VI / EN switch; Donate button (sun). Sticky, shrinks to 64px after scroll. |
| Language switch | Text "VI / EN", current one bold + underlined, `aria-current="true"`, `hreflang` and `lang` on each link. |
| Button | Primary (sun, ink text), Secondary (blue outline), Text link with arrow. Min height 48px, pill radius. States: hover, focus-visible, active, disabled, loading. |
| Impact figure | Big number + one-line label + optional year. Pending state shows dashed chip "Đang xác minh / Pending verification" (staging only). |
| Center card | Photo 4:3, 4px unit color band on top, name, since-year, one-line "who we serve", "Visit center →". |
| Program row | Status pill (Active / Completed), years, name, one-line summary, partner names, arrow. Rows, not cards, so programs look different from centers at a glance. |
| Story card | Photo 3:2, category label, title, 2-line excerpt, date. |
| Report row | Cover thumb 3:4, title, year, type, language badge ("PDF · Tiếng Việt"), file size, download. |
| Partner logo | Single color (ink at 70%), same height box, alt = partner name. |
| CTA band | Blue background, one sentence, max two buttons. |
| Form field | Label above, helper text, error below with icon, `aria-describedby`. Consent checkbox with link to privacy notice. |
| Language notice | Inline panel: "Bài viết này hiện chỉ có bằng tiếng Việt." / "This article is only available in Vietnamese." + link to the other version or back to the hub. |
| Breadcrumb | On every page except home. |
| Footer | 4 columns desktop, accordion on mobile (see `06-PAGES.md`). |

Icons: one outline set, 24px grid, 1.5px stroke (Phosphor Regular or Lucide). No emoji
anywhere, including news titles rendered in cards (strip leading emoji from titles in the
card template).

## Do / don't

| Do | Don't |
|---|---|
| Left-aligned headings, plenty of air | Centered walls of text over photos |
| One primary button per section | Three competing yellow buttons |
| Real photos with consent, or illustrations | Stock photos of "sad children" |
| Specific labels: "children attended Charity Class this year" | Vague labels: "lives touched" |
| Sentence case | ALL CAPS, wide letter-spacing |
| Partner logos in one tone | Full-color logo soup |
