"""Smoke test of an HCWA WordPress site (Phase 2): every seeded URL in both languages,
at 390x844 and 1440x900.

Checks on every URL and width:
  - HTTP 200 with no redirect (the not-found probes must answer 404)
  - <html lang> is the URL's language
  - hreflang alternates for vi, en and x-default (not on 404 pages): the page's own
    language points at the page, the other language at the seeded translation, x-default
    at the Vietnamese URL, and all of them at the host being tested (a stale port fails)
  - exactly one rendered <h1>
  - no PHP "Warning:", "Notice:", "Deprecated:" or "Fatal error" output, no WordPress
    critical-error or database-error message
  - no sideways scroll
  - no text-transform: uppercase on Vietnamese text
  - not production: <meta name="robots"> has noindex, and pages with impact figures show
    the pending-verification chip
  - --production: no pending chip or pending value anywhere, no noindex, no "[DRAFT"
    anywhere in the HTML, and none of the publish guard's other draft markers in the
    page text: placeholder tokens such as [N], [năm], [year] and bracketed notes that
    mention HCWA (skip both draft checks with --allow-draft)
With a manifest it also sends plain HTTP requests (no browser, redirects not followed)
for gates no page view shows:
  - a center's /embed/ URL answers 404 (embeds are off)
  - /wp-json/wp/v2/media answers 401 to a visitor, in any letter case
  - a center URL under the English base with the Vietnamese slug answers one 301 to the
    Vietnamese permalink on the host being tested
  - the SEO plugin's head stays out of public REST: /wp-json/wp/v2/posts carries no
    yoast_head or yoast_head_json, and /wp-json/yoast/v1/get_head never answers 200
    (401 with Yoast, 404 without); post-sitemap.xml lists no <image:loc>
  - the seed's Little Rose photo fixture (approved, but an identifiable child in a Little
    Rose context): its attachment page answers 404 with no Location header, and the
    Little Rose center page never names its file, so neither the page nor an SEO
    plugin's og:image, twitter:image or JSON-LD prints it
Port checks (docs/plan/11-WP-PORT.md 5.7), on every seeded view unless --legacy:
  - head: the first <script> is the head-inline (it sets data-tier), the second pageReveal;
    #i18n parses with the 11 non-empty keys; the main CSS and the module come from
    assets/dist/ and answer 200; no ?ver on any assets/dist/ URL; the module src equals the
    URL every loaded chunk imports the entry from; no hcwaI18n, Fraunces, jQuery, wp-embed or
    emoji script; on sections pages no global-styles or wp-block-library style
  - <html>: data-tier and data-motion after load, data-lit exactly on the lit pages
  - body children in the 11-WP-PORT.md 2.2 order; .lit-head + .day on lit inner pages;
    main ends on a dusk band where the page has one
  - every language-switch link equals the page's hreflang alternate; every header, sheet
    and footer link on this site answers 200 in the page's language
  - [data-beat] only inside #centers, as many as the seeded centers (production: minus the
    protected ones); data-traces on #centers off production when world.json enables
    traces, never on production
  - Little Rose: no location line, no "visit" link and no unit-colour --spill on a
    protected center; with --production also none of the exported LITTLE_ROSE_DOOR
    patterns, no protected door chip with a door or --unit, and no still listed with
    showsLittleRoseDoor in the HTML or the requests
  - impact numbers (.num, .plaque__value, .count__value) read the same at DOMContentLoaded
    and 3 s later, with no animation or transition
  - no mixed language: on EN pages no Vietnamese-only letter outside [lang=vi] (proper names
    from content/draft excepted); on VI pages no English dictionary phrase
  - every posters/, svg/, fonts/ and img/ request answers 200
  - --production: the exported BLOCKERS on the HTML without scripts and styles (always, even
    with --allow-draft; a digit-only [1] is a footnote), no form[data-form], no partners
    section, no # link without its target, no empty tel: or mailto: link
  - not production: the Contact form submitted without JavaScript answers 200 on the same
    path with no query string
  - --unpublished <file>: every URL listed answers 404
  - the refused Little Rose photo: /?attachment_id=, /?p= and /?page_id= with its id all
    answer 404 with no Location header
The production scans read wp/themes/hcwa-2026/assets/generated/guards.json and stills.json;
until the export has written guards.json they read the same constants from
prototype/src/render.mjs through node, and without stills.json the still scan fails.
--legacy runs only the checks above the port list (the 5 Nov baseline).
Full-page screenshots go to qa/wp/<date>/ with a report.json. Exit code 1 when any
check fails.

Safeguarding: this repo is public. Images from the media library and from other sites are
blurred before the page renders (theme, plugin and core assets stay sharp), and media is
blocked, so a run against a copy of the live site never commits an identifiable child.

Usage:
  .venv/bin/python -I scripts/wp-smoke.py http://127.0.0.1:8881 --manifest .wp-local/main/seed-urls.json
  .venv/bin/python -I scripts/wp-smoke.py https://staging.example.org --production
scripts/wp-local.sh smoke <name> passes the manifest written by the seed (and
--production when the instance is set to production), and uses the repo-local Chromium
in .playwright-browsers/ when it exists (otherwise set PLAYWRIGHT_BROWSERS_PATH yourself).
Without --manifest the built pages from prototype/src/routes.mjs are checked.
"""
import argparse
import datetime
import io
import json
import re
import subprocess
import sys
import time
import unicodedata
import urllib.error
import urllib.request
from pathlib import Path
from urllib.parse import urlsplit

try:
    from playwright.sync_api import Error as PlaywrightError
    from playwright.sync_api import sync_playwright
except ImportError:  # check() still loads without it (wp/tests/runtime-scripts-test.php).
    PlaywrightError, sync_playwright = Exception, None

try:
    from PIL import Image, ImageFilter
except ImportError:  # Without Pillow, uploaded images are blocked instead of blurred.
    Image = None

REPO = Path(__file__).resolve().parent.parent
VIEWPORTS = {"mobile": (390, 844), "desktop": (1440, 900)}
LOCALES = {"vi": "vi-VN", "en": "en-GB"}
IPHONE_UA = ("Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 "
             "(KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1")
DEFAULT_NOT_FOUND = [
    {"lang": "vi", "path": "/khong-co-trang-nay-hcwa-smoke/"},
    {"lang": "en", "path": "/en/no-such-page-hcwa-smoke/"},
]

# PHP errors as printed with html_errors on or off, and WordPress's own error pages.
PHP_ERROR = re.compile(
    r"(?:<b>)?\b(Warning|Notice|Deprecated|Fatal error|Parse error)(?:</b>)?:\s+.{1,600}?\son line\s+(?:<b>)?\d+",
    re.S,
)
PHP_ERROR_TEXT = re.compile(r"(?m)^\s*(?:PHP )?(Warning|Notice|Deprecated|Fatal error|Parse error):\s")
WP_ERROR_TEXT = re.compile(r"There has been a critical error on this website|WordPress database error|Error establishing a database connection")
DRAFT = re.compile(r"\[\s*DRAFT", re.I)
# The publish guard's other draft markers (hcwa_contains_draft() in
# wp/plugins/hcwa-core/includes/publish-guard.php). Keep DRAFT_TOKENS equal to
# hcwa_draft_tokens(); wp/tests/runtime-scripts-test.php compares the two.
DRAFT_TOKENS = ("N", "year", "năm", "amount", "số tiền", "start", "end", "năm bắt đầu", "năm kết thúc",
                "age range", "độ tuổi", "ward", "phường", "date", "ngày", "month/year", "tháng/năm")
