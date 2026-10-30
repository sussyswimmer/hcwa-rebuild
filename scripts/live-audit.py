"""Mobile/desktop audit of the live site (task 0.1): screenshots + layout measurements.

Renders 10 main pages (5 VI, 5 EN) at 390x844 (phone) and 1440x900 and writes to qa/before/:
  <slug>-<mobile|desktop>.jpg   full-page screenshot
  home-*-mobile-menu.jpg        first screen with the mobile menu opened
  report.json                   measurements per page and viewport

Safeguarding: this repo is public and the live pages carry photos of children. Every image
request is blurred before the page renders (layout unchanged), media is blocked, and social
embeds are blurred, so no child is identifiable in the committed screenshots. Lines with
bank details, account numbers, emails or QR mentions are greyed out (donation details stay
out of the repo until HCWA finance re-confirms them). Only SVGs and
images under 48 px stay sharp. Never exempt files by name: the live hero photo is called
"Hình-có-logo.jpg". Little Rose is left out of the page set on purpose.

Usage: .venv/bin/python -I scripts/live-audit.py [--base https://hcwa.org.vn] [--out qa/before] [--only slug ...]
"""
import argparse
import io
import json
from pathlib import Path

from PIL import Image, ImageFilter
from playwright.sync_api import sync_playwright

PAGES = [
    ("home-en", "/", "en-US"),
    ("about-en", "/about-us/", "en-US"),
    ("green-bamboo-en", "/gbws/", "en-US"),
    ("thrive-together-en", "/dva-child-abuse-prevention-project/", "en-US"),
    ("support-en", "/support-us/", "en-US"),
    ("home-vi", "/hoi-bao-tro-tre-em/", "vi-VN"),
    ("about-vi", "/ve-chung-toi/", "vi-VN"),
    ("green-bamboo-vi", "/mai-am-tre-xanh/", "vi-VN"),
    ("thrive-together-vi", "/dva2/", "vi-VN"),
    ("contact-vi", "/lien-he/", "vi-VN"),
]
VIEWPORTS = {"mobile": (390, 844), "desktop": (1440, 900)}
IPHONE_UA = ("Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 "
             "(KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1")
EMBED_BLUR_CSS = """
iframe:not([src*="google.com/maps"]), video, #sb_instagram, .sbi, [id^="sbi_"], [class*="instagram-feed"],
.efbl_feed_wraper, [class*="efbl"], .fb-page { filter: blur(14px) !important; }
"""

