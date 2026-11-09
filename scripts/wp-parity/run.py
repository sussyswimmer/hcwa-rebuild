#!/usr/bin/env python3
"""Parity driver: the built prototype and the real local WordPress, side by side
(docs/plan/11-WP-PORT.md section 5).

What it does, in order:
 1. Builds prototype/dist when it is missing (or with --build), serves it with vite preview on
    127.0.0.1:4173 (the next free port when that one is busy) and stops that server by PID on exit.
 2. Matrix (5.2): the 16 routes of routes.json at 390 and 1440, on both sides, in four modes:
    reduced (reduced motion, ?tier=0), nojs, nowebgl (a Chromium with WebGL off) and tier0 (?tier=0).
    Per page: structure, text and links per section (skeleton.js, 5.3 items 1-3), one rendered H1,
    no sideways scroll (and at 320 wide after the reduced pass at 390), console and page errors,
    failed requests, PHP errors in the HTML, stills present (nojs, nowebgl, tier0), no three.js chunk
    requested (reduced, tier0, nowebgl), html.world-off on home without WebGL, and a screenshot of
    every section compared pixel by pixel (reduced and nowebgl, 5.3 item 4).
 3. Behaviour (default and --full): the prototype's own browser checks (check-page, check-hero,
    check-bays, check-tiles, check-sunin, light-contrast) against both bases, one after another,
    with the driver's own browsers closed. Every line that passes on the prototype must pass on
    WordPress (5.3 item 5). check-hero's "three.js not requested" lines are prototype-only (it finds
    the chunk in prototype/dist), so the driver asserts them itself from its request logs with
    three-chunks.py (tier0, reduced, nowebgl, paused).
 4. World and budgets (default and --full): home reaches html.world-live at Tier 1 (390) and Tier 2
    (1440); a load with the motion pause stored requests no three.js; the gate 7.10 budgets at 1440,
    Tier 2, cold cache; the main entry module loads from one URL only (no ?ver twin) and no dist URL
    carries ?ver; the same script and style requests as the prototype; no jQuery, Elementor, emoji,
    embed, global-styles or block-library assets.
 5. --full only: frame strips of home (5.5) and mobile LCP through the gzip proxy (5.5, gate 7.11).
    Strip frames: 24 prototype positions to its footer, and on WordPress the same offsets into the
    same sections; each shot waits for in-view reveals and transitions to finish; each frame's pixel
    difference between the sides is recorded and gated at STRIP_SHARE (sections with a pixel allow
    entry for that route and width excepted).
 6. debug.log of the instance: no new warning, notice or deprecation naming hcwa-2026 or hcwa-core.
 7. Gate 7.14 "no hard line between sections" (05-DESIGN 0.5): in the reduced mode the driver also
    takes a full-page capture of each side and scans it row by row (hard_lines: the colour steps by
    more than HARD_STEP across HARD_SPAN of the width); a line WordPress has at an offset into a
    section where the prototype has none fails the view.
Allow entries (allow-assets.json, allow-templates.json, 5.4) may be scoped with "width" and "mode";
the driver refuses to start when an entry is malformed (allow_entry_problems).
Writes report.json, report.html, shots/, strips/ and logs/ to qa/wp-port/parity/<date>-<time>/ and
exits 1 when any gate fails. At most 2 headless browsers run at a time.

--self-test serves prototype/dist a second time and compares the prototype with itself: every
section must come out identical, which proves the driver itself adds no differences (the
9 Nov 2026 run had 0 differing sections in all 128 views).

--production-asserts runs only the explicit production assertions of gate 7.7 against --base (the
throwaway production instance of 5.9), with no prototype. It reads guards.json and stills.json from
wp/themes/hcwa-2026/assets/generated/ (the guards fall back to prototype/src/render.mjs through node
while that file does not exist yet; the listed stills have no fallback and that assertion fails).

Usage (normally through scripts/wp-local.sh parity <name> [-- args]):
  .venv/bin/python -I scripts/wp-parity/run.py --base http://127.0.0.1:8881 --instance .wp-local/main
      [--quick | --full] [--only home ...] [--widths 390,1440] [--modes reduced,nojs] [--build]
  .venv/bin/python -I scripts/wp-parity/run.py --base http://127.0.0.1:8890 --instance .wp-local/scratch-prod --production-asserts
"""
from __future__ import annotations

import argparse
import datetime
import difflib
import gzip
import html as htmllib
import importlib.util
import json
import os
import re
import signal
import socket
import subprocess
import sys
import time
import unicodedata
import urllib.error
import urllib.request
from pathlib import Path
from urllib.parse import urlsplit

HERE = Path(__file__).resolve().parent
REPO = HERE.parent.parent
PROTO = REPO / "prototype"
THEME = REPO / "wp" / "themes" / "hcwa-2026"
GENERATED = THEME / "assets" / "generated"
SKELETON = (HERE / "skeleton.js").read_text(encoding="utf-8")
ROUTES = json.loads((HERE / "routes.json").read_text(encoding="utf-8"))
SIDES = ("proto", "wp")
SIDE_NAME = {"proto": "prototype", "wp": "wordpress"}

try:
    from playwright.sync_api import Error as PlaywrightError
    from playwright.sync_api import sync_playwright
except ImportError:  # the module still loads for its pure helpers (wp/tests/runtime-scripts-test.php)
    PlaywrightError, sync_playwright = Exception, None

try:
    from PIL import Image, ImageChops
except ImportError:
    Image = ImageChops = None

# Mode → how the page is loaded (5.2). "compare" modes get the structure, text and links diff; "shots"
# modes also get the per-section screenshot comparison.
MODES = {
    "reduced": {"browser": "gpu", "ctx": {"reduced_motion": "reduce"}, "query": "?tier=0", "compare": True, "shots": True},
    "nojs": {"browser": "gpu", "ctx": {"java_script_enabled": False}, "query": "", "compare": True, "shots": False},
    "nowebgl": {"browser": "nowebgl", "ctx": {}, "query": "", "compare": True, "shots": True},
    "tier0": {"browser": "gpu", "ctx": {}, "query": "?tier=0", "compare": False, "shots": False},
}
NOWEBGL_ARGS = ["--disable-webgl", "--disable-webgl2", "--disable-3d-apis"]
LOCALES = {"vi": "vi-VN", "en": "en-GB"}
WORLD_CHUNK = re.compile(r"/assets/(?:world|journey|live|frame|corridor|shutters|gach-worker|gach-bake)-[\w-]+\.js(?:$|\?)")
MOTION_CHUNK = re.compile(r"/assets/motion-[\w-]+\.js(?:$|\?)")
IMAGE_URL = re.compile(r"\.(?:avif|webp|jpe?g|png|gif|svg|ico)(?:$|\?)", re.I)
FORBIDDEN = {
    "jquery": re.compile(r"jquery", re.I),
    "elementor": re.compile(r"elementor", re.I),
    "emoji": re.compile(r"wp-emoji|emoji-release|twemoji", re.I),
    "embed": re.compile(r"wp-embed", re.I),
    "global-styles": re.compile(r"global-styles", re.I),
    "block-library": re.compile(r"block-library|wp-block-library", re.I),
}
HASHED = re.compile(r"-[A-Za-z0-9_-]{8}(?=\.(?:js|css)$)")
PIXEL_FAR = 24        # a pixel differs when one channel is off by more than this (0-255)
PIXEL_SHARE = 1.0     # percent of differing pixels a section may have
HEIGHT_SLACK = 2      # px
STRIP_SHARE = 1.0     # percent of differing pixels a home strip frame may have between sides (5.5)
HARD_STEP = 10.0      # a "hard line" (05-DESIGN 0.5, gate 7.14): rows whose colour steps by more than this
HARD_SPAN = 0.9       # ... across at least this share of the width, between the 3 rows above and the 3 below
SECTION_BOXES_JS = """() => [...document.querySelectorAll('[data-parity-section]')].map(e => {
    const r = e.getBoundingClientRect();
    return { i: +e.dataset.paritySection, top: Math.round(r.top + scrollY), height: Math.round(r.height) };
})"""
BUDGET = {"home_requests": 15, "home_kb": 300, "home_html_gz_kb": 15.5, "head_style_bytes": 1024,
          "world_kb": 190, "home_total_kb": 1024, "inner_requests": 12}


# --------------------------------------------------------------------------- small helpers

def load_smoke():
    """scripts/wp-smoke.py as a module, for its PHP error patterns and production scans."""
    spec = importlib.util.spec_from_file_location("wp_smoke", REPO / "scripts" / "wp-smoke.py")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def three_chunks() -> dict:
    spec = importlib.util.spec_from_file_location("three_chunks", HERE / "three-chunks.py")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module.find()


def free_port(start: int) -> int:
    for port in range(start, start + 50):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            if s.connect_ex(("127.0.0.1", port)) != 0:
                return port
    raise SystemExit(f"no free port from {start} to {start + 49}")


def http_get(url: str, timeout: int = 30) -> tuple[int | None, bytes, dict]:
    """GET without following redirects or using a proxy (local servers only)."""
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *a, **k):  # noqa: D102
            return None
    opener = urllib.request.build_opener(NoRedirect(), urllib.request.ProxyHandler({}))
    req = urllib.request.Request(url, headers={"User-Agent": "hcwa-parity", "Accept-Encoding": "identity"})
    try:
        with opener.open(req, timeout=timeout) as r:
            return r.status, r.read(), dict(r.headers)
    except urllib.error.HTTPError as e:
        return e.code, e.read(), dict(e.headers)
    except (urllib.error.URLError, OSError):
        return None, b"", {}


def gz_kb(data: bytes) -> float:
    return round(len(gzip.compress(data, 9)) / 1024, 1)


def slug(text: str) -> str:
    return re.sub(r"[^a-z0-9]+", "-", text.lower()).strip("-") or "root"


def norm_url(u: str) -> str:
    """A request URL as the prototype names it: path only, dist prefix and hash dropped."""
    path = urlsplit(u).path
    path = re.sub(r"^/wp-content/themes/[^/]+/assets/dist/", "/", path)
    return HASHED.sub("", path)


class Procs:
    """Child processes started by the driver, stopped by PID (their process group) on exit."""

    def __init__(self):
        self.items: list[subprocess.Popen] = []

    def start(self, args, **kw) -> subprocess.Popen:
        proc = subprocess.Popen(args, start_new_session=True, **kw)
        self.items.append(proc)
        return proc

    def stop(self, proc: subprocess.Popen) -> None:
        if proc.poll() is None:
            try:
                os.killpg(proc.pid, signal.SIGTERM)
                proc.wait(timeout=10)
            except (ProcessLookupError, subprocess.TimeoutExpired):
                try:
                    os.killpg(proc.pid, signal.SIGKILL)
                except ProcessLookupError:
                    pass
        if proc in self.items:
            self.items.remove(proc)

    def stop_all(self) -> None:
        for proc in list(self.items):
            self.stop(proc)


