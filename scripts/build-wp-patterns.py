#!/usr/bin/env python3
"""Writes the ten page patterns of the hcwa-2026 theme (docs/plan/11-WP-PORT.md 4.4) from
content/draft/*.json, so their starter copy always equals the prototype's.

    python3 scripts/build-wp-patterns.py           # write wp/themes/hcwa-2026/patterns/page-*-{vi,en}.php
    python3 scripts/build-wp-patterns.py --check   # exit 1 and name the stale files; writes nothing

Each pattern is one hcwa/sections block (locked against moving and removal, its inner blocks
locked with templateLock) holding the page's hcwa/* blocks; every editable text is a plain
block attribute. A content field marked "draft": true is stored as "[DRAFT: <text>]", whole,
so hcwa_draft_text() prints it back exactly as the prototype does (2.1 rule 7, 3.3). Block
attributes are serialized the way WordPress's serialize_block_attributes() does, so the
editor reads the pattern back without a change.

Standard library only.
"""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CONTENT = ROOT / "content" / "draft"
PATTERNS = ROOT / "wp" / "themes" / "hcwa-2026" / "patterns"
LANGS = ("vi", "en")
# The lock of the sections container (4.2): the spike's result is recorded in wp/CONTRACT.md.
TEMPLATE_LOCK = "contentOnly"


def load(name: str) -> dict:
    return json.loads((CONTENT / f"{name}.json").read_text(encoding="utf-8"))


def text(field, lang: str) -> str:
    """One language of a {vi, en, draft?} field, wrapped whole in [DRAFT: …] when it is draft."""
    if field is None:
        return ""
    if isinstance(field, str):
        return field
    value = str(field.get(lang, "")).strip()
    if field.get("draft") and value:
        return f"[DRAFT: {value}]"
    return value


def serialize_attrs(attrs: dict) -> str:
    """serialize_block_attributes() (wp-includes/blocks.php): JSON without escaped slashes or
    Unicode, then --, <, >, & and \\" as Unicode escapes."""
    s = json.dumps(attrs, ensure_ascii=False, separators=(",", ":"))
    s = s.replace("--", "\\u002d\\u002d").replace("<", "\\u003c").replace(">", "\\u003e").replace("&", "\\u0026")
    return s.replace('\\"', "\\u0022")


def block(name: str, attrs: dict | None = None, inner: list[str] | None = None) -> str:
    attrs = {k: v for k, v in (attrs or {}).items() if v not in ("", [], None)}
    head = f"<!-- wp:{name}" + (" " + serialize_attrs(attrs) if attrs else "")
    if inner is None:
        return head + " /-->"
    return head + " -->\n" + "\n\n".join(inner) + "\n<!-- /wp:" + name + " -->"


def sections(inner: list[str]) -> str:
    return block("hcwa/sections", {"templateLock": TEMPLATE_LOCK, "lock": {"move": True, "remove": True}}, inner)


def day(variant: str, inner: list[str]) -> str:
    return block("hcwa/day", {"variant": variant, "templateLock": TEMPLATE_LOCK}, inner)


def ways(involve: dict, lang: str) -> list[dict]:
    return [{"id": w["id"], "title": text(w["title"], lang), "text": text(w["text"], lang)} for w in involve["ways"]]


def home(c: dict, lang: str) -> str:
    site, work, involve = c["site"], c["work"], c["involve"]
    return sections([
        block("hcwa/hero", {"title": text(site["hero"]["title"], lang), "intro": text(site["hero"]["intro"], lang)}),
        block("hcwa/passage", {"title": text(site["whatWeDo"]["title"], lang), "statement": text(site["whatWeDo"]["summary"], lang)}),
        block("hcwa/corridor", {"intro": text(site["centersIntro"], lang)}),
        block("hcwa/work-sheet", {"focusTitle": text(work["focusTitle"], lang)}),
        block("hcwa/news-latest"),
        block("hcwa/shutters"),
        block("hcwa/help", {"title": text(site["helpTitle"], lang), "ways": ways(involve, lang)}),
        block("hcwa/partner-wall", {"title": text(site["partnersTitle"], lang)}),
        block("hcwa/dusk-band", {"anchor": "contact", "titleId": "contact-title", "title": text(site["contactBand"]["title"], lang),
                                 "lines": ["phone", "email"], "actions": ["contact"]}),
    ])