DRAFT_TOKEN = re.compile(r"\[\s*(?:" + "|".join(map(re.escape, DRAFT_TOKENS)) + r")\s*\]", re.I)
HCWA_NOTE = re.compile(r"\[[^\[\]=]*\bHCWA\b[^\[\]=]*\]", re.A)  # \b as in PHP's /u (ASCII words)

PAGE_JS = r"""
() => {
  const vw = document.documentElement.clientWidth;
  const rendered = el => el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
  const sel = el => {
    let s = el.tagName.toLowerCase();
    if (el.id) s += '#' + el.id;
    const c = [...el.classList].slice(0, 2);
    return c.length ? s + '.' + c.join('.') : s;
  };
  const ownText = el => [...el.childNodes].filter(n => n.nodeType === 3)
    .map(n => n.textContent).join(' ').replace(/\s+/g, ' ').trim();
  const viChars = /[ăâđêôơưàảãáạằẳẵắặầẩẫấậèẻẽéẹềểễếệìỉĩíịòỏõóọồổỗốộờởỡớợùủũúụừửữứựỳỷỹýỵ]/iu;

  const scrollW = document.documentElement.scrollWidth;
  const sideways = scrollW > vw + 1 ? scrollW - vw : 0;
  const overflow = sideways
    ? [...document.body.querySelectorAll('*')]
        .filter(el => rendered(el) && el.getBoundingClientRect().right > vw + 1)
        .map(sel).slice(-6)
    : [];

  // Uppercase Vietnamese: any rendered text in a Vietnamese context (or with Vietnamese
  // letters) whose computed text-transform is uppercase and that is not already capitals.
  const caps = [];
  for (const el of document.body.querySelectorAll('*')) {
    const text = ownText(el);
    if (!/\p{L}/u.test(text) || text === text.toUpperCase()) continue;
    if (getComputedStyle(el).textTransform !== 'uppercase' || !rendered(el)) continue;
    const langEl = el.closest('[lang]');
    const lang = (langEl ? langEl.getAttribute('lang') : '').toLowerCase();
    if (lang.startsWith('vi') || viChars.test(text)) caps.push(text.slice(0, 60));
  }

  const chips = [...document.querySelectorAll('.chip-pending')];
  return {
    lang: document.documentElement.getAttribute('lang') || '',
    hreflang: [...document.querySelectorAll('link[rel="alternate"][hreflang]')]
      .map(l => ({ lang: (l.getAttribute('hreflang') || '').toLowerCase(), href: l.href })),
    robots: [...document.querySelectorAll('meta[name="robots"]')].map(m => m.content || '').join(', '),
    h1: [...document.querySelectorAll('h1')].filter(el => el.getClientRects().length > 0)
      .map(h => h.textContent.replace(/\s+/g, ' ').trim().slice(0, 80)),
    sideways,
    overflow,
    caps: [...new Set(caps)].slice(0, 6),
    chipsVisible: chips.filter(rendered).length,
    pendingInDom: chips.length + document.querySelectorAll('.pending-value').length,
    text: document.body ? document.body.innerText : '',
  };
}
"""


def load_targets(manifest: Path | None) -> tuple[list[dict], list[dict]]:
    """Seeded URLs and 404 probes, from the seed's manifest or from the prototype routes."""
    if manifest:
        data = json.loads(manifest.read_text(encoding="utf-8"))
        urls = [u for u in data.get("urls", []) if u.get("path")]
        return urls, data.get("not_found") or DEFAULT_NOT_FOUND

    routes = (REPO / "prototype" / "src" / "routes.mjs").read_text(encoding="utf-8")
    urls = []
    pattern = re.compile(r'^\s*(\w+):\s*\{\s*vi:\s*"([^"]+)",\s*en:\s*"([^"]+)"(.*)\},?\s*$', re.M)
    for key, vi, en, rest in pattern.findall(routes):
        page = re.search(r'page:\s*"([^"]+)"', rest)
        if not page or "prototypeOnly" in rest or page.group(1) == "not-found":
            continue
        figures = page.group(1) in ("home", "center", "program")
        for lang, path, other in (("vi", vi, en), ("en", en, vi)):
            urls.append({"id": key, "kind": page.group(1), "lang": lang, "path": path,
                         "translation": other, "figures": figures})
    return urls, DEFAULT_NOT_FOUND


def load_fixtures(manifest: Path | None) -> dict:
    """The seed's test objects (wp-seed.php hcwa_seed_little_rose_photo()), or {}."""
    if not manifest:
        return {}
    data = json.loads(manifest.read_text(encoding="utf-8"))
    return data.get("fixtures") or {}


def make_router(origin: str):
    """Blur raster images that are not theme, plugin or core assets; block media."""
    own_assets = tuple(origin + p for p in ("/wp-content/themes/", "/wp-content/plugins/", "/wp-includes/", "/wp-admin/"))

    def blur(route):
        if Image is None:
            return route.abort()
        try:
            resp = route.fetch()
        except PlaywrightError:
            return route.abort()
        ctype = resp.headers.get("content-type", "")
        if "svg" in ctype or route.request.url.lower().split("?")[0].endswith(".svg"):
            return route.fulfill(response=resp)
        try:
            img = Image.open(io.BytesIO(resp.body()))
            img.seek(0)
            img = img.convert("RGBA")
        except Exception:  # Unreadable formats are blocked, never passed through.
            return route.abort()
        if min(img.size) >= 48:
            img = img.filter(ImageFilter.GaussianBlur(radius=max(8, max(img.size) / 30)))
        buf = io.BytesIO()
        img.save(buf, format="PNG")
        return route.fulfill(status=200, body=buf.getvalue(), headers={"content-type": "image/png"})

    def router(route):
        kind = route.request.resource_type
        if kind == "media":
            return route.abort()
        if kind == "image" and not route.request.url.startswith(own_assets):
            return blur(route)
        return route.continue_()

    return router


