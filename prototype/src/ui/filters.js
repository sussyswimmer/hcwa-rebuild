import { $, $$ } from "./dom.js";

// Our Work filter: chips are plain ?area= links (they work without JS); this hides what doesn't match.
export function initFilters() {
  const area = new URLSearchParams(location.search).get("area");
  const groups = $$("[data-filter-group]");
  if (!area || !groups.length) return;
  $$(".filters .chip").forEach((chip) => (chip.dataset.area === area ? chip.setAttribute("aria-current", "true") : chip.removeAttribute("aria-current")));
  let shown = 0;
  groups.forEach((g) => {
    const items = $$("[data-areas]", g);
    items.forEach((it) => { const hit = it.dataset.areas.split(" ").includes(area); it.hidden = !hit; shown += hit; });
    g.hidden = !items.some((it) => !it.hidden);
  });
  $("[data-filter-empty]").hidden = shown > 0;
}
