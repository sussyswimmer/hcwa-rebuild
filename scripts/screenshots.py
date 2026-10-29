"""Screenshot prototype pages at 390x844 and 1440x900 (first screen + full page).

Usage: .venv/Scripts/python -I scripts/screenshots.py <base-url> <out-dir> [path ...]
Run with PLAYWRIGHT_BROWSERS_PATH=.playwright-browsers so the repo-local Chromium is used.
"""
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

SIZES = {"mobile": (390, 844), "desktop": (1440, 900)}
DEFAULT = ["/", "/en/", "/hoat-dong/co-so/tre-xanh/", "/en/our-work/programs/thrive-together/"]


def slug(path: str) -> str:
    return path.strip("/").replace("/", "_").replace(".html", "") or "home-vi"


def main(base: str, out: Path, paths: list[str]) -> None:
    out.mkdir(parents=True, exist_ok=True)
    with sync_playwright() as p:
        browser = p.chromium.launch()
        for name, (w, h) in SIZES.items():
            for path in paths:
                # Browser language matches the page, as for a real visitor (the EN bar only shows to EN browsers).
                locale = "en-GB" if path.startswith("/en/") else "vi-VN"
                page = browser.new_page(viewport={"width": w, "height": h}, device_scale_factor=1, locale=locale)
                page.goto(base.rstrip("/") + path, wait_until="networkidle")
                page.evaluate("document.fonts.ready")
                stem = f"{slug(path)}-{name}"
                page.screenshot(path=out / f"{stem}-fold.png")
                # Reveal everything before the full-page shot (fade-ups only run on scroll).
                page.evaluate("document.querySelectorAll('.reveal-pending').forEach(e => e.classList.remove('reveal-pending'))")
                page.wait_for_timeout(600)
                page.screenshot(path=out / f"{stem}-full.png", full_page=True)
                print(out / stem)
                page.close()
        browser.close()


if __name__ == "__main__":
    main(sys.argv[1], Path(sys.argv[2]), sys.argv[3:] or DEFAULT)
