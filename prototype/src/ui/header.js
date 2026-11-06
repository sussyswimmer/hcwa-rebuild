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