def our_work(c: dict, lang: str) -> str:
    work = c["work"]
    return sections([
        block("hcwa/lit-header", {"still": "header-our-work", "size": "display", "intro": text(work["hubIntro"], lang)}),
        day("hub", [block("hcwa/work-hub", {"centersLead": text(work["hub"]["centersLead"], lang),
                                            "programsLead": text(work["hub"]["programsLead"], lang)})]),
        block("hcwa/dusk-band", {"titleId": "hub-cta", "title": text(work["hub"]["ctaTitle"], lang), "actions": ["donate", "volunteer"]}),
    ])


# The Get Involved page's ways (prototype pages/involve.mjs waysList(), M7 one-object ways): each way's
# name and line, its page's list (from involve-pages.json), the words of its links into that page
# (in the order hcwa_ways_targets() gives their targets) and its one button.
def involve_ways(c: dict, lang: str) -> list[dict]:
    involve, pages = c["involve"], c["involve-pages"]
    d, v, p, k = involve["donatePage"], pages["volunteer"], pages["partner"], pages["inKind"]
    detail = {
        "donate": {"links": [d["inVietnam"], d["fromAbroad"], d["tellTitle"]], "cta": d["howTitle"]},
        "volunteer": {"label": v["lookTitle"], "items": [a["title"] for a in v["areas"]], "links": [v["expectTitle"], v["safetyTitle"]]},
        "partner": {"label": p["waysTitle"], "items": [w["title"] for w in p["ways"]], "links": [p["contactTitle"]]},
        "in-kind": {"note": k["first"], "links": [k["needsTitle"]]},
    }
    out = []
    for w in involve["ways"]:
        x = detail[w["id"]]
        row = {"id": w["id"], "title": text(w["title"], lang), "text": text(w["text"], lang)}
        if "label" in x:
            row["label"] = text(x["label"], lang)
            row["items"] = [text(t, lang) for t in x["items"]]
        if "note" in x:
            row["note"] = text(x["note"], lang)
        row["links"] = [text(t, lang) for t in x["links"]]
        row["cta"] = text(x.get("cta") or w["cta"], lang)
        out.append(row)
    return out


def get_involved(c: dict, lang: str) -> str:
    site, involve = c["site"], c["involve"]
    return sections([
        block("hcwa/involve-head", {"title": text(involve["welcomeTitle"], lang), "intro": text(involve["intro"], lang),
                                    "seeWays": text(involve["seeWays"], lang)}),
        day("involve", [
            block("hcwa/ways", {"title": text(involve["waysLabel"], lang), "ways": involve_ways(c, lang)}),
            block("hcwa/faq", {"title": text(involve["faqTitle"], lang), "faq": [{"q": text(f["q"], lang)} for f in involve["faq"]],
                               "answer": text(involve["faqAnswer"], lang)}),
        ]),
        block("hcwa/dusk-band", {"titleId": "gi-contact", "title": text(site["contactBand"]["title"], lang),
                                 "lines": ["phone", "email"], "actions": ["contact"]}),
    ])


DONATE_KEYS = ("howTitle", "tabsLabel", "inVietnam", "inVietnamShort", "fromAbroad", "fromAbroadShort", "qr", "pendingNote",
               "paypal", "toCenterTitle", "toCenter", "tellTitle", "tellLead", "amount", "amountError", "giftFor", "giftForAny", "thanks")


def fields(items: list[dict], lang: str) -> list[dict]:
    out = []
    for f in items:
        row = {"id": f["id"], "label": text(f["label"], lang), "copy": bool(f.get("copy"))}
        if f.get("hint"):
            row["hint"] = text(f["hint"], lang)
        out.append(row)
    return out


def donate(c: dict, lang: str) -> str:
    involve = c["involve"]
    d = involve["donatePage"]
    methods = {k: text(d[k], lang) for k in DONATE_KEYS}
    methods["bankFields"] = fields(involve["bankFields"], lang)
    methods["abroadFields"] = fields(involve["abroadFields"], lang)
    return sections([
        block("hcwa/sun-head", {"intro": text(d["intro"], lang), "go": text(d["inVietnam"], lang),
                                "jumpAbroad": text(d["fromAbroad"], lang), "jumpTell": text(d["tellTitle"], lang)}),
        day("donate", [
            block("hcwa/gifts", {"title": text(d["giftTitle"], lang),
                                 "gifts": [{"amount": text(g["amount"], lang), "text": text(g["text"], lang)} for g in involve["giftExamples"]]}),
            block("hcwa/donation-methods", methods),
        ]),
        block("hcwa/dusk-band", {"titleId": "donate-questions", "title": text(d["questionsTitle"], lang),
                                 "lines": ["phone", "sponsorship"], "actions": ["contact"]}),
    ])