MEASURE_JS = r"""
() => {
  const vw = document.documentElement.clientWidth, vh = innerHeight;
  const vis = el => { const r = el.getBoundingClientRect(), cs = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none' && +cs.opacity > 0; };
  const sel = el => { let s = el.tagName.toLowerCase(); if (el.id) s += '#' + el.id;
    const c = [...el.classList].filter(x => !/^elementor-element-|^e-con-/.test(x)).slice(0, 2);
    return c.length ? s + '.' + c.join('.') : s; };
  const all = [...document.body.querySelectorAll('*')];
  const ownText = el => [...el.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent).join('').trim();
  const textEls = all.filter(el => ownText(el).length > 1 && vis(el));

  const scrollW = document.documentElement.scrollWidth;
  const overflow = scrollW > vw + 1 ? all.filter(el => vis(el) && el.getBoundingClientRect().right > vw + 1)
      .map(el => ({ sel: sel(el), right: Math.round(el.getBoundingClientRect().right) })).slice(-8) : [];

  const fs = el => parseFloat(getComputedStyle(el).fontSize);
  const small = textEls.filter(el => fs(el) < 12);
  const paras = [...document.querySelectorAll('p')].filter(p => vis(p) && p.textContent.trim().length > 40);
  const tally = {};
  for (const p of paras) { const cs = getComputedStyle(p);
    const k = `${cs.fontSize} / lh ${cs.lineHeight}`; tally[k] = (tally[k] || 0) + 1; }

  const taps = [...document.querySelectorAll('a[href], button, input:not([type=hidden]), select, textarea, [role=button]')]
      .filter(el => vis(el) && !el.closest('p'));
  const tinyTaps = taps.filter(el => { const r = el.getBoundingClientRect(); return r.width < 24 || r.height < 24; });
  const under44 = taps.filter(el => { const r = el.getBoundingClientRect(); return r.width < 44 || r.height < 44; });

  const fixed = all.filter(el => vis(el) && ['fixed', 'sticky'].includes(getComputedStyle(el).position))
      .map(el => { const r = el.getBoundingClientRect();
        return { sel: sel(el), pos: getComputedStyle(el).position, x: Math.round(r.x), y: Math.round(r.y),
                 w: Math.round(r.width), h: Math.round(r.height) }; })
      .filter(f => f.w * f.h > 400);

  const viChars = /[ăâđêôơưàảãáạằẳẵắặầẩẫấậèẻẽéẹềểễếệìỉĩíịòỏõóọồổỗốộờởỡớợùủũúụừửữứựỳỷỹýỵ]/i;
  const caps = textEls.filter(el => getComputedStyle(el).textTransform === 'uppercase' && viChars.test(ownText(el)))
      .map(el => ownText(el).slice(0, 60));

  const firstScreen = el => { const r = el.getBoundingClientRect(); return r.top < vh && r.bottom > 0; };
  const buttons = taps.filter(el => firstScreen(el) && /btn|button/i.test(el.className + ' ' + el.tagName))
      .map(el => (el.textContent.trim() || el.getAttribute('aria-label') || el.querySelector('img')?.alt || '[image]').slice(0, 40));
  const imgs = [...document.images];
  return {
    pageHeight: document.documentElement.scrollHeight,
    sidewaysScroll: scrollW > vw + 1 ? scrollW - vw : 0,
    overflowElements: overflow,
    textUnder12px: small.length,
    smallTextSamples: [...new Set(small.map(el => `${fs(el)}px: ${ownText(el).slice(0, 40)}`))].slice(0, 6),
    paragraphStyles: tally,
    tapTargets: taps.length,
    tapTargetsUnder24px: tinyTaps.length,
    tapTargetsUnder44px: under44.length,
    tinyTapSamples: [...new Set(tinyTaps.map(el => sel(el)))].slice(0, 6),
    fixedOrSticky: fixed,
    uppercaseVietnamese: [...new Set(caps)].slice(0, 8),
    uppercaseVietnameseCount: caps.length,
    h1: [...document.querySelectorAll('h1')].filter(vis).map(h => h.textContent.trim().slice(0, 80)),
    firstScreenButtons: buttons,
    images: imgs.length, imagesNoAlt: imgs.filter(i => !i.hasAttribute('alt') || !i.alt.trim()).length,
    scripts: document.querySelectorAll('script[src]').length,
    stylesheets: document.querySelectorAll('link[rel=stylesheet]').length,
  };
}
"""

MENU_JS = r"""
() => {
  const vis = el => { const r = el.getBoundingClientRect(), cs = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none' && +cs.opacity > 0; };
  // Only the opened menu panel: a looser selector picks up Instagram captions that name children.
  const panel = document.querySelector('#header-menu-sidebar, .header-menu-sidebar, nav[aria-label]') || document.createElement('div');
  const links = [...panel.querySelectorAll('a')].filter(vis);
  return {
    visibleMenuLinks: [...new Set(links.map(a => a.textContent.trim()).filter(Boolean))].slice(0, 30),
    languageSwitchVisible: links.some(a => /lang|pll/i.test(a.className + ' ' + (a.parentElement?.className || ''))
                                         || a.hreflang || a.querySelector('img[src*="flag"], img[alt*="English"], img[alt*="Tiếng"]')),
  };
}
"""


# Donation details are never published until HCWA finance re-confirms them (CLAUDE.md), and this
# repo is public. Grey out the whole line around any account number, bank label, email or QR mention.
REDACT_JS = r"""
() => {
  const re = /\d[\d .\-]{6,}\d|swift|\bbic\b|iban|routing|t\u00e0i kho\u1ea3n|account|beneficiary|ng\u01b0\u1eddi \u0111\u1ea1i di\u1ec7n|\u0111\u1ee9ng t\u00ean|chi nh\u00e1nh|branch|ng\u00e2n h\u00e0ng|\bbank|vietqr|napas|qr|[\w.+-]+@[\w-]+\.[\w.]+/i;
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  let hits = 0;
  const style = document.createElement('style');
  style.textContent = '.hcwa-redacted, .hcwa-redacted * { color: transparent !important; text-shadow: none !important;'
    + ' background: #c4c4c4 !important; border-color: transparent !important; text-decoration: none !important; }';
  document.head.append(style);
  for (let n = walker.nextNode(); n; n = walker.nextNode()) {
    if (!re.test(n.textContent)) continue;
    const el = n.parentElement.closest('li, p, td, th, dd, dt, h1, h2, h3, h4, h5, h6, address, figcaption, label') || n.parentElement;
    el.classList.add('hcwa-redacted');
    hits++;
  }
  return hits;
}
"""