PROCS = Procs()


def run_logged(args: list[str], log: Path, timeout: int, env: dict | None = None) -> tuple[int | None, str]:
    """Runs a command with its output in log; returns (exit code or None on timeout, output)."""
    log.parent.mkdir(parents=True, exist_ok=True)
    with log.open("w", encoding="utf-8") as fh:
        proc = PROCS.start(args, cwd=REPO, stdout=fh, stderr=subprocess.STDOUT, env=env)
        try:
            code = proc.wait(timeout=timeout)
        except subprocess.TimeoutExpired:
            fh.write(f"\n[parity] stopped after {timeout} s\n")
            code = None
        PROCS.stop(proc)
    return code, log.read_text(encoding="utf-8", errors="replace")


def node_env() -> dict:
    env = dict(os.environ)
    if not env.get("PLAYWRIGHT_BROWSERS_PATH") and (REPO / ".playwright-browsers").is_dir():
        env["PLAYWRIGHT_BROWSERS_PATH"] = str(REPO / ".playwright-browsers")
    return env


# --------------------------------------------------------------------------- the prototype server

def ensure_dist(force: bool, log: Path) -> None:
    if force or not (PROTO / "dist" / "index.html").is_file():
        print("Building the prototype (npm run build)", flush=True)
        code, _ = run_logged(["npm", "--prefix", str(PROTO), "run", "build"], log, 600)
        if code != 0:
            raise SystemExit(f"npm run build failed, see {log}")


def start_preview(port: int, log: Path) -> tuple[str, subprocess.Popen]:
    port = free_port(port)
    vite = PROTO / "node_modules" / ".bin" / "vite"
    if not vite.exists():
        raise SystemExit("prototype/node_modules is missing: npm --prefix prototype ci")
    log.parent.mkdir(parents=True, exist_ok=True)
    fh = log.open("w", encoding="utf-8")
    proc = PROCS.start([str(vite), "preview", "--port", str(port), "--strictPort", "--host", "127.0.0.1"],
                       cwd=PROTO, stdout=fh, stderr=subprocess.STDOUT)
    base = f"http://127.0.0.1:{port}"
    for _ in range(100):
        if proc.poll() is not None:
            raise SystemExit(f"vite preview stopped, see {log}")
        status, _, _ = http_get(base + "/", timeout=2)
        if status == 200:
            return base, proc
        time.sleep(0.3)
    raise SystemExit(f"vite preview did not answer on {base}")


# --------------------------------------------------------------------------- one page load

def new_context(browser, width: str, lang: str, extra: dict, side: str):
    w = ROUTES["widths"][width]
    ctx = browser.new_context(viewport=w["viewport"], device_scale_factor=w["deviceScaleFactor"],
                              is_mobile=w["isMobile"], has_touch=w["hasTouch"], locale=LOCALES[lang],
                              service_workers="block", **extra)
    if side == "wp" and SMOKE is not None:
        # Media-library images are blurred, as in the smoke test (this repo is public); theme and
        # plugin assets stay sharp.
        origin = "{0.scheme}://{0.netloc}".format(urlsplit(BASES["wp"]))
        router = SMOKE.make_router(origin)
        ctx.route(re.compile(r"/wp-content/uploads/"), router)
    return ctx


