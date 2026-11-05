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
import sys
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


def gate_probes(urls: list[dict]) -> list[dict]:
    """HTTP-only checks built from the seed's URL list: embeds off, the media REST route
    closed to visitors (core routes REST case-insensitively, so an upper-case variant
    too), and a center URL that reuses the Vietnamese slug under the English base sent to
    the Vietnamese permalink in one 301 (hcwa-core's pll_check_canonical_url filter)."""
    probes = [
        {"id": "gate:rest-media", "path": "/wp-json/wp/v2/media", "status": 401},
        {"id": "gate:rest-media-case", "path": "/wp-json/wp/v2/MEDIA", "status": 401},
    ]
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


def check_probe(probe: dict, base: str, status: int | None, location: str) -> list[str]:
    """Failures for one HTTP probe. location is the raw Location header ('' if none)."""
    fails = []
    if status != probe["status"]:
        fails.append(f"{probe['id']}: HTTP {status}, expected {probe['status']}")
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


def fetch_status(url: str) -> tuple[int | None, str]:
    """Status and Location of one GET, redirects not followed. Loopback addresses skip any
    proxy set in the environment."""
    handlers: list = [_NoRedirect()]
    if urlsplit(url).hostname in ("127.0.0.1", "localhost", "::1"):
        handlers.append(urllib.request.ProxyHandler({}))
    opener = urllib.request.build_opener(*handlers)
    req = urllib.request.Request(url, headers={"User-Agent": "hcwa-smoke", "Accept": "*/*"})
    try:
        with opener.open(req, timeout=30) as resp:
            return resp.status, resp.headers.get("Location", "") or ""
    except urllib.error.HTTPError as exc:
        return exc.code, exc.headers.get("Location", "") or ""
    except (urllib.error.URLError, OSError) as exc:
        print(f"       - could not reach {url}: {exc}", flush=True)
        return None, ""


def run_probes(base: str, probes: list[dict]) -> list[dict]:
    results = []
    for probe in probes:
        status, location = fetch_status(base + probe["path"])
        fails = check_probe(probe, base, status, location)
        results.append({"id": probe["id"], "lang": "-", "path": probe["path"], "viewport": "http",
                        "status": status, "location": location, "fails": fails})
        print(f"{'ok  ' if not fails else 'FAIL'} http    {probe['path']}", flush=True)
        for fail in fails:
            print(f"       - {fail}", flush=True)
    return results


def shot_name(target: dict, viewport: str) -> str:
    slug = re.sub(r"[^a-z0-9]+", "-", str(target.get("id", target["path"])).lower()).strip("-")[:60] or "home"
    return f"{slug}-{target['lang']}-{viewport}.jpg"


def run(base: str, targets: list[dict], out: Path, production: bool, allow_draft: bool) -> dict:
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
                page = ctx.new_page()
                for target in todo:
                    url = base + target["path"]
                    result = {"id": target.get("id"), "lang": lang, "path": target["path"],
                              "viewport": viewport, "fails": []}
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

    urls, not_found = load_targets(args.manifest)
    targets = urls + [dict(nf, id="not-found", kind="404", expect=404) for nf in not_found]
    probes = gate_probes(urls) if args.manifest else []  # Without a manifest there is no WordPress.
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

    report = run(base, targets, out, args.production, args.allow_draft)
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