def blur_images(route):
    """Serve every raster image blurred. Unreadable formats other than SVG are blocked, not passed through."""
    try:
        resp = route.fetch()
    except Exception:
        return route.abort()
    url = route.request.url.lower()
    ctype = resp.headers.get("content-type", "")
    if "svg" in ctype or url.split("?")[0].endswith(".svg"):
        return route.fulfill(response=resp)
    try:
        img = Image.open(io.BytesIO(resp.body()))
        img.seek(0)
        img = img.convert("RGBA")
    except Exception:
        return route.abort()
    if min(img.size) < 48:
        return route.fulfill(response=resp)
    img = img.filter(ImageFilter.GaussianBlur(radius=max(8, max(img.size) / 30)))
    buf = io.BytesIO()
    img.save(buf, format="PNG", optimize=False)
    return route.fulfill(status=200, body=buf.getvalue(), headers={"content-type": "image/png"})


def router(route):
    kind = route.request.resource_type
    if kind == "image":
        return blur_images(route)
    if kind == "media":
        return route.abort()
    return route.continue_()


def settle(page):
    """Scroll the whole page so lazy images load and Elementor entrance animations fire, then return to top."""
    height = page.evaluate("document.documentElement.scrollHeight")
    step = page.viewport_size["height"] // 2
    for y in range(0, height + step, step):
        page.evaluate(f"window.scrollTo(0, {y})")
        page.wait_for_timeout(120)
    page.wait_for_load_state("networkidle", timeout=30000)
    page.evaluate("window.scrollTo(0, 0)")
    page.wait_for_timeout(800)


def open_mobile_menu(page) -> bool:
    for selector in [".navbar-toggle", "button[aria-label*='enu']", ".menu-toggle", ".elementor-menu-toggle"]:
        toggle = page.locator(selector).filter(visible=True).first
        if toggle.count():
            toggle.click()
            page.wait_for_timeout(900)
            return True
    return False


def main(base: str, out: Path, only: list[str]) -> None:
    out.mkdir(parents=True, exist_ok=True)
    report = {"base": base, "viewports": VIEWPORTS, "pages": {}}
    with sync_playwright() as p:
        browser = p.chromium.launch()
        for slug, path, locale in [p for p in PAGES if not only or p[0] in only]:
            report["pages"][slug] = {"url": base + path}
            for name, (w, h) in VIEWPORTS.items():
                mobile = name == "mobile"
                ctx = browser.new_context(viewport={"width": w, "height": h}, device_scale_factor=1, locale=locale,
                                          is_mobile=mobile, has_touch=mobile, service_workers="block",
                                          user_agent=IPHONE_UA if mobile else None)
                page = ctx.new_page()
                page.route("**/*", router)
                resp = page.goto(base + path, wait_until="load", timeout=90000)
                page.add_style_tag(content=EMBED_BLUR_CSS)
                settle(page)
                data = page.evaluate(MEASURE_JS)
                data["status"] = resp.status if resp else None
                data["finalUrl"] = page.url
                data["redactedLines"] = page.evaluate(REDACT_JS)
                page.screenshot(path=out / f"{slug}-{name}.jpg", full_page=True, type="jpeg", quality=60)
                if mobile and slug.startswith("home"):
                    data["menuToggleFound"] = open_mobile_menu(page)
                    data.update(page.evaluate(MENU_JS))
                    page.screenshot(path=out / f"{slug}-mobile-menu.jpg", type="jpeg", quality=60)
                report["pages"][slug][name] = data
                print(f"{slug} {name}: sideways={data['sidewaysScroll']} small={data['textUnder12px']} "
                      f"tiny-taps={data['tapTargetsUnder24px']} h={data['pageHeight']}")
                ctx.close()
        browser.close()
    (out / "report.json").write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")


if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", default="https://hcwa.org.vn")
    ap.add_argument("--out", default=str(Path(__file__).resolve().parent.parent / "qa" / "before"))
    ap.add_argument("--only", nargs="*", default=[], help="page slugs to render, e.g. home-vi contact-vi")
    a = ap.parse_args()
    main(a.base.rstrip("/"), Path(a.out), a.only)
