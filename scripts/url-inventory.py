"""Inventory every public URL on the live site (task 0.2) and write scripts/urls-live.csv.

Seeds: the Yoast sitemaps plus a few legacy URLs named in 01-AUDIT.md. Every internal
link found on a crawled HTML page is added, so URLs missing from the sitemaps (old slugs,
pagination, linked PDFs) show up too. Redirects are recorded, not followed, so the CSV
can seed the 301 map in task 3.4.

Usage: python -I scripts/url-inventory.py [--base https://hcwa.org.vn] [--out scripts/urls-live.csv]
Stdlib only. Sequential with a short pause between requests; the live site is on shared hosting.
"""
import argparse
import csv
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from collections import Counter, deque
from html.parser import HTMLParser
from pathlib import Path

UA = "hcwa-rebuild-url-inventory/1.0 (volunteer site audit for HCWA)"
DELAY = 0.25
MAX_URLS = 2000
SITEMAPS = ["post", "page", "category", "post_tag", "author"]
# Variants named in 01-AUDIT.md that may not be linked anywhere any more.
LEGACY = ["/dva", "/dva/", "/dva-child-abuse-prevention-project/", "/chp-2/", "/hoi-bao-tro-tre-em/"]
FILE_EXT = (".pdf", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".zip")
SKIP_PREFIX = ("/wp-admin", "/wp-json", "/wp-includes", "/wp-login.php", "/xmlrpc.php", "/cdn-cgi")
COLUMNS = ["url", "language", "title", "status", "translation", "type", "wp_id", "elementor",
           "redirect_to", "canonical", "lastmod", "source", "found_on"]


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


OPENER = urllib.request.build_opener(NoRedirect)


class PageParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.lang = self.canonical = ""
        self.body_class = ""
        self.alternates: dict[str, str] = {}
        self.links: list[str] = []
        self._in_title = False
        self.title = ""

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "html":
            self.lang = a.get("lang", "")
        elif tag == "title":
            self._in_title = True
        elif tag == "body":
            self.body_class = a.get("class", "")
        elif tag == "link":
            rel = (a.get("rel") or "").lower()
            if rel == "canonical":
                self.canonical = a.get("href", "")
            elif rel == "alternate" and a.get("hreflang") and a.get("hreflang") != "x-default":
                self.alternates[a["hreflang"]] = a.get("href", "")
        elif tag == "a" and a.get("href"):
            self.links.append(a["href"])

    def handle_endtag(self, tag):
        if tag == "title":
            self._in_title = False

    def handle_data(self, data):
        if self._in_title:
            self.title += data


def normalize(href: str, base: str, host: str) -> str | None:
    """Absolute https URL on the site's host, no fragment, no tracking query. None if external or skipped."""
    href = href.strip()
    if not href or href.startswith(("mailto:", "tel:", "javascript:", "#", "data:")):
        return None
    u = urllib.parse.urlsplit(urllib.parse.urljoin(base, href))
    if u.hostname not in (host, "www." + host):
        return None
    path = u.path or "/"
    if path.startswith(SKIP_PREFIX) or "/feed" in path or path.endswith("/embed/"):
        return None
    if path.startswith("/wp-content/") and not path.lower().endswith(FILE_EXT):
        return None
    # Keep only queries that address content (?p=, ?page_id=, ?lang=); drop search, replytocom, utm_*.
    q = urllib.parse.parse_qsl(u.query)
    keep = [(k, v) for k, v in q if k in ("p", "page_id", "lang", "cat")]
    if q and not keep:
        if any(k in ("s", "replytocom", "share") for k, _ in q):
            return None
    return urllib.parse.urlunsplit(("https", host, path, urllib.parse.urlencode(keep), ""))


def page_type(body_class: str, url: str) -> tuple[str, str]:
    """(type, wp_id) from WordPress body classes."""
    if url.lower().endswith(FILE_EXT):
        return "file", ""
    c = body_class.split()
    m = next((re.match(r"(?:page-id|postid)-(\d+)", x) for x in c if re.match(r"(?:page-id|postid)-\d+", x)), None)
    wp_id = m.group(1) if m else ""
    if "home" in c and "page" in c:
        kind = "front-page"
    elif "single-post" in c:
        kind = "post"
    elif "page" in c or "page-template" in c:
        kind = "page"
    elif "category" in c:
        kind = "category"
    elif "tag" in c:
        kind = "tag"
    elif "author" in c:
        kind = "author"
    elif "blog" in c or "archive" in c:
        kind = "archive"
    elif "error404" in c:
        kind = "404"
    else:
        kind = "other"
    if "paged" in c:
        kind += "-paged"
    return kind, wp_id


