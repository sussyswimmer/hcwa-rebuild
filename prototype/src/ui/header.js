import { $, $$ } from "./dom.js";

// The bar compacts after 80 px of scroll (05-DESIGN D5.7), driven by a sentinel, not a scroll listener.
// Over the home hero it also gains its lime-50 fill at that point. It never hides.
export function initHeader() {
  const header = $("[data-header]");
  if (!header) return;
  const sentinel = Object.assign(document.createElement("div"), { ariaHidden: "true" });
  sentinel.style.cssText = "position:absolute;top:80px;height:1px;width:1px;pointer-events:none";
  document.body.prepend(sentinel);
  new IntersectionObserver(([e]) => header.classList.toggle("is-compact", !e.isIntersecting)).observe(sentinel);
  fitBar(header);

  // Desktop submenus: toggle buttons; Escape, outside click and focus leaving the nav close them.
  const toggles = $$(".nav__toggle");
  const close = (except) => toggles.forEach((t) => {
    if (t === except) return;
    t.setAttribute("aria-expanded", "false");
    $("#" + t.getAttribute("aria-controls")).hidden = true;
  });
  toggles.forEach((t) => t.addEventListener("click", () => {
    const open = t.getAttribute("aria-expanded") !== "true";
    close(t);
    t.setAttribute("aria-expanded", String(open));
    $("#" + t.getAttribute("aria-controls")).hidden = !open;
  }));
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    const open = toggles.find((t) => t.getAttribute("aria-expanded") === "true");
    if (open) { close(); open.focus(); }
  });
  document.addEventListener("click", (e) => { if (!e.target.closest(".nav__item")) close(); });
  $(".nav")?.addEventListener("focusout", (e) => { if (!e.relatedTarget?.closest(".nav")) close(); });
}

// Reflow (WCAG 1.4.4, 1.4.10): when the bar's content is wider than the bar (200% text, a narrow window,
// a long translation), give up the logo's words, then the nav (the Menu button and its sheet take over),
// then the header's VI / EN (the sheet has it too), until it fits. Measured, not guessed from a width:
// the same rule as the corridor's fit. Runs again when the bar or the fonts change size.
const STEPS = ["is-tight-name", "is-tight-nav", "is-tight-lang"];
function fitBar(header) {
  const bar = header.querySelector(".site-header__bar");
  if (!bar) return;
  const fit = () => {
    header.classList.remove(...STEPS);
    for (const step of STEPS) {
      if (bar.scrollWidth <= bar.clientWidth + 1) break;
      header.classList.add(step);
    }
  };
  fit();
  // The bar for the window, the logo and the tools for the text size (they grow with it; the nav may be
  // hidden). The result does not depend on the classes it sets, so this never loops.
  const ro = new ResizeObserver(fit);
  for (const el of [bar, header.querySelector(".logo"), header.querySelector(".site-header__tools")]) if (el) ro.observe(el);
  document.fonts?.ready.then(fit);
}
