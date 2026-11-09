// In-page extractor for the parity driver (docs/plan/11-WP-PORT.md 5.3). Injected identically into
// the prototype page and the WordPress page; returns the page cut into sections, each with its
// structure lines, its text runs and its links, plus a few page facts (H1 count, sideways scroll,
// stills, <html> state).
//
// Called as page.evaluate(SOURCE) where SOURCE is this file: it is one arrow function expression.
// It also marks every section root with data-parity-section="<n>" so the driver can screenshot
// them; the attribute is never recorded.
() => {
  // data-* values kept in the structure (other data-* attributes are recorded by name only).
  const DATA_VALUES = new Set(["data-world", "data-stage", "data-beat", "data-unit", "data-door", "data-emblem",
    "data-status", "data-areas", "data-way", "data-span", "data-still", "data-reveal", "data-traces", "data-draft",
    "data-pending"]);
  // aria-* values kept (others by name only).
  const ARIA_VALUES = new Set(["aria-hidden", "aria-current", "aria-expanded"]);
  // Custom properties whose value is kept (others by name only).
  const VAR_VALUES = new Set(["--n", "--i", "--w"]);
  const SKIP = new Set(["SCRIPT", "STYLE", "NOSCRIPT", "TEMPLATE", "LINK", "META"]);
  const DIST = /\/wp-content\/themes\/[^/]+\/assets\/dist\//;
  const origin = location.origin;
  const nfc = (s) => s.normalize("NFC").replace(/\s+/g, " ").trim();

  const srcsetCount = (v) => (v ? v.split(/,\s*(?=\S)/).filter((c) => c.trim()).length : 0);
  const styleVars = (el) => {
    const out = [];
    const style = el.getAttribute("style");
    if (!style) return out;
    for (const decl of style.split(";")) {
      const i = decl.indexOf(":");
      if (i < 0) continue;
      const name = decl.slice(0, i).trim();
      if (!name.startsWith("--")) continue;
      out.push(VAR_VALUES.has(name) ? `${name}:${decl.slice(i + 1).trim()}` : name);
    }
    return out.sort();
  };

  // One structure line: tag#id.classes [attributes].
  const sig = (el) => {
    const tag = el.tagName.toLowerCase();
    let s = tag;
    if (el.id) s += "#" + el.id;
    const cls = [...el.classList].filter((c) => !c.startsWith("wp-")).sort();
    if (cls.length) s += "." + cls.join(".");
    const attrs = [];
    for (const a of [...el.attributes].sort((x, y) => (x.name < y.name ? -1 : 1))) {
      const n = a.name;
      if (n === "data-parity-section") continue;
      if (n === "role" || n === "lang") attrs.push(`${n}=${a.value}`);
      else if (n.startsWith("data-")) attrs.push(DATA_VALUES.has(n) ? `${n}=${a.value}` : n);
      else if (n.startsWith("aria-")) attrs.push(ARIA_VALUES.has(n) ? `${n}=${a.value}` : n);
      else if (n === "hidden") attrs.push("hidden");
      else if (n === "loading" || n === "fetchpriority" || n === "width" || n === "height") attrs.push(`${n}=${a.value}`);
      else if (tag === "source" && (n === "media" || n === "type" || n === "sizes")) attrs.push(`${n}=${a.value}`);
      else if (n === "srcset") attrs.push(`srcset#${srcsetCount(a.value)}`);
    }
    const vars = styleVars(el);
    if (vars.length) attrs.push("style{" + vars.join(";") + "}");
    return attrs.length ? `${s} [${attrs.join(" ")}]` : s;
  };

  const link = (a) => {
    let href = a.href || a.getAttribute("href") || "";
    try {
      const u = new URL(href, location.href);
      if (u.origin === origin) href = u.pathname + u.search + u.hash;
      else href = u.href;
    } catch { /* keep as written */ }
    return href.replace(DIST, "/");
  };

  // Structure lines, text runs and links of one section root (the root itself included).
  const walk = (root, withChildren = true) => {
    const tree = [], text = [], links = [];
    const visit = (el, depth) => {
      if (SKIP.has(el.tagName)) return;
      tree.push("  ".repeat(depth) + sig(el));
      if (el.tagName === "A" && el.hasAttribute("href")) links.push(link(el));
      const own = nfc([...el.childNodes].filter((n) => n.nodeType === 3).map((n) => n.textContent).join(" "));
      if (own) text.push(own);
      if (withChildren) for (const child of el.children) visit(child, depth + 1);
    };
    visit(root, 0);
    return { tree, text, links };
  };

  const body = document.body;
  const ignoreShell = (el) => SKIP.has(el.tagName) || el.id === "wpadminbar";
  const shell = [...body.children].filter((el) => !ignoreShell(el)).map(sig);

  // Section roots: header, the menu sheet, the footer, each main > * (a .day's children each).
  const roots = [];
  const seen = new Map();
  const keyOf = (prefix, el) => {
    const first = [...el.classList].filter((c) => !c.startsWith("wp-"))[0];
    let key = `${prefix}${el.tagName.toLowerCase()}${el.id ? "#" + el.id : ""}${first ? "." + first : ""}`;
    const n = (seen.get(key) || 0) + 1;
    seen.set(key, n);
    if (n > 1) key += "~" + n;
    return key;
  };
  const header = body.querySelector(":scope > .header-slot") || document.querySelector(".site-header");
  if (header) roots.push({ key: "header", el: header });
  const main = document.querySelector("main");
  if (main) {
    for (const child of main.children) {
      if (SKIP.has(child.tagName)) continue;
      if (child.classList.contains("day")) {
        roots.push({ key: keyOf("main>", child), el: child, shallow: true });
        for (const inner of child.children) if (!SKIP.has(inner.tagName)) roots.push({ key: keyOf("main>.day>", inner), el: inner });
      } else roots.push({ key: keyOf("main>", child), el: child });
    }
  }
  const footer = document.querySelector("footer.site-footer") || document.querySelector("footer");
  if (footer) roots.push({ key: "footer", el: footer });
  const sheet = document.getElementById("menu-sheet");
  if (sheet) roots.push({ key: "menu-sheet", el: sheet });

  const sections = roots.map((r, i) => {
    r.el.setAttribute("data-parity-section", String(i));
    const w = walk(r.el, !r.shallow);
    const rect = r.el.getBoundingClientRect();
    return { key: r.key, index: i, tree: w.tree, text: w.text, links: w.links, height: Math.round(rect.height), width: Math.round(rect.width), shallow: !!r.shallow };
  });

  const rendered = (el) => el.getClientRects().length > 0 && getComputedStyle(el).visibility !== "hidden";
  const h1 = [...document.querySelectorAll("h1")].filter(rendered).map((h) => nfc(h.textContent).slice(0, 80));
  const de = document.documentElement;
  // Stills: every picture's <img> that has a box (lazy images inside hidden subtrees never load).
  const pics = [...document.querySelectorAll("picture img")].filter((img) => img.getClientRects().length > 0);
  const stills = {
    total: pics.length,
    loaded: pics.filter((img) => img.complete && img.naturalWidth > 0).length,
    missing: pics.filter((img) => !(img.complete && img.naturalWidth > 0)).map((img) => (img.currentSrc || img.getAttribute("src") || "").replace(origin, "").replace(DIST, "/")).slice(0, 12),
  };
  const headStyle = [...document.head.querySelectorAll("style")].reduce((n, s) => n + s.textContent.length, 0);
  return {
    url: location.href,
    html: { cls: de.className, lang: de.getAttribute("lang") || "", lit: de.hasAttribute("data-lit"), tier: de.dataset.tier ?? null, motion: de.dataset.motion ?? null },
    bodyClass: body.className,
    shell,
    sections,
    h1,
    sideways: Math.max(0, de.scrollWidth - de.clientWidth),
    stills,
    headInlineStyleBytes: headStyle,
    moduleSrc: [...document.querySelectorAll('script[type="module"][src]')].map((s) => s.src),
  };
}
