import { $, $$ } from "./dom.js";

// Footer columns: disclosure buttons below 1024 px (folded at load), plain open columns above it, where
// the button is not rendered at all (CSS) so there is no tab stop that does nothing.
export function initFooter() {
  const narrow = matchMedia("(max-width: 1023.98px)");
  const toggles = $$(".foot-col__toggle");
  const set = (t, open) => {
    t.setAttribute("aria-expanded", String(open));
    $("#" + t.getAttribute("aria-controls")).hidden = !open;
  };
  const sync = () => toggles.forEach((t) => set(t, !narrow.matches));
  sync();
  narrow.addEventListener("change", sync);
  toggles.forEach((t) => t.addEventListener("click", () => set(t, t.getAttribute("aria-expanded") !== "true")));
}