def fetch(url: str, method: str = "GET"):
    req = urllib.request.Request(url, method=method, headers={"User-Agent": UA})  # no Accept-Language: Polylang would 302 "/" to the VI home, crawlers see EN
    try:
        with OPENER.open(req, timeout=30) as r:
            ctype = r.headers.get("Content-Type", "")
            body = r.read() if method == "GET" and "html" in ctype else b""
            return r.status, "", ctype, body
    except urllib.error.HTTPError as e:
        loc = e.headers.get("Location", "") if e.headers else ""
        body = e.read() if method == "GET" else b""
        return e.code, loc, e.headers.get("Content-Type", "") if e.headers else "", body
    except (urllib.error.URLError, TimeoutError) as e:
        return 0, "", "", str(e).encode()


def sitemap_urls(base: str) -> list[tuple[str, str]]:
    out = []
    for name in SITEMAPS:
        req = urllib.request.Request(f"{base}/{name}-sitemap.xml", headers={"User-Agent": UA})
        xml = urllib.request.urlopen(req, timeout=30).read().decode("utf-8", "replace")
        for block in re.findall(r"<url>(.*?)</url>", xml, re.S):
            loc = re.search(r"<loc>(.*?)</loc>", block)
            mod = re.search(r"<lastmod>(.*?)</lastmod>", block)
            if loc and "/wp-content/" not in loc.group(1):
                out.append((loc.group(1).strip(), mod.group(1)[:10] if mod else ""))
        time.sleep(DELAY)
    return out


def main(base: str, out: Path) -> None:
    host = urllib.parse.urlsplit(base).hostname
    rows: dict[str, dict] = {}
    queue: deque[str] = deque()

    def enqueue(url, source, found_on="", lastmod=""):
        if url and url not in rows and len(rows) < MAX_URLS:
            rows[url] = {"url": url, "source": source, "found_on": found_on, "lastmod": lastmod}
            queue.append(url)

    for loc, mod in sitemap_urls(base):
        enqueue(normalize(loc, base, host), "sitemap", lastmod=mod)
    for path in LEGACY:
        enqueue(normalize(path, base, host), "legacy")

    done = 0
    while queue:
        url = queue.popleft()
        row = rows[url]
        is_file = url.lower().endswith(FILE_EXT)
        status, loc, ctype, body = fetch(url, "HEAD" if is_file else "GET")
        if status == 405 and is_file:
            status, loc, ctype, body = fetch(url)
        row["status"] = status
        if loc:
            target = urllib.parse.urljoin(url, loc)
            row["redirect_to"] = target
            enqueue(normalize(target, base, host), "redirect", found_on=url)
        if body and "html" in ctype:
            p = PageParser()
            p.feed(body.decode("utf-8", "replace"))
            row["language"] = p.lang
            row["title"] = " ".join(p.title.split())
            row["canonical"] = p.canonical if p.canonical != url else ""
            row["type"], row["wp_id"] = page_type(p.body_class, url)
            row["elementor"] = "yes" if "elementor-page" in p.body_class else ""
            others = [h for h in p.alternates.values() if h and h.rstrip("/") != url.rstrip("/")]
            row["translation"] = " ".join(others)
            if status == 200:
                for href in p.links:
                    enqueue(normalize(href, url, host), "link", found_on=url)
        elif is_file:
            row["type"] = "file"
        done += 1
        if done % 25 == 0:
            print(f"{done} fetched, {len(queue)} queued", file=sys.stderr)
        time.sleep(DELAY)

    ordered = sorted(rows.values(), key=lambda r: (r.get("type", "~"), r.get("language", ""), r["url"]))
    out.parent.mkdir(parents=True, exist_ok=True)
    with out.open("w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=COLUMNS, extrasaction="ignore")
        w.writeheader()
        w.writerows(ordered)

    print(f"\n{len(ordered)} URLs -> {out}")
    print("status:", dict(Counter(str(r.get("status", "")) for r in ordered)))
    print("type:", dict(Counter(r.get("type", "") for r in ordered)))
    print("language:", dict(Counter(r.get("language", "") for r in ordered)))


if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", default="https://hcwa.org.vn")
    ap.add_argument("--out", default=str(Path(__file__).with_name("urls-live.csv")))
    a = ap.parse_args()
    main(a.base.rstrip("/"), Path(a.out))