def settle(page) -> None:
    """Scroll through the page once so lazy images load, then back to the top."""
    try:
        height = page.evaluate("document.documentElement.scrollHeight")
        step = max(240, page.viewport_size["height"] // 2)
        for y in range(0, height + step, step):
            page.evaluate(f"window.scrollTo(0, {y})")
            page.wait_for_timeout(50)
        page.wait_for_load_state("networkidle", timeout=10000)
    except PlaywrightError:
        pass
    page.evaluate("window.scrollTo(0, 0)")
    page.wait_for_timeout(300)


def load_page(browsers, side: str, route: dict, width: str, mode: str, shot_dir: Path | None) -> dict:
    m = MODES[mode]
    base = BASES[side]
    url = base + route["path"] + m["query"]
    js = m["ctx"].get("java_script_enabled", True)
    ctx = new_context(browsers[m["browser"]], width, route["lang"], m["ctx"], side)
    page = ctx.new_page()
    errors, failed, requests = [], [], []
    page.on("console", lambda msg: errors.append({"text": msg.text[:300], "url": (msg.location or {}).get("url", "")}) if msg.type == "error" else None)
    page.on("pageerror", lambda err: errors.append({"text": "page error: " + str(err)[:300], "url": ""}))
    page.on("request", lambda r: requests.append({"url": r.url, "type": r.resource_type}))
    page.on("requestfailed", lambda r: failed.append({"url": r.url, "why": str(r.failure or "")[:120]}) if "ERR_ABORTED" not in str(r.failure or "") else None)
    page.on("response", lambda r: failed.append({"url": r.url, "why": f"HTTP {r.status}", "doc": r.request.resource_type == "document" and r.request.frame == page.main_frame}) if r.status >= 400 else None)
    out = {"side": side, "url": url, "mode": mode}
    try:
        resp = page.goto(url, wait_until="load", timeout=60000)
        out["status"] = resp.status if resp else None
        body = resp.text() if resp else ""
        if js:
            for cond in ("document.fonts.status === 'loaded'", "!window.hcwa || window.hcwa.state.sunIn === 'done'"):
                try:
                    page.wait_for_function(f"() => {cond}", timeout=6000)
                except PlaywrightError:
                    pass
        settle(page)
        if js and route.get("world") and mode == "nowebgl":
            try:
                page.wait_for_function("() => document.documentElement.classList.contains('world-off')", timeout=8000)
            except PlaywrightError:
                pass
        data = page.evaluate(SKELETON)
        out.update(data)
        keys = {sec["index"]: sec["key"] for sec in data.get("sections", [])}
        out["sectionBoxes"] = [{"key": keys.get(b["i"]), "top": b["top"], "height": b["height"]}
                               for b in page.evaluate(SECTION_BOXES_JS) if b["height"] > 0 and keys.get(b["i"]) != "menu-sheet"]

        out["perf"] = page.evaluate("""() => {
            const nav = performance.getEntriesByType('navigation')[0];
            const load = nav ? nav.loadEventStart : 0;
            return { load, before: performance.getEntriesByType('resource').filter(e => e.startTime <= load).map(e => ({ url: e.name, type: e.initiatorType })) };
        }""")
        if shot_dir is not None:
            out["shots"] = shoot_sections(page, data["sections"], shot_dir)
        if mode == "reduced" and shot_dir is not None:
            # Gate 7.14 "no hard line between sections": one full page at rest, scanned row by row.
            # Taken after the section shots: a full-page capture resizes the layout for a moment,
            # which can flip the header's compact state (header.js's scroll sentinel) before the
            # header shot. shoot_sections() has hidden the header by now; it is fixed, not a seam.
            page.evaluate("window.scrollTo(0, 0)")
            page.wait_for_timeout(300)
            full = shot_dir / "full-page.png"
            page.screenshot(path=str(full), full_page=True, animations="disabled", scale="css")
            out["fullShot"] = str(full)
            out["hardLines"] = hard_lines(full)
        if mode == "reduced" and width == "390":
            page.set_viewport_size({"width": 320, "height": 640})
            page.wait_for_timeout(400)
            out["sideways320"] = page.evaluate("Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth)")
        out["php"] = php_errors(body)
        out["doc"] = body
        out["headStyleBytes"] = data.get("headInlineStyleBytes", 0)
        out["htmlBytes"] = len(body.encode("utf-8"))
    except PlaywrightError as exc:
        out["loadError"] = str(exc).splitlines()[0][:300]
    finally:
        out["errors"] = errors
        out["failed"] = failed
        out["requests"] = requests
        ctx.close()
    return out


def shoot_sections(page, sections: list[dict], shot_dir: Path) -> dict:
    """An element screenshot of every section root (header first, at the top of the page; the
    header is hidden for the others so a sticky header does not cover them)."""
    shot_dir.mkdir(parents=True, exist_ok=True)
    shots = {}
    hidden = False
    for s in sections:
        if s.get("shallow") or s["key"] == "menu-sheet":
            continue
        if s["key"] != "header" and not hidden:
            page.add_style_tag(content=".header-slot,.site-header{visibility:hidden!important}")
            hidden = True
        loc = page.locator(f'[data-parity-section="{s["index"]}"]')
        path = shot_dir / (slug(s["key"]) + ".png")
        try:
            box = loc.bounding_box()
            if not box or box["width"] < 1 or box["height"] < 1:
                continue
            loc.screenshot(path=str(path), animations="disabled", timeout=30000)
            shots[s["key"]] = str(path)
        except PlaywrightError as exc:
            shots[s["key"]] = "error: " + str(exc).splitlines()[0][:160]
    return shots


def php_errors(body: str) -> list[str]:
    if SMOKE is None:
        return []
    found = []
    for pattern in (SMOKE.PHP_ERROR, SMOKE.WP_ERROR_TEXT):
        m = pattern.search(body)
        if m:
            found.append(re.sub(r"\s+", " ", re.sub(r"<[^>]+>", "", m.group(0)))[:200])
    return found


# --------------------------------------------------------------------------- comparison

def load_allow() -> list[dict]:
    entries = []
    for owner in ("assets", "templates"):
        f = HERE / f"allow-{owner}.json"
        if f.is_file():
            data = json.loads(f.read_text(encoding="utf-8"))
            for e in data.get("entries", []) if isinstance(data, dict) else data:
                e = dict(e)
                e.setdefault("owner", owner)
                entries.append(e)
    return entries


ALLOW_KINDS = ("structure", "text", "links", "pixels", "timing")
ALLOW_KEYS = {"route", "section", "kind", "match", "reason", "owner", "width", "mode"}


def allow_entry_problems(entry, owner: str) -> list[str]:
    """What is wrong with one allow entry (5.4), or [] when its shape is right.

    route "*" or a path; section a non-empty key; kind one of ALLOW_KINDS; match a regular
    expression that compiles (non-empty, except for pixels, where it is not used); a non-empty
    reason; owner equal to the stream whose file holds it; optional width ("390", "1440" or a
    list of them) and mode (a MODES key or a list of them). No other keys."""
    if not isinstance(entry, dict):
        return ["not an object"]
    probs = []
    extra = sorted(set(entry) - ALLOW_KEYS)
    if extra:
        probs.append("unknown key(s) " + ", ".join(extra))
    route = entry.get("route")
    if not isinstance(route, str) or not (route == "*" or route.startswith("/")):
        probs.append(f"route {route!r} is not '*' or a path")
    if not isinstance(entry.get("section"), str) or not entry.get("section"):
        probs.append("no section")
    kind = entry.get("kind")
    if kind not in ALLOW_KINDS:
        probs.append(f"kind {kind!r} is not one of {'|'.join(ALLOW_KINDS)}")
    match = entry.get("match", "")
    if not isinstance(match, str):
        probs.append("match is not a string")
    else:
        if not match and kind != "pixels":
            probs.append(f"empty match on a {kind} entry")
        try:
            re.compile(match)
        except re.error as exc:
            probs.append(f"match does not compile: {exc}")
    if not isinstance(entry.get("reason"), str) or not entry["reason"].strip():
        probs.append("no reason")
    if entry.get("owner") != owner:
        probs.append(f"owner {entry.get('owner')!r} is not {owner!r} (the file's stream)")
    for key, valid in (("width", set(ROUTES["widths"])), ("mode", set(MODES))):
        if key in entry:
            vals = entry[key] if isinstance(entry[key], list) else [entry[key]]
            bad = [v for v in vals if str(v) not in valid]
            if not vals or bad:
                probs.append(f"{key} {entry[key]!r} is not one of {'|'.join(sorted(valid))}")
    return probs


def allow_problems() -> list[str]:
    """Shape problems of every entry in allow-assets.json and allow-templates.json."""
    out = []
    for owner in ("assets", "templates"):
        f = HERE / f"allow-{owner}.json"
        if not f.is_file():
            continue
        try:
            data = json.loads(f.read_text(encoding="utf-8"))
        except json.JSONDecodeError as exc:
            out.append(f"{f.name}: invalid JSON ({exc})")
            continue
        entries = data.get("entries") if isinstance(data, dict) else data
        if not isinstance(entries, list):
            out.append(f"{f.name}: no entries list")
            continue
        for i, e in enumerate(entries):
            out += [f"{f.name} entry {i}: {msg}" for msg in allow_entry_problems(e, owner)]
    return out


def scope_values(value) -> list[str]:
    """An allow entry's optional width or mode scope as a list of strings ([] = any)."""
    if value in (None, "", "*"):
        return []
    return [str(v) for v in (value if isinstance(value, list) else [value])]


def allowed(kind: str, route: str, section: str, width: str | None = None, mode: str | None = None) -> list[dict]:
    """The allow entries for one comparison. An entry may name a "width" ("390" or ["390"]) and a
    "mode" ("reduced", ["reduced", "nowebgl"]); a scoped entry applies only to comparisons made at
    that width or in that mode, never to one whose width or mode is not known."""
    out = []
    for e in ALLOW:
        if e.get("kind") != kind or e.get("route", "*") not in ("*", route) or e.get("section", "*") not in ("*", section):
            continue
        widths, modes = scope_values(e.get("width")), scope_values(e.get("mode"))
        if widths and str(width) not in widths:
            continue
        if modes and str(mode) not in modes:
            continue
        out.append(e)
    return out


def diff_lists(a: list[str], b: list[str], kind: str, route: str, section: str,
               width: str | None = None, mode: str | None = None) -> dict:
    """Unified diff of two line lists, minus the lines an allow entry matches."""
    raw = list(difflib.unified_diff(a, b, "prototype", "wordpress", lineterm="", n=1))
    entries = allowed(kind, route, section, width, mode)
    kept, dropped = [], 0
    for line in raw[2:]:
        if line[:1] in "+-" and any(re.search(e["match"], line[1:]) for e in entries if e.get("match")):
            dropped += 1
            continue
        kept.append(line)
    changes = [l for l in kept if l[:1] in "+-"]
    return {"pass": not changes, "changes": len(changes), "allowed": dropped, "diff": kept[:400]}


def compare_pixels(a: str, b: str, diff_path: Path, route: str, section: str,
                   width: str | None = None, mode: str | None = None) -> dict:
    if Image is None:
        return {"pass": False, "why": "Pillow is not installed"}
    A, B = Image.open(a).convert("RGB"), Image.open(b).convert("RGB")
    w, h = min(A.width, B.width), min(A.height, B.height)
    res = {"proto": [A.width, A.height], "wp": [B.width, B.height]}
    if w == 0 or h == 0:
        return {"pass": False, **res, "why": "empty"}
    ca, cb = A.crop((0, 0, w, h)), B.crop((0, 0, w, h))
    r, g, bl = ImageChops.difference(ca, cb).split()
    mask = ImageChops.lighter(ImageChops.lighter(r, g), bl).point(lambda v: 255 if v > PIXEL_FAR else 0)
    far = mask.histogram()[255]
    share = round(100 * far / (w * h), 2)
    height_ok = abs(A.height - B.height) <= HEIGHT_SLACK and abs(A.width - B.width) <= HEIGHT_SLACK
    ok = height_ok and share <= PIXEL_SHARE
    res.update({"share": share, "heightOk": height_ok})
    if not ok:
        accepted = allowed("pixels", route, section, width, mode)
        if accepted:
            res["allowedBy"] = accepted[0].get("reason", "")
            ok = True
        diff_path.parent.mkdir(parents=True, exist_ok=True)
        canvas = Image.new("RGB", (max(A.width, B.width), max(A.height, B.height)), (255, 0, 255))
        base = ImageChops.multiply(ca.convert("L").convert("RGB"), Image.new("RGB", (w, h), (200, 200, 200)))
        base.paste(Image.new("RGB", (w, h), (230, 30, 30)), mask=mask)
        canvas.paste(base, (0, 0))
        canvas.save(diff_path)
        res["diffMap"] = str(diff_path)
    res["pass"] = ok
    return res


def pixel_share(a: str, b: str) -> float | None:
    """Percent of pixels differing by more than PIXEL_FAR in a channel, over the common area."""
    if Image is None:
        return None
    A, B = Image.open(a).convert("RGB"), Image.open(b).convert("RGB")
    w, h = min(A.width, B.width), min(A.height, B.height)
    if not w or not h:
        return 100.0
    r, g, bl = ImageChops.difference(A.crop((0, 0, w, h)), B.crop((0, 0, w, h))).split()
    mask = ImageChops.lighter(ImageChops.lighter(r, g), bl).point(lambda v: 255 if v > PIXEL_FAR else 0)
    return round(100 * mask.histogram()[255] / (w * h), 2)


def hard_lines(path, min_step: float = HARD_STEP, span: float = HARD_SPAN) -> list[dict] | None:
    """Rows of a full-page capture where at least `span` of the columns step by more than
    `min_step` levels (max channel) between the 3 rows above and the 3 rows below: a straight
    edge across the page (05-DESIGN 0.5). None without numpy or Pillow."""
    try:
        import numpy as np
    except ImportError:
        return None
    if Image is None:
        return None
    im = Image.open(path).convert("RGB")
    W, H = im.size
    if H < 8:
        return []
    a = np.asarray(im.resize((max(1, W // 4), H)), dtype=np.float32)  # columns in groups of 4, rows exact
    c = np.cumsum(np.pad(a, ((1, 0), (0, 0), (0, 0))), axis=0)
    ys = np.arange(3, H - 3)
    step = np.abs((c[ys] - c[ys - 3]) / 3 - (c[ys + 3] - c[ys]) / 3).max(axis=2)
    share = (step > min_step).mean(axis=1)
    found, last = [], -10
    for idx in np.nonzero(share >= span)[0]:
        y = int(ys[idx])
        if y - last > 4:
            found.append({"y": y, "cols": round(float(share[idx]), 2), "step": round(float(np.median(step[idx])), 1)})
            last = y
    return found


def section_at(y: float, boxes: list[dict]) -> dict | None:
    """The innermost section box (smallest height) that holds page position y."""
    inside = [b for b in boxes if b["top"] <= y < b["top"] + b["height"]]
    return min(inside, key=lambda b: b["height"]) if inside else None


def place_lines(lines: list[dict], boxes: list[dict]) -> list[dict]:
    """Each line with the section it falls in, its offset from that section's top and its
    distance to that section's bottom."""
    out = []
    for ln in lines or []:
        box = section_at(ln["y"], boxes)
        out.append(dict(ln, section=box["key"] if box else None,
                        offset=(ln["y"] - box["top"]) if box else None,
                        offsetEnd=(box["top"] + box["height"] - ln["y"]) if box else None))
    return out


def wp_only_lines(proto: list[dict], wp: list[dict], tol: int = 3) -> list[dict]:
    """Lines WordPress has that the prototype does not: no prototype line in the same section at
    the same offset from its top or from its bottom (a section whose content is a few px shorter
    above the line, an allowed 2.8 difference, keeps the line's distance to the section's end), or,
    outside sections, at the same page position; all within tol px."""
    only = []
    for w in wp:
        same = any(
            (w["section"] is not None and p["section"] == w["section"]
             and (abs(p["offset"] - w["offset"]) <= tol
                  or abs(p.get("offsetEnd", 10 ** 9) - w.get("offsetEnd", -10 ** 9)) <= tol))
            or abs(p["y"] - w["y"]) <= tol
            for p in proto)
        if not same:
            only.append(w)
    return only


def map_positions(proto_boxes: list[dict], wp_boxes: list[dict], ys: list[int]) -> list[int]:
    """The prototype's strip positions on WordPress: the same offset into the same section, so
    a section that is a few px taller on one side does not shift every later frame (5.5).
    A position outside every section, or in a section WordPress lacks, stays where it is."""
    wp = {b["key"]: b for b in wp_boxes}
    out = []
    for y in ys:
        box = section_at(y, proto_boxes)
        w = wp.get(box["key"]) if box else None
        if not box or not w:
            out.append(int(y))
            continue
        out.append(int(round(w["top"] + min(y - box["top"], max(0, w["height"] - 1)))))
    return out


def page_checks(route: dict, mode: str, page: dict, three: dict) -> list[str]:
    """Failures of one side's page view that do not need the other side."""
    fails = []
    if page.get("loadError"):
        return ["load: " + page["loadError"]]
    if page.get("status") != route["status"] and not (route["status"] == 404):
        fails.append(f"status {page.get('status')}, expected {route['status']}")
    doc = page["url"]
    errs = [e for e in page["errors"] if not (route["status"] == 404 and "404" in e["text"] and e["url"].split("?")[0] == doc.split("?")[0])]
    if errs:
        fails.append(f"console: {len(errs)} error(s): " + " | ".join(e["text"][:120] for e in errs[:3]))
    bad = [f for f in page["failed"] if not f.get("doc")]
    if bad:
        fails.append(f"requests: {len(bad)} failed: " + " | ".join(f"{f['why']} {norm_url(f['url'])}" for f in bad[:4]))
    if page.get("php"):
        fails.append("php: " + " | ".join(page["php"]))
    if len(page.get("h1", [])) != 1:
        fails.append(f"h1: {len(page.get('h1', []))} rendered")
    if page.get("sideways"):
        fails.append(f"sideways scroll {page['sideways']} px")
    if page.get("sideways320"):
        fails.append(f"sideways scroll at 320: {page['sideways320']} px")
    if mode in ("nojs", "nowebgl", "tier0"):
        st = page.get("stills", {})
        if st.get("loaded", 0) < st.get("total", 0):
            fails.append(f"stills: {st['total'] - st['loaded']} of {st['total']} not shown: " + ", ".join(st.get("missing", [])[:4]))
    if mode in ("reduced", "tier0", "nowebgl"):
        hit = three_requested(page["requests"], three)
        if hit:
            fails.append("three.js requested: " + hit)
    if mode == "nowebgl" and route.get("world") and "world-off" not in page.get("html", {}).get("cls", ""):
        fails.append("html.world-off not reached without WebGL")
    return fails


def wp_only_checks(route: dict, mode: str, pages: dict) -> list[str]:
    """Gate 7.10 items measured on WordPress alone, with the prototype's value for reference."""
    fails = []
    p, q = pages["wp"], pages["proto"]
    if p.get("loadError"):
        return fails
    if p.get("headStyleBytes", 0) > BUDGET["head_style_bytes"]:
        fails.append(f"inline <style> in <head>: {p['headStyleBytes']} bytes (max {BUDGET['head_style_bytes']}; prototype {q.get('headStyleBytes')})")
    if mode == "tier0":
        before = [e for e in (p.get("perf") or {}).get("before", []) if not IMAGE_URL.search(e["url"])]
        if not route.get("world"):
            if len(before) + 1 > BUDGET["inner_requests"]:
                proto_n = len([e for e in (q.get("perf") or {}).get("before", []) if not IMAGE_URL.search(e["url"])]) + 1
                fails.append(f"requests before load: {len(before) + 1} (max {BUDGET['inner_requests']}; prototype {proto_n})")
            chunks = [norm_url(e["url"]) for e in before if MOTION_CHUNK.search(e["url"]) or WORLD_CHUNK.search(e["url"])]
            if chunks:
                fails.append("motion or world chunk before load on an inner page: " + ", ".join(chunks))
        mine = {norm_url(r["url"]) for r in p.get("requests", []) if r["type"] in ("script", "stylesheet")}
        theirs = {norm_url(r["url"]) for r in q.get("requests", []) if r["type"] in ("script", "stylesheet")}
        extra = sorted(u for u in mine - theirs if not re.search(r"/draft(\.css)?$", u) and not u.endswith("draft.css"))
        missing = sorted(theirs - mine)
        if extra or missing:
            fails.append("script/style requests differ from the prototype: " + ", ".join([f"+{u}" for u in extra[:6]] + [f"-{u}" for u in missing[:6]]))
        ver = sorted({norm_url(r["url"]) for r in p.get("requests", []) if "/assets/dist/" in r["url"] and "ver=" in r["url"]})
        if ver:
            fails.append("?ver on dist URLs: " + ", ".join(ver[:4]))
        bad = forbidden_assets(p.get("doc", ""), p.get("requests", []))
        if bad:
            fails.append("assets the prototype never loads: " + ", ".join(bad))
    return fails


def forbidden_assets(html: str, requests: list[dict]) -> list[str]:
    """jQuery, Elementor, emoji, embed, global-styles or block-library assets: in the request log
    or in the id, src and href attributes of the HTML (not in body classes such as
    wp-embed-responsive, which load nothing)."""
    attrs = " ".join(re.findall(r"""\b(?:id|src|href)=["']([^"']*)""", html))
    urls = " ".join(r["url"] for r in requests)
    return sorted(k for k, rx in FORBIDDEN.items() if rx.search(attrs) or rx.search(urls))


def three_requested(requests: list[dict], three: dict) -> str:
    names = three.get("chunks", [])
    for r in requests:
        path = urlsplit(r["url"]).path
        if any(path.endswith("/assets/" + n) for n in names):
            return norm_url(r["url"])
        if not three.get("exists") and re.search(r"three|webglrenderer", path, re.I):
            return norm_url(r["url"])
    return ""


def compare_view(route: dict, width: str, mode: str, pages: dict, shot_root: Path | None) -> dict:
    view = {"route": route["name"], "path": route["path"], "width": width, "mode": mode,
            "checks": {}, "sections": [], "facts": {}}
    for side in SIDES:
        p = pages[side]
        view["checks"][side] = page_checks(route, mode, p, THREE[SIDE_NAME[side]])
        view["facts"][side] = {k: p.get(k) for k in ("status", "h1", "sideways", "sideways320", "stills", "html", "bodyClass", "headStyleBytes", "htmlBytes", "loadError")}
        view["facts"][side]["requestCount"] = len(p.get("requests", []))
        view["facts"][side]["scriptsStyles"] = sorted({norm_url(r["url"]) for r in p.get("requests", []) if r["type"] in ("script", "stylesheet")})
        view["facts"][side]["beforeLoad"] = len([e for e in (p.get("perf") or {}).get("before", []) if not IMAGE_URL.search(e["url"])]) + 1
        view["facts"][side]["motionBeforeLoad"] = any(MOTION_CHUNK.search(e["url"]) or WORLD_CHUNK.search(e["url"]) for e in (p.get("perf") or {}).get("before", []))
    view["checks"]["wp"] += wp_only_checks(route, mode, pages)
    if not MODES[mode]["compare"]:
        return view
    a, b = pages["proto"], pages["wp"]
    if a.get("loadError") or b.get("loadError"):
        view["sections"].append({"key": "page", "structure": {"pass": False, "diff": ["load failed"]}})
        return view
    if a.get("hardLines") is not None and b.get("hardLines") is not None:
        pl = place_lines(a["hardLines"], a.get("sectionBoxes", []))
        wl = place_lines(b["hardLines"], b.get("sectionBoxes", []))
        only = wp_only_lines(pl, wl)
        view["hardLines"] = {"proto": len(pl), "wp": len(wl), "wpOnly": only, "pass": not only,
                             "protoShot": a.get("fullShot"), "wpShot": b.get("fullShot")}
    rkey = route["path"]
    shell = diff_lists(a.get("shell", []), b.get("shell", []), "structure", rkey, "body", width, mode)
    view["sections"].append({"key": "body", "structure": shell})
    A = {s["key"]: s for s in a.get("sections", [])}
    B = {s["key"]: s for s in b.get("sections", [])}
    keys = [s["key"] for s in a.get("sections", [])] + [k for k in B if k not in A]
    for key in keys:
        sa, sb = A.get(key), B.get(key)
        row = {"key": key}
        if not sa or not sb:
            only = "prototype" if sa else "WordPress"
            miss = {"pass": False, "changes": 1, "diff": [f"section only on the {only}"]}
            row.update(structure=miss, text=miss, links=miss)
        else:
            row["structure"] = diff_lists(sa["tree"], sb["tree"], "structure", rkey, key, width, mode)
            row["text"] = diff_lists(sa["text"], sb["text"], "text", rkey, key, width, mode)
            row["links"] = diff_lists(sa["links"], sb["links"], "links", rkey, key, width, mode)
        if MODES[mode]["shots"] and shot_root is not None:
            pa, pb = (a.get("shots") or {}).get(key), (b.get("shots") or {}).get(key)
            if pa and pb and not pa.startswith("error") and not pb.startswith("error"):
                row["pixels"] = compare_pixels(pa, pb, shot_root / "diff" / (slug(key) + ".png"), rkey, key, width, mode)
                row["pixels"]["protoShot"], row["pixels"]["wpShot"] = pa, pb
            elif pa or pb:
                row["pixels"] = {"pass": False, "why": "screenshot on one side only", "protoShot": pa, "wpShot": pb}
        view["sections"].append(row)
    return view


# --------------------------------------------------------------------------- behaviour (node checks)

CHECK_LINE = re.compile(r"^(PASS|FAIL|ok)\s+(.*\S)\s*$")
PROTOTYPE_ONLY = re.compile(r"three\.js not requested")


def line_key(text: str) -> str:
    """A check line without its measured numbers, so the same check matches on both sides."""
    t = text
    while True:
        u = re.sub(r"\([^()]*\)", "", t)
        if u == t:
            break
        t = u
    if ": " in t:
        head, rest = t.split(": ", 1)
    else:
        head, _, rest = t.partition(" ")
    rest = re.sub(r"\d+(?:\.\d+)?", "#", rest)
    return re.sub(r"\s+", " ", f"{head}: {rest}").strip()


def parse_lines(output: str) -> list[dict]:
    rows = []
    for raw in output.splitlines():
        m = CHECK_LINE.match(raw.strip())
        if m:
            rows.append({"ok": m.group(1) != "FAIL", "text": m.group(2), "key": line_key(m.group(2))})
    return rows


def contrast_lines(report: Path) -> list[dict]:
    if not report.is_file():
        return []
    data = json.loads(report.read_text(encoding="utf-8"))
    rows = []
    for r in data.get("results", []):
        text = f'light-contrast {r["path"]} @{r["w"]} {r["tag"]}.{r["cls"]} "{r["text"]}"'
        rows.append({"ok": bool(r.get("pass")), "text": f"{text} ({r.get('p02')} >= {r.get('need')})", "key": line_key(text)})
    return rows


def behaviour(out: Path) -> dict:
    s = PROTO / "scripts"
    checks = {
        "check-page": lambda base, tag: ["node", str(s / "check-page.cjs"), base],
        "check-hero": lambda base, tag: ["node", str(s / "check-hero.cjs"), base, str(out / "logs" / f"hero-{tag}")],
        "check-bays": lambda base, tag: ["node", str(s / "check-bays.cjs"), base],
        "check-tiles": lambda base, tag: ["node", str(s / "check-tiles.cjs"), base],
        "check-sunin": lambda base, tag: ["node", str(s / "check-sunin.cjs"), base, str(out / "logs" / f"sunin-{tag}")],
        "light-contrast": lambda base, tag: ["node", str(s / "light-contrast.mjs"), base, str(out / "logs" / f"contrast-{tag}.json")],
    }
    timeouts = {"check-hero": 2400, "light-contrast": 1500}
    timing = [e for e in ALLOW if e.get("kind") == "timing" and e.get("match")]
    result = {}
    for name, cmd in checks.items():
        sides = {}
        for side in SIDES:
            print(f"  {name} on the {SIDE_NAME[side]} ...", flush=True)
            t0 = time.time()
            code, output = run_logged(cmd(BASES[side], side), out / "logs" / f"{name}-{side}.log", timeouts.get(name, 900), node_env())
            rows = contrast_lines(out / "logs" / f"contrast-{side}.json") if name == "light-contrast" else parse_lines(output)
            sides[side] = {"exit": code, "seconds": round(time.time() - t0), "lines": rows}
        rerun = None
        comparison = compare_lines(sides["proto"]["lines"], sides["wp"]["lines"])
        retry = [r for r in comparison["missing"] if any(re.search(e["match"], r["text"]) for e in timing)]
        if retry:
            print(f"  {name}: re-running once on WordPress for {len(retry)} timing line(s)", flush=True)
            code, output = run_logged(cmd(BASES["wp"], "wp-rerun"), out / "logs" / f"{name}-wp-rerun.log", timeouts.get(name, 900), node_env())
            again = {r["key"] for r in parse_lines(output) if r["ok"]}
            rerun = {"lines": [r["text"] for r in retry], "passed": [r["text"] for r in retry if r["key"] in again]}
            comparison["missing"] = [r for r in comparison["missing"] if r["text"] not in rerun["passed"]]
        result[name] = {"sides": {k: {"exit": v["exit"], "seconds": v["seconds"], "pass": sum(r["ok"] for r in v["lines"]), "fail": sum(not r["ok"] for r in v["lines"])} for k, v in sides.items()},
                        "missing": [r["text"] for r in comparison["missing"]], "protoFails": comparison["protoFails"],
                        "prototypeOnly": comparison["prototypeOnly"], "rerun": rerun,
                        "pass": not comparison["missing"]}
        print(f"  {name}: prototype {result[name]['sides']['proto']['pass']} pass / {result[name]['sides']['proto']['fail']} fail, "
              f"WordPress {result[name]['sides']['wp']['pass']} / {result[name]['sides']['wp']['fail']}; "
              f"{len(result[name]['missing'])} prototype PASS line(s) not passing on WordPress", flush=True)
    return result


def compare_lines(proto: list[dict], wp: list[dict]) -> dict:
    wp_pass: dict[str, int] = {}
    for r in wp:
        if r["ok"]:
            wp_pass[r["key"]] = wp_pass.get(r["key"], 0) + 1
    missing, only = [], []
    for r in proto:
        if not r["ok"]:
            continue
        if PROTOTYPE_ONLY.search(r["text"]):
            only.append(r["text"])
            continue
        if wp_pass.get(r["key"], 0) > 0:
            wp_pass[r["key"]] -= 1
        else:
            missing.append(r)
    return {"missing": missing, "protoFails": [r["text"] for r in proto if not r["ok"]], "prototypeOnly": only}


# --------------------------------------------------------------------------- world, budgets, strips

def world_load(browser, side: str, path: str, width: str, query: str, init: str | None = None,
               wait_live: bool = True) -> dict:
    """A cold-cache load of a home page with the request log and the world state."""
    lang = "en" if path.startswith("/en/") else "vi"
    ctx = new_context(browser, width, lang, {}, side)
    if init:
        ctx.add_init_script(init)
    page = ctx.new_page()
    errors, requests = [], []
    page.on("console", lambda msg: errors.append(msg.text[:300]) if msg.type == "error" else None)
    page.on("pageerror", lambda err: errors.append("page error: " + str(err)[:300]))
    page.on("request", lambda r: requests.append({"url": r.url, "type": r.resource_type}))
    out = {"side": side, "path": path, "width": width, "query": query}
    t0 = time.time()
    try:
        page.goto(BASES[side] + path + query, wait_until="load", timeout=90000)
        live = False
        if wait_live:
            try:
                page.wait_for_function("() => document.documentElement.classList.contains('world-live')", timeout=40000)
                live = True
            except PlaywrightError:
                pass
        else:
            page.wait_for_timeout(3000)
        try:
            page.wait_for_load_state("networkidle", timeout=15000)
        except PlaywrightError:
            pass
        out["live"] = live
        out["seconds"] = round(time.time() - t0, 1)
        out["perf"] = page.evaluate("""() => {
            const nav = performance.getEntriesByType('navigation')[0];
            return { load: nav.loadEventEnd, loadStart: nav.loadEventStart,
                     entries: performance.getEntriesByType('resource').map(e => ({ url: e.name, type: e.initiatorType, start: Math.round(e.startTime) })) };
        }""")
        out["moduleSrc"] = page.evaluate("[...document.querySelectorAll('script[type=module][src]')].map(s => s.src)")
        out["htmlCls"] = page.evaluate("document.documentElement.className")
        out["page"] = page
        out["ctx"] = ctx
    except PlaywrightError as exc:
        out["loadError"] = str(exc).splitlines()[0][:300]
        ctx.close()
    out["errors"], out["requests"] = errors, requests
    return out


def close_load(load: dict) -> None:
    ctx = load.pop("ctx", None)
    load.pop("page", None)
    if ctx:
        ctx.close()


def body_kb(url: str, cache: dict) -> tuple[float, float]:
    """(raw KB, gzip -9 KB) of one URL, fetched again from the local server."""
    if url not in cache:
        status, data, _ = http_get(url)
        cache[url] = (round(len(data) / 1024, 1), gz_kb(data) if not IMAGE_URL.search(url) else round(len(data) / 1024, 1))
    return cache[url]


def budgets(load: dict, doc_html: bytes) -> dict:
    cache: dict = {}
    perf = load.get("perf") or {}
    before = [e for e in perf.get("entries", []) if e["start"] <= perf.get("loadStart", 0)]
    no_img = [e for e in before if not IMAGE_URL.search(e["url"]) and e["type"] != "img"]
    kb = sum(body_kb(e["url"], cache)[1] for e in no_img) + gz_kb(doc_html)
    world = [e for e in perf.get("entries", []) if WORLD_CHUNK.search(e["url"])]
    world_kb = sum(body_kb(e["url"], cache)[1] for e in world)
    world_after = all(e["start"] >= perf.get("load", 0) for e in world)
    total = sum(body_kb(e["url"], cache)[1] for e in perf.get("entries", [])) + gz_kb(doc_html)
    return {"requestsBeforeLoad": len(no_img) + 1, "kbBeforeLoad": round(kb, 1), "htmlGzKb": gz_kb(doc_html),
            "worldKb": round(world_kb, 1), "worldChunks": sorted({norm_url(e["url"]) for e in world}),
            "worldAfterLoad": world_after, "totalKb": round(total, 1), "live": load.get("live")}


def entry_instances(loads: list[dict]) -> dict:
    """Distinct URLs the entry module was fetched from (each is one evaluation of main.js)."""
    out = {}
    for load in loads:
        srcs = load.get("moduleSrc") or []
        if not srcs:
            out[load["side"]] = out.get(load["side"], 0)
            continue
        entry = urlsplit(srcs[0]).path
        urls = {r["url"] for r in load["requests"] if urlsplit(r["url"]).path == entry}
        out[load["side"]] = max(out.get(load["side"], 0), len(urls))
    return out


def world_and_budgets(browsers, out: Path, full: bool) -> dict:
    res = {"loads": [], "gates": {}}
    loads = []
    for side in SIDES:
        for width, tier in (("390", 1), ("1440", 2)):
            print(f"  world {SIDE_NAME[side]} / at {width}, Tier {tier}", flush=True)
            load = world_load(browsers["gpu"], side, "/", width, f"?tier={tier}")
            row = {"side": side, "width": width, "tier": tier, "live": load.get("live"), "seconds": load.get("seconds"),
                   "errors": load.get("errors"), "loadError": load.get("loadError")}
            if width == "1440" and not load.get("loadError"):
                _, html_bytes, _ = http_get(BASES[side] + "/?tier=2")
                row["budgets"] = budgets(load, html_bytes)
                row["distVer"] = sorted({norm_url(r["url"]) for r in load["requests"] if "/assets/dist/" in r["url"] and "ver=" in r["url"]})
                row["forbidden"] = forbidden_assets(html_bytes.decode("utf-8", "replace"), load["requests"])
            close_load(load)
            loads.append(load)
            res["loads"].append(row)
        # The motion pause stored on an earlier page: no three.js on this one.
        load = world_load(browsers["gpu"], side, "/", "1440", "", "try { localStorage.setItem('hcwa:motion', 'off') } catch {}", wait_live=False)
        res["loads"].append({"side": side, "mode": "paused", "three": three_requested(load["requests"], THREE[SIDE_NAME[side]]), "errors": load.get("errors"), "loadError": load.get("loadError")})
        close_load(load)
    res["entryInstances"] = entry_instances(loads)
    by = {(r["side"], r.get("width"), r.get("mode")): r for r in res["loads"]}
    g = res["gates"]
    for side in SIDES:
        t1, t2, paused = by.get((side, "390", None), {}), by.get((side, "1440", None), {}), by.get((side, None, "paused"), {})
        g[f"world-live {SIDE_NAME[side]}"] = bool(t1.get("live")) and bool(t2.get("live"))
        g[f"world console {SIDE_NAME[side]}"] = not (t1.get("errors") or t2.get("errors"))
        g[f"paused: no three.js {SIDE_NAME[side]}"] = not paused.get("three") and not paused.get("loadError")
    wp2 = by.get(("wp", "1440", None), {})
    b = wp2.get("budgets") or {}
    g["main.js evaluated once"] = res["entryInstances"].get("wp") == 1
    g["no ?ver on dist URLs"] = not wp2.get("distVer") and bool(b)
    g["no forbidden assets"] = not wp2.get("forbidden") and bool(b)
    if b:
        g[f"home requests before load <= {BUDGET['home_requests']}"] = b["requestsBeforeLoad"] <= BUDGET["home_requests"]
        g[f"home KB before load <= {BUDGET['home_kb']}"] = b["kbBeforeLoad"] <= BUDGET["home_kb"]
        g[f"home HTML gzip <= {BUDGET['home_html_gz_kb']} KB"] = b["htmlGzKb"] <= BUDGET["home_html_gz_kb"]
        g[f"world set <= {BUDGET['world_kb']} KB, after load"] = b["worldKb"] <= BUDGET["world_kb"] and b["worldAfterLoad"] and bool(b["worldChunks"])
        g[f"home after world-live <= {BUDGET['home_total_kb']} KB"] = b["totalKb"] <= BUDGET["home_total_kb"] and bool(b["live"])
    if full:
        res["strips"] = frame_strips(browsers["gpu"], out / "strips")
        for k, v in res["strips"].get("gates", {}).items():
            g[k] = v
    return res


STRIP_GEO_JS = """() => {
    const beats = [...document.querySelectorAll('[data-beat]')];
    const last = beats.length ? beats[beats.length - 1].getBoundingClientRect().top + scrollY : null;
    const h2 = document.querySelector('#work h2');
    const foot = document.querySelector('footer');
    // Section boxes named as skeleton.js names them (main > * and the footer; home has no .day).
    const skip = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEMPLATE', 'LINK', 'META']);
    const seen = new Map(), boxes = [];
    const keyOf = (el) => {
        const first = [...el.classList].filter((c) => !c.startsWith('wp-'))[0];
        let key = 'main>' + el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (first ? '.' + first : '');
        const n = (seen.get(key) || 0) + 1; seen.set(key, n);
        return n > 1 ? key + '~' + n : key;
    };
    const box = (key, el) => { const r = el.getBoundingClientRect(); boxes.push({ key, top: Math.round(r.top + scrollY), height: Math.round(r.height) }); };
    const main = document.querySelector('main');
    for (const child of main ? main.children : []) if (!skip.has(child.tagName)) box(keyOf(child), child);
    if (foot) box('footer', foot);
    return { last, h2: h2 ? h2.getBoundingClientRect().top + scrollY : null, vh: innerHeight, boxes,
             end: foot ? foot.getBoundingClientRect().top + scrollY : document.documentElement.scrollHeight };
}"""

# Waits until no element still waiting for its reveal is in view and no CSS transition runs
# (reveals.js: IntersectionObserver plus a 520 ms fade), at most maxMs.
STRIP_SETTLE_JS = """async (maxMs) => {
    const t0 = performance.now();
    const pending = () => [...document.querySelectorAll('.reveal-pending')].some((el) => {
        const r = el.getBoundingClientRect();
        return r.width > 0 && r.bottom > 0 && r.top < innerHeight;
    });
    const moving = () => document.getAnimations().some((a) => a.playState === 'running' && typeof CSSTransition !== 'undefined' && a instanceof CSSTransition);
    await new Promise((r) => setTimeout(r, 250));
    while (performance.now() - t0 < maxMs && (pending() || moving())) await new Promise((r) => setTimeout(r, 50));
    await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
    return Math.round(performance.now() - t0);
}"""


def frame_strips(browser, out: Path) -> dict:
    """24 viewport frames from the hero to the footer, paused after world-live, both sides (5.5).

    The prototype is captured first at 24 positions spread to its footer top. WordPress is then
    captured at the same offsets into the same sections (map_positions), so a section that is a
    few px taller on one side does not shift every later frame. Before each shot the driver waits
    until no reveal is pending in view and no transition runs (at most 1.5 s). Each frame's pixel
    share between the sides is recorded; a frame over STRIP_SHARE fails its strip's gate unless
    its section has a pixel allow entry for that route and width."""
    out.mkdir(parents=True, exist_ok=True)
    res = {"strips": [], "gates": {}}
    for path in ("/", "/en/"):
        for width, tier in (("390", 1), ("1440", 2)):
            frames = {}
            meta = {}
            ys: dict[str, list[int]] = {}
            for side in SIDES:
                load = world_load(browser, side, path, width, f"?tier={tier}")
                page = load.get("page")
                frames[side], meta[side] = [], {"live": load.get("live")}
                if not page:
                    meta[side]["loadError"] = load.get("loadError")
                    continue
                try:
                    page.locator("[data-motion-toggle]").first.click(timeout=3000)
                except PlaywrightError:
                    pass
                geo = page.evaluate(STRIP_GEO_JS)
                meta[side]["geo"] = geo
                if side == "proto" or "proto" not in ys:
                    ys[side] = [round(geo["end"] * i / 23) for i in range(24)]
                else:
                    ys[side] = map_positions(meta["proto"]["geo"]["boxes"], geo["boxes"], ys["proto"])
                for i, y in enumerate(ys[side]):
                    page.evaluate(f"window.scrollTo({{top: {y}, behavior: 'instant'}})")
                    waited = page.evaluate(STRIP_SETTLE_JS, 1500)
                    shot = out / f"{slug(path)}-{width}-{side}-{i:02d}.png"
                    page.screenshot(path=str(shot))
                    frames[side].append({"y": y, "scrollY": page.evaluate("Math.round(scrollY)"), "settleMs": waited,
                                         "file": str(shot), "nonBackground": non_background(shot)})
                close_load(load)
            name = f"{'vi' if path == '/' else 'en'}-{width}-t{tier}"
            strip = out / f"strip-{name}.png"
            compose_strip(frames, strip, width)
            shares = []
            boxes = ((meta.get("proto") or {}).get("geo") or {}).get("boxes", [])
            for i in range(min(len(frames["proto"]), len(frames["wp"]))):
                a, b = frames["proto"][i], frames["wp"][i]
                box = section_at(a["y"], boxes)
                key = box["key"] if box else None
                share = pixel_share(a["file"], b["file"])
                accepted = bool(key and allowed("pixels", path, key, width, None))
                shares.append({"i": i, "yProto": a["y"], "yWp": b["y"], "section": key, "share": share, "allowed": accepted,
                               "pass": share is not None and (share <= STRIP_SHARE or accepted)})
            res["strips"].append({"name": name, "file": str(strip), "meta": meta, "frameShares": shares,
                                  "frames": {s: [{k: f[k] for k in ("y", "scrollY", "settleMs", "nonBackground")} for f in frames[s]] for s in SIDES}})
            for side in SIDES:
                g = meta[side].get("geo") or {}
                lo, hi = g.get("last"), (g.get("h2") or 0) - (g.get("vh") or 0)
                thin = [f for f in frames[side] if lo is not None and lo <= f["y"] <= hi and f["nonBackground"] < 15]
                res["gates"][f"strip {name} {SIDE_NAME[side]}: world-live"] = bool(meta[side].get("live"))
                res["gates"][f"strip {name} {SIDE_NAME[side]}: no frame under 15% non-background between the last door and the H2"] = lo is not None and not thin
            res["gates"][f"strip {name}: 24 frames within {STRIP_SHARE}% between sides (outside allowed sections)"] = (
                len(shares) == 24 and all(r["pass"] for r in shares))
    return res


def non_background(path: Path) -> float:
    """Percent of pixels off the frame's most common colour by more than 24/255 in a channel."""
    if Image is None:
        return 0.0
    img = Image.open(path).convert("RGB").resize((160, 160))
    q = img.quantize(colors=16, method=Image.Quantize.MEDIANCUT).convert("RGB")
    common = max(q.getcolors(160 * 160), key=lambda c: c[0])[1]
    flat = Image.new("RGB", img.size, common)
    r, g, b = ImageChops.difference(img, flat).split()
    mask = ImageChops.lighter(ImageChops.lighter(r, g), b).point(lambda v: 255 if v > 24 else 0)
    return round(100 * mask.histogram()[255] / (160 * 160), 1)


def compose_strip(frames: dict, path: Path, width: str) -> None:
    if Image is None or not frames.get("proto"):
        return
    scale = 0.5 if width == "390" else 0.25
    thumbs = {}
    for side in SIDES:
        thumbs[side] = []
        for f in frames.get(side, []):
            im = Image.open(f["file"]).convert("RGB")
            thumbs[side].append(im.resize((int(im.width * scale), int(im.height * scale))))
    tw, th = thumbs["proto"][0].size
    cols = 4
    rows = (24 + cols - 1) // cols
    sheet = Image.new("RGB", (cols * (2 * tw + 12) , rows * (th + 8)), (255, 255, 255))
    for i in range(24):
        x, y = (i % cols) * (2 * tw + 12), (i // cols) * (th + 8)
        for j, side in enumerate(SIDES):
            if i < len(thumbs[side]):
                sheet.paste(thumbs[side][i], (x + j * tw, y))
    sheet.save(path)


def lcp_runs(out: Path) -> dict:
    res = {}
    proxy = PROCS.start(["node", str(HERE / "gzip-proxy.mjs"), BASES["wp"], "0"], cwd=REPO, stdout=subprocess.PIPE, text=True)
    line = proxy.stdout.readline().strip()
    m = re.search(r"listening (http://\S+)", line)
    if not m:
        PROCS.stop(proxy)
        return {"error": "gzip proxy did not start: " + line}
    proxy_base = m.group(1)
    try:
        for path in ("/", "/hoat-dong/co-so/tre-xanh/"):
            key = slug(path)
            print(f"  LCP {path} on the prototype ...", flush=True)
            _, proto_out = run_logged(["node", str(PROTO / "scripts" / "lcp.cjs"), "3", path], out / "logs" / f"lcp-proto-{key}.log", 1200, node_env())
            print(f"  LCP {path} on WordPress (gzip proxy) ...", flush=True)
            _, wp_out = run_logged(["node", str(HERE / "lcp.cjs"), proxy_base, "3", path, str(out / "logs" / f"lcp-wp-{key}.json")], out / "logs" / f"lcp-wp-{key}.log", 1200, node_env())
            res[path] = {"proto": parse_lcp(proto_out), "wp": parse_lcp(wp_out)}
    finally:
        PROCS.stop(proxy)
    gates = {}
    for path, sides in res.items():
        for label, wp in sides["wp"].items():
            proto = sides["proto"].get(label, {})
            ok = wp.get("lcp") is not None and wp["lcp"] <= 2500 and wp.get("cls", 1) < 0.1 and wp.get("after", False)
            if proto.get("lcp") is not None and wp.get("lcp") is not None:
                ok = ok and wp["lcp"] - proto["lcp"] <= 300
            if label.startswith("default"):
                ok = ok and wp.get("longest", 999) <= 200
            gates[f"LCP {path} {label}"] = ok
    return {"paths": res, "gates": gates}


def parse_lcp(text: str) -> dict:
    out = {}
    for m in re.finditer(r"^(.+?): median LCP (\d+|NaN|Infinity) ms .*?max CLS ([\d.]+) · three\.js after LCP: (true|false)", text, re.M):
        out[m.group(1)] = {"lcp": int(m.group(2)) if m.group(2).isdigit() else None, "cls": float(m.group(3)), "after": m.group(4) == "true"}
    for m in re.finditer(r"^(.+?): longest main-thread task in the first 10 s (\d+) ms", text, re.M):
        out.setdefault(m.group(1), {})["longest"] = int(m.group(2))
    return out


# --------------------------------------------------------------------------- debug.log

DEBUG_LINE = re.compile(r"PHP (Warning|Notice|Deprecated|Fatal error|Parse error)")
OURS = re.compile(r"themes/hcwa-2026|plugins/hcwa-core")


def debug_log_size(instance: Path | None) -> int:
    f = instance / "logs" / "debug.log" if instance else None
    return f.stat().st_size if f and f.is_file() else 0


def debug_log_new(instance: Path | None, start: int) -> dict:
    f = instance / "logs" / "debug.log" if instance else None
    if not f or not f.is_file():
        return {"checked": False, "ours": []}
    with f.open("rb") as fh:
        fh.seek(start)
        text = fh.read().decode("utf-8", "replace")
    lines = [l for l in text.splitlines() if DEBUG_LINE.search(l)]
    return {"checked": True, "newLines": len(lines), "ours": [l[:300] for l in lines if OURS.search(l)][:50]}


# --------------------------------------------------------------------------- production assertions (7.7)

# The guards, the listed stills and the BLOCKERS scan are the smoke test's own functions
# (scripts/wp-smoke.py load_guards, listed_stills, blocker_hits), so both read the same exports.


PROD_JS = r"""
() => {
  const ids = new Set([...document.querySelectorAll('[id]')].map(e => e.id));
  // The corridor section: the English Our Work page's h2 is #centers too (2.1 rule 5).
  const centers = document.querySelector('section.bays-section#centers[data-world="corridor"]');
  return {
    beats: document.querySelectorAll('[data-beat]').length,
    n: centers ? (centers.getAttribute('style') || '').match(/--n:\s*(\d+)/)?.[1] ?? null : null,
    hasCenters: !!centers,
    traces: document.querySelectorAll('[data-traces]').length,
    forms: document.querySelectorAll('form[data-form]').length,
    partners: document.querySelectorAll('section.partners, #partners').length,
    slip: document.querySelectorAll('.slip__rows dd, .slip__qr img, .slip__qr svg').length,
    paypal: [...document.querySelectorAll('a[href]')].filter(a => /paypal/i.test(a.href)).length,
    draftAttrs: document.querySelectorAll('[data-draft], [data-pending], [data-donation-placeholder]').length,
    pendingChips: document.querySelectorAll('.chip-pending').length,
    danglingLabel: [...document.querySelectorAll('[aria-labelledby]')].flatMap(e => e.getAttribute('aria-labelledby').split(/\s+/)).filter(id => id && !ids.has(id)),
    danglingHash: [...document.querySelectorAll('a[href^="#"]')].map(a => a.getAttribute('href').slice(1)).filter(id => !id || !ids.has(decodeURIComponent(id))),
    emptyContact: [...document.querySelectorAll('a[href^="tel:"], a[href^="mailto:"]')].filter(a => !a.textContent.trim() || /^(tel|mailto):\s*$/i.test(a.getAttribute('href'))).length,
    robots: [...document.querySelectorAll('meta[name=robots]')].map(m => m.content).join(','),
    chips: [...document.querySelectorAll('li.door-chip[data-unit]')].map(li => ({ unit: li.dataset.unit, door: !!li.querySelector('.door-chip__door'), unitVar: /--unit/.test(li.getAttribute('style') || '') })),
    contact: {
      litActions: document.querySelectorAll('.lit-head__actions').length,
      reachWrite: document.querySelectorAll('section.reach__write').length,
      office: document.querySelectorAll('section.office').length,
      units: document.querySelectorAll('.units__list li.unit').length,
      footContact: document.querySelectorAll('ul.foot-contact').length,
    },
    donate: {
      go: document.querySelectorAll('a.sun-head__go').length,
      jump: document.querySelectorAll('ul.sun-head__jump a').length,
      slip: document.querySelectorAll('section.slip').length,
      tell: document.querySelectorAll('section.tell').length,
      trust: document.querySelectorAll('nav.trust').length,
      dusk: document.querySelectorAll('section.dusk .dusk__title').length,
      duskBtn: document.querySelectorAll('section.dusk a.btn').length,
    },
  };
}
"""


def production_asserts(base: str, instance: Path | None, out: Path) -> dict:
    try:
        guards = SMOKE.load_guards()
    except (RuntimeError, OSError, ValueError, subprocess.SubprocessError) as exc:
        raise SystemExit(f"production assertions need the guards: {exc}")
    stills = SMOKE.listed_stills()
    stills_source = "assets/generated/stills.json" if stills is not None else "assets/generated/stills.json is missing (milestone A of the assets stream)"
    manifest = json.loads((instance / "seed-urls.json").read_text(encoding="utf-8")) if instance and (instance / "seed-urls.json").is_file() else {}
    parity = manifest.get("parity") or {}
    protected = [c["unit"] for c in parity.get("centers", []) if c.get("protected")]
    expected_beats = parity.get("expected_beats_production")
    res = {"base": base, "guards": guards["source"], "stills": stills_source, "routes": []}
    with sync_playwright() as p:
        browser = p.chromium.launch()
        for route in ROUTES["routes"]:
            for width in ("390", "1440"):
                ctx = new_context(browser, width, route["lang"], {}, "wp")
                page = ctx.new_page()
                requests = []
                page.on("request", lambda r: requests.append(r.url))
                fails = []
                try:
                    resp = page.goto(base + route["path"], wait_until="load", timeout=60000)
                    html = resp.text() if resp else ""
                    status = resp.status if resp else None
                    settle(page)
                    d = page.evaluate(PROD_JS)
                except PlaywrightError as exc:
                    fails.append("load: " + str(exc).splitlines()[0][:200])
                    res["routes"].append({"route": route["path"], "width": width, "fails": fails})
                    ctx.close()
                    continue
                ctx.close()
                if status != route["status"]:
                    fails.append(f"status {status}, expected {route['status']}")
                fails += ["BLOCKERS: " + h for h in SMOKE.blocker_hits(html, guards)]
                if guards["door"].search(html):
                    fails.append("LITTLE_ROSE_DOOR pattern in the HTML")
                if stills is None:
                    fails.append("listed stills not checked: " + stills_source)
                else:
                    named = [s for s in stills if s in html or any(s in u for u in requests)]
                    if named:
                        fails.append("listed stills in the HTML or requests: " + ", ".join(named))
                if route.get("world") or d["hasCenters"]:
                    if expected_beats is None:
                        fails.append("beat count not checked: seed-urls.json has no parity.expected_beats_production (re-seed)")
                    else:
                        if d["beats"] != expected_beats:
                            fails.append(f"beats {d['beats']}, expected {expected_beats}")
                        if d["n"] is not None and int(d["n"]) != expected_beats:
                            fails.append(f"--n {d['n']}, expected {expected_beats}")
                bad_chips = [c for c in d["chips"] if c["unit"] in protected and (c["door"] or c["unitVar"])]
                if bad_chips:
                    fails.append(f"{len(bad_chips)} protected door chip(s) with a door or --unit")
                for key, label in (("traces", "data-traces"), ("forms", "form[data-form]"), ("partners", "partners section"),
                                   ("slip", "slip values or QR"), ("paypal", "PayPal link"), ("draftAttrs", "data-draft/-pending/-donation-placeholder"),
                                   ("pendingChips", "pending chip"), ("emptyContact", "empty tel:/mailto: link")):
                    if d[key]:
                        fails.append(f"{label}: {d[key]}")
                if d["danglingLabel"]:
                    fails.append("aria-labelledby without a target: " + ", ".join(d["danglingLabel"][:4]))
                if d["danglingHash"]:
                    fails.append("# link without a target: " + ", ".join("#" + x for x in d["danglingHash"][:4]))
                if "noindex" in d["robots"]:
                    fails.append("noindex on production")
                c, dn = d["contact"], d["donate"]
                if route["page"] == "contact" and (c["litActions"] or c["reachWrite"] or c["office"] or not c["units"] or c["footContact"]):
                    fails.append(f"Contact differs from 3.5 (actions {c['litActions']}, write {c['reachWrite']}, office {c['office']}, units {c['units']}, footer contact {c['footContact']})")
                if route["page"] == "donate" and (dn["go"] or dn["jump"] or dn["slip"] or dn["tell"] or not dn["trust"] or not dn["dusk"] or not dn["duskBtn"]):
                    fails.append(f"Donate differs from 3.5 ({json.dumps(dn)})")
                res["routes"].append({"route": route["path"], "width": width, "fails": fails})
                print(f"{'ok  ' if not fails else 'FAIL'} {width:>4} {route['path']}", flush=True)
                for f in fails:
                    print(f"       - {f}", flush=True)
        browser.close()
    # The refused Little Rose attachment through every query that names it (5.7): 404, no Location.
    probes = [pr for pr in SMOKE.gate_probes(manifest.get("urls", []), manifest.get("fixtures") or {})
              if pr["id"].startswith("gate:refused-attachment-")]
    if not probes:
        res["probes"] = [{"id": "gate:refused-attachment", "path": "-", "fails": ["no refused_photo fixture in seed-urls.json (re-seed)"]}]
    else:
        res["probes"] = SMOKE.run_probes(base, probes)
    res["pass"] = all(not r["fails"] for r in res["routes"]) and all(not r["fails"] for r in res["probes"])
    return res


# --------------------------------------------------------------------------- report

def gate_summary(report: dict) -> dict:
    g = {}
    views = report.get("matrix", [])
    sec = [(v, s) for v in views for s in v["sections"]]
    for kind in ("structure", "text", "links"):
        rows = [s[kind] for _, s in sec if kind in s]
        g[f"7.8 {kind}"] = {"pass": sum(r["pass"] for r in rows), "fail": sum(not r["pass"] for r in rows)}
    px = [s["pixels"] for _, s in sec if "pixels" in s]
    g["7.9 pixels"] = {"pass": sum(r["pass"] for r in px), "fail": sum(not r["pass"] for r in px)}
    for side in SIDES:
        n = sum(1 for v in views if v["checks"][side])
        g[f"page checks {SIDE_NAME[side]}"] = {"pass": len(views) - n, "fail": n}
    lines = [v["hardLines"] for v in views if "hardLines" in v]
    if lines:
        g["7.14 no hard line WordPress adds"] = {"pass": sum(r["pass"] for r in lines), "fail": sum(not r["pass"] for r in lines)}
    if "behaviour" in report:
        b = report["behaviour"]
        g["7.10 behaviour lines"] = {"pass": sum(1 for v in b.values() if v["pass"]), "fail": sum(1 for v in b.values() if not v["pass"]),
                                     "missing": sum(len(v["missing"]) for v in b.values())}
    if "world" in report:
        w = report["world"]["gates"]
        g["7.10 world and budgets"] = {"pass": sum(1 for v in w.values() if v), "fail": sum(1 for v in w.values() if not v)}
    if "lcp" in report and "gates" in report["lcp"]:
        l = report["lcp"]["gates"]
        g["7.11 LCP"] = {"pass": sum(1 for v in l.values() if v), "fail": sum(1 for v in l.values() if not v)}
    if "debugLog" in report:
        g["debug.log"] = {"pass": int(not report["debugLog"]["ours"]), "fail": int(bool(report["debugLog"]["ours"]))}
    return g


def write_html(report: dict, out: Path) -> None:
    esc = htmllib.escape
    rel = lambda p: os.path.relpath(p, out) if p and not str(p).startswith("error") else ""
    parts = ["<!doctype html><meta charset=utf-8><title>Parity report</title>",
             "<style>body{font:14px/1.6 system-ui,sans-serif;margin:16px;color:#1d2131;background:#fff}"
             "table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:2px 6px;vertical-align:top}"
             ".ok{color:#176b2c}.no{color:#a31515}pre{white-space:pre-wrap;font-size:12px;max-height:24em;overflow:auto;background:#f6f6f2}"
             "img{max-width:32vw;border:1px solid #ccc;vertical-align:top}details{margin:2px 0}</style>",
             f"<h1>Parity report</h1><p>{esc(report['started'])} · prototype {esc(report['bases'].get('proto', '-'))} · WordPress {esc(report['bases'].get('wp', '-'))} · options {esc(json.dumps(report['options']))}</p>",
             "<h2>Gates</h2><table><tr><th>gate</th><th>pass</th><th>fail</th></tr>"]
    for k, v in report["summary"].items():
        parts.append(f"<tr><td>{esc(k)}</td><td class=ok>{v.get('pass')}</td><td class=no>{v.get('fail')}{' (' + str(v['missing']) + ' lines)' if 'missing' in v else ''}</td></tr>")
    parts.append("</table>")
    if report.get("behaviour"):
        parts.append("<h2>Behaviour (prototype checks)</h2><table><tr><th>check</th><th>prototype pass/fail</th><th>WordPress pass/fail</th><th>prototype PASS not passing on WordPress</th></tr>")
        for name, b in report["behaviour"].items():
            miss = "".join(f"<li>{esc(m)}</li>" for m in b["missing"][:60])
            parts.append(f"<tr><td>{esc(name)}</td><td>{b['sides']['proto']['pass']}/{b['sides']['proto']['fail']}</td><td>{b['sides']['wp']['pass']}/{b['sides']['wp']['fail']}</td><td><details><summary>{len(b['missing'])}</summary><ul>{miss}</ul></details></td></tr>")
        parts.append("</table>")
    if report.get("world"):
        parts.append("<h2>World and budgets</h2><pre>" + esc(json.dumps({"gates": report["world"]["gates"], "loads": report["world"]["loads"], "entryInstances": report["world"].get("entryInstances")}, indent=1, ensure_ascii=False)) + "</pre>")
        for s in report["world"].get("strips", {}).get("strips", []):
            parts.append(f"<h3>Strip {esc(s['name'])} (prototype left, WordPress right)</h3><img src='{esc(rel(s['file']))}' style='max-width:96vw'>")
            rows = "".join(f"<tr><td>{r['i']}</td><td>{r['yProto']}</td><td>{r['yWp']}</td><td>{esc(str(r['section']))}</td>"
                           f"<td class={'ok' if r['pass'] else 'no'}>{r['share']}%{' (allowed)' if r['allowed'] else ''}</td></tr>"
                           for r in s.get("frameShares", []))
            parts.append(f"<details><summary>per-frame difference (limit {STRIP_SHARE}%)</summary><table><tr><th>frame</th><th>y prototype</th>"
                         f"<th>y WordPress</th><th>section</th><th>differing pixels</th></tr>{rows}</table></details>")
    if report.get("lcp"):
        parts.append("<h2>LCP</h2><pre>" + esc(json.dumps(report["lcp"], indent=1)) + "</pre>")
    if report.get("debugLog"):
        parts.append("<h2>debug.log</h2><pre>" + esc(json.dumps(report["debugLog"], indent=1)) + "</pre>")
    parts.append("<h2>Matrix</h2>")
    for v in report.get("matrix", []):
        bad = [s for s in v["sections"] if any(not s[k]["pass"] for k in ("structure", "text", "links", "pixels") if k in s)]
        head = f"{v['path']} · {v['width']} · {v['mode']}"
        checks = "".join(f"<li><b>{SIDE_NAME[side]}</b>: {esc(c)}</li>" for side in SIDES for c in v["checks"][side])
        parts.append(f"<details><summary>{esc(head)}: {len(bad)} of {len(v['sections'])} sections differ; {sum(len(v['checks'][s]) for s in SIDES)} page check failures</summary>")
        if checks:
            parts.append(f"<ul>{checks}</ul>")
        hl = v.get("hardLines")
        if hl:
            only = ", ".join(f"y {l['y']} ({esc(str(l['section']))} +{l['offset']})" for l in hl["wpOnly"][:12])
            parts.append(f"<p>Hard lines (7.14): prototype {hl['proto']}, WordPress {hl['wp']}; "
                         + (f"<span class=no>only on WordPress: {only}</span>" if hl["wpOnly"] else "<span class=ok>none only on WordPress</span>") + "</p>")
        parts.append("<table><tr><th>section</th><th>structure</th><th>text</th><th>links</th><th>pixels</th></tr>")
        for s in v["sections"]:
            cells = []
            for k in ("structure", "text", "links"):
                if k not in s:
                    cells.append("<td></td>")
                    continue
                r = s[k]
                mark = "<span class=ok>ok</span>" if r["pass"] else f"<span class=no>{r.get('changes', '')} changes</span>"
                body = "" if r["pass"] else f"<details><summary>diff</summary><pre>{esc(chr(10).join(r.get('diff', [])[:200]))}</pre></details>"
                cells.append(f"<td>{mark}{body}</td>")
            px = s.get("pixels")
            if px:
                mark = "<span class=ok>ok</span>" if px["pass"] else f"<span class=no>{px.get('share', '')}% · {px.get('proto')} vs {px.get('wp')}</span>"
                imgs = "" if px["pass"] else "".join(f"<img loading=lazy src='{esc(rel(px.get(k)))}'>" for k in ("protoShot", "wpShot", "diffMap") if px.get(k))
                cells.append(f"<td>{mark}<br>{imgs}</td>")
            else:
                cells.append("<td></td>")
            parts.append(f"<tr><td>{esc(s['key'])}</td>{''.join(cells)}</tr>")
        parts.append("</table></details>")
    (out / "report.html").write_text("\n".join(parts), encoding="utf-8")


# --------------------------------------------------------------------------- main

SMOKE = None
BASES: dict = {}
THREE: dict = {}
ALLOW: list = []


def main() -> int:
    global SMOKE, THREE, ALLOW
    ap = argparse.ArgumentParser(description="Parity driver: prototype and WordPress side by side (11-WP-PORT.md 5).")
    ap.add_argument("--base", help="WordPress root, e.g. http://127.0.0.1:8881")
    ap.add_argument("--self-test", action="store_true", help="compare the prototype with itself (the driver's noise floor)")
    ap.add_argument("--instance", type=Path, help="the instance folder (.wp-local/<name>): debug.log and seed-urls.json")
    ap.add_argument("--out", type=Path, help="report folder (default qa/wp-port/parity/<date>-<time>)")
    speed = ap.add_mutually_exclusive_group()
    speed.add_argument("--quick", action="store_true", help="matrix only (no prototype checks, world, budgets, strips or LCP)")
    speed.add_argument("--full", action="store_true", help="everything, with frame strips and LCP")
    ap.add_argument("--only", nargs="*", default=[], help="routes whose name or path contains one of these (e.g. home)")
    ap.add_argument("--widths", default="390,1440", help="comma-separated widths from routes.json")
    ap.add_argument("--modes", default=",".join(MODES), help="comma-separated matrix modes")
    ap.add_argument("--build", action="store_true", help="run npm run build in prototype/ first (default: only when dist is missing)")
    ap.add_argument("--port", type=int, default=4173, help="first port to try for vite preview")
    ap.add_argument("--production-asserts", action="store_true", help="only the gate 7.7 production assertions against --base")
    args = ap.parse_args()
    if sync_playwright is None:
        ap.error("Playwright is not installed for this Python: use the repo's .venv")
    if not args.base and not args.self_test:
        ap.error("--base is required (or --self-test)")

    started = datetime.datetime.now()
    out = args.out or REPO / "qa" / "wp-port" / "parity" / started.strftime("%Y-%m-%d-%H%M%S")
    out.mkdir(parents=True, exist_ok=True)
    BASES["wp"] = (args.base or "").rstrip("/")
    SMOKE = load_smoke()
    signal.signal(signal.SIGTERM, lambda *a: (PROCS.stop_all(), sys.exit(143)))

    if args.production_asserts:
        res = production_asserts(BASES["wp"], args.instance, out)
        (out / "production-asserts.json").write_text(json.dumps(res, ensure_ascii=False, indent=1), encoding="utf-8")
        bad = [r for r in res["routes"] if r["fails"]]
        bad_probes = [r for r in res.get("probes", []) if r["fails"]]
        print(f"\nProduction assertions: {len(res['routes']) - len(bad)} of {len(res['routes'])} route views pass; "
              f"{len(res.get('probes', [])) - len(bad_probes)} of {len(res.get('probes', []))} attachment probes pass. Report: {out / 'production-asserts.json'}")
        return 0 if res["pass"] else 1

    widths = [w for w in args.widths.split(",") if w in ROUTES["widths"]]
    modes = [m for m in args.modes.split(",") if m in MODES]
    routes = [r for r in ROUTES["routes"] if not args.only or any(o in r["name"] or o == r["path"] for o in args.only)]
    THREE = three_chunks()
    problems = allow_problems()
    if problems:
        # A malformed entry would silently accept (or fail to accept) differences: stop before the run.
        print("Allow lists are malformed (11-WP-PORT.md 5.4):\n  " + "\n  ".join(problems), file=sys.stderr)
        return 2
    ALLOW = load_allow()
    log_start = debug_log_size(args.instance)
    report = {"started": started.isoformat(timespec="seconds"), "bases": BASES,
              "options": {"quick": args.quick, "full": args.full, "only": args.only, "widths": widths, "modes": modes},
              "threeChunks": THREE, "allowEntries": len(ALLOW), "matrix": []}
    try:
        ensure_dist(args.build, out / "logs" / "build.log")
        BASES["proto"], _ = start_preview(args.port, out / "logs" / "vite-preview.log")
        if args.self_test:
            BASES["wp"], _ = start_preview(args.port + 1, out / "logs" / "vite-preview-second.log")
        print(f"Prototype at {BASES['proto']}, WordPress at {BASES['wp']}; report in {out}", flush=True)

        with sync_playwright() as p:
            browsers = {"gpu": p.chromium.launch()}
            if "nowebgl" in modes:
                browsers["nowebgl"] = p.chromium.launch(args=NOWEBGL_ARGS)
            total = len(routes) * len(widths) * len(modes)
            n = 0
            for route in routes:
                for width in widths:
                    for mode in modes:
                        n += 1
                        pages = {}
                        shot_root = out / "shots" / route["name"] / width / mode if MODES[mode]["shots"] else None
                        for side in SIDES:
                            pages[side] = load_page(browsers, side, route, width, mode, shot_root / side if shot_root else None)
                        view = compare_view(route, width, mode, pages, shot_root)
                        report["matrix"].append(view)
                        bad = sum(1 for s in view["sections"] if any(not s[k]["pass"] for k in ("structure", "text", "links", "pixels") if k in s))
                        print(f"[{n}/{total}] {route['path']} {width} {mode}: {bad}/{len(view['sections'])} sections differ; "
                              f"page checks prototype {len(view['checks']['proto'])}, WordPress {len(view['checks']['wp'])}", flush=True)
            if not args.quick:
                print("World and budgets", flush=True)
                report["world"] = world_and_budgets(browsers, out, args.full)
            for b in browsers.values():
                b.close()

        if not args.quick:
            print("Behaviour: the prototype's own checks on both sides", flush=True)
            report["behaviour"] = behaviour(out)
        if args.full:
            print("Mobile LCP", flush=True)
            report["lcp"] = lcp_runs(out)
    finally:
        PROCS.stop_all()

    report["debugLog"] = debug_log_new(args.instance, log_start)
    report["finished"] = datetime.datetime.now().isoformat(timespec="seconds")
    report["summary"] = gate_summary(report)
    (out / "report.json").write_text(json.dumps(report, ensure_ascii=False, indent=1, default=str), encoding="utf-8")
    write_html(report, out)
    print()
    for k, v in report["summary"].items():
        print(f"  {k:<34} pass {v.get('pass')}  fail {v.get('fail')}" + (f"  ({v['missing']} lines)" if "missing" in v else ""))
    failed = any(v.get("fail") for v in report["summary"].values())
    print(f"\n{'FAIL' if failed else 'PASS'}: report in {out / 'report.html'}")
    return 1 if failed else 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    finally:
        PROCS.stop_all()