def settle(page) -> None:
    """Scroll through the page so lazy images load, then back to the top."""
    height = page.evaluate("document.documentElement.scrollHeight")
    step = max(200, page.viewport_size["height"] // 2)
    for y in range(0, height + step, step):
        page.evaluate(f"window.scrollTo(0, {y})")
        page.wait_for_timeout(60)
    try:
        page.wait_for_load_state("networkidle", timeout=10000)
    except PlaywrightError:
        pass
    page.evaluate("window.scrollTo(0, 0)")
    page.wait_for_timeout(300)


def path_of(url: str) -> str:
    return urlsplit(url).path or "/"


def snippet(text: str, match: re.Match) -> str:
    """The match with a little context, on one line."""
    return re.sub(r"\s+", " ", text[max(0, match.start() - 20):match.end() + 40]).strip()


def check(target: dict, base: str, status: int | None, redirects: list[str], final_url: str, html: str,
          data: dict, production: bool, allow_draft: bool) -> list[str]:
    """Failures for one page view. base is the site root being tested."""
    fails = []
    not_found = target.get("expect") == 404
    lang = target["lang"]

    want_status = 404 if not_found else 200
    if status != want_status:
        fails.append(f"status: HTTP {status}, expected {want_status}")
    if redirects:
        fails.append(f"redirect: {' -> '.join(redirects + [path_of(final_url)])}")

    if not data["lang"].lower().startswith(lang):
        fails.append(f'html lang: "{data["lang"]}", expected {lang}')

    if not not_found:
        found = {}
        for alt in data["hreflang"]:
            code = alt["lang"] if alt["lang"] == "x-default" else alt["lang"].split("-")[0]
            found.setdefault(code, alt["href"])
        missing = [c for c in ("vi", "en", "x-default") if c not in found]
        if missing:
            fails.append("hreflang: missing " + ", ".join(missing))
        other = "en" if lang == "vi" else "vi"
        if lang in found and path_of(found[lang]) != target["path"]:
            fails.append(f"hreflang: {lang} points to {path_of(found[lang])}, expected {target['path']}")
        if target.get("translation") and other in found and path_of(found[other]) != target["translation"]:
            fails.append(f"hreflang: {other} points to {path_of(found[other])}, expected {target['translation']}")
        # 07-BILINGUAL: x-default is the Vietnamese URL.
        vi_path = target["path"] if lang == "vi" else target.get("translation")
        if vi_path and "x-default" in found and path_of(found["x-default"]) != vi_path:
            fails.append(f"hreflang: x-default points to {path_of(found['x-default'])}, expected {vi_path}")
        host = urlsplit(base).netloc
        hosts = sorted({urlsplit(alt["href"]).netloc for alt in data["hreflang"]} - {host})
        if hosts:
            fails.append(f"hreflang: links to {', '.join(hosts)}, expected {host}")

    if len(data["h1"]) != 1:
        fails.append(f"h1: {len(data['h1'])} rendered, expected 1" + (f" {data['h1']}" if data["h1"] else ""))

    php = PHP_ERROR.search(html) or PHP_ERROR_TEXT.search(data["text"])
    if php:
        fails.append("php output: " + re.sub(r"\s+", " ", re.sub(r"<[^>]+>", "", php.group(0)))[:160].strip())
    wp_error = WP_ERROR_TEXT.search(html)
    if wp_error:
        fails.append("wordpress error: " + wp_error.group(0))

    if data["sideways"]:
        fails.append(f"sideways scroll: {data['sideways']}px wider than the screen, from {', '.join(data['overflow'])}")
    if data["caps"]:
        fails.append("uppercase vietnamese: " + " | ".join(data["caps"]))

    if production:
        if data["pendingInDom"]:
            fails.append(f"pending chip: {data['pendingInDom']} pending-verification element(s) on production")
        if not not_found and "noindex" in data["robots"]:
            fails.append(f'robots: "{data["robots"]}" on production')
        if not allow_draft:
            draft = DRAFT.search(html)
            if draft:
                fails.append("draft text: " + snippet(html, draft))
            # Tokens and notes only in the rendered text, so shortcodes and attributes pass.
            text = unicodedata.normalize("NFC", data["text"])
            for pattern in (DRAFT_TOKEN, HCWA_NOTE):
                marker = pattern.search(text)
                if marker:
                    fails.append("draft text: " + snippet(text, marker))
    else:
        if not not_found and "noindex" not in data["robots"]:
            fails.append("robots: no noindex on a non-production site")
        if target.get("figures") and not data["chipsVisible"]:
            fails.append("pending chip: none shown, so the impact figures did not render")
    return fails


# --------------------------------------------------------------------------- port checks (11-WP-PORT.md 5.7)

GENERATED = REPO / "wp" / "themes" / "hcwa-2026" / "assets" / "generated"
I18N_KEYS = ("copy", "copied", "copyFailed", "f_sending", "f_send", "e_summary", "close", "motionPause",
             "motionPlay", "draftHide", "draftShow")
LIT_PAGES = {"page-home", "page-work", "page-center", "page-program", "page-involve", "page-contact"}
SECTIONS_PAGES = LIT_PAGES | {"page-donate", "page-gap"}
DUSK_PAGES = {"page-home", "page-work", "page-center", "page-program", "page-involve", "page-donate"}
# 11-WP-PORT.md 2.2: body children in this order (each optional except the ones the page needs).
BODY_ORDER = [
    ("skip", re.compile(r"^a\b.*\.skip-link")),
    ("proto-note", re.compile(r"^div\b.*\.proto-note")),
    ("en-bar", re.compile(r"^aside\b.*\.en-bar")),
    ("announce", re.compile(r"^div\b.*\.announce-slot")),
    ("header", re.compile(r"^div\b.*\.header-slot")),
    ("canvas", re.compile(r"^canvas#world")),
    ("main", re.compile(r"^main#main")),
    ("footer", re.compile(r"^footer\b.*\.site-footer")),
    ("sheet", re.compile(r"^dialog#menu-sheet")),
]
VI_ONLY = re.compile(r"[ăâđêôơưĂÂĐÊÔƠƯ]|[̣̀́̃̉]")
WORD = re.compile(r"[^\W\d_]+(?:['’][^\W\d_]+)*", re.U)
ASSET = re.compile(r"/(?:posters|svg|fonts|img)/")
ENTRY_IMPORT = re.compile(r"""from\s*["'](\./main-[\w-]+\.js)["']|import\s*\(\s*["'](\./main-[\w-]+\.js)["']\s*\)""")


def load_guards() -> dict:
    """The exported production guards: assets/generated/guards.json, or (until the export has
    written it) the same constants read from prototype/src/render.mjs through node. Never
    re-typed here."""
    f = GENERATED / "guards.json"
    if f.is_file():
        data, source = json.loads(f.read_text(encoding="utf-8")), "assets/generated/guards.json"
    else:
        script = ("import('./prototype/src/render.mjs').then((m) => console.log(JSON.stringify({"
                  "blockers: m.BLOCKERS.map(([re, why]) => ({ source: re.source, flags: re.flags, why })),"
                  "littleRoseDoor: { source: m.LITTLE_ROSE_DOOR.source, flags: m.LITTLE_ROSE_DOOR.flags },"
                  "minPartnerLogos: m.MIN_PARTNER_LOGOS })))")
        proc = subprocess.run(["node", "--input-type=module", "-e", script], cwd=REPO, capture_output=True,
                              text=True, timeout=120)
        if proc.returncode != 0 or not proc.stdout.strip():
            raise RuntimeError("could not read the guards from prototype/src/render.mjs: " + proc.stderr[-400:])
        data, source = json.loads(proc.stdout.strip().splitlines()[-1]), "prototype/src/render.mjs (no guards.json yet)"

    def src(entry) -> str:
        if isinstance(entry, dict):
            return str(entry.get("source") or entry.get("pattern") or "")
        if isinstance(entry, (list, tuple)):
            return str(entry[0]) if entry else ""
        return str(entry or "")

    blockers = []
    for b in data.get("blockers", []):
        why = b[1] if isinstance(b, (list, tuple)) and len(b) > 1 else (b.get("why", "") if isinstance(b, dict) else "")
        blockers.append({"re": re.compile(src(b)), "why": str(why)})
    door = data.get("littleRoseDoor", data.get("little_rose_door"))
    return {"source": source, "blockers": blockers, "door": re.compile(src(door)),
            "footnote": re.compile(src(data.get("footnote")) or r"^\[\d+\]$"),
            "minPartnerLogos": int(data.get("minPartnerLogos", data.get("min_partner_logos", 4)))}


def listed_stills() -> list[str] | None:
    """Still names whose scene shows the Little Rose door (stills.json showsLittleRoseDoor), or
    None while the export has not written stills.json."""
    f = GENERATED / "stills.json"
    if not f.is_file():
        return None
    data = json.loads(f.read_text(encoding="utf-8"))
    stills = data.get("stills", data) if isinstance(data, dict) else {}
    return sorted(n for n, v in stills.items() if isinstance(v, dict) and v.get("showsLittleRoseDoor"))


SCRIPT_STYLE = re.compile(r"<(script|style)\b[^>]*>.*?</\1\s*>", re.S | re.I)
BRACKET = re.compile(r"\[[^\]<]{1,80}\]")


def blocker_hits(html: str, guards: dict) -> list[str]:
    """The exported BLOCKERS on the HTML with scripts and styles removed. A bracket token made
    only of digits is a footnote and passes (11-WP-PORT.md 3.3)."""
    text = SCRIPT_STYLE.sub("", html)
    hits = []
    for b in guards["blockers"]:
        for m in b["re"].finditer(text):
            tokens = BRACKET.findall(m.group(0))
            if tokens and all(guards["footnote"].match(t) for t in tokens):
                continue
            hits.append(f"{b['why']}: {re.sub(r'\s+', ' ', m.group(0))[:100]}")
            break
    return hits


def dictionaries() -> dict:
    """Words and phrases for the mixed-language check, from content/draft/*.json and the UI
    dictionary: English words that carry Vietnamese letters (proper names) and English UI
    phrases of two or more words that differ from their Vietnamese."""
    en_words, phrases = set(), set()

    def walk(node):
        if isinstance(node, dict):
            if isinstance(node.get("en"), str):
                for w in WORD.findall(unicodedata.normalize("NFC", node["en"])):
                    if VI_ONLY.search(unicodedata.normalize("NFD", w)):
                        en_words.add(w)
            for v in node.values():
                walk(v)
        elif isinstance(node, list):
            for v in node:
                walk(v)

    for f in sorted((REPO / "content" / "draft").glob("*.json")):
        try:
            walk(json.loads(f.read_text(encoding="utf-8")))
        except ValueError:
            continue
    for name in ("ui.json", "ui-world.json"):
        f = REPO / "content" / "draft" / name
        data = json.loads(f.read_text(encoding="utf-8")) if f.is_file() else {}
        for key, entry in data.items():
            if not isinstance(entry, dict) or "{" in entry.get("en", "") or entry.get("en") == entry.get("vi"):
                continue
            en = unicodedata.normalize("NFC", entry.get("en", "")).strip()
            if len(en.split()) >= 2 and not en.startswith("["):
                phrases.add(en)
    return {"en_words": en_words, "phrases": sorted(phrases, key=len, reverse=True)}


PORT_JS = r"""
(args) => {
  const de = document.documentElement, body = document.body;
  const sig = (el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + [...el.classList].map(c => '.' + c).join('');
  const skip = new Set(['SCRIPT', 'STYLE', 'LINK', 'NOSCRIPT', 'TEMPLATE']);
  const origin = location.origin;
  let i18n = null, i18nError = '';
  const node = document.getElementById('i18n');
  if (node) { try { i18n = JSON.parse(node.textContent); } catch (e) { i18nError = String(e).slice(0, 120); } }
  const centers = document.getElementById('centers');
  const rendered = (el) => el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
  const runs = [];
  const walker = document.createTreeWalker(body, NodeFilter.SHOW_TEXT);
  for (let t = walker.nextNode(); t; t = walker.nextNode()) {
    const el = t.parentElement;
    if (!el || skip.has(el.tagName) || el.closest('template, script, style, noscript')) continue;
    const text = t.textContent.replace(/\s+/g, ' ').trim();
    if (!text || !rendered(el)) continue;
    const langEl = el.closest('[lang]');
    runs.push({ text, lang: (langEl ? langEl.getAttribute('lang') : '').toLowerCase().slice(0, 2) });
  }
  const protectedEls = [...document.querySelectorAll('[data-unit]')].filter(e => args.protected.includes(e.dataset.unit) && !e.matches('li.door-chip'));
  const ids = new Set([...document.querySelectorAll('[id]')].map(e => e.id));
  return {
    tier: de.getAttribute('data-tier'), motion: de.getAttribute('data-motion'), lit: de.hasAttribute('data-lit'),
    bodyClass: [...body.classList],
    bodyKids: [...body.children].filter(el => !skip.has(el.tagName) && el.id !== 'wpadminbar').map(sig),
    i18n, i18nError, hasI18nGlobal: typeof window.hcwaI18n !== 'undefined',
    langSwitch: [...document.querySelectorAll('[data-lang-switch] a[hreflang]')].map(a => ({ lang: a.getAttribute('hreflang').toLowerCase(), href: a.href })),
    // Header, sheet and footer links on this site, without the language switches (they lead to
    // the other language on purpose).
    menuLinks: [...new Set([...document.querySelectorAll('.site-header a[href], #menu-sheet a[href], .site-footer a[href]')]
      .filter(a => !a.matches('[hreflang], [lang]') && !a.closest('[data-lang-switch], .lang-switch, .en-bar, [lang]:not(html)'))
      .map(a => a.href).filter(h => h.startsWith(origin)).map(h => h.split('#')[0]))],
    litHead: !!document.querySelector('main > .lit-head'),
    litHeadDay: !!document.querySelector('main > .lit-head + .day'),
    endsOnDusk: !!document.querySelector('main > :last-child.dusk'),
    beats: document.querySelectorAll('[data-beat]').length,
    beatsOutside: [...document.querySelectorAll('[data-beat]')].filter(b => !b.closest('#centers')).length,
    hasCenters: !!centers,
    centersTraces: centers ? centers.hasAttribute('data-traces') : null,
    traces: document.querySelectorAll('[data-traces]').length,
    protectedFacts: protectedEls.map(e => ({
      sig: sig(e).slice(0, 80),
      where: !!e.querySelector('.bay__where, .door__where'),
      visit: !!args.visit && e.textContent.includes(args.visit),
      spill: /--spill\s*:/.test(e.getAttribute('style') || '') && !/--spill\s*:\s*var\(--color-sun-100\)/.test(e.getAttribute('style') || ''),
      door: e.getAttribute('data-door'),
      unitVar: /--unit\s*:/.test(e.getAttribute('style') || ''),
    })),
    chips: [...document.querySelectorAll('li.door-chip[data-unit]')].map(li => ({ unit: li.dataset.unit, door: !!li.querySelector('.door-chip__door'), unitVar: /--unit/.test(li.getAttribute('style') || '') })),
    nums: [...document.querySelectorAll('.num, .plaque__value, .count__value')].map(e => ({ text: e.textContent, anim: getComputedStyle(e).animationName, trans: getComputedStyle(e).transitionDuration })),
    numsDCL: window.__hcwaNums || null,
    forms: document.querySelectorAll('form[data-form]').length,
    partners: document.querySelectorAll('section.partners').length,
    danglingHash: [...document.querySelectorAll('a[href^="#"]')].map(a => a.getAttribute('href').slice(1)).filter(id => !id || !ids.has(decodeURIComponent(id))),
    emptyContact: [...document.querySelectorAll('a[href^="tel:"], a[href^="mailto:"]')].filter(a => !a.textContent.trim() || /^(tel|mailto):\s*$/i.test(a.getAttribute('href'))).length,
    modules: [...document.querySelectorAll('script[type="module"][src]')].map(s => s.src),
    runs,
  };
}
"""
NUMS_INIT = ("document.addEventListener('DOMContentLoaded', () => { window.__hcwaNumsAt = performance.now();"
             " window.__hcwaNums = [...document.querySelectorAll('.num, .plaque__value, .count__value')].map(e => e.textContent); });")


def head_scripts(html: str) -> list[str]:
    head = re.search(r"<head\b[^>]*>(.*?)</head>", html, re.S | re.I)
    return re.findall(r"<script\b[^>]*>(.*?)</script\s*>", head.group(1) if head else "", re.S | re.I)


def chunk_imports(chunks: dict[str, str]) -> dict[str, list[str]]:
    """For each loaded chunk URL, the absolute URLs it imports the entry (main-*.js) from."""
    from urllib.parse import urljoin
    out = {}
    for url, body in chunks.items():
        found = [urljoin(url, a or b) for a, b in ENTRY_IMPORT.findall(body)]
        if found:
            out[url] = sorted(set(found))
    return out


def port_checks(target: dict, html: str, port: dict, requests: list[dict], ctx: dict) -> list[str]:
    """Failures of the 11-WP-PORT.md 5.7 checks for one page view (no browser needed).

    requests: [{"url", "status", "type"}] seen while loading; ctx: {"production", "lang",
    "expected_beats", "protected", "listed_stills", "guards", "dicts", "traces_enabled",
    "link_status" (callable url -> (status, lang)), "chunk_imports" ({chunk url: [entry urls]})}."""
    fails: list[str] = []
    lang = target["lang"]
    not_found = target.get("expect") == 404
    production = ctx.get("production", False)
    body_class = set(port.get("bodyClass") or [])
    page_class = next((c for c in body_class if c in SECTIONS_PAGES or c == "page-wp"), "")

    # Head.
    scripts = head_scripts(html)
    if not scripts or "data-tier" not in scripts[0]:
        fails.append("port head: the first <script> in <head> is not the head-inline (it sets data-tier)")
    if len(scripts) < 2 or "pagereveal" not in scripts[1]:
        fails.append("port head: the second <script> in <head> is not pageReveal")
    i18n = port.get("i18n")
    if not isinstance(i18n, dict):
        fails.append("port head: #i18n missing or not JSON" + (f" ({port.get('i18nError')})" if port.get("i18nError") else ""))
    else:
        bad = [k for k in I18N_KEYS if not str(i18n.get(k, "")).strip()]
        if bad:
            fails.append("port head: #i18n lacks " + ", ".join(bad))
    dist = [r for r in requests if "/assets/dist/" in r["url"]]
    css = [r for r in dist if r.get("type") == "stylesheet"]
    if not css or any(r.get("status") != 200 for r in css):
        fails.append("port head: the main CSS does not come from assets/dist/ with 200")
    modules = port.get("modules") or []
    if not modules or "/assets/dist/" not in modules[0]:
        fails.append("port head: the module script does not come from assets/dist/")
    elif not any(r["url"] == modules[0] and r.get("status") == 200 for r in requests):
        fails.append(f"port head: the module {modules[0]} did not answer 200")
    ver = sorted({r["url"] for r in dist if re.search(r"[?&]ver=", r["url"])})
    if ver:
        fails.append("port head: ?ver on assets/dist/ URLs: " + ", ".join(u.rsplit("/", 1)[-1] for u in ver[:4]))
    if modules:
        wrong = {c: imp for c, imp in (ctx.get("chunk_imports") or {}).items() if any(u != modules[0] for u in imp)}
        if wrong:
            fails.append("port head: chunks import the entry from another URL than the module tag: "
                         + ", ".join(f"{c.rsplit('/', 1)[-1]} -> {imp[0].rsplit('/', 1)[-1]}" for c, imp in list(wrong.items())[:3]))
    low = html.lower()
    for label, pattern in (("hcwaI18n", r"hcwai18n"), ("Fraunces", r"fraunces"),
                           ("jQuery", r"<script\b[^>]*\bsrc=[^>]*jquery"),
                           ("wp-embed", r"<script\b[^>]*\bsrc=[^>]*wp-embed|wp-embed(?:\.min)?\.js"),
                           ("emoji script", r"wp-emoji|emoji-release|_wpemojisettings")):
        if re.search(pattern, low) or (label == "hcwaI18n" and port.get("hasI18nGlobal")):
            fails.append(f"port head: {label} on the page")
    if page_class in SECTIONS_PAGES and re.search(r"id=['\"](?:global-styles-inline-css|wp-block-library-css)", html):
        fails.append("port head: global-styles or wp-block-library style on a sections page")

    # <html>, body, shell.
    if not port.get("tier") or not port.get("motion"):
        fails.append("port html: no data-tier or data-motion after load")
    want_lit = page_class in LIT_PAGES
    if bool(port.get("lit")) != want_lit:
        fails.append(f"port html: data-lit {'missing' if want_lit else 'present'} on {page_class or 'a page without a page class'}")
    if not page_class:
        fails.append("port body: no page class (page-home … page-gap, page-wp) on <body>")
    kids, pos = port.get("bodyKids") or [], 0
    for kid in kids:
        for i in range(pos, len(BODY_ORDER)):
            if BODY_ORDER[i][1].search(kid):
                pos = i + 1
                break
        else:
            fails.append(f"port body: <body> child out of the 2.2 order or not ours: {kid[:80]}")
            break
    if page_class in LIT_PAGES - {"page-home"} and not port.get("litHeadDay"):
        fails.append("port body: no main > .lit-head + .day on a lit page")
    if page_class in DUSK_PAGES and not port.get("endsOnDusk"):
        fails.append("port body: main does not end on its dusk band")

    # Links.
    alternates = {}
    for alt in ctx.get("hreflang") or []:
        alternates.setdefault(alt["lang"].split("-")[0], alt["href"])
    for a in port.get("langSwitch") or []:
        code = a["lang"].split("-")[0]
        if not not_found and code in alternates and a["href"] != alternates[code]:
            fails.append(f"port links: language switch {code} is {a['href']}, hreflang says {alternates[code]}")
    link_status = ctx.get("link_status")
    if link_status:
        bad = []
        for url in port.get("menuLinks") or []:
            status, page_lang = link_status(url)
            if status != 200 or (page_lang and not page_lang.startswith(lang)):
                bad.append(f"{urlsplit(url).path} ({status}, {page_lang or '?'})")
        if bad:
            fails.append("port links: menu links not answering 200 in this language: " + ", ".join(bad[:5]))

    # World: beats, traces.
    if port.get("beatsOutside"):
        fails.append(f"port world: {port['beatsOutside']} [data-beat] outside #centers")
    if port.get("hasCenters") and ctx.get("expected_beats") is not None and port.get("beats") != ctx["expected_beats"]:
        fails.append(f"port world: {port.get('beats')} beats, expected {ctx['expected_beats']}")
    if production and port.get("traces"):
        fails.append("port world: data-traces on production")
    if not production and port.get("hasCenters") and ctx.get("traces_enabled") and not port.get("centersTraces"):
        fails.append("port world: no data-traces on #centers off production (world.json enables traces)")

    # Little Rose.
    for f in port.get("protectedFacts") or []:
        what = [label for k, label in (("where", "a location line"), ("visit", "a visit link"), ("spill", "a unit-colour --spill")) if f.get(k)]
        if f.get("door") == "open":
            what.append('data-door="open"')
        if production and not ctx.get("door_approved") and (f.get("door") or f.get("unitVar")):
            what.append("a door before approval")
        if what:
            fails.append(f"port little rose: {f['sig']} has " + ", ".join(what))
    if production and not ctx.get("door_approved"):
        guards = ctx.get("guards")
        if guards and guards["door"].search(html):
            fails.append("port little rose: a LITTLE_ROSE_DOOR pattern in the HTML")
        chips = [c for c in port.get("chips") or [] if c["unit"] in (ctx.get("protected") or []) and (c["door"] or c["unitVar"])]
        if chips:
            fails.append(f"port little rose: {len(chips)} protected door chip(s) with a door or --unit")
        listed = ctx.get("listed_stills")
        if listed is None:
            fails.append("port little rose: assets/generated/stills.json is missing, so the listed stills were not checked")
        else:
            named = sorted({n for n in listed if n in html or any(n in r["url"] for r in requests)})
            if named:
                fails.append("port little rose: listed stills in the HTML or requests: " + ", ".join(named))

    # Impact numbers never count up.
    nums, before = port.get("nums") or [], port.get("numsDCL")
    if nums:
        if before is not None and [n["text"] for n in nums] != before:
            fails.append("port numbers: an impact number changed after DOMContentLoaded")
        moving = [n["text"].strip()[:20] for n in nums if n["anim"] != "none" or any(d.strip() not in ("0s", "") for d in n["trans"].split(","))]
        if moving:
            fails.append("port numbers: animation or transition on " + ", ".join(moving[:4]))

    # Mixed language.
    dicts = ctx.get("dicts") or {"en_words": set(), "phrases": []}
    runs = port.get("runs") or []
    if lang == "en":
        bad = set()
        for r in runs:
            if r["lang"] == "vi":
                continue
            for w in WORD.findall(unicodedata.normalize("NFC", r["text"])):
                if VI_ONLY.search(unicodedata.normalize("NFD", w)) and w not in dicts["en_words"]:
                    bad.add(w)
        if bad:
            fails.append("port language: Vietnamese words on an English page: " + ", ".join(sorted(bad)[:8]))
    else:
        text = " ".join(unicodedata.normalize("NFC", r["text"]) for r in runs if r["lang"] != "en")
        found = [ph for ph in dicts["phrases"] if ph in text]
        if found:
            fails.append("port language: English phrases on a Vietnamese page: " + ", ".join(found[:5]))

    # Assets.
    broken = sorted({r["url"] for r in requests if ASSET.search(urlsplit(r["url"]).path) and r.get("status") not in (200, 304, None)})
    if broken:
        fails.append("port assets: " + ", ".join(f"{u.rsplit('/', 1)[-1]}" for u in broken[:5]) + " did not answer 200")

    # Production scans: always, whatever --allow-draft says.
    if production:
        guards = ctx.get("guards")
        if guards:
            fails += ["port production: " + h for h in blocker_hits(html, guards)]
        if port.get("forms"):
            fails.append("port production: form[data-form] while forms are off (hcwa_forms_enabled)")
        if port.get("partners"):
            fails.append("port production: a partners section (fewer than the minimum approved logos)")
        if port.get("danglingHash"):
            fails.append("port production: # links without a target: " + ", ".join("#" + x for x in port["danglingHash"][:4]))
        if port.get("emptyContact"):
            fails.append(f"port production: {port['emptyContact']} empty tel:/mailto: link(s)")
    return fails


def contact_nojs(browser, base: str, targets: list[dict]) -> list[dict]:
    """Staging: the Contact form submitted without JavaScript answers 200 on the same path and the
    final URL has no query string (11-WP-PORT.md 2.8: c-{id} names, method="post")."""
    results = []
    for target in [t for t in targets if t.get("id") == "page:contact"]:
        ctx = browser.new_context(java_script_enabled=False, locale=LOCALES[target["lang"]])
        page = ctx.new_page()
        fails = []
        try:
            page.goto(base + target["path"], wait_until="load", timeout=60000)
            form = page.locator("form[data-form]")
            if form.count() == 0:
                fails.append("contact no-JS: no form[data-form] on the Contact page")
            else:
                f = form.first
                for el in f.locator("input, textarea, select").all():
                    kind = (el.get_attribute("type") or el.evaluate("e => e.tagName")).lower()
                    if kind in ("hidden", "submit", "button"):
                        continue
                    if kind == "checkbox":
                        el.check()
                    elif kind == "select":
                        values = [v for v in el.evaluate("e => [...e.options].map(o => o.value)") if v]
                        if values:
                            el.select_option(values[0])
                    elif kind == "email":
                        el.fill("smoke@example.org")
                    elif kind == "tel":
                        el.fill("0900000000")
                    else:
                        el.fill("Smoke test")
                with page.expect_navigation(timeout=30000) as nav:
                    f.locator("button[type=submit], input[type=submit], button:not([type])").first.click()
                resp = nav.value
                final = urlsplit(page.url)
                if not resp or resp.status != 200:
                    fails.append(f"contact no-JS: the submit answered HTTP {resp.status if resp else None}")
                if final.path != target["path"]:
                    fails.append(f"contact no-JS: the submit went to {final.path}")
                if final.query:
                    fails.append(f"contact no-JS: the final URL has a query string ({final.query[:80]})")
        except PlaywrightError as exc:
            fails.append("contact no-JS: " + str(exc).splitlines()[0][:200])
        ctx.close()
        results.append({"id": "contact-nojs", "lang": target["lang"], "path": target["path"], "viewport": "nojs", "fails": fails})
        print(f"{'ok  ' if not fails else 'FAIL'} {target['lang']} no-JS   {target['path']} (contact submit)", flush=True)
        for fail in fails:
            print(f"       - {fail}", flush=True)
    return results


def unpublished_probes(paths: list[str]) -> list[dict]:
    """--unpublished: URLs set to draft by the production procedure (11-WP-PORT.md 5.9) must 404."""
    out = []
    for i, path in enumerate(paths):
        rel = urlsplit(path)
        out.append({"id": f"unpublished:{i + 1}", "path": (rel.path or "/") + (f"?{rel.query}" if rel.query else ""), "status": 404})
    return out


def gate_probes(urls: list[dict], fixtures: dict | None = None) -> list[dict]:
    """HTTP-only checks built from the seed's URL list: embeds off, the media REST route
    closed to visitors (core routes REST case-insensitively, so an upper-case variant
    too), a center URL that reuses the Vietnamese slug under the English base sent to
    the Vietnamese permalink in one 301 (hcwa-core's pll_check_canonical_url filter), the
    SEO plugin's head and image sitemap kept from visitors (hcwa-core seo.php; they pass
    without the plugin too), and the seed's refused Little Rose photo.

    A probe's "status" is one code, a list of allowed codes, or None for any; "no_location"
    fails on any Location header; "body_absent" lists text the response body must not
    contain."""
    fixtures = fixtures or {}
    probes = [
        {"id": "gate:rest-media", "path": "/wp-json/wp/v2/media", "status": 401},
        {"id": "gate:rest-media-case", "path": "/wp-json/wp/v2/MEDIA", "status": 401},
        {"id": "gate:rest-seo-head", "path": "/wp-json/wp/v2/posts?_fields=id,yoast_head,yoast_head_json",
         "status": 200, "body_absent": ["yoast_head"]},
        {"id": "gate:seo-get-head", "path": "/wp-json/yoast/v1/get_head?url=%2F", "status": [401, 404]},
        {"id": "gate:image-sitemap", "path": "/post-sitemap.xml", "status": None, "body_absent": ["<image:loc"]},
    ]
    photo = int(fixtures.get("refused_photo") or 0)
    if photo > 0:
        probes.append({"id": "gate:refused-attachment-page", "path": f"/?attachment_id={photo}", "status": 404,
                       "no_location": True})
        # The same attachment through the other id query vars (11-WP-PORT.md 6.3: core's
        # redirect_canonical turned each into a 301 to the Little Rose page path).
        for var in ("p", "page_id"):
            probes.append({"id": f"gate:refused-attachment-{var.replace('_', '-')}", "path": f"/?{var}={photo}",
                           "status": 404, "no_location": True})
        name = str(fixtures.get("refused_photo_file") or "")
        page = str(fixtures.get("refused_photo_page") or "")
        if name and page:
            probes.append({"id": "gate:refused-photo-on-page", "path": page, "status": 200, "body_absent": [name]})
    vi_centers = [u for u in urls if u.get("kind") == "center" and u.get("lang") == "vi" and u.get("path")]
    if vi_centers:
        probes.append({"id": "gate:embed", "path": vi_centers[0]["path"].rstrip("/") + "/embed/", "status": 404})
    for center in vi_centers:
        vi_slug = center["path"].rstrip("/").rsplit("/", 1)[-1]
        en_base, _, en_slug = str(center.get("translation") or "").rstrip("/").rpartition("/")
        if en_base and en_slug and en_slug != vi_slug:
            probes.append({"id": "gate:other-language-slug", "path": f"{en_base}/{vi_slug}/", "status": 301,
                           "location": center["path"]})
            break
    return probes


def check_probe(probe: dict, base: str, status: int | None, location: str, body: str = "") -> list[str]:
    """Failures for one HTTP probe. location is the raw Location header ('' if none), body
    the response body (only read for probes with "body_absent")."""
    fails = []
    want_status = probe["status"]
    allowed = want_status if isinstance(want_status, list) else [want_status]
    if want_status is not None and status not in allowed:
        expected = " or ".join(str(code) for code in allowed)
        fails.append(f"{probe['id']}: HTTP {status}, expected {expected}")
    if probe.get("no_location") and location:
        fails.append(f"{probe['id']}: redirects to {location}, expected no redirect")
    for text in probe.get("body_absent", []):
        if text and text in body:
            fails.append(f"{probe['id']}: the response contains {text!r}")
    want = probe.get("location")
    if want is not None and status == probe["status"]:
        got = urlsplit(location or "")
        if path_of(location or "") != want:
            fails.append(f"{probe['id']}: redirects to {location or '(nothing)'}, expected {want}")
        elif got.netloc and got.netloc != urlsplit(base).netloc:
            fails.append(f"{probe['id']}: redirects to host {got.netloc}, not {urlsplit(base).netloc}")
    return fails


class _NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: D102 - report the redirect itself
        return None


def fetch_status(url: str, read_body: bool = False) -> tuple[int | None, str, str]:
    """Status, Location and (when asked, first 2 MB) body of one GET, redirects not
    followed. Loopback addresses skip any proxy set in the environment."""
    handlers: list = [_NoRedirect()]
    if urlsplit(url).hostname in ("127.0.0.1", "localhost", "::1"):
        handlers.append(urllib.request.ProxyHandler({}))
    opener = urllib.request.build_opener(*handlers)
    req = urllib.request.Request(url, headers={"User-Agent": "hcwa-smoke", "Accept": "*/*"})

    def body_of(resp) -> str:
        return resp.read(2_000_000).decode("utf-8", "replace") if read_body else ""

    try:
        with opener.open(req, timeout=30) as resp:
            return resp.status, resp.headers.get("Location", "") or "", body_of(resp)
    except urllib.error.HTTPError as exc:
        return exc.code, exc.headers.get("Location", "") or "", body_of(exc)
    except (urllib.error.URLError, OSError) as exc:
        print(f"       - could not reach {url}: {exc}", flush=True)
        return None, "", ""


def run_probes(base: str, probes: list[dict]) -> list[dict]:
    results = []
    for probe in probes:
        status, location, body = fetch_status(base + probe["path"], bool(probe.get("body_absent")))
        fails = check_probe(probe, base, status, location, body)
        results.append({"id": probe["id"], "lang": "-", "path": probe["path"], "viewport": "http",
                        "status": status, "location": location, "fails": fails})
        print(f"{'ok  ' if not fails else 'FAIL'} http    {probe['path']}", flush=True)
        for fail in fails:
            print(f"       - {fail}", flush=True)
    return results


def shot_name(target: dict, viewport: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", str(target.get("id", target["path"])).lower()).strip("-")[:60] or "home"
    return f"{slug}-{target['lang']}-{viewport}.jpg"


def make_port_ctx(base: str, manifest: Path | None, production: bool, door_approved: bool) -> dict:
    """What the port checks need besides the page: guards, listed stills, dictionaries, the seeded
    units and beat count, and cached HTTP lookups for menu links and chunk bodies."""
    data = json.loads(manifest.read_text(encoding="utf-8")) if manifest else {}
    parity = data.get("parity") or {}
    protected = [c["unit"] for c in parity.get("centers", []) if c.get("protected")]
    expected = parity.get("expected_beats_production" if production and not door_approved else "expected_beats")
    world = json.loads((REPO / "content" / "draft" / "world.json").read_text(encoding="utf-8"))
    ui = json.loads((REPO / "content" / "draft" / "ui.json").read_text(encoding="utf-8"))
    try:
        guards = load_guards()
    except (RuntimeError, OSError, ValueError, subprocess.SubprocessError) as exc:
        print(f"warning: production guards not loaded: {exc}", flush=True)
        guards = None
    links: dict[str, tuple] = {}
    chunks: dict[str, str] = {}

    def link_status(url: str) -> tuple:
        if url not in links:
            status, _, body = fetch_status(url, True)
            m = re.search(r"<html\b[^>]*\blang=[\"']([^\"']+)", body or "", re.I)
            links[url] = (status, m.group(1).lower() if m else "")
        return links[url]

    def chunk_body(url: str) -> str:
        if url not in chunks:
            _, _, body = fetch_status(url, True)
            chunks[url] = body or ""
        return chunks[url]

    return {"production": production, "door_approved": door_approved, "protected": protected,
            "expected_beats": expected, "guards": guards, "listed_stills": listed_stills(), "dicts": dictionaries(),
            "traces_enabled": bool((world.get("traces") or {}).get("enabled")),
            "visit": {lang: (ui.get("visitCenter") or {}).get(lang, "") for lang in ("vi", "en")},
            "link_status": link_status, "chunk_body": chunk_body}


def run(base: str, targets: list[dict], out: Path, production: bool, allow_draft: bool,
        port_ctx: dict | None = None) -> dict:
    out.mkdir(parents=True, exist_ok=True)
    origin = "{0.scheme}://{0.netloc}".format(urlsplit(base))
    report = {"base": base, "production": production, "date": datetime.date.today().isoformat(),
              "viewports": VIEWPORTS, "results": []}

    with sync_playwright() as p:
        browser = p.chromium.launch()
        for viewport, (w, h) in VIEWPORTS.items():
            mobile = viewport == "mobile"
            for lang in ("vi", "en"):
                todo = [t for t in targets if t["lang"] == lang]
                if not todo:
                    continue
                ctx = browser.new_context(viewport={"width": w, "height": h}, device_scale_factor=1,
                                          locale=LOCALES[lang], is_mobile=mobile, has_touch=mobile,
                                          user_agent=IPHONE_UA if mobile else None,
                                          reduced_motion="reduce", service_workers="block")
                ctx.route("**/*", make_router(origin))
                if port_ctx is not None:
                    ctx.add_init_script(NUMS_INIT)
                page = ctx.new_page()
                seen: list[dict] = []
                page.on("response", lambda r: seen.append({"url": r.url, "status": r.status, "type": r.request.resource_type}))
                page.on("requestfailed", lambda r: seen.append({"url": r.url, "status": None, "type": r.resource_type, "failed": True}))
                for target in todo:
                    url = base + target["path"]
                    result = {"id": target.get("id"), "lang": lang, "path": target["path"],
                              "viewport": viewport, "fails": []}
                    seen.clear()
                    try:
                        resp = page.goto(url, wait_until="load", timeout=60000)
                        redirects = []
                        req = resp.request.redirected_from if resp else None
                        while req:
                            redirects.insert(0, path_of(req.url))
                            req = req.redirected_from
                        html = resp.text() if resp else ""
                        settle(page)
                        data = page.evaluate(PAGE_JS)
                        result["fails"] = check(target, base, resp.status if resp else None, redirects, page.url,
                                                html, data, production, allow_draft)
                        if port_ctx is not None:
                            # Impact numbers: compare with DOMContentLoaded once 3 s have passed.
                            since = page.evaluate("performance.now() - (window.__hcwaNumsAt || 0)")
                            if since < 3000:
                                page.wait_for_timeout(int(3000 - since) + 50)
                            port = page.evaluate(PORT_JS, {"protected": port_ctx["protected"], "visit": port_ctx["visit"][lang]})
                            js = sorted({r["url"] for r in seen if "/assets/dist/assets/" in r["url"] and r["url"].split("?")[0].endswith(".js")})
                            ctx_now = dict(port_ctx, lang=lang, hreflang=data["hreflang"],
                                           chunk_imports=chunk_imports({u: port_ctx["chunk_body"](u) for u in js if u not in (port.get("modules") or [])}))
                            result["fails"] += port_checks(target, html, port, [dict(r) for r in seen if r.get("status") is not None or r.get("failed")], ctx_now)
                        result["h1"] = data["h1"]
                        result["screenshot"] = shot_name(target, viewport)
                        page.screenshot(path=out / result["screenshot"], full_page=True, type="jpeg", quality=70)
                    except PlaywrightError as exc:
                        result["fails"].append("load: could not load the page: " + str(exc).splitlines()[0][:200])
                    report["results"].append(result)
                    mark = "ok  " if not result["fails"] else "FAIL"
                    print(f"{mark} {lang} {viewport:<7} {target['path']}", flush=True)
                    for fail in result["fails"]:
                        print(f"       - {fail}", flush=True)
                ctx.close()
        if port_ctx is not None and not production:
            report["results"].extend(contact_nojs(browser, base, targets))
        browser.close()
    return report


def main() -> int:
    ap = argparse.ArgumentParser(description="Smoke test of an HCWA WordPress site at phone and desktop width.")
    ap.add_argument("base", help="site root, e.g. http://127.0.0.1:8881")
    ap.add_argument("--manifest", type=Path, help="seed-urls.json written by scripts/wp-seed.php")
    ap.add_argument("--production", action="store_true",
                    help="expect production: no pending chips, no noindex, no [DRAFT text or placeholder tokens")
    ap.add_argument("--allow-draft", action="store_true",
                    help="with --production, skip the draft text checks (to test the other gates on seeded content)")
    ap.add_argument("--legacy", action="store_true",
                    help="only the checks from before the port (11-WP-PORT.md 5.7 adds the rest)")
    ap.add_argument("--unpublished", type=Path,
                    help="JSON list of URLs or paths that must answer 404 (the production pass of 11-WP-PORT.md 5.9)")
    ap.add_argument("--door-approved", action="store_true",
                    help="with --production: HCWA has approved the Little Rose door, so its door may show")
    ap.add_argument("--only", nargs="*", default=[], help="only URLs whose id or path contains one of these")
    ap.add_argument("--out", type=Path, help="screenshot folder (default qa/wp/<date>)")
    args = ap.parse_args()

    base = args.base.rstrip("/")
    if not re.match(r"^https?://", base):
        ap.error("base must start with http:// or https://")
    if args.manifest and not args.manifest.is_file():
        ap.error(f"no manifest at {args.manifest}")
    if sync_playwright is None:
        ap.error("Playwright is not installed for this Python: use the repo's .venv (see wp/README.md)")
    if args.legacy and args.production:
        ap.error("--legacy cannot be combined with --production: the production guard scans always run (11-WP-PORT.md 5.7)")

    urls, not_found = load_targets(args.manifest)
    targets = urls + [dict(nf, id="not-found", kind=nf.get("kind", "404"), expect=404) for nf in not_found]
    if args.legacy:
        targets = [t for t in targets if t.get("kind") != "parity"]
    # Without a manifest there is no WordPress.
    probes = gate_probes(urls, load_fixtures(args.manifest)) if args.manifest else []
    if args.legacy:
        probes = [p for p in probes if p["id"] not in ("gate:refused-attachment-p", "gate:refused-attachment-page-id")]
    if args.unpublished:
        listed = json.loads(args.unpublished.read_text(encoding="utf-8"))
        listed = listed.get("urls", []) if isinstance(listed, dict) else listed
        probes += unpublished_probes([str(u) for u in listed])
    if args.only:
        targets = [t for t in targets if any(o in str(t.get("id", "")) or o in t["path"] for o in args.only)]
        probes = [p for p in probes if any(o in p["id"] or o in p["path"] for o in args.only)]
    if not targets and not probes:
        print("No URLs to check.", file=sys.stderr)
        return 1

    out = args.out or REPO / "qa" / "wp" / datetime.date.today().isoformat()
    mode = "production" if args.production else "non-production"
    print(f"Checking {len(targets)} URLs x {len(VIEWPORTS)} widths and {len(probes)} HTTP gates on {base} ({mode})",
          flush=True)

    port_ctx = None if args.legacy else make_port_ctx(base, args.manifest, args.production, args.door_approved)
    report = run(base, targets, out, args.production, args.allow_draft, port_ctx)
    report["results"].extend(run_probes(base, probes))
    (out / "report.json").write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    failed = [r for r in report["results"] if r["fails"]]
    total = len(report["results"])
    print()
    if not failed:
        print(f"All {total} page views passed. Screenshots and report.json in {out}")
        return 0
    print(f"{len(failed)} of {total} page views failed (screenshots and report.json in {out}):")
    by_check: dict[str, int] = {}
    for r in failed:
        for fail in r["fails"]:
            key = fail.split(":", 1)[0]
            by_check[key] = by_check.get(key, 0) + 1
    for key, count in sorted(by_check.items(), key=lambda kv: -kv[1]):
        print(f"  {count:>4} x {key}")
    return 1


if __name__ == "__main__":
    sys.exit(main())
