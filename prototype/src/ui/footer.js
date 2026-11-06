import { $$ } from "./dom.js";

// Footer columns are accordions on mobile and plain columns on desktop.
export function initFooter() {
  const desktop = matchMedia("(min-width: 1024px)");
  const sync = () => $$(".foot-col").forEach((d) => { d.open = desktop.matches; });
  sync();
  desktop.addEventListener("change", sync);
  $$(".foot-col summary").forEach((s) => s.addEventListener("click", (e) => { if (desktop.matches) e.preventDefault(); }));
}
