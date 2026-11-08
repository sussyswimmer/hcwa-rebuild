import { $, $$ } from "./dom.js";

// Tabs (Donate's In Vietnam / From abroad). Without JS both panels stand one after the other under their
// own headings and the tab list stays hidden. With JS: the ARIA tabs pattern, arrow keys, Home / End,
// selection follows focus, and a #fragment (the header's jump links) picks its tab. Switching is instant:
// nothing in the payment panel animates (05-DESIGN D5.1).
export function initTabs() {
  $$("[data-tabs]").forEach((root) => {
    const list = $('[role="tablist"]', root);
    const tabs = $$('[role="tab"]', list);
    const panels = tabs.map((t) => document.getElementById(t.getAttribute("aria-controls")));
    if (!tabs.length || panels.some((p) => !p)) return;
    const select = (i, focus = false) => {
      tabs.forEach((t, n) => {
        const on = n === i;
        t.setAttribute("aria-selected", String(on));
        t.tabIndex = on ? 0 : -1;
        panels[n].hidden = !on;
      });
      if (focus) tabs[i].focus();
    };
    panels.forEach((p, n) => {
      p.setAttribute("role", "tabpanel");
      p.setAttribute("aria-labelledby", tabs[n].id);
      p.tabIndex = 0;
    });
    list.hidden = false;
    root.classList.add("is-tabbed");
    const fromHash = () => panels.findIndex((p) => "#" + p.id === location.hash);
    select(Math.max(0, fromHash()));
    tabs.forEach((t, i) => t.addEventListener("click", () => select(i)));
    list.addEventListener("keydown", (e) => {
      const i = tabs.indexOf(document.activeElement);
      const to = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
      if (i < 0 || to === undefined) return;
      e.preventDefault();
      select((to + tabs.length) % tabs.length, true);
    });
    // A jump link to a hidden panel would land nowhere: pick its tab, then bring the tab list into view.
    addEventListener("hashchange", () => {
      const i = fromHash();
      if (i < 0) return;
      select(i);
      root.scrollIntoView({ block: "start" });
    });
  });
}