def contact(c: dict, lang: str) -> str:
    p = c["involve"]["contactPage"]
    return sections([
        block("hcwa/lit-header", {"still": "header-contact", "size": "display", "intro": text(p["intro"], lang), "actions": "reach",
                                  "callUs": text(p["callUs"], lang), "writeUs": text(p["writeUs"], lang)}),
        day("contact", [
            block("hcwa/contact-reach", {"formTitle": text(p["formTitle"], lang), "officeTitle": text(p["officeTitle"], lang),
                                         "chat": text(p["chat"], lang), "chatPending": text(p["chatPending"], lang)}),
            block("hcwa/contact-units", {"littleRose": text(p["littleRose"], lang), "centerPending": text(p["centerPending"], lang)}),
        ]),
    ])


PAGES = {
    "page-home": (home, ("Trang chủ", "Home page"), "trang chủ, home, starter",
                  ("Toàn bộ trang chủ theo bản mẫu: mở đầu, lối sáng, hành lang các cơ sở, lĩnh vực, tin, cửa chớp, cách giúp, đối tác, liên hệ.",
                   "The whole home page as in the prototype: hero, passage, centers corridor, areas of work, news, shutters, ways to help, partners, contact.")),
    "page-our-work": (our_work, ("Hoạt động", "Our Work"), "hoạt động, our work, centers, programs",
                      ("Trang Hoạt động: đầu trang, bộ lọc, các cửa cơ sở, các dự án, dải hoàng hôn.",
                       "The Our Work page: header, filters, center doors, programs, evening band.")),
    "page-get-involved": (get_involved, ("Chung tay", "Get Involved"), "chung tay, get involved, volunteer, faq",
                          ("Trang Chung tay: đầu trang với khung cửa sổ mở, từng cách chung tay, câu hỏi thường gặp, dải hoàng hôn.",
                           "The Get Involved page: header with the open window, the ways to help one by one, common questions, evening band.")),
    "page-donate": (donate, ("Ủng hộ", "Donate"), "ủng hộ, donate, bank, transfer",
                    ("Trang Ủng hộ: đầu trang, ví dụ khoản ủng hộ, phiếu chuyển khoản, báo cho Hội, liên kết tin cậy, dải hoàng hôn.",
                     "The Donate page: header, gift examples, transfer slip, tell us you gave, trust links, evening band.")),
    "page-contact": (contact, ("Liên hệ", "Contact"), "liên hệ, contact, form, office",
                     ("Trang Liên hệ: đầu trang, biểu mẫu, văn phòng, các cơ sở.",
                      "The Contact page: header, form, office, centers.")),
}
LANG_NAME = {"vi": "Tiếng Việt", "en": "English"}


def render(name: str, lang: str, c: dict) -> str:
    build, titles, keywords, desc = PAGES[name]
    i = LANGS.index(lang)
    return f"""<?php
/**
 * Title: {titles[0]} / {titles[1]} ({LANG_NAME[lang]})
 * Slug: hcwa-2026/{name}-{lang}
 * Categories: hcwa
 * Keywords: {keywords}
 * Viewport Width: 1400
 * Block Types: core/post-content
 * Post Types: page
 * Description: {desc[i]}
 *
 * GENERATED by scripts/build-wp-patterns.py from content/draft/*.json. Do not edit: change the
 * content and run the script again (wp-static-check.py fails while this file is stale).
 * The page is one locked hcwa/sections block (11-WP-PORT.md 4.2, 4.4); staff change words only.
 *
 * @package hcwa-2026
 */

defined( 'ABSPATH' ) || exit;
?>
{build(c, lang)}
"""


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--check", action="store_true", help="exit 1 and name the stale patterns; write nothing")
    args = ap.parse_args()
    c = {name: load(name) for name in ("site", "work", "involve", "involve-pages")}
    stale = []
    for name in PAGES:
        for lang in LANGS:
            path = PATTERNS / f"{name}-{lang}.php"
            want = render(name, lang, c)
            have = path.read_text(encoding="utf-8") if path.exists() else None
            if have == want:
                continue
            if args.check:
                stale.append(path.relative_to(ROOT).as_posix())
            else:
                path.write_text(want, encoding="utf-8")
                print(f"wrote {path.relative_to(ROOT).as_posix()}")
    if stale:
        print("stale patterns: " + ", ".join(stale), file=sys.stderr)
        return 1
    if args.check:
        print(f"{len(PAGES) * len(LANGS)} patterns are up to date")
    return 0


if __name__ == "__main__":
    sys.exit(main())
