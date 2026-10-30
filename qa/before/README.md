# Live site before the rebuild (task 0.1)

Screenshots and measurements of hcwa.org.vn as it stood on 30 Oct 2026, made by
`scripts/live-audit.py`. Findings are written up in `docs/plan/01-AUDIT.md` under
"Mobile audit".

- `<page>-mobile.jpg`: full page at 390×844, iPhone user agent, touch on
- `<page>-desktop.jpg`: full page at 1440×900
- `home-*-mobile-menu.jpg`: first screen on a phone with the menu opened
- `report.json`: the measurements behind the findings (sideways scroll, text under 12px,
  tap targets under 24px, fixed overlays, ALL CAPS Vietnamese, alt text, script counts)

## Every photo is blurred on purpose

This repository is public and the live pages carry photos of children. The audit script
blurs every image before the page renders, blocks video, and blurs social embeds
(Instagram feed, Facebook box). Layout, text and spacing are unchanged, which is all the
audit needs. Logos are blurred too: only SVG icons and images under 48px stay sharp,
because the live hero photo of children is a file named "Hình-có-logo.jpg".

Bank and donation details are covered with solid grey boxes: any line with an account number, bank or
SWIFT label, account holder, email or QR mention. They stay out of this repo until HCWA
finance re-confirms them in writing (see `CLAUDE.md`).

Do not replace these with unblurred screenshots, and do not add Little Rose pages to the
set. If HCWA needs to see a photo in context, open the live page.
